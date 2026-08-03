<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>MultiWallet — Multi-Chain Crypto Wallet</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- ============ Login Screen ============ -->
<div class="login-screen" id="loginScreen">
  <div class="login-logo">
    <div class="login-logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <div class="login-title" data-i18n="app_name">MultiWallet</div>
    <div class="login-sub" data-i18n="app_sub">One wallet, every chain</div>
  </div>

  <div class="login-card">
    <div class="login-tabs">
      <button class="ltab active" onclick="switchLoginTab('import',this)" data-i18n="tab_import">Import Wallet</button>
      <button class="ltab" onclick="switchLoginTab('create',this)" data-i18n="tab_create">Create New</button>
    </div>

    <div id="login-import" class="tab-pane active">
      <div id="importError" class="alert alert-error" style="display:none;"></div>
      <div class="form-group">
        <label class="form-label" data-i18n="lbl_mnemonic">Seed Phrase (12/24 words)</label>
        <textarea id="mnemonicInput" class="form-input" rows="3" data-i18n-ph="ph_mnemonic" placeholder="Enter your seed phrase, separated by spaces..."></textarea>
      </div>
      <div class="divider">— OR —</div>
      <div class="form-group">
        <label class="form-label" data-i18n="lbl_privkey">Or import a single-chain private key</label>
        <select id="importChainSelect" class="form-input" style="margin-bottom:8px;font-family:'Inter',sans-serif;">
          <option value="ethereum">Ethereum / EVM chains</option>
          <option value="bitcoin">Bitcoin</option>
          <option value="solana">Solana</option>
          <option value="tron">Tron</option>
        </select>
        <input type="text" id="privateKeyInput" class="form-input" data-i18n-ph="ph_privkey" placeholder="Private key...">
      </div>
      <div class="alert alert-info" data-i18n="info_import">🔒 Your seed phrase never leaves this browser. We derive addresses locally only.</div>
      <button class="btn-primary" onclick="importWallet()" data-i18n="btn_import">Import Wallet</button>
    </div>

    <div id="login-create" class="tab-pane">
      <div class="alert alert-info" style="margin-bottom:16px;" data-i18n="info_create">⚡ A new wallet will be generated in your browser across all 10 networks. Save your seed phrase — it cannot be recovered.</div>
      <button class="btn-primary" onclick="createNewWallet()" data-i18n="btn_create">Generate New Wallet</button>
    </div>
  </div>
</div>

