<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    json_response(['success' => false, 'message' => 'Unauthorized - Admin access required'], 403);
}

$action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'updateTokenValue':
        updateTokenValue();
        break;
    case 'createToken':
        createToken();
        break;
    case 'updateTokenLogo':
        updateTokenLogo();
        break;
    case 'creditDebitUser':
        creditDebitUser();
        break;
    case 'getAllUsers':
        getAllUsers();
        break;
    case 'getAllTokens':
        getAllTokens();
        break;
    default:
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
}

function updateTokenValue() {
    try {
        $tokenId = intval($_POST['token_id'] ?? 0);
        $usdValue = floatval($_POST['usd_value'] ?? 0);

        if ($tokenId <= 0 || $usdValue < 0) {
            json_response(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            UPDATE tokens
            SET usd_value = ?
            WHERE id = ?
        ');
        $stmt->execute([$usdValue, $tokenId]);

        json_response(['success' => true, 'message' => 'Token value updated successfully']);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function createToken() {
    try {
        $tokenName = sanitizeInput($_POST['token_name'] ?? '');
        $tokenSymbol = strtoupper(sanitizeInput($_POST['token_symbol'] ?? ''));
        $usdValue = floatval($_POST['usd_value'] ?? 0);

        if (empty($tokenName) || empty($tokenSymbol) || strlen($tokenSymbol) > 20) {
            json_response(['success' => false, 'message' => 'Invalid token parameters'], 400);
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('SELECT id FROM tokens WHERE token_symbol = ?');
        $stmt->execute([$tokenSymbol]);
        if ($stmt->fetch()) {
            json_response(['success' => false, 'message' => 'Token symbol already exists'], 400);
        }

        $logoUrl = $_POST['logo_url'] ?? null;
        if ($logoUrl) {
            $logoUrl = sanitizeInput($logoUrl);
        }

        $stmt = $pdo->prepare('
            INSERT INTO tokens (token_name, token_symbol, usd_value, logo_url)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$tokenName, $tokenSymbol, $usdValue, $logoUrl]);
        $tokenId = $pdo->lastInsertId();

        $userStmt = $pdo->prepare('SELECT id FROM users WHERE role = "user"');
        $userStmt->execute();
        $users = $userStmt->fetchAll();

        $balanceStmt = $pdo->prepare('INSERT INTO balances (user_id, token_id, balance) VALUES (?, ?, ?)');
        foreach ($users as $user) {
            $balanceStmt->execute([$user['id'], $tokenId, 0.00]);
        }

        json_response(['success' => true, 'message' => 'Token created successfully', 'token_id' => $tokenId]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function updateTokenLogo() {
    try {
        $tokenId = intval($_POST['token_id'] ?? 0);
        $logoUrl = sanitizeInput($_POST['logo_url'] ?? '');

        if ($tokenId <= 0 || empty($logoUrl)) {
            json_response(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            UPDATE tokens
            SET logo_url = ?
            WHERE id = ?
        ');
        $stmt->execute([$logoUrl, $tokenId]);

        json_response(['success' => true, 'message' => 'Token logo updated successfully']);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function creditDebitUser() {
    try {
        $userId = intval($_POST['user_id'] ?? 0);
        $tokenId = intval($_POST['token_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $operation = sanitizeInput($_POST['operation'] ?? 'credit');

        if ($userId <= 0 || $tokenId <= 0) {
            json_response(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        if ($operation !== 'credit' && $operation !== 'debit') {
            json_response(['success' => false, 'message' => 'Invalid operation'], 400);
        }

        if ($amount < 0) {
            json_response(['success' => false, 'message' => 'Invalid amount'], 400);
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('SELECT balance FROM balances WHERE user_id = ? AND token_id = ?');
        $stmt->execute([$userId, $tokenId]);
        $balance = $stmt->fetch();

        if (!$balance) {
            json_response(['success' => false, 'message' => 'User balance record not found'], 404);
        }

        if ($operation === 'debit' && $balance['balance'] < $amount) {
            json_response(['success' => false, 'message' => 'Insufficient balance to debit'], 400);
        }

        $operator = $operation === 'credit' ? '+' : '-';

        $stmt = $pdo->prepare('
            UPDATE balances
            SET balance = balance ' . $operator . ' ?
            WHERE user_id = ? AND token_id = ?
        ');
        $stmt->execute([$amount, $userId, $tokenId]);

        json_response(['success' => true, 'message' => ucfirst($operation) . ' completed successfully']);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function getAllUsers() {
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query('
            SELECT id, username, email, wallet_address, role, created_at
            FROM users
            ORDER BY created_at DESC
        ');
        $users = $stmt->fetchAll();

        json_response(['success' => true, 'data' => $users]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function getAllTokens() {
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query('
            SELECT id, token_name, token_symbol, usd_value, logo_url
            FROM tokens
            ORDER BY token_name
        ');
        $tokens = $stmt->fetchAll();

        json_response(['success' => true, 'data' => $tokens]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
?>