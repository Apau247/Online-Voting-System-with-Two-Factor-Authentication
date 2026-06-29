<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!checkRateLimit('login', 5, 300)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many login attempts. Try again later.']);
    exit;
}

// CSRF Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password_hash, totp_secret, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }

    // Login successful - start session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];

    // Check if 2FA is enabled
    $requires_2fa = !empty($user['totp_secret']);

    echo json_encode([
        'success'      => true,
        'requires_2fa' => $requires_2fa,
        'redirect'     => $requires_2fa ? null : 'vote.html'
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Login failed. Try again later.']);
}
?>