<?php
// diagnostic.php - Find the exact blocking issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 REGISTRATION DIAGNOSTIC</h1>";

// Test if we can even reach this point on form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: green; color: white; padding: 20px;'>";
    echo "🎉 FORM REACHED THE SERVER!";
    echo "</div>";
    
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    exit(); // Stop here to see what happens
}

// Test basic includes
echo "<h3>1. Testing Includes</h3>";
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/auth.php';
    echo "<p style='color: green;'>✅ All includes loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Includes failed: " . $e->getMessage() . "</p>";
}

// Test form rendering
?>
<h3>2. Testing Form Rendering</h3>
<form method="POST" action="diagnostic.php" id="testForm">
    <input type="text" name="first_name" value="Test" required>
    <input type="text" name="last_name" value="User" required> 
    <input type="email" name="email" value="test<?php echo rand(1000,9999); ?>@test.com" required>
    <input type="password" name="password" value="123456" required>
    <input type="password" name="confirm_password" value="123456" required>
    <button type="submit" name="register" value="1">SUBMIT DIAGNOSTIC FORM</button>
</form>

<script>
console.log("🔧 Diagnostic script loaded");
document.getElementById('testForm').addEventListener('submit', function(e) {
    console.log("🔄 Form submit event fired");
    // Let it submit normally
});
</script>