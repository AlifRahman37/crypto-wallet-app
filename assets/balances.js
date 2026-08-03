// ============================================
// Multi-Chain Wallet — Balance Fetching
// ============================================

async function fetchEvmBalance(chainId) {
  const chain = CHAINS[chainId];
  const acc = wallet.accounts[chainId];
  if (!acc) return 0;
  try {
    const provider = new ethersLib.JsonRpcProvider(chain.rpc());
    const bal = await provider.getBalance(acc.address);
    return parseFloat(ethersLib.formatEther(bal));
  } catch (e) { console.warn(chainId, 'balance error', e); return 0; }
}

async function fetchTronBalance() {
  const acc = wallet.accounts.tron;
  if (!acc) return { trx: 0, usdt: 0 };
  try {
    const r = await fetch(`${CHAINS.tron.api()}/v1/accounts/${acc.address}`, {
      headers: API_KEYS.trongrid ? { 'TRON-PRO-API-KEY': API_KEYS.trongrid } : {}
    });
    const d = await r.json();
    const info = d.data && d.data[0];
    const trx = info ? (info.balance || 0) / 1e6 : 0;
    let usdt = 0;
    const trc20 = info?.trc20 || [];
    for (const entry of trc20) {
      if (entry[CHAINS.tron.usdtContract] !== undefined) {
        usdt = parseInt(entry[CHAINS.tron.usdtContract]) / 1e6;
      }
    }
    return { trx, usdt };
  } catch (e) { console.warn('tron balance error', e); return { trx: 0, usdt: 0 }; }
}

async function fetchBitcoinBalance() {
  const acc = wallet.accounts.bitcoin;
  if (!acc) return 0;
  try {
    const url = `${CHAINS.bitcoin.api()}/addrs/${acc.address}/balance` + (API_KEYS.blockcypher ? `?token=${API_KEYS.blockcypher}` : '');
    const r = await fetch(url);
    const d = await r.json();
    return (d.balance || 0) / 1e8;
  } catch (e) { console.warn('btc balance error', e); return 0; }
}

async function fetchSolanaBalance() {
  const acc = wallet.accounts.solana;
  if (!acc) return 0;
  try {
    const connection = new solanaWeb3.Connection(CHAINS.solana.rpc());
    const pubkey = new solanaWeb3.PublicKey(acc.address);
    const lamports = await connection.getBalance(pubkey);
    return lamports / solanaWeb3.LAMPORTS_PER_SOL;
  } catch (e) { console.warn('sol balance error', e); return 0; }
}

async function loadAllBalances() {
  const results = {};
  const evmPromises = EVM_CHAINS.map(async c => { results[c] = await fetchEvmBalance(c); });
  const tronPromise = fetchTronBalance().then(r => { results.tron_trx = r.trx; results.tron_usdt = r.usdt; });
  const btcPromise = fetchBitcoinBalance().then(r => { results.bitcoin = r; });
  const solPromise = fetchSolanaBalance().then(r => { results.solana = r; });
  await Promise.all([...evmPromises, tronPromise, btcPromise, solPromise]);
  balancesCache = results;
  return results;
}

// ---------- USD Prices (CoinGecko, free, no key) ----------
const COINGECKO_IDS = {
  ethereum:'ethereum', bitcoin:'bitcoin', solana:'solana', tron:'tron',
  bnb:'binancecoin', polygon:'matic-network', linea:'ethereum',
  arbitrum:'ethereum', base:'ethereum', optimism:'ethereum', usdt:'tether'
};
let priceCache = {};
async function loadPrices() {
  try {
    const ids = [...new Set(Object.values(COINGECKO_IDS))].join(',');
    const r = await fetch(`https://api.coingecko.com/api/v3/simple/price?ids=${ids}&vs_currencies=usd&include_24hr_change=true`);
    priceCache = await r.json();
  } catch (e) { console.warn('price fetch error', e); }
  return priceCache;
}
function priceFor(chainId) {
  const id = COINGECKO_IDS[chainId];
  return priceCache[id]?.usd || 0;
}
function changeFor(chainId) {
  const id = COINGECKO_IDS[chainId];
  return priceCache[id]?.usd_24h_change || 0;
}
