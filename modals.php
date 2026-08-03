<!-- ===== Send Modal ===== -->
<div class="modal-overlay" id="sendModal" onclick="closeModalOverlay(event,'sendModal')">
  <div class="modal" style="position:relative;">
    <div class="modal-handle"></div>
    <button class="modal-close-btn" onclick="closeModal('sendModal')">×</button>
    <div class="modal-title" data-i18n="send">Send</div>
    <div class="modal-sub" data-i18n="select_network">Select Network</div>

    <div class="chain-select" id="sendChainSelect"></div>

    <div class="form-group">
      <label class="form-label" data-i18n="recipient">Recipient Address</label>
      <input type="text" id="sendToAddr" class="form-input" placeholder="0x... / T... / bc1... ">
    </div>
    <div class="form-group">
      <label class="form-label" id="sendBalanceLabel">Amount</label>
      <div style="position:relative;">
        <input type="number" id="sendAmount" class="form-input" placeholder="0.00" step="any">
        <button onclick="setMaxSend()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:rgba(109,94,252,0.15);border:1px solid rgba(109,94,252,0.3);color:#8b7bff;border-radius:8px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer;" data-i18n="max">MAX</button>
      </div>
    </div>

    <div id="sendError" class="alert alert-error" style="display:none;"></div>
    <div id="sendSuccess" class="alert" style="display:none;background:rgba(0,214,143,0.08);border:1px solid rgba(0,214,143,0.2);color:var(--green);"></div>

    <button class="btn-primary" id="sendBtn" onclick="executeSend()">
      <span id="sendBtnText" data-i18n="confirm_send">Confirm & Send</span>
    </button>
    <div style="text-align:center;font-size:11px;color:var(--text3);margin-top:12px;" data-i18n="local_signing">Signed locally · Keys never leave your browser</div>
  </div>
</div>

<!-- ===== Receive Modal ===== -->
<div class="modal-overlay" id="receiveModal" onclick="closeModalOverlay(event,'receiveModal')">
  <div class="modal" style="position:relative;">
    <div class="modal-handle"></div>
    <button class="modal-close-btn" onclick="closeModal('receiveModal')">×</button>
    <div class="modal-title" data-i18n="receive">Receive</div>
    <select id="receiveChainDropdown" class="form-input" style="margin-bottom:12px;font-family:'Inter',sans-serif;"></select>
    <div id="receiveQR" style="text-align:center;margin:16px 0;background:#fff;border-radius:16px;padding:16px;display:flex;justify-content:center;"></div>
    <div class="form-label" data-i18n="your_address">Your Address</div>
    <div id="receiveAddr" class="address-box"></div>
    <div style="display:flex;gap:10px;">
      <button class="copy-btn" onclick="copyEl('receiveAddr',this)" style="flex:1;justify-content:center;">📋 <span data-i18n="copy_address">Copy Address</span></button>
      <button class="copy-btn" onclick="shareAddress()" style="flex:1;justify-content:center;">↗ <span data-i18n="share">Share</span></button>
    </div>
    <div style="text-align:center;font-size:11px;color:var(--text3);margin-top:14px;" data-i18n="receive_sub">Send only assets on the matching network to this address</div>
  </div>
</div>

<!-- ===== Swap Modal ===== -->
<div class="modal-overlay" id="swapModal" onclick="closeModalOverlay(event,'swapModal')">
  <div class="modal" style="position:relative;">
    <div class="modal-handle"></div>
    <button class="modal-close-btn" onclick="closeModal('swapModal')">×</button>
    <div class="modal-title" data-i18n="swap">Swap</div>
    <div style="background:var(--surface2);border:1px solid var(--border2);border-radius:20px;padding:24px;text-align:center;">
      <div style="width:60px;height:60px;background:rgba(168,85,247,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4l4 4"/><path d="M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
      </div>
      <div style="font-size:14px;font-weight:600;margin-bottom:8px;">1inch DEX Aggregator</div>
      <div style="font-size:12.5px;color:var(--text2);margin-bottom:20px;">Best swap rates across chains via 1inch</div>
      <button class="btn-primary" onclick="window.open('https://app.1inch.io/','_blank')">Open 1inch ↗</button>
    </div>
  </div>
