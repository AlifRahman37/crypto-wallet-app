<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'log_tx': {
            $b = json_decode(file_get_contents('php://input'), true);
            $stmt = db()->prepare("INSERT INTO tx_log (wallet_address, chain, tx_hash, from_address, to_address, token_symbol, amount, status)
                                    VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $b['wallet_address'] ?? $b['from'] ?? '',
                $b['chain'] ?? '',
                $b['tx_hash'] ?? '',
                $b['from'] ?? '',
                $b['to'] ?? '',
                $b['token'] ?? '',
                $b['amount'] ?? 0,
                $b['status'] ?? 'sent'
            ]);
            json_out(['ok' => true]);
            break;
        }

        case 'get_history': {
            $addr = $_GET['address'] ?? '';
            $stmt = db()->prepare("SELECT * FROM tx_log WHERE wallet_address = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$addr]);
            json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
            break;
        }

        case 'save_contact': {
            $b = json_decode(file_get_contents('php://input'), true);
            $stmt = db()->prepare("INSERT INTO address_book (owner_address, label, chain, saved_address) VALUES (?,?,?,?)");
            $stmt->execute([$b['owner'] ?? '', $b['label'] ?? '', $b['chain'] ?? '', $b['address'] ?? '']);
            json_out(['ok' => true, 'id' => db()->lastInsertId()]);
            break;
        }

        case 'get_contacts': {
            $owner = $_GET['owner'] ?? '';
            $stmt = db()->prepare("SELECT * FROM address_book WHERE owner_address = ? ORDER BY created_at DESC");
            $stmt->execute([$owner]);
            json_out(['ok' => true, 'data' => $stmt->fetchAll()]);
            break;
        }

        case 'delete_contact': {
            $b = json_decode(file_get_contents('php://input'), true);
            $stmt = db()->prepare("DELETE FROM address_book WHERE id = ? AND owner_address = ?");
            $stmt->execute([$b['id'] ?? 0, $b['owner'] ?? '']);
            json_out(['ok' => true]);
            break;
        }

        case 'save_prefs': {
            $b = json_decode(file_get_contents('php://input'), true);
            $stmt = db()->prepare("INSERT INTO user_prefs (wallet_address, theme, language) VALUES (?,?,?)
                                    ON DUPLICATE KEY UPDATE theme=VALUES(theme), language=VALUES(language)");
            $stmt->execute([$b['address'] ?? '', $b['theme'] ?? 'dark', $b['language'] ?? 'en']);
            json_out(['ok' => true]);
            break;
        }

        case 'get_prefs': {
            $addr = $_GET['address'] ?? '';
            $stmt = db()->prepare("SELECT theme, language FROM user_prefs WHERE wallet_address = ?");
            $stmt->execute([$addr]);
            $row = $stmt->fetch();
            json_out(['ok' => true, 'data' => $row ?: ['theme' => 'dark', 'language' => 'en']]);
            break;
        }

        case 'config': {
            // Expose only public/free-tier API keys needed client-side. Never expose DB creds.
            json_out([
                'ok' => true,
                'alchemy'     => ALCHEMY_KEY,
                'bscscan'     => BSCSCAN_KEY,
                'blockcypher' => BLOCKCYPHER_KEY,
                'helius'      => HELIUS_KEY,
                'trongrid'    => TRONGRID_KEY,
            ]);
            break;
        }

        default:
            json_out(['ok' => false, 'error' => 'unknown action'], 404);
    }
} catch (Exception $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}
