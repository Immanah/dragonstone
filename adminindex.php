<?php
session_start();

// Proper session authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlogin.php');
    exit();
}

// Get user info from session
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$admin_permissions = $_SESSION['admin_permissions'] ?? [];

// Function to check permissions
function hasPermission($permission) {
    global $admin_permissions;
    return in_array('all', $admin_permissions) || in_array($permission, $admin_permissions);
}

// Use the existing project database configuration
require_once '../includes/config.php';
require_once '../includes/database.php';

// Initialize database connection
$conn = getDatabaseConnection();

// Get real statistics from your actual database
function getDashboardStats($conn) {
    $stats = [
        'total_users' => 0,
        'total_products' => 0,
        'total_sales' => 0,
        'total_co2' => 0,
        'total_orders' => 0,
        'active_users' => 0
    ];
    
    if (!$conn) {
        // Fallback values if no connection
        return [
            'total_users' => 0,
            'total_products' => 0,
            'total_sales' => 0,
            'total_co2' => 0,
            'total_orders' => 0,
            'active_users' => 0
        ];
    }
    
    try {
        // Total Users - from your users table
        $result = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_users'] = $row['total_users'] ?? 0;
        }
        
        // Total Products - from your products table
        $result = $conn->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_products'] = $row['total_products'] ?? 0;
        }
        
        // Total Sales - from your orders table
        $result = $conn->query("SELECT SUM(total_amount) as total_sales, COUNT(*) as total_orders FROM orders WHERE status IN ('Paid', 'Processing', 'Shipped', 'Delivered')");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_sales'] = $row['total_sales'] ?? 0;
            $stats['total_orders'] = $row['total_orders'] ?? 0;
        }
        
        // Total CO2 Saved - from order_items joined with products
        $result = $conn->query("
            SELECT SUM(oi.quantity * p.co2_saved) as total_co2 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.product_id 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE o.status IN ('Paid', 'Processing', 'Shipped', 'Delivered')
        ");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_co2'] = $row['total_co2'] ?? 0;
        }
        
        // Active users (logged in last 30 days)
        $result = $conn->query("SELECT COUNT(*) as active_users FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_active = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['active_users'] = $row['active_users'] ?? 0;
        }
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
    }
    
    return $stats;
}

// Get recent activity from your actual database
function getRecentActivity($conn) {
    $activities = [];
    
    if (!$conn) {
        return $activities;
    }
    
    try {
        // Get recent user registrations
        $result = $conn->query("
            SELECT user_id, email, first_name, last_name, date_created 
            FROM users 
            ORDER BY date_created DESC 
            LIMIT 5
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'type' => 'user_registered',
                    'username' => $row['first_name'] . ' ' . $row['last_name'],
                    'email' => $row['email'],
                    'created_at' => $row['date_created']
                ];
            }
        }
        
        // If no recent registrations, get recent orders
        if (empty($activities)) {
            $result = $conn->query("
                SELECT o.order_id, u.first_name, u.last_name, o.total_amount, o.order_date 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.user_id 
                ORDER BY o.order_date DESC 
                LIMIT 5
            ");
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = [
                        'type' => 'new_order',
                        'username' => $row['first_name'] . ' ' . $row['last_name'],
                        'order_id' => $row['order_id'],
                        'amount' => $row['total_amount'],
                        'created_at' => $row['order_date']
                    ];
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Recent activity error: " . $e->getMessage());
    }
    
    return $activities;
}

// Get low stock products
function getLowStockProducts($conn) {
    $low_stock = [];
    
    if (!$conn) {
        return $low_stock;
    }
    
    try {
        $result = $conn->query("
            SELECT product_id, name, stock_quantity, low_stock_threshold 
            FROM products 
            WHERE stock_quantity <= low_stock_threshold 
            AND is_active = 1 
            ORDER BY stock_quantity ASC 
            LIMIT 5
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $low_stock[] = $row;
            }
        }
        
    } catch (Exception $e) {
        error_log("Low stock products error: " . $e->getMessage());
    }
    
    return $low_stock;
}

