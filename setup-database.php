<?php
// setup-database.php - Run this once to set up your database
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - DragonStone</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>DragonStone Database Setup</h1>";

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("<div class='error'>❌ Database connection failed: " . $conn->connect_error . "</div>");
}

// Create database if it doesn't exist
if (!$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die("<div class='error'>❌ Failed to create database: " . $conn->error . "</div>");
}

$conn->select_db(DB_NAME);
echo "<div class='success'>✅ Connected to database: " . DB_NAME . "</div>";

// Table creation queries - Simplified version
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255),
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        role ENUM('Customer','Admin','ContentManager','ReportViewer') DEFAULT 'Customer',
        eco_points_balance INT DEFAULT 0,
        date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        is_verified TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "products" => "CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock_quantity INT NOT NULL,
        co2_saved DECIMAL(8,2),
        image_url VARCHAR(500),
        is_active BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "reviews" => "CREATE TABLE IF NOT EXISTS reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        product_id INT,
        rating TINYINT NOT NULL,
        comment TEXT,
        review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_approved BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "forum_posts" => "CREATE TABLE IF NOT EXISTS forum_posts (
        post_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        post_type ENUM('Discussion','Question','Tip','Announcement') DEFAULT 'Discussion',
        is_pinned BOOLEAN DEFAULT FALSE,
        like_count INT DEFAULT 0,
        post_date DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "forum_replies" => "CREATE TABLE IF NOT EXISTS forum_replies (
        reply_id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT,
        user_id INT,
        content TEXT NOT NULL,
        reply_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "community_events" => "CREATE TABLE IF NOT EXISTS community_events (
        event_id INT AUTO_INCREMENT PRIMARY KEY,
        event_name VARCHAR(255) NOT NULL,
        event_description TEXT,
        event_schedule VARCHAR(100),
        event_location VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "event_participants" => "CREATE TABLE IF NOT EXISTS event_participants (
        participation_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        user_id INT,
        join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_participation (event_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// Create tables
foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Table '$table_name' created successfully</div>";
    } else {
        echo "<div class='error'>❌ Error creating table '$table_name': " . $conn->error . "</div>";
    }
}

// Insert sample data
echo "<h2>Inserting Sample Data</h2>";

// Insert sample users
$users = [
    ['admin@dragonstone.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User', 'Admin', 1000],
    ['john@email.com', password_hash('customer123', PASSWORD_DEFAULT), 'John', 'Doe', 'Customer', 500],
    ['sarah@email.com', password_hash('customer123', PASSWORD_DEFAULT), 'Sarah', 'Smith', 'Customer', 750]
];

$user_stmt = $conn->prepare("INSERT IGNORE INTO users (email, password_hash, first_name, last_name, role, eco_points_balance) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($users as $user) {
    $user_stmt->bind_param("sssssi", $user[0], $user[1], $user[2], $user[3], $user[4], $user[5]);
    if ($user_stmt->execute()) {
        echo "<div class='success'>✅ User '$user[0]' created</div>";
    }
}
$user_stmt->close();

// Insert sample products
$products = [
    ['Bamboo Toothbrush', 'Eco-friendly bamboo toothbrush', 199.99, 50, 32],
    ['Reusable Shopping Bag', 'Durable reusable shopping bag', 149.99, 100, 25],
    ['Solar Powered Light', 'Solar garden light', 459.99, 20, 56]
];

$product_stmt = $conn->prepare("INSERT IGNORE INTO products (name, description, price, stock_quantity, co2_saved) VALUES (?, ?, ?, ?, ?)");
foreach ($products as $product) {
    $product_stmt->bind_param("ssdii", $product[0], $product[1], $product[2], $product[3], $product[4]);
    if ($product_stmt->execute()) {
        echo "<div class='success'>✅ Product '$product[0]' created</div>";
    }
}
$product_stmt->close();

// Insert sample reviews
$reviews = [
    [2, 1, 5, 'Great product! Very eco-friendly.'],
    [3, 2, 4, 'Love this bag, use it every day!'],
    [2, 3, 5, 'Amazing quality and saves energy!']
];

$review_stmt = $conn->prepare("INSERT IGNORE INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
foreach ($reviews as $review) {
    $review_stmt->bind_param("iiis", $review[0], $review[1], $review[2], $review[3]);
    if ($review_stmt->execute()) {
        echo "<div class='success'>✅ Review added</div>";
    }
}
$review_stmt->close();

// Insert sample forum posts
$posts = [
    [2, 'Welcome to our community!', 'Hello everyone! Excited to be part of this sustainable community.', 'Discussion'],
    [3, 'Best eco-friendly products?', 'What are your favorite sustainable products?', 'Question'],
    [2, 'Tip: Reduce plastic waste', 'Carry a reusable water bottle and shopping bag!', 'Tip']
];

$post_stmt = $conn->prepare("INSERT IGNORE INTO forum_posts (user_id, title, content, post_type) VALUES (?, ?, ?, ?)");
foreach ($posts as $post) {
    $post_stmt->bind_param("isss", $post[0], $post[1], $post[2], $post[3]);
    if ($post_stmt->execute()) {
        echo "<div class='success'>✅ Forum post '$post[1]' created</div>";
    }
}
$post_stmt->close();

// Insert community events
$events = [
    ['#RentToReset Campaign', 'Join us in donating worn items to be repurposed for community projects.', 'Every Saturday', 'Various Locations'],
    ['Community Clean-Up Day', 'Help offset carbon through local tree planting and neighborhood clean-ups.', 'Monthly', 'Various Locations'],
    ['Virtual Style Exchange', 'Trade looks and styling tips online with fellow sustainable fashion lovers.', 'Bi-weekly', 'Online']
];

$event_stmt = $conn->prepare("INSERT IGNORE INTO community_events (event_name, event_description, event_schedule, event_location) VALUES (?, ?, ?, ?)");
foreach ($events as $event) {
    $event_stmt->bind_param("ssss", $event[0], $event[1], $event[2], $event[3]);
    if ($event_stmt->execute()) {
        echo "<div class='success'>✅ Event '$event[0]' created</div>";
    }
}
$event_stmt->close();

echo "<div class='success' style='font-size: 1.2em; padding: 20px;'>
    <h3>🎉 Database Setup Complete!</h3>
    <p><strong>Admin Login:</strong> admin@dragonstone.com / admin123</p>
    <p><strong>Customer Login:</strong> john@email.com / customer123</p>
    <p><a href='community.php' style='color: #155724; font-weight: bold;'>➡️ Go to Community Page</a></p>
</div>";

$conn->close();
echo "</body></html>";
?>