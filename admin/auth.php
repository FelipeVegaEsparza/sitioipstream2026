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

// Session fingerprint
$fingerprint = $_SERVER['REMOTE_ADDR'] . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '');
if (!empty($_SESSION['user_id']) && empty($_SESSION['fingerprint'])) {
    $_SESSION['fingerprint'] = $fingerprint;
} elseif (!empty($_SESSION['user_id']) && $_SESSION['fingerprint'] !== $fingerprint) {
    session_destroy();
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generar token CSRF en cada request autenticado
generateCsrfToken();
?>