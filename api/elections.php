<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

echo json_encode([
    'success' => false,
    'message' => 'Elections endpoint not yet implemented'
]);
