<?php
session_start();

// Proper session authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlogin.php');
    exit();
}

// Check if user has permission for category management
$admin_permissions = $_SESSION['admin_permissions'] ?? [];
if (!in_array('all', $admin_permissions) && !in_array('categories', $admin_permissions)) {
    header('Location: adminindex.php');
    exit();
}

// Database connection
include '../includes/database.php';
$conn = getDatabaseConnection();

// Check if categories table has the required columns
$has_advanced_features = false;
$missing_columns = [];

try {
    // Check if parent_id column exists
    $check_sql = "SHOW COLUMNS FROM categories LIKE 'parent_id'";
    $result = $conn->query($check_sql);
    $has_parent_id = $result->num_rows > 0;
    
    // Check all required columns for advanced features
    $required_columns = ['parent_id', 'image_path', 'is_active', 'show_in_menu', 'sort_order', 'date_created'];
    $existing_columns = [];
    
    $columns_result = $conn->query("SHOW COLUMNS FROM categories");
    while ($column = $columns_result->fetch_assoc()) {
        $existing_columns[] = $column['Field'];
    }
    
    foreach ($required_columns as $column) {
        if (!in_array($column, $existing_columns)) {
            $missing_columns[] = $column;
        }
    }
    
    $has_advanced_features = empty($missing_columns);
    
} catch (Exception $e) {
    // If there's an error checking columns, assume basic mode
    $has_advanced_features = false;
    $missing_columns = $required_columns;
}

// Handle database upgrade
if (isset($_POST['upgrade_database'])) {
    $success = upgradeCategoriesTable($conn);
    if ($success) {
        header('Location: categorymanagement.php?upgrade=success');
        exit();
    } else {
        $error = "Failed to upgrade database. Please check your database permissions.";
    }
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle add category
    if (isset($_POST['action']) && $_POST['action'] == 'add_category') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if ($has_advanced_features) {
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order'] ?? 0);
        } else {
            $parent_id = NULL;
            $is_active = 1;
            $show_in_menu = 1;
            $sort_order = 0;
        }
        
        // Handle image upload
        $image_path = NULL;
        if ($has_advanced_features && isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
            $filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/categories/' . $filename;
                }
            }
        }
        
        // Check if category name already exists
        if ($has_advanced_features) {
            $check_sql = "SELECT category_id FROM categories WHERE name = ? AND (parent_id = ? OR (? IS NULL AND parent_id IS NULL))";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("sii", $name, $parent_id, $parent_id);
        } else {
            $check_sql = "SELECT category_id FROM categories WHERE name = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $name);
        }
        
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Category name already exists!";
        } else {
            // Build SQL based on available columns
            if ($has_advanced_features) {
                $sql = "INSERT INTO categories (name, description, parent_id, image_path, is_active, show_in_menu, sort_order, date_created) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisiii", $name, $description, $parent_id, $image_path, $is_active, $show_in_menu, $sort_order);
            } else {
                $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $name, $description);
            }
            
            if ($stmt->execute()) {
                $success = "Category added successfully!";
            } else {
                $error = "Error adding category: " . $conn->error;
            }
        }
    }
    
    // Handle edit category
    if (isset($_POST['action']) && $_POST['action'] == 'edit_category') {
        $category_id = intval($_POST['category_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if ($has_advanced_features) {
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order'] ?? 0);
        }
        
        // Handle image upload if column exists
        $image_sql = "";
        $image_params = [];
        
        if ($has_advanced_features && isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
            // Upload new image
            $upload_dir = '../uploads/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
            $filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['category_image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/categories/' . $filename;
                    $image_sql = ", image_path = ?";
                    $image_params[] = $image_path;
                }
            }
        }
        
        // Check if category name already exists (excluding current category)
        if ($has_advanced_features) {
            $check_sql = "SELECT category_id FROM categories WHERE name = ? AND category_id != ? AND (parent_id = ? OR (? IS NULL AND parent_id IS NULL))";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("siii", $name, $category_id, $parent_id, $parent_id);
        } else {
            $check_sql = "SELECT category_id FROM categories WHERE name = ? AND category_id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $name, $category_id);
        }
        
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Category name already exists!";
        } else {
            // Build SQL based on available columns
            if ($has_advanced_features) {
                $sql = "UPDATE categories SET name = ?, description = ?, parent_id = ?, is_active = ?, show_in_menu = ?, sort_order = ?" . $image_sql . " WHERE category_id = ?";
                $stmt = $conn->prepare($sql);
                
                $params = [$name, $description, $parent_id, $is_active, $show_in_menu, $sort_order];
                if (!empty($image_params)) {
                    $params = array_merge($params, $image_params);
                }
                $params[] = $category_id;
                
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            } else {
                $sql = "UPDATE categories SET name = ?, description = ? WHERE category_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $description, $category_id);
            }
            
            if ($stmt->execute()) {
                $success = "Category updated successfully!";
            } else {
                $error = "Error updating category: " . $conn->error;
            }
        }
    }
    
    // Handle delete category
    if (isset($_POST['action']) && $_POST['action'] == 'delete_category') {
        $category_id = intval($_POST['category_id']);
        
        // Check if category has products
        $check_products_sql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_products_sql);
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['product_count'] > 0) {
            $error = "Cannot delete category that has products assigned! Please reassign or delete the products first.";
        } else {
            // Check if category has subcategories (only if parent_id column exists)
            $has_subcategories = false;
            if ($has_advanced_features) {
                $check_subcategories_sql = "SELECT COUNT(*) as subcategory_count FROM categories WHERE parent_id = ?";
                $check_stmt = $conn->prepare($check_subcategories_sql);
                $check_stmt->bind_param("i", $category_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['subcategory_count'] > 0) {
                    $error = "Cannot delete category that has subcategories! Please delete or reassign the subcategories first.";
                    $has_subcategories = true;
                }
            }
            
            if (!$has_subcategories) {
                $sql = "DELETE FROM categories WHERE category_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $category_id);
                
                if ($stmt->execute()) {
                    $success = "Category deleted successfully!";
                } else {
                    $error = "Error deleting category: " . $conn->error;
                }
            }
        }
    }
}

