<?php
require_once 'includes/config.php';

echo "<h1>Email System Test</h1>";

// Test configuration
echo "<h3>Current Configuration:</h3>";
echo "<p>SMTP Host: " . SMTP_HOST . "</p>";
echo "<p>SMTP Port: " . SMTP_PORT . "</p>";
echo "<p>From Email: " . FROM_EMAIL . "</p>";

// Test email sending
$test_email = 'your-test-email@gmail.com'; // CHANGE TO YOUR TEST EMAIL
$subject = "DragonStone Email System Test";
$message = "If you receive this, your email system is working correctly!";
$headers = "From: " . FROM_EMAIL . "\r\n";

$result = mail($test_email, $subject, $message, $headers);

if ($result) {
    echo "<p style='color: green; font-size: 20px;'>✅ SUCCESS: Email sent to $test_email!</p>";
    echo "<p>Check your inbox (and spam folder) for the test email.</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ FAILED: Email could not be sent.</p>";
    echo "<p>Check your php.ini and sendmail.ini configuration.</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<p>1. Check if you received the test email</p>";
echo "<p>2. If not, check C:\\xampp2\\php\\logs\\mail.log for errors</p>";
echo "<p>3. Test the registration flow with a real email address</p>";
?>