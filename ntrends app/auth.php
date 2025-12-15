<?php
// --- ADD THESE 3 LINES TEMPORARILY ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -------------------------------------
require 'config/security.php'; // Includes session_start() and CSRF helpers
require 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verify Password
    if ($user && password_verify($password, $user['password'])) {
        // Success: Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Redirect to Appointments Module
        header("Location: modules/appointments/index.php");
        exit;
    } else {
        // Failure: Redirect back with error
        header("Location: login.php?error=Invalid username or password");
        exit;
    }
}
?>