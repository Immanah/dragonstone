<?php
// debug_simple.php - Ultra simple debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 ULTRA SIMPLE REGISTRATION DEBUG</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>📨 FORM WAS SUBMITTED!</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Test basic database
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $conn = getDatabaseConnection();
    if ($conn) {
        echo "<p style='color: green;'>✅ DATABASE CONNECTED</p>";
        
        // Try simple insert
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES ('Debug', 'Test', 'debug@test.com', 'test', 'Customer')";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ DATABASE INSERT WORKS</p>";
            // Clean up
            $conn->query("DELETE FROM users WHERE email = 'debug@test.com'");
        } else {
            echo "<p style='color: red;'>❌ DATABASE INSERT FAILED: " . $conn->error . "</p>";
        }
        
        $conn->close();
    } else {
        echo "<p style='color: red;'>❌ DATABASE CONNECTION FAILED</p>";
    }
} else {
    echo "<p>Form NOT submitted yet.</p>";
}
?>

<h3>🧪 TEST FORM (No JavaScript)</h3>
<form method="POST" action="">
    <input type="text" name="first_name" placeholder="First Name" value="Test" required>
    <input type="text" name="last_name" placeholder="Last Name" value="User" required>
    <input type="email" name="email" placeholder="Email" value="test<?php echo time(); ?>@test.com" required>
    <input type="password" name="password" placeholder="Password" value="123456" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" value="123456" required>
    <button type="submit" name="register" value="1">SUBMIT TEST FORM</button>
</form>

<hr>
<h3>🔧 CHECK PHP CONFIG</h3>
<pre>
PHP Version: <?php echo phpversion(); ?>

POST Max Size: <?php echo ini_get('post_max_size'); ?>

Upload Max Filesize: <?php echo ini_get('upload_max_filesize'); ?>

Max Input Time: <?php echo ini_get('max_input_time'); ?>

Memory Limit: <?php echo ini_get('memory_limit'); ?>
</pre>