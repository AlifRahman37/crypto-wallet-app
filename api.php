<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!isUserLoggedIn()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');

switch ($action) {
    case 'getTokens':
        getTokens();
        break;
    case 'getUserBalance':
        getUserBalance();
        break;
    case 'sendTransaction':
        sendTransaction();
        break;
    case 'getTransactionHistory':
        getTransactionHistory();
        break;
    case 'searchUser':
        searchUser();
        break;
    case 'getWalletInfo':
        getWalletInfo();
        break;
    default:
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
}

function getTokens() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query('SELECT id, token_name, token_symbol, usd_value, logo_url FROM tokens ORDER BY token_name');
        $tokens = $stmt->fetchAll();
        json_response(['success' => true, 'data' => $tokens]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function getUserBalance() {
    try {
        $userId = getCurrentUserId();
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            SELECT 
                t.id,
                t.token_name,
                t.token_symbol,
                t.usd_value,
                t.logo_url,
                b.balance
            FROM balances b
            JOIN tokens t ON b.token_id = t.id
            WHERE b.user_id = ?
            ORDER BY t.token_name
        ');
        $stmt->execute([$userId]);
        $balances = $stmt->fetchAll();

        json_response(['success' => true, 'data' => $balances]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function sendTransaction() {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['success' => false, 'message' => 'Invalid request method'], 400);
        }

        $senderId = getCurrentUserId();
        $receiverAddress = sanitizeInput($_POST['receiver_address'] ?? '');
        $tokenId = intval($_POST['token_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);

        if (empty($receiverAddress) || !validateWalletAddress($receiverAddress)) {
            json_response(['success' => false, 'message' => 'Invalid receiver wallet address'], 400);
        }

        if ($amount <= 0) {
            json_response(['success' => false, 'message' => 'Amount must be greater than 0'], 400);
        }

        if ($tokenId <= 0) {
            json_response(['success' => false, 'message' => 'Invalid token'], 400);
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            SELECT balance FROM balances
            WHERE user_id = ? AND token_id = ?
        ');
        $stmt->execute([$senderId, $tokenId]);
        $senderBalance = $stmt->fetch();

        if (!$senderBalance || $senderBalance['balance'] < $amount) {
            json_response(['success' => false, 'message' => 'Insufficient balance'], 400);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE wallet_address = ?');
        $stmt->execute([$receiverAddress]);
        $receiver = $stmt->fetch();

        if (!$receiver) {
            json_response(['success' => false, 'message' => 'Receiver wallet not found'], 404);
        }

        $receiverId = $receiver['id'];

        if ($senderId === $receiverId) {
            json_response(['success' => false, 'message' => 'Cannot send to yourself'], 400);
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('
                UPDATE balances
                SET balance = balance - ?
                WHERE user_id = ? AND token_id = ?
            ');
            $stmt->execute([$amount, $senderId, $tokenId]);

            $stmt = $pdo->prepare('
                UPDATE balances
                SET balance = balance + ?
                WHERE user_id = ? AND token_id = ?
            ');
            $stmt->execute([$amount, $receiverId, $tokenId]);

            $transactionHash = '0x' . bin2hex(random_bytes(32));
            $stmt = $pdo->prepare('
                INSERT INTO transactions (sender_id, receiver_id, token_id, amount, transaction_hash, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$senderId, $receiverId, $tokenId, $amount, $transactionHash, 'completed']);

            $pdo->commit();

            json_response([
                'success' => true,
                'message' => 'Transaction completed successfully',
                'transaction_hash' => $transactionHash
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function getTransactionHistory() {
    try {
        $userId = getCurrentUserId();
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            SELECT 
                t.id,
                t.transaction_hash,
                u1.username as sender_username,
                u1.wallet_address as sender_address,
                u2.username as receiver_username,
                u2.wallet_address as receiver_address,
                tok.token_symbol,
                tok.token_name,
                t.amount,
                t.status,
                t.created_at
            FROM transactions t
            JOIN users u1 ON t.sender_id = u1.id
            JOIN users u2 ON t.receiver_id = u2.id
            JOIN tokens tok ON t.token_id = tok.id
            WHERE t.sender_id = ? OR t.receiver_id = ?
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$userId, $userId, $limit, $offset]);
        $transactions = $stmt->fetchAll();

        json_response(['success' => true, 'data' => $transactions]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function searchUser() {
    try {
        $query = sanitizeInput($_GET['q'] ?? '');

        if (strlen($query) < 2) {
            json_response(['success' => false, 'message' => 'Query too short'], 400);
        }

        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            SELECT id, username, wallet_address
            FROM users
            WHERE username LIKE ? OR wallet_address LIKE ?
            LIMIT 10
        ');
        $query = '%' . $query . '%';
        $stmt->execute([$query, $query]);
        $users = $stmt->fetchAll();

        json_response(['success' => true, 'data' => $users]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function getWalletInfo() {
    try {
        $userId = getCurrentUserId();
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            SELECT id, username, email, wallet_address, role, created_at
            FROM users
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        json_response(['success' => true, 'data' => $user]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
?>