// Get all categories
if ($has_advanced_features) {
    $categories_sql = "
        SELECT c.*, 
               p.name as parent_name,
               (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count,
               (SELECT COUNT(*) FROM categories WHERE parent_id = c.category_id) as subcategory_count
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.category_id 
        ORDER BY COALESCE(c.parent_id, 0), c.sort_order ASC, c.name ASC
    ";
} else {
    $categories_sql = "
        SELECT c.*, 
               (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count,
               0 as subcategory_count,
               NULL as parent_name
        FROM categories c 
        ORDER BY c.name ASC
    ";
}

$categories_result = $conn->query($categories_sql);

// Build category tree for dropdowns (only if advanced features exist)
$categories_tree = [];
$flat_categories = [];

if ($has_advanced_features && $categories_result) {
    while ($category = $categories_result->fetch_assoc()) {
        $flat_categories[$category['category_id']] = $category;
        
        if ($category['parent_id'] === NULL) {
            $categories_tree[$category['category_id']] = $category;
            $categories_tree[$category['category_id']]['children'] = [];
        }
    }

    // Add children to their parents
    foreach ($flat_categories as $category) {
        if ($category['parent_id'] !== NULL && isset($categories_tree[$category['parent_id']])) {
            $categories_tree[$category['parent_id']]['children'][$category['category_id']] = $category;
        }
    }

    // Reset pointer for later use
    $categories_result->data_seek(0);
}

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM categories WHERE category_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_category = $edit_result->fetch_assoc();
}

// Get category statistics
if ($has_advanced_features) {
    $stats_sql = "
        SELECT 
            COUNT(*) as total_categories,
            SUM(is_active) as active_categories,
            SUM(show_in_menu) as menu_categories,
            (SELECT COUNT(*) FROM categories WHERE parent_id IS NULL) as main_categories
        FROM categories
    ";
} else {
    $stats_sql = "
        SELECT 
            COUNT(*) as total_categories,
            COUNT(*) as active_categories,
            COUNT(*) as menu_categories,
            COUNT(*) as main_categories
        FROM categories
    ";
}

$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total_categories' => 0, 'active_categories' => 0, 'menu_categories' => 0, 'main_categories' => 0];

// Database upgrade function
function upgradeCategoriesTable($conn) {
    $queries = [
        "ALTER TABLE categories ADD COLUMN parent_id INT NULL AFTER description",
        "ALTER TABLE categories ADD COLUMN image_path VARCHAR(255) NULL AFTER parent_id",
        "ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER image_path",
        "ALTER TABLE categories ADD COLUMN show_in_menu TINYINT(1) DEFAULT 1 AFTER is_active",
        "ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0 AFTER show_in_menu",
        "ALTER TABLE categories ADD COLUMN date_created DATETIME DEFAULT CURRENT_TIMESTAMP AFTER sort_order"
    ];
    
    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            error_log("Database upgrade failed: " . $conn->error);
            return false;
        }
    }
    
    // Add foreign key constraint
    $fk_query = "ALTER TABLE categories ADD FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL";
    if (!$conn->query($fk_query)) {
        error_log("Foreign key constraint failed (this might be expected): " . $conn->error);
    }
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - DragonStone</title>
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
        
        .filter-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            border: 1px solid #e9ecef;
        }
        
        .category-card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid #e9ecef;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .category-card .card-body {
            padding: 1.25rem;
        }
        
        .category-card .card-footer {
            background-color: white;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .category-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.5rem;
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
            background-color: #f8f9fa;
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
            color: white;
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
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stat-card .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .subcategory-row {
            background-color: #f8f9fa;
            padding-left: 2rem !important;
        }
        
        .subcategory-indicator {
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            color: #6c757d;
        }
        
        .db-upgrade-alert {
            border-left: 4px solid #dc3545;
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
            
            .category-card .card-body {
                padding: 1rem;
            }
            
            .subcategory-row {
                padding-left: 1rem !important;
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
                <small class="d-block opacity-75">Category Management</small>
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
                <a class="nav-link" href="productmanagement.php"><i class="fas fa-box me-1"></i>Product Management</a>
                <a class="nav-link active" href="categorymanagement.php"><i class="fas fa-tags me-1"></i>Category Management</a>
                <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i>Reports</a>
            </div>
        </div>
    </div>

    <!-- Category Management Content -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tags me-2"></i>Category Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fas fa-plus me-1"></i>Add New Category</button>
        </div>

        <!-- Database Upgrade Alert -->
        <?php if (!$has_advanced_features): ?>
            <div class="alert alert-warning db-upgrade-alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                    <div>
                        <h5 class="alert-heading mb-2">Database Upgrade Required</h5>
                        <p class="mb-2">Advanced category features are not available. The following columns are missing:</p>
                        <ul class="mb-3">
                            <?php foreach ($missing_columns as $column): ?>
                                <li><code><?php echo $column; ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                        <form method="POST">
                            <button type="submit" name="upgrade_database" class="btn btn-primary">
                                <i class="fas fa-database me-1"></i>Upgrade Database Now
                            </button>
                            <small class="text-muted ms-2">This will add the missing columns to enable advanced features</small>
                        </form>
                    </div>
                </div>
            </div>
        <?php elseif (isset($_GET['upgrade']) && $_GET['upgrade'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                Database upgraded successfully! Advanced category features are now available.
            </div>
        <?php endif; ?>

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

        <!-- Category Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_categories']; ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
            </div>
            <?php if ($has_advanced_features): ?>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['active_categories']; ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bars"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['menu_categories']; ?></div>
                    <div class="stat-label">In Menu</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['main_categories']; ?></div>
                    <div class="stat-label">Main Categories</div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-9">
                <div class="card stat-card bg-light">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="stat-value text-warning">Basic Mode</div>
                    <div class="stat-label">Upgrade database for advanced features</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filters and Bulk Actions -->
        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search Categories</label>
                    <input type="text" class="form-control" id="searchCategories" placeholder="Search by name...">
                </div>
                <?php if ($has_advanced_features): ?>
                <div class="col-md-3">
                    <label class="form-label">Status Filter</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Menu Filter</label>
                    <select class="form-select" id="menuFilter">
                        <option value="">All Categories</option>
                        <option value="in_menu">In Menu</option>
                        <option value="not_in_menu">Not In Menu</option>
                    </select>
                </div>
                <?php else: ?>
                <div class="col-md-6">
                    <div class="alert alert-info py-2 mb-0">
                        <small><i class="fas fa-info-circle me-1"></i>Advanced filtering requires database upgrade</small>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Actions</label>
                    <div>
                        <a href="categorymanagement.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-times me-1"></i>Clear Filters</a>
                        <span class="text-muted d-block mt-1 text-center">
                            <?php echo $stats['total_categories']; ?> categories total
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <?php if ($has_advanced_features): ?>
        <div class="bulk-actions">
            <form method="POST" id="bulkActionForm">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <select class="form-select" name="bulk_action" id="bulkActionSelect">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="show_in_menu">Show in Menu</option>
                            <option value="hide_from_menu">Hide from Menu</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" id="applyBulkAction">Apply</button>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllCategories">
                            <label class="form-check-label" for="selectAllCategories">
                                Select All Categories
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <?php if ($has_advanced_features): ?>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAllHeader">
                                </th>
                                <?php else: ?>
                                <th>#</th>
                                <?php endif; ?>
                                <th>Category</th>
                                <th>Products</th>
                                <?php if ($has_advanced_features): ?>
                                <th>Subcategories</th>
                                <th>Status</th>
                                <th>Menu</th>
                                <th>Sort Order</th>
                                <?php endif; ?>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                <?php 
                                $row_count = 0;
                                if ($has_advanced_features) {
                                    function displayCategoryTree($categories, $level = 0) {
                                        foreach ($categories as $category_id => $category) {
                                            $is_subcategory = $level > 0;
                                            $statusColor = $category['is_active'] ? 'bg-success' : 'bg-danger';
                                            $statusText = $category['is_active'] ? 'Active' : 'Inactive';
                                            $menuColor = $category['show_in_menu'] ? 'bg-info' : 'bg-secondary';
                                            $menuText = $category['show_in_menu'] ? 'Visible' : 'Hidden';
                                            ?>
                                            <tr class="<?php echo $is_subcategory ? 'subcategory-row' : ''; ?>">
                                                <td>
                                                    <input type="checkbox" class="category-checkbox" name="selected_categories[]" value="<?php echo $category['category_id']; ?>">
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($is_subcategory): ?>
                                                            <span class="subcategory-indicator">
                                                                <i class="fas fa-level-down-alt fa-rotate-90"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                        <div class="category-image me-3">
                                                            <?php if ($category['image_path']): ?>
                                                                <img src="../<?php echo htmlspecialchars($category['image_path']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 100%; height: 100%; border-radius: 8px;">
                                                            <?php else: ?>
                                                                <i class="fas fa-tag"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                            <?php if ($is_subcategory && $category['parent_name']): ?>
                                                                <br><small class="text-muted">Parent: <?php echo htmlspecialchars($category['parent_name']); ?></small>
                                                            <?php endif; ?>
                                                            <?php if ($category['description']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class='badge bg-primary status-badge'>
                                                        <i class="fas fa-box me-1"></i><?php echo $category['product_count']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class='badge bg-secondary status-badge'>
                                                        <i class="fas fa-sitemap me-1"></i><?php echo $category['subcategory_count']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class='badge <?php echo $statusColor; ?> status-badge'>
                                                        <i class="fas fa-circle me-1"></i><?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class='badge <?php echo $menuColor; ?> status-badge'>
                                                        <i class="fas fa-eye<?php echo $category['show_in_menu'] ? '' : '-slash'; ?> me-1"></i><?php echo $menuText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo $category['sort_order']; ?></span>
                                                </td>
                                                <td>
                                                    <small><i class="fas fa-calendar-alt me-1"></i><?php echo date('M j, Y', strtotime($category['date_created'] ?? '2023-01-01')); ?></small>
                                                </td>
                                                <td class="action-buttons">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?edit=<?php echo $category['category_id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit Category">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')" data-bs-toggle="tooltip" title="Delete Category">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                            if (isset($category['children']) && !empty($category['children'])) {
                                                displayCategoryTree($category['children'], $level + 1);
                                            }
                                        }
                                    }
                                    
                                    displayCategoryTree($categories_tree);
                                } else {
                                    while($category = $categories_result->fetch_assoc()): 
                                        $row_count++;
                                        ?>
                                        <tr>
                                            <td><?php echo $row_count; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="category-image me-3">
                                                        <i class="fas fa-tag"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                        <?php if ($category['description']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class='badge bg-primary status-badge'>
                                                    <i class="fas fa-box me-1"></i><?php echo $category['product_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><i class="fas fa-calendar-alt me-1"></i><?php echo date('M j, Y', strtotime($category['date_created'] ?? '2023-01-01')); ?></small>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?edit=<?php echo $category['category_id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit Category">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')" data-bs-toggle="tooltip" title="Delete Category">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                }
                                ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $has_advanced_features ? '9' : '5'; ?>" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-tags fa-3x mb-3"></i>
                                            <h5>No categories found</h5>
                                            <p>Get started by adding your first category.</p>
                                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add First Category</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal">
        <div class="modal-dialog <?php echo $has_advanced_features ? 'modal-lg' : ''; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_category">
                    <div class="modal-body">
                        <div class="row">
                            <div class="<?php echo $has_advanced_features ? 'col-md-6' : 'col-12'; ?> mb-3">
                                <label class="form-label">Category Name *</label>
                                <input type="text" class="form-control" name="name" required maxlength="100">
                            </div>
                            <?php if ($has_advanced_features): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent Category</label>
                                <select class="form-select" name="parent_id">
                                    <option value="">No Parent (Main Category)</option>
                                    <?php 
                                    function categoryOptions($categories, $level = 0, $exclude_id = null) {
                                        foreach ($categories as $category_id => $category) {
                                            if ($category_id === $exclude_id) continue;
                                            $prefix = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                                            echo '<option value="' . $category_id . '">' . $prefix . $category['name'] . '</option>';
                                            if (isset($category['children']) && !empty($category['children'])) {
                                                categoryOptions($category['children'], $level + 1, $exclude_id);
                                            }
                                        }
                                    }
                                    if ($has_advanced_features) {
                                        categoryOptions($categories_tree);
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" maxlength="500"></textarea>
                            <div class="form-text">Optional category description (max 500 characters)</div>
                        </div>
                        <?php if ($has_advanced_features): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category Image</label>
                                <input type="file" class="form-control" name="category_image" accept="image/*">
                                <div class="form-text">Recommended: 300x300px, JPG/PNG/WEBP</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="0" min="0">
                                <div class="form-text">Lower numbers display first</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Settings</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="add_is_active" checked>
                                    <label class="form-check-label" for="add_is_active">Active Category</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="show_in_menu" id="add_show_in_menu" checked>
                                    <label class="form-check-label" for="add_show_in_menu">Show in Menu</label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <?php if ($edit_category): ?>
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog <?php echo $has_advanced_features ? 'modal-lg' : ''; ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category: <?php echo htmlspecialchars($edit_category['name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="<?php echo $has_advanced_features ? 'col-md-6' : 'col-12'; ?> mb-3">
                                <label class="form-label">Category Name *</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_category['name']); ?>" required maxlength="100">
                            </div>
                            <?php if ($has_advanced_features): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent Category</label>
                                <select class="form-select" name="parent_id">
                                    <option value="">No Parent (Main Category)</option>
                                    <?php 
                                    if ($has_advanced_features) {
                                        categoryOptions($categories_tree, 0, $edit_category['category_id']);
                                    }
                                    ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" maxlength="500"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                            <div class="form-text">Optional category description (max 500 characters)</div>
                        </div>
                        <?php if ($has_advanced_features): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category Image</label>
                                <?php if ($edit_category['image_path']): ?>
                                    <div class="mb-2">
                                        <img src="../<?php echo htmlspecialchars($edit_category['image_path']); ?>" alt="Current image" style="max-width: 100px; border-radius: 8px;">
                                        <div class="form-text">Current image</div>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="category_image" accept="image/*">
                                <div class="form-text">Leave empty to keep current image</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="<?php echo $edit_category['sort_order'] ?? 0; ?>" min="0">
                                <div class="form-text">Lower numbers display first</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Settings</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="edit_is_active" <?php echo ($edit_category['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_is_active">Active Category</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="show_in_menu" id="edit_show_in_menu" <?php echo ($edit_category['show_in_menu'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_show_in_menu">Show in Menu</label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_category">
        <input type="hidden" name="category_id" id="delete_category_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(categoryId, categoryName) {
            if (confirm(`Are you sure you want to delete category "${categoryName}"?\n\nThis action cannot be undone!`)) {
                document.getElementById('delete_category_id').value = categoryId;
                document.getElementById('deleteForm').submit();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($edit_category): ?>
                var editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                editModal.show();
            <?php endif; ?>
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            <?php if ($has_advanced_features): ?>
            // Select all functionality
            document.getElementById('selectAllHeader').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                document.getElementById('selectAllCategories').checked = this.checked;
            });
            
            document.getElementById('selectAllCategories').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                document.getElementById('selectAllHeader').checked = this.checked;
            });
            
            // Bulk action functionality
            document.getElementById('applyBulkAction').addEventListener('click', function() {
                const selectedAction = document.getElementById('bulkActionSelect').value;
                const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked'))
                    .map(checkbox => checkbox.value);
                
                if (!selectedAction) {
                    alert('Please select a bulk action.');
                    return;
                }
                
                if (selectedCategories.length === 0) {
                    alert('Please select at least one category.');
                    return;
                }
                
                if (selectedAction === 'delete') {
                    if (!confirm(`Are you sure you want to delete ${selectedCategories.length} category/categories? This action cannot be undone!`)) {
                        return;
                    }
                }
                
                const form = document.getElementById('bulkActionForm');
                const hiddenInputs = selectedCategories.map(categoryId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_categories[]';
                    input.value = categoryId;
                    return input;
                });
                
                hiddenInputs.forEach(input => form.appendChild(input));
                form.submit();
            });
            
            // Status filter functionality
            document.getElementById('statusFilter').addEventListener('change', function(e) {
                const status = e.target.value;
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    if (!status) {
                        row.style.display = '';
                        return;
                    }
                    
                    const statusBadges = row.querySelectorAll('.badge');
                    let statusText = '';
                    statusBadges.forEach(badge => {
                        if (badge.textContent.includes('Active') || badge.textContent.includes('Inactive')) {
                            statusText = badge.textContent.toLowerCase();
                        }
                    });
                    
                    row.style.display = statusText.includes(status) ? '' : 'none';
                });
            });
            
            // Menu filter functionality
            document.getElementById('menuFilter').addEventListener('change', function(e) {
                const menuStatus = e.target.value;
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    if (!menuStatus) {
                        row.style.display = '';
                        return;
                    }
                    
                    const menuBadges = row.querySelectorAll('.badge');
                    let menuText = '';
                    menuBadges.forEach(badge => {
                        if (badge.textContent.includes('Visible') || badge.textContent.includes('Hidden')) {
                            menuText = badge.textContent.toLowerCase();
                        }
                    });
                    
                    if (menuStatus === 'in_menu' && menuText.includes('visible')) {
                        row.style.display = '';
                    } else if (menuStatus === 'not_in_menu' && menuText.includes('hidden')) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            <?php endif; ?>
            
            // Simple search functionality
            document.getElementById('searchCategories').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>