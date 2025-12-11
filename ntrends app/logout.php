<?php
session_start();
session_unset();
session_destroy();

// Redirect to login page (change 'login.php' if your file is named differently)
header("Location: login.php"); 
exit;
?>