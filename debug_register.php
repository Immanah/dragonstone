<?php
// debug_register.php - Find exactly where registration fails
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Registration Debug</h1>";

// Test database connection
echo "<h3>1. Testing Database Connection</h3>";
require_once 'includes/config.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();
if ($conn) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check if users table has required columns
    echo "<h3>2. Checking Database Columns</h3>";
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ verification_code column exists</p>";
    } else {
        echo "<p style='color: red;'>❌ verification_code column missing</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ is_verified column exists</p>";
    } else {
        echo "<p style='color: red;'>❌ is_verified column missing</p>";
    }
    
    // Test inserting a user
    echo "<h3>3. Testing User Insertion</h3>";
    $test_email = "test_" . time() . "@test.com";
    $verification_code = "123456";
    $password_hash = password_hash("test123", PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (first_name, last_name, email, password_hash, role, eco_points_balance, verification_code, is_verified) 
            VALUES ('Test', 'User', ?, ?, 'Customer', 100, ?, 0)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sss", $test_email, $password_hash, $verification_code);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ User insertion successful</p>";
            echo "<p>Test user created with email: $test_email</p>";
            
            // Clean up
            $conn->query("DELETE FROM users WHERE email = '$test_email'");
        } else {
            echo "<p style='color: red;'>❌ User insertion failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Statement preparation failed: " . $conn->error . "</p>";
    }
    
    $conn->close();
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
}

// Test session
echo "<h3>4. Testing Session</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['debug_test'] = 'session_works';
if (isset($_SESSION['debug_test'])) {
    echo "<p style='color: green;'>✅ Session working</p>";
} else {
    echo "<p style='color: red;'>❌ Session not working</p>";
}

// Test email function
echo "<h3>5. Testing Email Function</h3>";
function testEmail() {
    $test_email = "immanahmakitla@gmail.com"; // Your email
    $subject = "Debug Test Email";
    $message = "This is a debug test email from registration system.";
    $headers = "From: noreply@dragonstone.com\r\n";
    
    $result = mail($test_email, $subject, $message, $headers);
    return $result;
}

if (testEmail()) {
    echo "<p style='color: green;'>✅ Email function working</p>";
} else {
    echo "<p style='color: red;'>❌ Email function failed</p>";
}

// Test redirect
echo "<h3>6. Testing Redirect</h3>";
echo "<p>If you can see this, redirects are not happening automatically.</p>";
echo "<p><a href='test_redirect.php'>Test redirect manually</a></p>";

echo "<hr>";
echo "<h3>🎯 Next Steps</h3>";
echo "<p>Based on the results above, we'll know exactly where the issue is.</p>";
?>