// ============================================
// Multi-Chain Wallet — App Controller
// ============================================
const API_BASE = 'api.php';
let pendingWallet = null; // wallet awaiting "I've saved it" confirmation

// ---------- Boot ----------
window.addEventListener('DOMContentLoaded', async () => {
  await loadApiKeys();
  currentTheme = localStorage.getItem('mcw_theme') || 'dark';
  currentLang = localStorage.getItem('mcw_lang') || 'en';
  document.documentElement.setAttribute('data-theme', currentTheme);
  applyTranslations();
  syncSettingsUI();

  const sess = sessionStorage.getItem('mcw_session');
  if (sess) {
    wallet = JSON.parse(sess);
    loadApp();
  }
});

// ---------- Login tabs ----------
function switchLoginTab(tab, btn) {
  document.querySelectorAll('.ltab').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('login-' + tab).classList.add('active');
}

// ---------- Import ----------
async function importWallet() {
  const errDiv = document.getElementById('importError');
  errDiv.style.display = 'none';
  const mnemonicInput = document.getElementById('mnemonicInput').value.trim();
  const pkChain = document.getElementById('importChainSelect').value;
  const pkInput = document.getElementById('privateKeyInput').value.trim();

  try {
    let result;
    if (mnemonicInput) {
      result = await importFromMnemonic(mnemonicInput);
    } else if (pkInput) {
      result = await importFromPrivateKey(pkChain, pkInput);
    } else {
      throw new Error(t('err_invalid_mnemonic'));
    }
    wallet = result;
    sessionStorage.setItem('mcw_session', JSON.stringify(wallet));
    loadApp();
  } catch (e) {
    errDiv.innerText = e.message || String(e);
    errDiv.style.display = 'block';
  }
}

async function createNewWallet() {
  pendingWallet = await generateNewWallet();
  showNewWalletScreen(pendingWallet);
}

function showNewWalletScreen(w) {
  document.getElementById('loginScreen').style.display = 'none';
  const screen = document.getElementById('newWalletScreen');
  screen.style.display = 'flex';
  const words = w.mnemonic.split(' ');
  document.getElementById('seedGrid').innerHTML = words.map((word, i) =>
    `<div class="seed-word"><span>${i+1}.</span>${word}</div>`
  ).join('');
}

function confirmSavedSeed() {
  wallet = pendingWallet;
  pendingWallet = null;
  sessionStorage.setItem('mcw_session', JSON.stringify(wallet));
  document.getElementById('newWalletScreen').style.display = 'none';
  loadApp();
}

// ---------- Main app ----------
async function loadApp() {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('newWalletScreen').style.display = 'none';
  document.getElementById('mainApp').style.display = 'block';
  renderAssetList();
  refreshAll();
}

async function refreshAll() {
  document.getElementById('portfolioBalance').innerHTML = '<span class="spinner" style="width:20px;height:20px;margin:0;display:inline-block;"></span>';
  await Promise.all([loadAllBalances(), loadPrices()]);
  renderAssetList();
  renderPortfolioTotal();
  populateSendChainOptions();
}

function renderPortfolioTotal() {
  let total = 0;
  EVM_CHAINS.forEach(c => total += (balancesCache[c] || 0) * priceFor(c));
  total += (balancesCache.tron_trx || 0) * priceFor('tron');
  total += (balancesCache.tron_usdt || 0) * 1; // USDT ~ $1
  total += (balancesCache.bitcoin || 0) * priceFor('bitcoin');
  total += (balancesCache.solana || 0) * priceFor('solana');
  document.getElementById('portfolioBalance').innerHTML = `<em>$</em>${total.toFixed(2)}`;
}

