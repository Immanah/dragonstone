<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Basic PHP Test</h1>";
echo "<p>PHP is working...</p>";

// Test database configuration
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "<p style='color: green;'>✅ config.php loaded</p>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_USER: " . DB_USER . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
} else {
    echo "<p style='color: red;'>❌ config.php not found</p>";
}

// Test basic database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Database server connected</p>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Connection error: " . $e->getMessage() . "</p>";
}
?>