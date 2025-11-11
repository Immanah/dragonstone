<?php
echo "<h3>Testing MySQL Connection...</h3>";

// Test different connection methods
$hosts = [
    'localhost',
    '127.0.0.1',
    'localhost:3306', 
    '127.0.0.1:3306'
];

foreach ($hosts as $host) {
    echo "Trying: <strong>$host</strong>... ";
    $conn = @new mysqli($host, 'root', '');
    
    if ($conn->connect_error) {
        echo "❌ FAILED: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ SUCCESS!<br>";
        $conn->close();
    }
}

// Test with socket (common XAMPP issue)
echo "Trying with socket... ";
$conn = @new mysqli('localhost', 'root', '', null, null, 'C:/xampp2/mysql/mysql.sock');
if ($conn->connect_error) {
    echo "❌ FAILED: " . $conn->connect_error . "<br>";
} else {
    echo "✅ SUCCESS!<br>";
    $conn->close();
}
?>