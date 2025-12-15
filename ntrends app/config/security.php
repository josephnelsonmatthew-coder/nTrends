<?php
// config/security.php

// 1. Secure Session Management
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters BEFORE starting the session
    // Lifetime: 0 (until browser close), Path: /, Domain: null (current), Secure: false (dev) / true (prod), HttpOnly: true
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. CSRF Verification Helper
function verify_csrf_token($token = null)
{
    if (!$token) {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $token = $headers['x-csrf-token'] ?? $_POST['csrf_token'] ?? '';
    }

    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(['status' => 'error', 'message' => 'CSRF Token Mismatch. Please refresh the page.']));
    }
}

// 4. Input Sanitization Helper
function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>