$stats = getDashboardStats($conn);
$recent_activities = getRecentActivity($conn);
$low_stock_products = getLowStockProducts($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DragonStone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2d5016;
            --secondary-color: #4a7c2a;
            --accent-color: #6b8e23;
            --light-bg: #f8f9fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-nav .nav-link {
            color: #6c757d;
            border-radius: var(--border-radius);
            margin: 0.1rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .admin-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: var(--box-shadow);
        }

        .admin-nav .nav-link:not(.active):hover {
            background-color: #e9ecef;
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .permission-denied {
            opacity: 0.6;
            pointer-events: none;
        }

        .role-badge {
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .stat-card:hover {
            background-color: var(--light-bg);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .quick-action-btn {
            transition: var(--transition);
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
        }

        .low-stock-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .low-stock-item:last-child {
            border-bottom: none;
        }

        .stock-warning {
            color: #dc3545;
            font-weight: 600;
        }

        .db-connected {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-dragon me-2"></i>DragonStone Admin
                <small class="d-block opacity-75">
                    Logged in as: <?php echo htmlspecialchars($admin_name); ?>
                    <span class="badge bg-success role-badge"><?php echo ucfirst($admin_role); ?></span>
                </small>
            </span>
            <div class="navbar-nav">
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2">View Main Site</a>
                <a href="adminlogin.php?logout=true" class="btn btn-outline-warning btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Admin Navigation -->
    <div class="bg-light border-bottom">
        <div class="container">
            <div class="nav admin-nav nav-pills py-2">
                <a class="nav-link active" href="adminindex.php">Dashboard</a>
                
                <?php if (hasPermission('users') || hasPermission('all')): ?>
                    <a class="nav-link" href="usermanagement.php">User Management</a>
                <?php else: ?>
                    <a class="nav-link permission-denied" href="#" title="Permission Denied">User Management</a>
                <?php endif; ?>
                
                <?php if (hasPermission('products') || hasPermission('all') || hasPermission('content')): ?>
                    <a class="nav-link" href="productmanagement.php">Product Management</a>
                <?php else: ?>
                    <a class="nav-link permission-denied" href="#" title="Permission Denied">Product Management</a>
                <?php endif; ?>
                
                <?php if (hasPermission('products') || hasPermission('all') || hasPermission('content')): ?>
                    <a class="nav-link" href="categorymanagement.php">Category Management</a>
                <?php else: ?>
                    <a class="nav-link permission-denied" href="#" title="Permission Denied">Category Management</a>
                <?php endif; ?>
                
                <?php if (hasPermission('reports') || hasPermission('all') || hasPermission('reports_view')): ?>
                    <a class="nav-link" href="reports.php">Reports</a>
                <?php else: ?>
                    <a class="nav-link permission-denied" href="#" title="Permission Denied">Reports</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container py-4">
        <?php if ($conn): ?>
            <div class="alert alert-success db-connected mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-database me-3 fs-4"></i>
                    <div>
                        <h5 class="alert-heading mb-1">✅ Database Connected</h5>
                        <p class="mb-0">Showing real data from your DragonStone database</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-dark">Admin Dashboard</h1>
            <div class="text-muted">
                Role: <strong class="text-primary"><?php echo ucfirst($admin_role); ?></strong>
                | Permissions: 
                <?php 
                $permission_count = count($admin_permissions);
                if (in_array('all', $admin_permissions)) {
                    echo '<strong class="text-success">Full Access</strong>';
                } else {
                    echo "<strong class='text-info'>{$permission_count} permissions</strong>";
                }
                ?>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card" data-bs-toggle="modal" data-bs-target="#usersModal">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="stat-value"><?php echo number_format($stats['total_users']); ?></h3>
                        <p class="stat-label">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" data-bs-toggle="modal" data-bs-target="#productsModal">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3 class="stat-value"><?php echo number_format($stats['total_products']); ?></h3>
                        <p class="stat-label">Total Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" data-bs-toggle="modal" data-bs-target="#salesModal">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="stat-value">R<?php echo number_format($stats['total_sales'], 2); ?></h3>
                        <p class="stat-label">Total Sales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card" data-bs-toggle="modal" data-bs-target="#co2Modal">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3 class="stat-value"><?php echo number_format($stats['total_co2'], 2); ?>kg</h3>
                        <p class="stat-label">CO2 Saved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark fw-bold">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (hasPermission('users') || hasPermission('all')): ?>
                                <a href="usermanagement.php" class="btn btn-outline-primary quick-action-btn py-2">
                                    <i class="fas fa-user-cog me-2"></i>Manage Users
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary py-2" disabled>
                                    <i class="fas fa-user-cog me-2"></i>Manage Users
                                </button>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('products') || hasPermission('all') || hasPermission('content')): ?>
                                <a href="productmanagement.php" class="btn btn-outline-success quick-action-btn py-2">
                                    <i class="fas fa-plus-circle me-2"></i>Add New Product
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary py-2" disabled>
                                    <i class="fas fa-plus-circle me-2"></i>Add New Product
                                </button>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('reports') || hasPermission('all') || hasPermission('reports_view')): ?>
                                <a href="reports.php" class="btn btn-outline-info quick-action-btn py-2">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary py-2" disabled>
                                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark fw-bold">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php if ($activity['type'] === 'user_registered'): ?>
                                            <i class="fas fa-user-plus"></i>
                                        <?php else: ?>
                                            <i class="fas fa-shopping-cart"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($activity['type'] === 'user_registered'): ?>
                                            <strong>New user registered</strong> - <?php echo htmlspecialchars($activity['username']); ?>
                                        <?php else: ?>
                                            <strong>New order #<?php echo $activity['order_id']; ?></strong> - R<?php echo number_format($activity['amount'], 2); ?>
                                        <?php endif; ?>
                                        <small class="d-block text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-info-circle me-2"></i>No recent activity
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark fw-bold">Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($low_stock_products)): ?>
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="low-stock-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-medium"><?php echo htmlspecialchars($product['name']); ?></span>
                                        <span class="stock-warning">
                                            <?php echo $product['stock_quantity']; ?> left
                                        </span>
                                    </div>
                                    <small class="text-muted">Threshold: <?php echo $product['low_stock_threshold']; ?></small>
                                </div>
                            <?php endforeach; ?>
                            <div class="mt-3">
                                <a href="productmanagement.php" class="btn btn-sm btn-warning w-100">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Manage Stock
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle me-2"></i>All products are well stocked
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Information -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark fw-bold">Your Access Level</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Permissions Granted:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($admin_permissions as $permission): ?>
                                        <?php if ($permission === 'all'): ?>
                                            <span class="badge bg-success p-2">
                                                <i class="fas fa-shield-alt me-1"></i>Full System Access
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary p-2">
                                                <i class="fas fa-check-circle me-1"></i><?php echo ucfirst(str_replace('_', ' ', $permission)); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Role Description:</h6>
                                <p class="mb-3">
                                    <?php
                                    $role_descriptions = [
                                        'superadmin' => 'You have full system access including user management, content management, reports, and system settings.',
                                        'admin' => 'You can manage users, products, orders, and view all reports.',
                                        'manager' => 'You can manage products and content, and view reports.',
                                        'reports' => 'You have read-only access to the dashboard and reports.'
                                    ];
                                    echo $role_descriptions[$admin_role] ?? 'Standard administrative access.';
                                    ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    If you need additional permissions, contact a Super Administrator.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Users -->
    <div class="modal fade" id="usersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Users Overview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <h3 class="text-primary"><?php echo number_format($stats['total_users']); ?></h3>
                            <p class="text-muted">Total Users</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success"><?php echo number_format($stats['active_users']); ?></h3>
                            <p class="text-muted">Active (30 days)</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-info"><?php echo number_format($stats['total_users'] - $stats['active_users']); ?></h3>
                            <p class="text-muted">Inactive Users</p>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="usermanagement.php" class="btn btn-primary">View Full User Management</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Products -->
    <div class="modal fade" id="productsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-box-open me-2"></i>Products Overview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <h3 class="text-primary"><?php echo number_format($stats['total_products']); ?></h3>
                            <p class="text-muted">Total Products</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success"><?php echo number_format(count($low_stock_products)); ?></h3>
                            <p class="text-muted">Low Stock Items</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-info"><?php echo number_format($stats['total_products'] - count($low_stock_products)); ?></h3>
                            <p class="text-muted">Well Stocked</p>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="productmanagement.php" class="btn btn-primary">View Full Product Management</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Sales -->
    <div class="modal fade" id="salesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chart-line me-2"></i>Sales Overview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <h3 class="text-primary">R<?php echo number_format($stats['total_sales'], 2); ?></h3>
                            <p class="text-muted">Total Sales</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success"><?php echo number_format($stats['total_orders']); ?></h3>
                            <p class="text-muted">Total Orders</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-info">R<?php echo number_format($stats['total_orders'] > 0 ? $stats['total_sales'] / $stats['total_orders'] : 0, 2); ?></h3>
                            <p class="text-muted">Average Order</p>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="reports.php" class="btn btn-primary">View Full Sales Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for CO2 -->
    <div class="modal fade" id="co2Modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-leaf me-2"></i>Environmental Impact</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <h3 class="text-primary"><?php echo number_format($stats['total_co2'], 2); ?>kg</h3>
                            <p class="text-muted">CO2 Saved</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success"><?php echo number_format($stats['total_co2'] / 22, 1); ?></h3>
                            <p class="text-muted">Equivalent Trees</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-info"><?php echo number_format($stats['total_co2'] * 2.5, 2); ?>kg</h3>
                            <p class="text-muted">Plastic Reduced</p>
                        </div>
                    </div>
                    <div class="alert alert-success">
                        <i class="fas fa-trophy me-2"></i>
                        Your eco-friendly products have made a significant environmental impact!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
            });
            
            // Animate cards on load
            setTimeout(() => {
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 100);
        });
    </script>
</body>
</html>