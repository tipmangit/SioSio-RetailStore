<?php
// admin_session_check.php

// Make sure the session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if the admin is logged in. If not, redirect to the login page.
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// 2. Set the timeout period to 30 minutes (1800 seconds)
$timeout = 1800; 

// 3. Check if 'last_activity' is set in the session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // If the last activity was more than 10 minutes ago, destroy the session
    session_unset();    
    session_destroy();
    
    // Redirect to the login page with a message
    header("Location: admin_login.php?reason=inactive");
    exit;
}

// 4. If the user is active, update the 'last_activity' timestamp
$_SESSION['last_activity'] = time();

?>