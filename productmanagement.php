<?php
session_start();

// Proper session authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlogin.php');
    exit();
}

// Check if user has permission for product management
$admin_permissions = $_SESSION['admin_permissions'] ?? [];
if (!in_array('all', $admin_permissions) && !in_array('products', $admin_permissions) && !in_array('content', $admin_permissions)) {
    header('Location: adminindex.php');
    exit();
}

// Database connection
include '../includes/database.php';
$conn = getDatabaseConnection();

// Check if suppliers table exists, if not create it
$check_suppliers = $conn->query("SHOW TABLES LIKE 'suppliers'");
if ($check_suppliers->num_rows == 0) {
    $create_suppliers_sql = "
        CREATE TABLE suppliers (
            supplier_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact_email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    if ($conn->query($create_suppliers_sql)) {
        $conn->query("INSERT INTO suppliers (name, contact_email) VALUES ('Default Supplier', 'supplier@dragonstone.com')");
    }
}

// Check if products table has the new columns
$check_columns = $conn->query("SHOW COLUMNS FROM products LIKE 'supplier_id'");
if ($check_columns->num_rows == 0) {
    $alter_products_sql = "
        ALTER TABLE products 
        ADD COLUMN image_path VARCHAR(255),
        ADD COLUMN supplier_id INT DEFAULT 1,
        ADD COLUMN low_stock_threshold INT DEFAULT 10,
        ADD COLUMN is_active BOOLEAN DEFAULT TRUE
    ";
    $conn->query($alter_products_sql);
}

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$stock_filter = $_GET['stock'] ?? '';
$sort_by = $_GET['sort'] ?? 'product_id';
$sort_order = $_GET['order'] ?? 'DESC';
$view_mode = $_GET['view'] ?? 'list';

// Check if we're coming from a form submission (to prevent modal persistence)
$from_form_submission = false;

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_form_submission = true;
    
    // Handle bulk delete
    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'delete' && isset($_POST['selected_products'])) {
        $selected_products = $_POST['selected_products'];
        // If it's a string (from JSON), decode it
        if (is_string($selected_products)) {
            $selected_products = json_decode($selected_products, true);
        }
        
        $success_count = 0;
        $error_count = 0;
        $deactivated_count = 0;
        $errors = [];
        
        foreach ($selected_products as $product_id) {
            $product_id = intval($product_id);
            
            // Check if there are any reviews for this product
            $check_reviews_sql = "SELECT COUNT(*) as review_count FROM reviews WHERE product_id = ?";
            $check_stmt = $conn->prepare($check_reviews_sql);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $review_result = $check_stmt->get_result();
            $review_count = $review_result->fetch_assoc()['review_count'];
            $check_stmt->close();
            
            // Check if there are any subscriptions for this product
            $check_subscriptions_sql = "SELECT COUNT(*) as subscription_count FROM subscriptions WHERE product_id = ?";
            $check_sub_stmt = $conn->prepare($check_subscriptions_sql);
            $check_sub_stmt->bind_param("i", $product_id);
            $check_sub_stmt->execute();
            $subscription_result = $check_sub_stmt->get_result();
            $subscription_count = $subscription_result->fetch_assoc()['subscription_count'];
            $check_sub_stmt->close();
            
            // Check if there are any order items for this product
            $check_order_items_sql = "SELECT COUNT(*) as order_items_count FROM order_items WHERE product_id = ?";
            $check_order_stmt = $conn->prepare($check_order_items_sql);
            $check_order_stmt->bind_param("i", $product_id);
            $check_order_stmt->execute();
            $order_items_result = $check_order_stmt->get_result();
            $order_items_count = $order_items_result->fetch_assoc()['order_items_count'];
            $check_order_stmt->close();
            
            $has_dependencies = $review_count > 0 || $subscription_count > 0 || $order_items_count > 0;
            
            if ($has_dependencies) {
                // If there are dependencies, deactivate the product instead of deleting
                $sql = "UPDATE products SET is_active = 0, stock_quantity = 0 WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                
                if ($stmt->execute()) {
                    $deactivated_count++;
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "Error deactivating product ID $product_id: " . $conn->error;
                }
            } else {
                // If no dependencies, proceed with deletion
                $sql = "DELETE FROM products WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = "Error deleting product ID $product_id: " . $conn->error;
                }
            }
        }
        
        if ($success_count > 0) {
            $message = "Successfully processed $success_count products";
            if ($deactivated_count > 0) {
                $message .= " ($deactivated_count deactivated due to dependencies)";
            }
            if ($error_count > 0) {
                $message .= ", $error_count failed";
            }
            header("Location: productmanagement.php?success=" . urlencode($message));
            exit();
        } else {
            $error = "Error processing products: " . implode(", ", $errors);
        }
    }
    
    // Handle add product
    if (isset($_POST['action']) && $_POST['action'] == 'add_product') {
        $name = trim($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $co2_saved = intval($_POST['co2_saved']);
        $supplier_id = intval($_POST['supplier_id']);
        $low_stock_threshold = intval($_POST['low_stock_threshold']);
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $filename = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/products/' . $filename;
                }
            }
        }
        
        $sql = "INSERT INTO products (name, category_id, description, price, stock_quantity, co2_saved, image_path, supplier_id, low_stock_threshold) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdiisii", $name, $category_id, $description, $price, $stock_quantity, $co2_saved, $image_path, $supplier_id, $low_stock_threshold);
        
        if ($stmt->execute()) {
            header("Location: productmanagement.php?success=Product+added+successfully");
            exit();
        } else {
            $error = "Error adding product: " . $conn->error;
        }
    }
    
    // Handle edit product
    if (isset($_POST['action']) && $_POST['action'] == 'edit_product') {
        $product_id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $co2_saved = intval($_POST['co2_saved']);
        $supplier_id = intval($_POST['supplier_id']);
        $low_stock_threshold = intval($_POST['low_stock_threshold']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle image upload
        $image_sql = "";
        $image_path = null;
        $params = [];
        $types = "sisdiiiiii";
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $filename = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/products/' . $filename;
                    $image_sql = ", image_path = ?";
                    $types .= "s";
                }
            }
        }
        
        if ($image_path) {
            $sql = "UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, stock_quantity = ?, co2_saved = ?, supplier_id = ?, low_stock_threshold = ?, is_active = ? $image_sql WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, $name, $category_id, $description, $price, $stock_quantity, $co2_saved, $supplier_id, $low_stock_threshold, $is_active, $image_path, $product_id);
        } else {
            $sql = "UPDATE products SET name = ?, category_id = ?, description = ?, price = ?, stock_quantity = ?, co2_saved = ?, supplier_id = ?, low_stock_threshold = ?, is_active = ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, $name, $category_id, $description, $price, $stock_quantity, $co2_saved, $supplier_id, $low_stock_threshold, $is_active, $product_id);
        }
        
        if ($stmt->execute()) {
            header("Location: productmanagement.php?success=Product+updated+successfully");
            exit();
        } else {
            $error = "Error updating product: " . $conn->error;
        }
    }
    
    // Handle delete product - UPDATED: Check for all dependencies
    if (isset($_POST['action']) && $_POST['action'] == 'delete_product') {
        $product_id = intval($_POST['product_id']);
        
        // Check if there are any reviews for this product
        $check_reviews_sql = "SELECT COUNT(*) as review_count FROM reviews WHERE product_id = ?";
        $check_stmt = $conn->prepare($check_reviews_sql);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $review_result = $check_stmt->get_result();
        $review_count = $review_result->fetch_assoc()['review_count'];
        $check_stmt->close();
        
        // Check if there are any subscriptions for this product
        $check_subscriptions_sql = "SELECT COUNT(*) as subscription_count FROM subscriptions WHERE product_id = ?";
        $check_sub_stmt = $conn->prepare($check_subscriptions_sql);
        $check_sub_stmt->bind_param("i", $product_id);
        $check_sub_stmt->execute();
        $subscription_result = $check_sub_stmt->get_result();
        $subscription_count = $subscription_result->fetch_assoc()['subscription_count'];
        $check_sub_stmt->close();
        
        // Check if there are any order items for this product
        $check_order_items_sql = "SELECT COUNT(*) as order_items_count FROM order_items WHERE product_id = ?";
        $check_order_stmt = $conn->prepare($check_order_items_sql);
        $check_order_stmt->bind_param("i", $product_id);
        $check_order_stmt->execute();
        $order_items_result = $check_order_stmt->get_result();
        $order_items_count = $order_items_result->fetch_assoc()['order_items_count'];
        $check_order_stmt->close();
        
        $has_dependencies = $review_count > 0 || $subscription_count > 0 || $order_items_count > 0;
        
        if ($has_dependencies) {
            // If there are dependencies, deactivate the product instead of deleting
            $sql = "UPDATE products SET is_active = 0, stock_quantity = 0 WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                $dependencies = [];
                if ($review_count > 0) $dependencies[] = "$review_count reviews";
                if ($subscription_count > 0) $dependencies[] = "$subscription_count subscriptions";
                if ($order_items_count > 0) $dependencies[] = "$order_items_count order items";
                
                $deps_text = implode(", ", $dependencies);
                header("Location: productmanagement.php?success=Product+deactivated+due+to+existing+" . urlencode($deps_text));
                exit();
            } else {
                $error = "Error deactivating product: " . $conn->error;
            }
        } else {
            // If no dependencies, proceed with deletion
            $sql = "DELETE FROM products WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                header("Location: productmanagement.php?success=Product+deleted+successfully");
                exit();
            } else {
                $error = "Error deleting product: " . $conn->error;
            }
        }
    }
    
    // Handle restock order
    if (isset($_POST['action']) && $_POST['action'] == 'order_restock') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['restock_quantity']);
        
        $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quantity, $product_id);
        
        if ($stmt->execute()) {
            header("Location: productmanagement.php?success=Restock+order+placed");
            exit();
        } else {
            $error = "Error placing restock order: " . $conn->error;
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}

