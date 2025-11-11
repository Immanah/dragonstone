<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dragonstone_db');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1); // Show errors during development
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-errors.log');

// Set timezone
date_default_timezone_set('UTC');

// Application settings
define('SITE_NAME', 'Dragonstone Eco Grocery');
define('SITE_URL', 'http://localhost/dragonstone/'); // Update for production

// SMTP Configuration - Works for both local and production
define('SMTP_HOST', 'smtp.gmail.com'); // For production, use your domain's SMTP
define('SMTP_PORT', 587);
define('SMTP_USER', 'immanahmakitla@gmail.com'); // Change this for production
define('SMTP_PASS', 'uyso icmo wadv yses'); // Change this for production
define('FROM_EMAIL', 'noreply@dragonstone.com');
define('FROM_NAME', 'Dragonstone Eco Grocery');

// Email Configuration - Auto-detects environment
define('IS_PRODUCTION', false); // Set to true when deploying to production

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('SESSION_TIMEOUT', 86400); // 24 hours
define('VERIFICATION_CODE_EXPIRY', 86400); // 24 hours

// Include other files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

// Authentication helper functions - ONLY if they don't exist in auth.php
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
}

if (!function_exists('redirectIfLoggedIn')) {
    function redirectIfLoggedIn() {
        if (isLoggedIn()) {
            header('Location: index.php');
            exit();
        }
    }
}
?>