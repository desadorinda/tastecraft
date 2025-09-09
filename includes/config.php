<?php
// includes/config.php

// Error reporting (dev) - turn off on production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session cookie params - secure if using HTTPS
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() === PHP_SESSION_NONE) {
    // PHP 7.3+ accepts array; if older PHP adjust accordingly.
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Base URL (adjust if different)
define('BASE_URL', 'http://localhost/tastecraft/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tastecraft');
define('DB_USER', 'root');
define('DB_PASS', '');