function renderAssetList() {
  const list = document.getElementById('assetList');
  if (!wallet) return;
  let html = '';
  CHAIN_ORDER.forEach(id => {
    const chain = CHAINS[id];
    if (!wallet.accounts[id]) return; // single-chain import may only have one
    let bal, symbol = chain.symbol;
    if (id === 'tron') bal = balancesCache.tron_trx;
    else bal = balancesCache[id];
    bal = bal !== undefined ? bal : 0;
    const usd = bal * priceFor(id);
    const chg = changeFor(id);
    html += assetRow(chain, bal, symbol, usd, chg, id);
    if (id === 'tron' && balancesCache.tron_usdt !== undefined) {
      html += assetRow({ ...chain, name: 'Tether USD', logo: 'https://cryptologos.cc/logos/tether-usdt-logo.png' }, balancesCache.tron_usdt, 'USDT', balancesCache.tron_usdt, 0, 'tron_usdt');
    }
  });
  list.innerHTML = html || `<div class="loading-text">${t('loading')}</div>`;
}

function assetRow(chain, bal, symbol, usd, chg, key) {
  return `
    <div class="asset-item" onclick="openModal('receiveModal');selectReceiveChain('${key.split('_')[0]}')">
      <div class="token-logo asset-logo" style="background:${chain.color}22;">
        <img src="${chain.logo}" onerror="this.style.display='none';this.parentElement.innerHTML='${symbol[0]}'">
      </div>
      <div class="asset-info">
        <div class="asset-name">${chain.name}${symbol!==chain.symbol?' ('+symbol+')':''}</div>
        <div class="asset-sub">${bal.toFixed(5)} ${symbol}</div>
      </div>
      <div class="asset-right">
        <div class="asset-balance">$${usd.toFixed(2)}</div>
        <div class="asset-change ${chg>=0?'up':'down'}">${chg>=0?'+':''}${chg.toFixed(2)}%</div>
      </div>
    </div>`;
}

// ---------- Send ----------
function populateSendChainOptions() {
  const wrap = document.getElementById('sendChainSelect');
  let html = '';
  CHAIN_ORDER.forEach(id => {
    if (!wallet.accounts[id]) return;
    const chain = CHAINS[id];
    html += `<div class="chain-opt ${id===selectedSendChain?'active':''}" id="sendopt-${id}" onclick="selectSendChain('${id}')">
      <img class="chain-opt-logo" src="${chain.logo}" onerror="this.style.display='none'">
      <div class="chain-opt-sym">${chain.symbol}</div>
    </div>`;
  });
  wrap.innerHTML = html;
  updateSendBalanceLabel();
}

function selectSendChain(id) {
  selectedSendChain = id;
  document.querySelectorAll('.chain-opt').forEach(el => el.classList.remove('active'));
  document.getElementById('sendopt-' + id).classList.add('active');
  updateSendBalanceLabel();
}

function updateSendBalanceLabel() {
  const chain = CHAINS[selectedSendChain];
  const bal = selectedSendChain === 'tron' ? balancesCache.tron_trx : balancesCache[selectedSendChain];
  document.getElementById('sendBalanceLabel').innerText = `${t('amount')} (${chain.symbol}) — ${t('max')}: ${(bal||0).toFixed(5)}`;
}

function setMaxSend() {
  const bal = selectedSendChain === 'tron' ? balancesCache.tron_trx : balancesCache[selectedSendChain];
  document.getElementById('sendAmount').value = bal || 0;
}

async function executeSend() {
  const btn = document.getElementById('sendBtn');
  const errDiv = document.getElementById('sendError');
  const sucDiv = document.getElementById('sendSuccess');
  errDiv.style.display = 'none'; sucDiv.style.display = 'none';

  const toAddr = document.getElementById('sendToAddr').value.trim();
  const amount = parseFloat(document.getElementById('sendAmount').value);
  if (!toAddr || !amount || amount <= 0) {
    errDiv.innerText = t('err_invalid_mnemonic'); errDiv.style.display = 'block'; return;
  }

  btn.disabled = true;
  document.getElementById('sendBtnText').innerText = t('loading');
  try {
    const txHash = await executeSendTx(selectedSendChain, toAddr, amount, CHAINS[selectedSendChain].symbol);
    sucDiv.innerHTML = `✅ ${t('tx_sent')}<br><small style="font-family:monospace;word-break:break-all;font-size:10px;">${txHash}</small>`;
    sucDiv.style.display = 'block';
    document.getElementById('sendBtnText').innerText = t('tx_sent');
    setTimeout(refreshAll, 3000);
  } catch (e) {
    errDiv.innerText = t('tx_failed') + ': ' + (e.message || e);
    errDiv.style.display = 'block';
    document.getElementById('sendBtnText').innerText = t('confirm_send');
  }
  btn.disabled = false;
}

