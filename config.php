<?php
/**
 * Crypto Wallet Application - Configuration File
 * Database connection and global settings
 */

// Database Configuration for InfinityFree
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'crypto_wallet');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'crypto_wallet_session');

// Application Configuration
define('APP_NAME', 'CryptoVault');
define('APP_URL', 'http://localhost'); // Change this to your domain
define('MAX_UPLOAD_SIZE', 2097152); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Security Configuration
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start Session
session_name(SESSION_NAME);
session_start();

// Session Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Database Connection Function
function getDatabaseConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Helper Functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isUserLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? 'user';
}

function redirectTo($path) {
    header('Location: ' . $path);
    exit;
}

function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function generateWalletAddress() {
    return '0x' . bin2hex(random_bytes(20));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateWalletAddress($address) {
    return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
}

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>
