<?php
// Secure Login API
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!checkRateLimit('login', 5, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many login attempts. Try again later.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';

if (!verifyCSRFToken($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password_hash, role, totp_secret FROM users WHERE email = ? AND is_verified = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        logAudit(null, 'failed_login', "Failed login for $email");
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $email;

    // Require 2FA if secret exists
    if (!empty($user['totp_secret'])) {
        $_SESSION['2fa_required'] = true;
        $_SESSION['2fa_user_id'] = $user['id'];
    } else {
        $_SESSION['2fa_verified'] = true;
    }

    logAudit($user['id'], 'login', 'Successful login');

    echo json_encode([
        'success' => true,
        'requires_2fa' => !empty($user['totp_secret']),
        'redirect' => $user['role'] === 'admin' ? 'admin/dashboard.html' : 'vote.html'
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Login error']);
}
?>