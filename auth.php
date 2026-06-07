<?php
require_once __DIR__ . '/config.php';

$action = sanitizeInput($_POST['action'] ?? $_GET['action'] ?? '');

if ($action === 'register') {
    handleRegister();
} elseif ($action === 'login') {
    handleLogin();
} elseif ($action === 'logout') {
    handleLogout();
}

function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirectTo('index.php');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];

    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }

    if (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    if (!empty($errors)) {
        setFlashMessage(implode(' | ', $errors), 'error');
        redirectTo('index.php?page=register');
    }

    try {
        $pdo = getDatabaseConnection();

        // Check if username exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            setFlashMessage('Username or email already exists', 'error');
            redirectTo('index.php?page=register');
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS);

        // Generate wallet address
        $walletAddress = generateWalletAddress();

        // Insert user
        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, password, wallet_address, role)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$username, $email, $passwordHash, $walletAddress, 'user']);
        $userId = $pdo->lastInsertId();

        // Initialize token balances
        $tokenStmt = $pdo->prepare('SELECT id FROM tokens');
        $tokenStmt->execute();
        $tokens = $tokenStmt->fetchAll();

        $balanceStmt = $pdo->prepare('INSERT INTO balances (user_id, token_id, balance) VALUES (?, ?, ?)');
        foreach ($tokens as $token) {
            $balanceStmt->execute([$userId, $token['id'], 0.00]);
        }

        setFlashMessage('Registration successful! Please login.', 'success');
        redirectTo('index.php?page=login');
    } catch (PDOException $e) {
        setFlashMessage('Registration failed: ' . $e->getMessage(), 'error');
        redirectTo('index.php?page=register');
    }
}

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirectTo('index.php');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlashMessage('Please enter username and password', 'error');
        redirectTo('index.php?page=login');
    }

    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare('
            SELECT id, username, email, password, wallet_address, role
            FROM users
            WHERE username = ?
        ');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            setFlashMessage('Invalid username or password', 'error');
            redirectTo('index.php?page=login');
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['wallet_address'] = $user['wallet_address'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        setFlashMessage('Login successful!', 'success');
        redirectTo(isAdmin() ? 'admin.php' : 'dashboard.php');
    } catch (PDOException $e) {
        setFlashMessage('Login failed: ' . $e->getMessage(), 'error');
        redirectTo('index.php?page=login');
    }
}

function handleLogout() {
    session_destroy();
    setFlashMessage('Logged out successfully', 'success');
    redirectTo('index.php?page=login');
}
?>