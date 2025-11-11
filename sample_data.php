<?php
// Insert sample categories
$categories = [
    ['Cleaning & Household', 'Eco-friendly cleaning supplies'],
    ['Kitchen & Dining', 'Sustainable kitchen essentials'],
    ['Home Décor & Living', 'Eco-conscious home decor'],
    ['Bathroom & Personal Care', 'Natural personal care products'],
    ['Lifestyle & Wellness', 'Sustainable lifestyle items'],
    ['Kids & Pets', 'Eco-friendly for family and pets'],
    ['Outdoor & Garden', 'Sustainable outdoor living']
];

foreach ($categories as $category) {
    $sql = "INSERT IGNORE INTO categories (name, description) VALUES ('{$category[0]}', '{$category[1]}')";
    $conn->query($sql);
}

// Insert sample products
$products = [
    [1, 'Bamboo Toothbrush', 'Natural bamboo toothbrush with replaceable head', 199.99, 50, 32],
    [1, 'Compostable Sponges', 'Plant-based cleaning sponges', 89.99, 25, 15],
    [2, 'Reusable Silicone Bags', 'Silicone food storage bags', 249.99, 30, 28],
    [4, 'Organic Cotton Towels', 'Chemical-free bath towels', 399.99, 20, 41],
    [7, 'Solar Garden Light', 'Solar powered garden light', 459.99, 15, 56]
];

foreach ($products as $product) {
    $sql = "INSERT IGNORE INTO products (category_id, name, description, price, stock_quantity, co2_saved) 
            VALUES ({$product[0]}, '{$product[1]}', '{$product[2]}', {$product[3]}, {$product[4]}, {$product[5]})";
    $conn->query($sql);
}

// Insert admin user
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (email, password_hash, first_name, last_name, role) 
        VALUES ('admin@dragonstone.com', '$password_hash', 'System', 'Administrator', 'Admin')";
$conn->query($sql);
?>