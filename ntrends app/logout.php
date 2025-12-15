<?php
require 'config/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
} else {
    // If GET request, redirect to dashboard or show error (prevent logout via link)
    header("Location: modules/appointments/index.php");
    exit;
}
?>