// ---------- Receive ----------
function selectReceiveChain(id) {
  const chain = CHAINS[id];
  const acc = wallet.accounts[id];
  if (!acc) return;
  document.getElementById('receiveChainName').innerText = chain.name;
  document.getElementById('receiveAddr').innerText = acc.address;
  const qrDiv = document.getElementById('receiveQR');
  qrDiv.innerHTML = '';
  new QRCode(qrDiv, { text: acc.address, width: 176, height: 176, colorDark: '#000', colorLight: '#fff' });
}

function openReceiveDefault() {
  const firstChain = CHAIN_ORDER.find(id => wallet.accounts[id]);
  openModal('receiveModal');
  selectReceiveChain(firstChain);
  populateReceiveChainSelect();
}

function populateReceiveChainSelect() {
  const sel = document.getElementById('receiveChainDropdown');
  sel.innerHTML = CHAIN_ORDER.filter(id => wallet.accounts[id]).map(id =>
    `<option value="${id}">${CHAINS[id].name}</option>`).join('');
  sel.onchange = () => selectReceiveChain(sel.value);
}

// ---------- History ----------
async function loadHistory() {
  const listEl = document.getElementById('txList');
  listEl.innerHTML = `<div class="spinner"></div>`;
  try {
    const firstAddr = wallet.accounts.ethereum?.address || Object.values(wallet.accounts)[0].address;
    const r = await fetch(`${API_BASE}?action=get_history&address=${firstAddr}`);
    const d = await r.json();
    const txs = d.data || [];
    if (!txs.length) { listEl.innerHTML = `<div class="loading-text" style="padding:40px 0;">${t('no_tx')}</div>`; return; }
    listEl.innerHTML = txs.map(tx => {
      const isMine = tx.from_address === (wallet.accounts[tx.chain]?.address);
      return `<div class="tx-item">
        <div class="tx-icon ${isMine?'sent':'received'}">
          <svg viewBox="0 0 24 24" fill="none" stroke="${isMine?'#ff4466':'#00d68f'}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            ${isMine ? '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>' : '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>'}
          </svg>
        </div>
        <div class="tx-info">
          <div class="tx-type">${isMine?t('sent'):t('received')} · ${CHAINS[tx.chain]?.name||tx.chain}</div>
          <div class="tx-addr">${(isMine?tx.to_address:tx.from_address).slice(0,14)}...</div>
        </div>
        <div class="tx-right">
          <div class="tx-amount" style="color:${isMine?'var(--red)':'var(--green)'}">${isMine?'-':'+'}${parseFloat(tx.amount).toFixed(4)} ${tx.token_symbol}</div>
          <div class="tx-time">${new Date(tx.created_at).toLocaleDateString()}</div>
        </div>
      </div>`;
    }).join('');
  } catch (e) {
    listEl.innerHTML = `<div class="loading-text" style="padding:40px 0;">${t('tx_failed')}</div>`;
  }
}

// ---------- Address book ----------
async function loadContacts() {
  const listEl = document.getElementById('contactList');
  const owner = wallet.accounts.ethereum?.address || Object.values(wallet.accounts)[0].address;
  try {
    const r = await fetch(`${API_BASE}?action=get_contacts&owner=${owner}`);
    const d = await r.json();
    const contacts = d.data || [];
    listEl.innerHTML = contacts.map(c => `
      <div class="contact-item">
        <div class="contact-info">
          <div class="contact-label">${c.label} <span style="color:var(--text3);font-weight:400;">(${c.chain})</span></div>
          <div class="contact-addr">${c.saved_address.slice(0,20)}...</div>
        </div>
        <button class="del-btn" onclick="deleteContact(${c.id})">${t('delete')}</button>
      </div>`).join('') || `<div class="loading-text">—</div>`;
  } catch (e) { listEl.innerHTML = `<div class="loading-text">—</div>`; }
}

