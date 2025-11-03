<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/database.php';
include 'includes/auth.php';

echo "<pre>SESSION: ";
print_r($_SESSION);
echo "</pre>";

// Test database connection
$conn = getDatabaseConnection();
if (!$conn) {
    die("Database connection failed");
}
echo "Database connected successfully<br>";

// Test cart
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "CART IS EMPTY - redirecting to cart.php";
    header('Location: cart.php');
    exit();
}

echo "Cart contents: ";
print_r($_SESSION['cart']);
?>