// Build filter SQL
$where_conditions = [];
$params = [];
$types = "";

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($status_filter) {
    if ($status_filter === 'active') {
        $where_conditions[] = "p.is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "p.is_active = 0";
    } elseif ($status_filter === 'low_stock') {
        $where_conditions[] = "p.stock_quantity <= p.low_stock_threshold AND p.is_active = 1";
    } elseif ($status_filter === 'out_of_stock') {
        $where_conditions[] = "p.stock_quantity = 0 AND p.is_active = 1";
    }
}

if ($stock_filter) {
    if ($stock_filter === 'low') {
        $where_conditions[] = "p.stock_quantity <= p.low_stock_threshold AND p.stock_quantity > 0";
    } elseif ($stock_filter === 'out') {
        $where_conditions[] = "p.stock_quantity = 0";
    } elseif ($stock_filter === 'available') {
        $where_conditions[] = "p.stock_quantity > p.low_stock_threshold";
    }
}

// Validate sort parameters
$allowed_sorts = ['product_id', 'name', 'price', 'stock_quantity', 'co2_saved', 'date_added'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'product_id';
}
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'DESC';
}

// Build the main query
$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

$products_sql = "
    SELECT p.*, c.name as category_name, s.name as supplier_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
    $where_sql
    ORDER BY $sort_by $sort_order
