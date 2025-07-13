<?php
// Logout Page (logout.php)
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit();
?>