<!-- ============ New Wallet Reveal Screen ============ -->
<div id="newWalletScreen" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:24px;min-height:100vh;background:radial-gradient(ellipse at 50% 0%,#6d5efc18,transparent 60%),var(--bg);">
  <div style="max-width:420px;width:100%;">
    <div style="text-align:center;margin-bottom:24px;">
      <div class="login-title" data-i18n="new_wallet_title">Your New Wallet</div>
    </div>
    <div class="alert alert-error" data-i18n="new_wallet_warn">Write this down and store it safely. Anyone with this phrase can access your funds.</div>
    <div class="seed-grid" id="seedGrid"></div>
    <button class="btn-primary" onclick="confirmSavedSeed()" data-i18n="btn_saved">I've Saved It — Continue</button>
  </div>
</div>

<!-- ============ Main App ============ -->
<div class="app" id="mainApp">
  <div class="wrap">
    <div class="topbar">
      <div class="topbar-left">
        <div class="topbar-logo"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
        <div class="topbar-brand" data-i18n="app_name">MultiWallet</div>
      </div>
      <button class="icon-btn" onclick="refreshAll()">
        <svg viewBox="0 0 24 24"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><polyline points="12 7 12 12 15 15"/></svg>
      </button>
    </div>

    <div class="portfolio-card">
      <div class="portfolio-label" data-i18n="portfolio_label">Total Balance</div>
      <div class="portfolio-balance" id="portfolioBalance"><em>$</em>0.00</div>
    </div>

    <div class="actions-wrap">
      <div class="actions-grid">
        <button class="action-btn" onclick="populateSendChainOptions();openModal('sendModal')">
          <div class="action-icon send"><svg viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg></div>
          <span class="action-label" data-i18n="send">Send</span>
        </button>
        <button class="action-btn" onclick="openReceiveDefault()">
          <div class="action-icon receive"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg></div>
          <span class="action-label" data-i18n="receive">Receive</span>
        </button>
        <button class="action-btn" onclick="openModal('swapModal')">
          <div class="action-icon swap"><svg viewBox="0 0 24 24"><path d="M7 16V4m0 0L3 8m4-4l4 4"/><path d="M17 8v12m0 0l4-4m-4 4l-4-4"/></svg></div>
          <span class="action-label" data-i18n="swap">Swap</span>
        </button>
        <button class="action-btn" onclick="setNav('nav-history');openModal('historyModal')">
          <div class="action-icon buy"><svg viewBox="0 0 24 24"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><polyline points="12 7 12 12 15 15"/></svg></div>
          <span class="action-label" data-i18n="history">History</span>
        </button>
      </div>
    </div>

    <div class="section-header">
      <div class="section-title" data-i18n="assets">Assets</div>
    </div>
    <div class="asset-list" id="assetList"></div>
  </div>

  <nav class="bottom-nav">
    <button class="bnav-item active" id="nav-assets" onclick="setNav('nav-assets')">
      <div class="bnav-icon-wrap"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></div>
      <span class="bnav-label" data-i18n="assets">Assets</span>
    </button>
    <button class="bnav-item" id="nav-send" onclick="setNav('nav-send');populateSendChainOptions();openModal('sendModal')">
      <div class="bnav-icon-wrap"><svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></div>
      <span class="bnav-label" data-i18n="send">Send</span>
    </button>
    <button class="bnav-item" id="nav-receive" onclick="setNav('nav-receive');openReceiveDefault()">
      <div class="bnav-icon-wrap"><svg viewBox="0 0 24 24"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"/></svg></div>
      <span class="bnav-label" data-i18n="receive">Receive</span>
    </button>
    <button class="bnav-item" id="nav-history" onclick="setNav('nav-history');openModal('historyModal')">
      <div class="bnav-icon-wrap"><svg viewBox="0 0 24 24"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><polyline points="12 7 12 12 15 15"/></svg></div>
      <span class="bnav-label" data-i18n="history">History</span>
    </button>
    <button class="bnav-item" id="nav-settings" onclick="setNav('nav-settings');openModal('settingsModal')">
      <div class="bnav-icon-wrap"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></div>
      <span class="bnav-label" data-i18n="settings">Settings</span>
    </button>
  </nav>
</div><!-- /mainApp -->

<?php include 'modals.php'; ?>

<!-- ============ Libraries (CDN) ============ -->
<script src="https://cdn.jsdelivr.net/npm/ethers@6.13.4/dist/ethers.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tiny-secp256k1@2.2.3/lib/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bitcoinjs-lib@6.1.5/dist/bitcoinjs-lib.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ecpair@2.1.0/dist/ecpair.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bs58@5.0.0/dist/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bip32@4.0.0/dist/bip32.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/micro-ed25519-hdkey@0.1.2/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@solana/web3.js@1.98.0/lib/index.iife.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tronweb@5.3.2/dist/TronWeb.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
  // Library aliasing to match app.js expectations
  const ethersLib = ethers;
  const solanaWeb3 = solanaWeb3js;
  window.bitcoinLib = window.bitcoinjsLib || bitcoin;
  window.ECPair = (ECPairFactory.default || ECPairFactory)(window.tinySecp256k1 || nobleSecp256k1);
  window.bip32Lib = (BIP32Factory.default || BIP32Factory)(window.tinySecp256k1 || nobleSecp256k1);
  window.bs58 = window.bs58 && window.bs58.default ? window.bs58.default : window.bs58;
  const tronWeb = new TronWeb({ fullHost: 'https://api.trongrid.io' });
</script>

<!-- Custom shim: derive Solana ed25519 key from seed via SLIP-0010 without an external CDN lib -->
<script>
window.ed25519HdKey = {
  derivePath: function(path, seedHex) {
    // Minimal SLIP-0010 ed25519 derivation (hardened-only, matches m/44'/501'/0'/0')
    function hmacSha512(key, data) {
      return ethersLib.getBytes(ethersLib.computeHmac('sha512', key, data));
    }
    const ED25519_CURVE = new TextEncoder().encode('ed25519 seed');
    let I = hmacSha512(ED25519_CURVE, ethersLib.getBytes('0x' + seedHex));
    let IL = I.slice(0, 32), IR = I.slice(32);
    const segments = path.split('/').slice(1).map(s => parseInt(s.replace("'", "")) + 0x80000000);
    for (const segment of segments) {
      const idxBytes = new Uint8Array(4);
      new DataView(idxBytes.buffer).setUint32(0, segment, false);
      const data = new Uint8Array(1 + 32 + 4);
      data.set([0], 0); data.set(IL, 1); data.set(idxBytes, 33);
      I = hmacSha512(IR, data);
      IL = I.slice(0, 32); IR = I.slice(32);
    }
    return { key: IL, chainCode: IR };
  }
};
</script>

<script src="assets/chains.js"></script>
<script src="assets/i18n.js"></script>
<script src="assets/wallet.js"></script>
<script src="assets/balances.js"></script>
<script src="assets/send.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