";

if (!empty($params)) {
    $stmt = $conn->prepare($products_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products_result = $stmt->get_result();
} else {
    $products_result = $conn->query($products_sql);
}

// Get categories for dropdown and filters
$categories_for_filters = [];
$categories_query = $conn->query("SELECT DISTINCT category_id, name FROM categories ORDER BY name");
if ($categories_query && $categories_query->num_rows > 0) {
    while($category = $categories_query->fetch_assoc()) {
        $categories_for_filters[] = $category;
    }
} else {
    // If no categories exist, create a default one to prevent errors
    $conn->query("INSERT IGNORE INTO categories (name) VALUES ('Uncategorized')");
    $categories_query = $conn->query("SELECT DISTINCT category_id, name FROM categories ORDER BY name");
    if ($categories_query && $categories_query->num_rows > 0) {
        while($category = $categories_query->fetch_assoc()) {
            $categories_for_filters[] = $category;
        }
    }
}

// Get suppliers for dropdown
$suppliers_for_filters = [];
$suppliers_query = $conn->query("SELECT * FROM suppliers ORDER BY name");
if ($suppliers_query && $suppliers_query->num_rows > 0) {
    while($supplier = $suppliers_query->fetch_assoc()) {
        $suppliers_for_filters[] = $supplier;
    }
}

// Get product for editing - ONLY if not coming from form submission
$edit_product = null;
if (isset($_GET['edit']) && !$from_form_submission) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM products WHERE product_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_product = $edit_result->fetch_assoc();
}

