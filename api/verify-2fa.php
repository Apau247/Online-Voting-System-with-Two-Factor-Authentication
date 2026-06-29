<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo json_encode([
    'success' => false,
    'message' => '2FA verification endpoint not yet implemented'
]);
