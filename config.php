<?php
// ============================================
// Multi-Chain Wallet — Server Config
// IMPORTANT: This file NEVER stores private keys or seed phrases.
// It only handles: tx history logs, address book, user preferences.
// ============================================

// ---- Database (fill in your InfinityFree credentials) ----
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_XXXXXXX_wallet');
define('DB_USER', 'if0_XXXXXXX');
define('DB_PASS', 'YOUR_DB_PASSWORD_HERE');

// ---- Public RPC / API keys (safe to expose client-side; free tiers) ----
// Get these yourself and paste in. See README for links.
define('ALCHEMY_KEY',     'YOUR_ALCHEMY_KEY');      // ethereum, linea, arbitrum, base, optimism, polygon
define('BSCSCAN_KEY',     'YOUR_BSCSCAN_KEY');       // BNB Chain
define('BLOCKCYPHER_KEY', 'YOUR_BLOCKCYPHER_KEY');   // Bitcoin
define('HELIUS_KEY',      'YOUR_HELIUS_KEY');        // Solana
define('TRONGRID_KEY',    'YOUR_TRONGRID_KEY');      // Tron

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