// Get product for restocking - ONLY if not coming from form submission
$restock_product = null;
if (isset($_GET['restock']) && !$from_form_submission) {
    $restock_id = intval($_GET['restock']);
    $restock_sql = "SELECT * FROM products WHERE product_id = ?";
    $restock_stmt = $conn->prepare($restock_sql);
    $restock_stmt->bind_param("i", $restock_id);
    $restock_stmt->execute();
    $restock_result = $restock_stmt->get_result();
    $restock_product = $restock_result->fetch_assoc();
}

// Get stats for filters
$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$active_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1")->fetch_assoc()['total'];
$low_stock_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0 AND is_active = 1")->fetch_assoc()['total'];
$out_of_stock_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity = 0 AND is_active = 1")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - DragonStone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2d5016;
            --secondary-color: #4a7c2a;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 10px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-nav .nav-link {
            color: #6c757d;
            border-radius: var(--border-radius);
            margin: 0.1rem;
            transition: var(--transition);
        }
        .admin-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .admin-nav .nav-link:hover:not(.active) {
            background-color: rgba(45, 80, 22, 0.1);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .card-product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .low-stock-alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .out-of-stock-alert {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .filter-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }
        
        .sortable-header {
            cursor: pointer;
            user-select: none;
            transition: var(--transition);
        }
        .sortable-header:hover {
            background-color: #e9ecef;
        }
        
        .view-product-link {
            font-size: 0.8rem;
            color: #6c757d;
            text-decoration: none;
            transition: var(--transition);
        }
        .view-product-link:hover {
            color: var(--primary-color);
        }
        
        .product-card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid #e9ecef;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .product-card .card-body {
            padding: 1.25rem;
        }
        
        .product-card .card-footer {
            background-color: white;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .view-toggle-btn {
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            transition: var(--transition);
        }
        
        .view-toggle-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .bulk-actions {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(45, 80, 22, 0.25);
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
        }
        
        .table > :not(caption) > * > * {
            padding: 0.75rem 0.5rem;
        }
        
        .checkbox-cell {
            width: 40px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .action-buttons .btn {
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons .btn-group {
                display: flex;
                flex-direction: column;
            }
            
            .action-buttons .btn {
                margin-bottom: 0.25rem;
                border-radius: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-dragon me-2"></i>DragonStone Admin
                <small class="d-block opacity-75">Product Management</small>
            </span>
            <div class="navbar-nav">
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-external-link-alt me-1"></i>View Main Site</a>
                <a href="adminindex.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Admin Navigation -->
    <div class="bg-light border-bottom">
        <div class="container">
            <div class="nav admin-nav nav-pills py-2">
                <a class="nav-link" href="adminindex.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                <a class="nav-link" href="usermanagement.php"><i class="fas fa-users me-1"></i>User Management</a>
                <a class="nav-link active" href="productmanagement.php"><i class="fas fa-box me-1"></i>Product Management</a>
                <a class="nav-link" href="categorymanagement.php"><i class="fas fa-tags me-1"></i>Category Management</a>
                <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i>Reports</a>
            </div>
        </div>
    </div>

    <!-- Product Management Content -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-boxes me-2"></i>Product Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus me-1"></i>Add New Product</button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <div><?php echo $success; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div><?php echo $error; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Category Filter</label>
                    <select class="form-select" onchange="applyFilter('category', this.value)">
                        <option value="">All Categories</option>
                        <?php if (!empty($categories_for_filters)): ?>
                            <?php foreach($categories_for_filters as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No categories available</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status Filter</label>
                    <select class="form-select" onchange="applyFilter('status', this.value)">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active (<?php echo $active_products; ?>)</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="low_stock" <?php echo $status_filter == 'low_stock' ? 'selected' : ''; ?>>Low Stock (<?php echo $low_stock_count; ?>)</option>
                        <option value="out_of_stock" <?php echo $status_filter == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock (<?php echo $out_of_stock_count; ?>)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock Filter</label>
                    <select class="form-select" onchange="applyFilter('stock', this.value)">
                        <option value="">All Stock Levels</option>
                        <option value="available" <?php echo $stock_filter == 'available' ? 'selected' : ''; ?>>Good Stock</option>
                        <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">View Mode</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn view-toggle-btn <?php echo $view_mode == 'list' ? 'active' : ''; ?>" onclick="changeView('list')">
                            <i class="fas fa-list me-1"></i> List View
                        </button>
                        <button type="button" class="btn view-toggle-btn <?php echo $view_mode == 'card' ? 'active' : ''; ?>" onclick="changeView('card')">
                            <i class="fas fa-th-large me-1"></i> Card View
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Actions</label>
                    <div>
                        <a href="productmanagement.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-times me-1"></i>Clear Filters</a>
                        <span class="text-muted d-block mt-1 text-center">Total: <?php echo $total_products; ?> products</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions" style="display: none;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span id="selectedCount">0</span> products selected
                </div>
                <div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmBulkDelete()">
                        <i class="fas fa-trash me-1"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-1" onclick="clearSelection()">
                        <i class="fas fa-times me-1"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Table (List View) -->
        <?php if ($view_mode == 'list'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                </th>
                                <th class="sortable-header" onclick="sortTable('product_id')">
                                    ID 
                                    <?php if ($sort_by == 'product_id'): ?>
                                        <small><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></small>
                                    <?php endif; ?>
                                </th>
                                <th>Image</th>
                                <th class="sortable-header" onclick="sortTable('name')">
                                    Product 
                                    <?php if ($sort_by == 'name'): ?>
                                        <small><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></small>
                                    <?php endif; ?>
                                </th>
                                <th>Category</th>
                                <th class="sortable-header" onclick="sortTable('price')">
                                    Price 
                                    <?php if ($sort_by == 'price'): ?>
                                        <small><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></small>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable-header" onclick="sortTable('co2_saved')">
                                    CO2 Saved 
                                    <?php if ($sort_by == 'co2_saved'): ?>
                                        <small><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></small>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable-header" onclick="sortTable('stock_quantity')">
                                    Stock 
                                    <?php if ($sort_by == 'stock_quantity'): ?>
                                        <small><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></small>
                                    <?php endif; ?>
                                </th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products_result && $products_result->num_rows > 0): ?>
                                <?php while($product = $products_result->fetch_assoc()): ?>
                                    <?php
                                    $status = 'Active';
                                    $statusColor = 'bg-success';
                                    $low_stock_threshold = $product['low_stock_threshold'] ?? 10;
                                    $row_class = '';
                                    
                                    if ($product['stock_quantity'] <= $low_stock_threshold && $product['stock_quantity'] > 0) {
                                        $status = 'Low Stock';
                                        $statusColor = 'bg-warning';
                                        $row_class = 'low-stock-alert';
                                    }
                                    if ($product['stock_quantity'] == 0) {
                                        $status = 'Out of Stock';
                                        $statusColor = 'bg-danger';
                                        $row_class = 'out-of-stock-alert';
                                    }
                                    if (!$product['is_active']) {
                                        $status = 'Inactive';
                                        $statusColor = 'bg-secondary';
                                    }
                                    
                                    $is_low_stock = $product['stock_quantity'] <= $low_stock_threshold && $product['is_active'];
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td class="checkbox-cell">
                                            <input type="checkbox" class="product-checkbox" value="<?php echo $product['product_id']; ?>" onchange="updateBulkActions()">
                                        </td>
                                        <td><?php echo $product['product_id']; ?></td>
                                        <td>
                                            <?php if (!empty($product['image_path'])): ?>
                                                <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                            <?php else: ?>
                                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                    <small class="text-muted">No image</small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <br><small class='text-muted'><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></small>
                                            <br><a href="../product-detail.php?id=<?php echo $product['product_id']; ?>" target="_blank" class="view-product-link"><i class="fas fa-external-link-alt me-1"></i>View on Customer Site</a>
                                        </td>
                                        <td><span class='badge bg-info status-badge'><?php echo htmlspecialchars($product['category_name']); ?></span></td>
                                        <td>R<?php echo number_format($product['price'], 2); ?></td>
                                        <td><span class='badge bg-success status-badge'><?php echo $product['co2_saved']; ?>kg</span></td>
                                        <td>
                                            <?php echo $product['stock_quantity']; ?>
                                            <?php if ($is_low_stock): ?>
                                                <br><small class="text-warning">Threshold: <?php echo $low_stock_threshold; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($product['supplier_name'] ?? 'Default Supplier'); ?></small></td>
                                        <td><span class='badge <?php echo $statusColor; ?> status-badge'><?php echo $status; ?></span></td>
                                        <td class="action-buttons">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $product['product_id']; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&view=<?php echo $view_mode; ?>" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                <?php if ($is_low_stock): ?>
                                                    <a href="?restock=<?php echo $product['product_id']; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&view=<?php echo $view_mode; ?>" class="btn btn-outline-warning"><i class="fas fa-boxes"></i></a>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fa-3x mb-3"></i>
                                            <h5>No products found</h5>
                                            <p>Get started by adding your first product.</p>
                                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">Add First Product</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Products Card View -->
        <div class="row" id="productsCardView">
            <?php if ($products_result && $products_result->num_rows > 0): ?>
                <?php 
                // Reset pointer for card view
                $products_result->data_seek(0); 
                ?>
                <?php while($product = $products_result->fetch_assoc()): ?>
                    <?php
                    $status = 'Active';
                    $statusColor = 'bg-success';
                    $low_stock_threshold = $product['low_stock_threshold'] ?? 10;
                    
                    if ($product['stock_quantity'] <= $low_stock_threshold && $product['stock_quantity'] > 0) {
                        $status = 'Low Stock';
                        $statusColor = 'bg-warning';
                    }
                    if ($product['stock_quantity'] == 0) {
                        $status = 'Out of Stock';
                        $statusColor = 'bg-danger';
                    }
                    if (!$product['is_active']) {
                        $status = 'Inactive';
                        $statusColor = 'bg-secondary';
                    }
                    
                    $is_low_stock = $product['stock_quantity'] <= $low_stock_threshold && $product['is_active'];
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card product-card h-100">
                            <?php if (!empty($product['image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" class="card-product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="card-product-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-box-open fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <span class="badge <?php echo $statusColor; ?> status-badge"><?php echo $status; ?></span>
                                </div>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-primary">R<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Stock: <?php echo $product['stock_quantity']; ?></span>
                                    <span class="badge bg-success">CO2: <?php echo $product['co2_saved']; ?>kg</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Supplier: <?php echo htmlspecialchars($product['supplier_name'] ?? 'Default Supplier'); ?></small>
                                </div>
                                <?php if ($is_low_stock): ?>
                                    <div class="alert alert-warning py-1 mb-2">
                                        <small><i class="fas fa-exclamation-triangle me-1"></i> Low stock (Threshold: <?php echo $low_stock_threshold; ?>)</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <a href="../product-detail.php?id=<?php echo $product['product_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye me-1"></i> View</a>
                                    <div class="btn-group">
                                        <a href="?edit=<?php echo $product['product_id']; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&view=<?php echo $view_mode; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                        <?php if ($is_low_stock): ?>
                                            <a href="?restock=<?php echo $product['product_id']; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&stock=<?php echo $stock_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&view=<?php echo $view_mode; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-boxes"></i></a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <input type="checkbox" class="form-check-input product-checkbox" value="<?php echo $product['product_id']; ?>" onchange="updateBulkActions()">
                                    <small class="text-muted ms-1">Select for bulk action</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-box-open fa-4x mb-3"></i>
                        <h3>No products found</h3>
                        <p>Get started by adding your first product.</p>
                        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addProductModal">Add First Product</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php if (!empty($categories_for_filters)): ?>
                                        <?php foreach($categories_for_filters as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No categories available</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (R)</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CO2 Saved (kg)</label>
                                <input type="number" class="form-control" name="co2_saved" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach($suppliers_for_filters as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>">
                                            <?php echo htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" name="low_stock_threshold" value="10" min="1" required>
                                <div class="form-text">Alert when stock reaches this level</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock_quantity" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="product_image" accept="image/*">
                                <div class="form-text">Recommended: 500x500px, JPG/PNG</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <?php if ($edit_product && !$from_form_submission): ?>
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product: <?php echo htmlspecialchars($edit_product['name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <?php if (!empty($categories_for_filters)): ?>
                                        <?php foreach($categories_for_filters as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" <?php echo $edit_product['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (R)</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" value="<?php echo $edit_product['price']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CO2 Saved (kg)</label>
                                <input type="number" class="form-control" name="co2_saved" min="0" value="<?php echo $edit_product['co2_saved']; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id" required>
                                    <?php foreach($suppliers_for_filters as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo $edit_product['supplier_id'] == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" name="low_stock_threshold" value="<?php echo $edit_product['low_stock_threshold'] ?? 10; ?>" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock_quantity" min="0" value="<?php echo $edit_product['stock_quantity']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Image</label>
                                <?php if (!empty($edit_product['image_path'])): ?>
                                    <div class="mb-2">
                                        <img src="../<?php echo htmlspecialchars($edit_product['image_path']); ?>" alt="Current image" class="product-image">
                                        <small class="d-block text-muted">Current image</small>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="product_image" accept="image/*">
                                <div class="form-text">Leave empty to keep current image</div>
                            </div>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $edit_product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active Product</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Restock Modal -->
    <?php if ($restock_product && !$from_form_submission): ?>
    <div class="modal fade" id="restockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-boxes me-2"></i>Restock Product: <?php echo htmlspecialchars($restock_product['name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="order_restock">
                    <input type="hidden" name="product_id" value="<?php echo $restock_product['product_id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <p><strong>Current Stock:</strong> <?php echo $restock_product['stock_quantity']; ?></p>
                            <p><strong>Low Stock Threshold:</strong> <?php echo $restock_product['low_stock_threshold'] ?? 10; ?></p>
                            <p><strong>Supplier:</strong> Default Supplier</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity to Order</label>
                            <input type="number" class="form-control" name="restock_quantity" min="1" value="50" required>
                            <div class="form-text">Standard order quantity from supplier</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Place Restock Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bulk Delete Form -->
    <form id="bulkDeleteForm" method="POST" style="display: none;">
        <input type="hidden" name="bulk_action" value="delete">
        <input type="hidden" name="selected_products" id="selected_products">
    </form>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_product">
        <input type="hidden" name="product_id" id="delete_product_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(productId, productName) {
            if (confirm(`Are you sure you want to delete product "${productName}"?\n\nNote: If this product has reviews, subscriptions, or order history, it will be deactivated instead of deleted.`)) {
                document.getElementById('delete_product_id').value = productId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        function confirmBulkDelete() {
            const selectedProducts = getSelectedProducts();
            if (selectedProducts.length === 0) {
                alert('Please select at least one product to delete.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedProducts.length} selected product(s)?\n\nNote: Products with reviews, subscriptions, or order history will be deactivated instead of deleted.`)) {
                document.getElementById('selected_products').value = JSON.stringify(selectedProducts);
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        function applyFilter(type, value) {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (value) {
                urlParams.set(type, value);
            } else {
                urlParams.delete(type);
            }
            
            window.location.href = '?' + urlParams.toString();
        }

        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort');
            const currentOrder = urlParams.get('order');
            
            let newOrder = 'ASC';
            if (currentSort === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }
            
            urlParams.set('sort', column);
            urlParams.set('order', newOrder);
            
            window.location.href = '?' + urlParams.toString();
        }
        
        function changeView(viewMode) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('view', viewMode);
            window.location.href = '?' + urlParams.toString();
        }
        
        function toggleSelectAll(checkbox) {
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            productCheckboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBulkActions();
        }
        
        function getSelectedProducts() {
            const selectedProducts = [];
            const productCheckboxes = document.querySelectorAll('.product-checkbox:checked');
            productCheckboxes.forEach(cb => {
                selectedProducts.push(cb.value);
            });
            return selectedProducts;
        }
        
        function updateBulkActions() {
            const selectedProducts = getSelectedProducts();
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            const selectAll = document.getElementById('selectAll');
            
            if (selectedProducts.length > 0) {
                bulkActions.style.display = 'block';
                selectedCount.textContent = selectedProducts.length;
                
                // Update select all checkbox state
                if (selectAll) {
                    const totalCheckboxes = document.querySelectorAll('.product-checkbox').length;
                    selectAll.checked = selectedProducts.length === totalCheckboxes;
                    selectAll.indeterminate = selectedProducts.length > 0 && selectedProducts.length < totalCheckboxes;
                }
            } else {
                bulkActions.style.display = 'none';
                if (selectAll) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                }
            }
        }
        
        function clearSelection() {
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            productCheckboxes.forEach(cb => {
                cb.checked = false;
            });
            updateBulkActions();
        }

        // Auto-show modals when URL parameters are present
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($edit_product && !$from_form_submission): ?>
                var editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                editModal.show();
            <?php endif; ?>
            
            <?php if ($restock_product && !$from_form_submission): ?>
                var restockModal = new bootstrap.Modal(document.getElementById('restockModal'));
                restockModal.show();
            <?php endif; ?>
            
            // Initialize bulk actions
            updateBulkActions();
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>