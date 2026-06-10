<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set flash message on the new session
require_once __DIR__ . '/db.php';
set_flash('success', 'Anda telah berhasil keluar dari dashboard.');

header('Location: login.html');
exit;
