<?php
// Authentication and Security Functions
session_start();

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// CSRF Token Generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting (simple in-memory for starter; use Redis in production)
function checkRateLimit($key, $maxAttempts = 5, $decaySeconds = 300) {
    $attempts = $_SESSION['rate_limit'][$key] ?? 0;
    $lastAttempt = $_SESSION['rate_limit_time'][$key] ?? 0;
    
    if (time() - $lastAttempt > $decaySeconds) {
        $_SESSION['rate_limit'][$key] = 1;
        $_SESSION['rate_limit_time'][$key] = time();
        return true;
    }
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key] = $attempts + 1;
    return true;
}

// Base32 encoding/decoding (RFC 4648)
function base32_encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $char) {
        $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $binary = str_pad($binary, ceil(strlen($binary) / 5) * 5, '0');
    $result = '';
    foreach (str_split($binary, 5) as $chunk) {
        $result .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
    }
    return $result;
}

function base32_decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split(strtoupper($data)) as $char) {
        $val = strpos($alphabet, $char);
        if ($val === false) continue;
        $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) < 8) break;
        $result .= chr(bindec($chunk));
    }
    return $result;
}

// TOTP 2FA Functions (Pure PHP implementation - Google Authenticator compatible)
function generateTOTP($secret) {
    // Simple TOTP generator - in production use robust library like otphp
    $time = floor(time() / 30);
    $data = pack('J', $time);
    $hash = hash_hmac('sha1', $data, base32_decode($secret), true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function verifyTOTP($secret, $code) {
    $expected = generateTOTP($secret);
    return hash_equals($expected, $code);  // Timing-safe comparison
}

function generateTOTPSecret() {
    return base32_encode(random_bytes(20));  // 20 bytes → 32 char base32
}

// Login required middleware
function requireLogin($role = 'voter') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.html');
        exit;
    }
    if ($role === 'admin' && $_SESSION['role'] !== 'admin') {
        header('Location: ../index.html');
        exit;
    }
    // Check 2FA if enabled
    if (isset($_SESSION['2fa_required']) && $_SESSION['2fa_required'] && !isset($_SESSION['2fa_verified'])) {
        header('Location: ../2fa.html');  // Assume 2fa page
        exit;
    }
}
?>