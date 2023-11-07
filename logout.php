<?php
session_start();
$_SESSION['previous_page'] = basename($_SERVER['PHP_SELF']);
$previousPage = $_SESSION['previous_page'] ?? 'unknown';

// Clear the session data
session_destroy();

// Redirect the user to the login page
header('Location: /baiplus/login.php');
exit;
?>
