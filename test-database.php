<?php
include 'includes/database.php';

// Test if we can read data
echo "<h2>Testing Database Connection</h2>";

// Test Categories
$result = $conn->query("SELECT * FROM categories");
echo "<h3>Categories (" . $result->num_rows . " found):</h3>";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['name'] . "<br>";
}

// Test Products  
$result = $conn->query("SELECT * FROM products");
echo "<h3>Products (" . $result->num_rows . " found):</h3>";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['name'] . " (R" . $row['price'] . ")<br>";
}

// Test Users
$result = $conn->query("SELECT * FROM users");
echo "<h3>Users (" . $result->num_rows . " found):</h3>";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['email'] . " (" . $row['role'] . ")<br>";
}

$conn->close();
?>