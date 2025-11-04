<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/database.php';

// Include authentication functions
require_once __DIR__ . '/auth.php';

// Set timezone
date_default_timezone_set('UTC');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-errors.log');

// Application settings
define('SITE_NAME', 'Dragonstone Eco Grocery');
define('SITE_URL', '/');

// Google OAuth settings (optional - requires setup)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');

// Email settings (optional - for order confirmations)
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@dragonstone.eco');
define('FROM_NAME', getenv('FROM_NAME') ?: 'Dragonstone Eco Grocery');
?>
