<?php
// ============================================
// Multi-Chain Wallet — Server Config
// IMPORTANT: This file NEVER stores private keys or seed phrases.
// It only handles: tx history logs, address book, user preferences.
// ============================================

// ---- Database (fill in your InfinityFree credentials) ----
define('DB_HOST', 'sql311.infinityfree.com');
define('DB_NAME', 'if0_42569493_hdwallet');
define('DB_USER', 'if0_42569493');
define('DB_PASS', 'C3DFSE66mJJ');

// ---- Public RPC / API keys (safe to expose client-side; free tiers) ----
// Get these yourself and paste in. See README for links.
define('ALCHEMY_KEY',     'alch_vqz3GAVZltgspL-7q2m0e');      // ethereum, linea, arbitrum, base, optimism,
define('BSCSCAN_KEY',     'TKCCRKIABTYWS87AJBWFNZTAKED4SHXTSS');       // BNB Chain
define('BLOCKCYPHER_KEY', 'ebf3cce4bb0845d6ad4880f4a0b73e61');   // Bitcoin
define('HELIUS_KEY',      '1493670d-1196-4733-8dd8-8aaa3b794811');        // Solana
define('TRONGRID_KEY',    'fe9181e9-8ed8-4bbf-9759-8e54e884127b');      // Tron

// ---- CORS ----
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