</div>

<!-- ===== History Modal ===== -->
<div class="modal-overlay" id="historyModal" onclick="closeModalOverlay(event,'historyModal')">
  <div class="modal" style="position:relative;">
    <div class="modal-handle"></div>
    <button class="modal-close-btn" onclick="closeModal('historyModal')">×</button>
    <div class="modal-title" data-i18n="tx_history">Transaction History</div>
    <div id="txList"><div class="spinner"></div></div>
  </div>
</div>

<!-- ===== Settings Modal ===== -->
<div class="modal-overlay" id="settingsModal" onclick="closeModalOverlay(event,'settingsModal')">
  <div class="modal" style="position:relative;">
    <div class="modal-handle"></div>
    <button class="modal-close-btn" onclick="closeModal('settingsModal')">×</button>
    <div class="modal-title" data-i18n="settings_title">Settings</div>

    <div class="settings-row">
      <div class="settings-label" data-i18n="theme">Theme</div>
      <div class="theme-switch">
        <button class="theme-opt" data-theme="dark" onclick="setTheme('dark')" data-i18n="dark">Dark</button>
        <button class="theme-opt" data-theme="light" onclick="setTheme('light')" data-i18n="light">Light</button>
      </div>
    </div>

    <div class="settings-row">
      <div class="settings-label" data-i18n="language">Language</div>
      <select id="langSelect" class="lang-select" onchange="setLanguage(this.value)">
        <option value="en">English</option>
        <option value="bn">বাংলা</option>
        <option value="zh">中文</option>
        <option value="hi">हिन्दी</option>
        <option value="es">Español</option>
        <option value="ar">العربية</option>
      </select>
    </div>

    <div style="margin-top:20px;">
      <div class="settings-label" style="margin-bottom:10px;" data-i18n="address_book">Address Book</div>
      <div id="contactList" style="margin-bottom:12px;"></div>
      <div style="display:flex;gap:8px;margin-bottom:8px;">
        <input type="text" id="newContactLabel" class="form-input" data-i18n-ph="label" placeholder="Label" style="flex:1;">
        <select id="newContactChain" class="form-input" style="flex:1;font-family:'Inter',sans-serif;">
          <?php foreach (['ethereum'=>'Ethereum','bitcoin'=>'Bitcoin','solana'=>'Solana','tron'=>'Tron','linea'=>'Linea','arbitrum'=>'Arbitrum','bnb'=>'BNB Chain','base'=>'Base','optimism'=>'OP','polygon'=>'Polygon'] as $k=>$v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="text" id="newContactAddr" class="form-input" placeholder="Address" style="margin-bottom:8px;">
      <button class="btn-secondary" onclick="addContact()" data-i18n="add_contact">Add Contact</button>
    </div>

    <div style="margin-top:20px;">
      <select id="privKeyChainSelect" class="form-input" style="margin-bottom:8px;font-family:'Inter',sans-serif;"></select>
      <button class="btn-secondary" id="showPrivKeyBtn" data-i18n="show_privkey" onclick="togglePrivKey(document.getElementById('privKeyChainSelect').value)">Show Private Key</button>
      <div id="showPrivKeyArea" style="display:none;margin-top:10px;">
        <div class="address-box" id="privKeyDisplay"></div>
      </div>
      <button class="btn-secondary" id="showSeedBtn" data-i18n="show_seed" onclick="toggleSeedPhrase()">Show Seed Phrase</button>
      <div id="showSeedArea" style="display:none;margin-top:10px;">
        <div class="address-box" id="seedDisplay"></div>
      </div>
    </div>

    <button class="btn-primary" style="margin-top:24px;background:linear-gradient(135deg,#ff4466,#cc2244);" onclick="logout()" data-i18n="logout">Log Out</button>
  </div>
</div>
