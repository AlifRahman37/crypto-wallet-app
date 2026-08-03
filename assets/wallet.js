// ============================================
// Multi-Chain Wallet — Core Wallet Logic
// SECURITY: Seed phrase / private keys NEVER leave the browser.
// Nothing here ever sends a key or mnemonic to any server.
// ============================================

let API_KEYS = { alchemy:'', bscscan:'', blockcypher:'', helius:'', trongrid:'' };
let wallet = null;        // { mnemonic, accounts: { ethereum: {address, privateKey}, bitcoin: {...}, ... } }
let currentTheme = 'dark';
let currentLang = 'en';
let selectedSendChain = 'ethereum';
let balancesCache = {};

// ---------- Load public config (RPC keys only, no secrets) ----------
async function loadApiKeys() {
  try {
    const r = await fetch(API_BASE + '?action=config');
    const d = await r.json();
    if (d.ok) API_KEYS = { alchemy:d.alchemy, bscscan:d.bscscan, blockcypher:d.blockcypher, helius:d.helius, trongrid:d.trongrid };
  } catch(e) { console.warn('Could not load API keys from server, using empty (public RPC fallback)', e); }
}

// ============================================
// WALLET GENERATION (BIP39 / BIP44, client-side only)
// ============================================
async function generateNewWallet() {
  const mnemonic = ethersLib.Mnemonic.entropyToPhrase(ethersLib.randomBytes(16)); // 12 words
  return deriveAllChains(mnemonic);
}

async function importFromMnemonic(phrase) {
  phrase = phrase.trim().toLowerCase().replace(/\s+/g, ' ');
  if (!ethersLib.Mnemonic.isValidMnemonic(phrase)) throw new Error(t('err_invalid_mnemonic'));
  return deriveAllChains(phrase);
}

async function importFromPrivateKey(chainId, pk) {
  // Single-chain private key import: only that one chain will be usable.
  pk = pk.trim();
  const accounts = {};
  if (CHAINS[chainId].family === 'evm') {
    const w = new ethersLib.Wallet(pk.startsWith('0x') ? pk : '0x' + pk);
    EVM_CHAINS.forEach(c => accounts[c] = { address: w.address, privateKey: w.privateKey });
  } else if (chainId === 'tron') {
    const address = tronWeb.address.fromPrivateKey(pk.replace(/^0x/, ''));
    accounts.tron = { address, privateKey: pk.replace(/^0x/, '') };
  } else if (chainId === 'bitcoin') {
    const keyPair = window.ECPair.fromWIF(pk, window.bitcoinLib.networks.bitcoin);
    const { address } = window.bitcoinLib.payments.p2wpkh({ pubkey: keyPair.publicKey, network: window.bitcoinLib.networks.bitcoin });
    accounts.bitcoin = { address, privateKey: pk, wif: pk };
  } else if (chainId === 'solana') {
    const secretKey = window.bs58.decode(pk);
    const kp = solanaWeb3.Keypair.fromSecretKey(secretKey);
    accounts.solana = { address: kp.publicKey.toBase58(), privateKey: pk };
  }
  return { mnemonic: null, accounts, singleChain: chainId };
}

async function deriveAllChains(mnemonic) {
  const accounts = {};
  const seed = ethersLib.Mnemonic.fromPhrase(mnemonic).computeSeed();

  // EVM chains — one address shared across all EVM networks (standard m/44'/60'/0'/0/0)
  const evmNode = ethersLib.HDNodeWallet.fromSeed(seed).derivePath("m/44'/60'/0'/0/0");
  EVM_CHAINS.forEach(c => accounts[c] = { address: evmNode.address, privateKey: evmNode.privateKey });

  // TRON — m/44'/195'/0'/0/0
  const tronNode = ethersLib.HDNodeWallet.fromSeed(seed).derivePath("m/44'/195'/0'/0/0");
  const tronPk = tronNode.privateKey.replace(/^0x/, '');
  accounts.tron = { address: tronWeb.address.fromPrivateKey(tronPk), privateKey: tronPk };

  // Bitcoin — m/84'/0'/0'/0/0 (native segwit, bech32)
  const btcRoot = window.bip32Lib.fromSeed(Buffer.from(seed.slice(2), 'hex'));
  const btcChild = btcRoot.derivePath("m/84'/0'/0'/0/0");
  const btcPayment = window.bitcoinLib.payments.p2wpkh({ pubkey: btcChild.publicKey, network: window.bitcoinLib.networks.bitcoin });
  accounts.bitcoin = {
    address: btcPayment.address,
    privateKey: btcChild.toWIF(),
    wif: btcChild.toWIF()
  };

  // Solana — m/44'/501'/0'/0'
  const solSeedHex = seed.slice(2);
  const { key } = window.ed25519HdKey.derivePath("m/44'/501'/0'/0'", solSeedHex);
  const solKeypair = solanaWeb3.Keypair.fromSeed(key);
  accounts.solana = {
    address: solKeypair.publicKey.toBase58(),
    privateKey: window.bs58.encode(solKeypair.secretKey)
  };

  return { mnemonic, accounts };
}

// ============================================
// LOCAL STORAGE (encrypted, browser-only — never sent to server)
// ============================================
async function encryptAndStore(walletObj, password) {
  const enc = new TextEncoder();
  const salt = crypto.getRandomValues(new Uint8Array(16));
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const keyMaterial = await crypto.subtle.importKey('raw', enc.encode(password), 'PBKDF2', false, ['deriveKey']);
  const key = await crypto.subtle.deriveKey(
    { name: 'PBKDF2', salt, iterations: 150000, hash: 'SHA-256' },
    keyMaterial, { name: 'AES-GCM', length: 256 }, false, ['encrypt']
  );
  const plaintext = enc.encode(JSON.stringify(walletObj));
  const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, plaintext);
  const payload = {
    salt: btoa(String.fromCharCode(...salt)),
    iv: btoa(String.fromCharCode(...iv)),
    data: btoa(String.fromCharCode(...new Uint8Array(ciphertext)))
  };
  localStorage.setItem('mcw_vault', JSON.stringify(payload));
}

async function decryptStored(password) {
  const raw = localStorage.getItem('mcw_vault');
  if (!raw) return null;
  const payload = JSON.parse(raw);
  const salt = Uint8Array.from(atob(payload.salt), c => c.charCodeAt(0));
  const iv = Uint8Array.from(atob(payload.iv), c => c.charCodeAt(0));
  const data = Uint8Array.from(atob(payload.data), c => c.charCodeAt(0));
  const enc = new TextEncoder();
  const keyMaterial = await crypto.subtle.importKey('raw', enc.encode(password), 'PBKDF2', false, ['deriveKey']);
  const key = await crypto.subtle.deriveKey(
    { name: 'PBKDF2', salt, iterations: 150000, hash: 'SHA-256' },
    keyMaterial, { name: 'AES-GCM', length: 256 }, false, ['decrypt']
  );
  const plaintext = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, data);
  return JSON.parse(new TextDecoder().decode(plaintext));
}

function hasStoredVault() {
  return !!localStorage.getItem('mcw_vault');
}

function clearVault() {
  localStorage.removeItem('mcw_vault');
  sessionStorage.removeItem('mcw_session');
}
