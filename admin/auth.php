<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session expiration after 2 hours of inactivity
$session_timeout = 7200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>