async function addContact() {
  const label = document.getElementById('newContactLabel').value.trim();
  const chain = document.getElementById('newContactChain').value;
  const address = document.getElementById('newContactAddr').value.trim();
  if (!label || !address) return;
  const owner = wallet.accounts.ethereum?.address || Object.values(wallet.accounts)[0].address;
  await fetch(`${API_BASE}?action=save_contact`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ owner, label, chain, address })
  });
  document.getElementById('newContactLabel').value = '';
  document.getElementById('newContactAddr').value = '';
  loadContacts();
}

async function deleteContact(id) {
  const owner = wallet.accounts.ethereum?.address || Object.values(wallet.accounts)[0].address;
  await fetch(`${API_BASE}?action=delete_contact`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ id, owner })
  });
  loadContacts();
}

// ---------- Settings ----------
function syncSettingsUI() {
  document.querySelectorAll('.theme-opt').forEach(b => b.classList.toggle('active', b.dataset.theme === currentTheme));
  const langSel = document.getElementById('langSelect');
  if (langSel) langSel.value = currentLang;
}

function setTheme(theme) {
  currentTheme = theme;
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('mcw_theme', theme);
  syncSettingsUI();
}

function setLanguage(lang) {
  currentLang = lang;
  localStorage.setItem('mcw_lang', lang);
  applyTranslations();
}

function togglePrivKey(chainId) {
  const area = document.getElementById('showPrivKeyArea');
  const btn = document.getElementById('showPrivKeyBtn');
  if (area.style.display === 'none' || !area.style.display) {
    if (!confirm(t('warn_privkey') + '\n\n' + t('confirm_view'))) return;
    document.getElementById('privKeyDisplay').innerText = wallet.accounts[chainId]?.privateKey || '—';
    area.style.display = 'block';
    btn.innerText = t('hide_privkey');
  } else {
    area.style.display = 'none';
    btn.innerText = t('show_privkey');
  }
}

function toggleSeedPhrase() {
  const area = document.getElementById('showSeedArea');
  const btn = document.getElementById('showSeedBtn');
  if (!wallet.mnemonic) { alert('No seed phrase (single-chain import).'); return; }
  if (area.style.display === 'none' || !area.style.display) {
    if (!confirm(t('warn_privkey') + '\n\n' + t('confirm_view'))) return;
    document.getElementById('seedDisplay').innerText = wallet.mnemonic;
    area.style.display = 'block';
    btn.innerText = t('hide_seed');
  } else {
    area.style.display = 'none';
    btn.innerText = t('show_seed');
  }
}

function logout() {
  if (!confirm(t('logout_confirm'))) return;
  wallet = null;
  sessionStorage.removeItem('mcw_session');
  location.reload();
}

function populateSettingsChainSelect() {
  const sel = document.getElementById('privKeyChainSelect');
  sel.innerHTML = CHAIN_ORDER.filter(id => wallet.accounts[id]).map(id =>
    `<option value="${id}">${CHAINS[id].name}</option>`).join('');
}

// ---------- Helpers ----------
function openModal(id) {
  document.getElementById(id).classList.add('open');
  if (id === 'historyModal') loadHistory();
  if (id === 'settingsModal') { populateSettingsChainSelect(); loadContacts(); }
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function closeModalOverlay(e, id) { if (e.target.id === id) closeModal(id); }
function setNav(id) {
  document.querySelectorAll('.bnav-item').forEach(b => b.classList.remove('active'));
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}
function copyEl(id, btn) {
  const text = document.getElementById(id).innerText;
  navigator.clipboard.writeText(text).then(() => {
    const old = btn.innerText; btn.innerText = '✓ ' + t('copied');
    setTimeout(() => btn.innerText = old, 1500);
  });
}
function shareAddress() {
  const addr = document.getElementById('receiveAddr').innerText;
  if (navigator.share) navigator.share({ title: t('your_address'), text: addr });
  else { navigator.clipboard.writeText(addr); alert(t('copied')); }
}
