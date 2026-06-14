<?php
// Database Configuration - Secure connection settings
// Use environment variables in production for credentials

define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Change to secure user
define('DB_PASS', '');      // Set strong password
define('DB_NAME', 'online_voting');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,  // Security: Prevent SQL injection
        ]
    );
} catch (PDOException $e) {
    // Log error but don't expose details
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Function to log audit events
function logAudit($user_id, $action, $details) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}
?>