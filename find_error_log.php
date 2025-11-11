<?php
// find_error_log.php
echo "<h3>PHP Error Log Location:</h3>";
echo "Current error_log setting: " . ini_get('error_log') . "<br>";
echo "Script directory: " . __DIR__ . "<br>";

// Try to create a test error
error_log("TEST: This is a test error message from find_error_log.php");

echo "<h3>Checking common locations:</h3>";

$possible_locations = [
    __DIR__ . '/php-errors.log',
    __DIR__ . '/../php-errors.log', 
    __DIR__ . '/logs/php-errors.log',
    'C:/xampp/php/logs/php_error_log',
    'C:/wamp64/logs/php_error.log',
    '/Applications/XAMPP/xamppfiles/logs/php_error_log',
    '/Applications/MAMP/logs/php_error.log'
];

foreach ($possible_locations as $location) {
    if (file_exists($location)) {
        echo "✅ FOUND: " . $location . " (Size: " . filesize($location) . " bytes)<br>";
    } else {
        echo "❌ NOT FOUND: " . $location . "<br>";
    }
}
?>