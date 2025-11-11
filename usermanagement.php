<?php
session_start();

// Proper session authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlogin.php');
    exit();
}

// Check if user has permission for user management
$admin_permissions = $_SESSION['admin_permissions'] ?? [];
if (!in_array('all', $admin_permissions) && !in_array('users', $admin_permissions)) {
    header('Location: adminindex.php');
    exit();
}

// Database connection
include '../includes/database.php';
$conn = getDatabaseConnection();

// Check if user_activity table exists, if not create it
$check_activity = $conn->query("SHOW TABLES LIKE 'user_activity'");
if ($check_activity->num_rows == 0) {
    $create_activity_sql = "
        CREATE TABLE user_activity (
            activity_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            activity_details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ";
    $conn->query($create_activity_sql);
    
    // Add some sample activity data for existing users
    $users_sql = "SELECT user_id FROM users";
    $users_result = $conn->query($users_sql);
    if ($users_result->num_rows > 0) {
        while($user = $users_result->fetch_assoc()) {
            $sample_activities = [
                ['account_created', 'User account was created'],
                ['login', 'User logged into the system'],
                ['profile_updated', 'User updated their profile information'],
                ['password_changed', 'User changed their password'],
                ['product_viewed', 'User viewed product details']
            ];
            
            foreach ($sample_activities as $activity) {
                $activity_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details, ip_address) 
                                VALUES (?, ?, ?, '192.168.1.1')";
                $stmt = $conn->prepare($activity_sql);
                $stmt->bind_param("iss", $user['user_id'], $activity[0], $activity[1]);
                $stmt->execute();
            }
        }
    }
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle add user
    if (isset($_POST['action']) && $_POST['action'] == 'add_user') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $user_role = $_POST['role'];
        $password = $_POST['password'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } else {
            // Check if email already exists
            $check_sql = "SELECT user_id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Email already exists!";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (first_name, last_name, email, role, password_hash, is_active, date_created) 
                        VALUES (?, ?, ?, ?, ?, 1, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $first_name, $last_name, $email, $user_role, $password_hash);
                
                if ($stmt->execute()) {
                    $success = "User added successfully!";
                    
                    // Log the activity
                    $user_id = $stmt->insert_id;
                    $activity_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details, ip_address, user_agent) 
                                    VALUES (?, 'account_created', 'Account created by admin', ?, ?)";
                    $activity_stmt = $conn->prepare($activity_sql);
                    $activity_stmt->bind_param("iss", $user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                    $activity_stmt->execute();
                } else {
                    $error = "Error adding user: " . $conn->error;
                }
            }
        }
    }
    
    // Handle edit user
    if (isset($_POST['action']) && $_POST['action'] == 'edit_user') {
        $user_id = intval($_POST['user_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $user_role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, is_active = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $first_name, $last_name, $email, $user_role, $is_active, $user_id);
        
        if ($stmt->execute()) {
            $success = "User updated successfully!";
            
            // Log the activity
            $activity_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details, ip_address, user_agent) 
                            VALUES (?, 'profile_updated', 'Profile updated by admin', ?, ?)";
            $activity_stmt = $conn->prepare($activity_sql);
            $activity_stmt->bind_param("iss", $user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            $activity_stmt->execute();
        } else {
            $error = "Error updating user: " . $conn->error;
        }
    }
    
    // Handle delete user - FIXED VERSION WITH TABLE EXISTENCE CHECK
    if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
        $user_id = intval($_POST['user_id']);
        
        // First, check if this is the last admin
        $check_admin_sql = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'Admin' AND user_id != ?";
        $check_stmt = $conn->prepare($check_admin_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['admin_count'] == 0) {
            $error = "Cannot delete the last administrator!";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Disable foreign key checks temporarily to avoid constraint errors
                $conn->query("SET FOREIGN_KEY_CHECKS = 0");
                
                // List of all possible related tables - only delete from those that exist
                $possible_tables = [
                    'eco_point_transactions' => 'user_id',
                    'user_activity' => 'user_id',
                    'event_participants' => 'user_id',
                    'user_sessions' => 'user_id',
                    'user_registration_log' => 'user_id', 
                    'user_login_activity' => 'user_id',
                    'user_activity_log' => 'user_id',
                    'cart' => 'user_id',
                    'reviews' => 'user_id',
                    'subscriptions' => 'user_id',
                    'reward_redemptions' => 'user_id',
                    'forum_posts' => 'user_id',
                    'forum_replies' => 'user_id',
                    'user_addresses' => 'user_id',
                    'user_locations' => 'user_id'
                ];
                
                // Check which tables actually exist
                $existing_tables = [];
                $tables_result = $conn->query("SHOW TABLES");
                $all_tables = [];
                while ($table_row = $tables_result->fetch_array()) {
                    $all_tables[] = $table_row[0];
                }
                
                foreach ($possible_tables as $table => $column) {
                    if (in_array($table, $all_tables)) {
                        $existing_tables[$table] = $column;
                    }
                }
                
                // Delete from existing tables only
                foreach ($existing_tables as $table => $column) {
                    $delete_sql = "DELETE FROM $table WHERE $column = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    if ($delete_stmt) {
                        $delete_stmt->bind_param("i", $user_id);
                        $delete_stmt->execute();
                        $delete_stmt->close();
                    }
                }
                
                // For orders table, set user_id to NULL instead of deleting (to preserve order history)
                if (in_array('orders', $all_tables)) {
                    $update_orders_sql = "UPDATE orders SET user_id = NULL WHERE user_id = ?";
                    $update_orders_stmt = $conn->prepare($update_orders_sql);
                    if ($update_orders_stmt) {
                        $update_orders_stmt->bind_param("i", $user_id);
                        $update_orders_stmt->execute();
                        $update_orders_stmt->close();
                    }
                }
                
                // Now delete the user
                $delete_user_sql = "DELETE FROM users WHERE user_id = ?";
                $delete_user_stmt = $conn->prepare($delete_user_sql);
                $delete_user_stmt->bind_param("i", $user_id);
                
                if ($delete_user_stmt->execute() && $delete_user_stmt->affected_rows > 0) {
                    $conn->commit();
                    $success = "User and all related data deleted successfully!";
                    
                    // Log admin activity
                    $admin_id = $_SESSION['admin_id'] ?? 0;
                    if (in_array('admin_activity', $all_tables)) {
                        $activity_sql = "INSERT INTO admin_activity (admin_id, activity_type, activity_details, ip_address, user_agent) 
                                        VALUES (?, 'user_deleted', 'Deleted user ID: $user_id', ?, ?)";
                        $activity_stmt = $conn->prepare($activity_sql);
                        $activity_stmt->bind_param("iss", $admin_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                        $activity_stmt->execute();
                    }
                } else {
                    throw new Exception("Error deleting user or user not found");
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error deleting user: " . $e->getMessage();
            } finally {
                // Always re-enable foreign key checks
                $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            }
        }
    }
}

// Get all users with activity data
try {
    // First check if user_activity table exists
    $check_activity_exists = $conn->query("SHOW TABLES LIKE 'user_activity'");
    $activity_table_exists = $check_activity_exists->num_rows > 0;
    
    // First check if user_sessions table exists
    $check_sessions_exists = $conn->query("SHOW TABLES LIKE 'user_sessions'");
    $sessions_table_exists = $check_sessions_exists->num_rows > 0;
    
    if ($activity_table_exists && $sessions_table_exists) {
        $users_sql = "SELECT u.*, 
                             COUNT(ua.activity_id) as total_activities,
                             MAX(ua.activity_date) as last_activity,
                             (SELECT SUM(TIMESTAMPDIFF(SECOND, login_time, COALESCE(logout_time, NOW()))) 
                              FROM user_sessions WHERE user_id = u.user_id) as total_session_seconds
                      FROM users u 
                      LEFT JOIN user_activity ua ON u.user_id = ua.user_id 
                      GROUP BY u.user_id 
                      ORDER BY u.user_id DESC";
    } elseif ($activity_table_exists) {
        $users_sql = "SELECT u.*, 
                             COUNT(ua.activity_id) as total_activities,
                             MAX(ua.activity_date) as last_activity,
                             0 as total_session_seconds
                      FROM users u 
                      LEFT JOIN user_activity ua ON u.user_id = ua.user_id 
                      GROUP BY u.user_id 
                      ORDER BY u.user_id DESC";
    } else {
        $users_sql = "SELECT u.*, 
                             0 as total_activities,
                             NULL as last_activity,
                             0 as total_session_seconds
                      FROM users u 
                      ORDER BY u.user_id DESC";
    }
    
    $users_result = $conn->query($users_sql);
    
    if (!$users_result) {
        throw new Exception("Error fetching users: " . $conn->error);
    }
    
} catch (Exception $e) {
    // Fallback to basic user query
    $users_sql = "SELECT u.*, 
                         0 as total_activities,
                         NULL as last_activity,
                         0 as total_session_seconds
                  FROM users u 
                  ORDER BY u.user_id DESC";
    $users_result = $conn->query($users_sql);
}

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM users WHERE user_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_user = $edit_result->fetch_assoc();
}

// Get user activity logs
$activity_logs = [];
$activity_user = null;
if (isset($_GET['view_activity'])) {
    $user_id = intval($_GET['view_activity']);
    
    // Get user details for the modal title
    $user_sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $activity_user = $user_result->fetch_assoc();
    
    // Check if user_activity table exists before trying to query it
    $check_activity = $conn->query("SHOW TABLES LIKE 'user_activity'");
    if ($check_activity->num_rows > 0) {
        // Get activity logs
        $activity_sql = "SELECT * FROM user_activity WHERE user_id = ? ORDER BY activity_date DESC LIMIT 50";
        $activity_stmt = $conn->prepare($activity_sql);
        $activity_stmt->bind_param("i", $user_id);
        $activity_stmt->execute();
        $activity_result = $activity_stmt->get_result();
        $activity_logs = $activity_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Check if we need to auto-show modals
$show_edit_modal = isset($_GET['edit']);
$show_activity_modal = isset($_GET['view_activity']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - DragonStone</title>
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
        
        .session-time {
            font-size: 0.8rem;
            color: #6c757d;
            line-height: 1.3;
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
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .action-buttons .btn {
            margin-bottom: 0.25rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .activity-badge {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }
        
        .no-activity {
            color: #6c757d;
            font-style: italic;
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
                <small class="d-block opacity-75">User Management</small>
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
                <a class="nav-link active" href="usermanagement.php"><i class="fas fa-users me-1"></i>User Management</a>
                <a class="nav-link" href="productmanagement.php"><i class="fas fa-box me-1"></i>Product Management</a>
                <a class="nav-link" href="categorymanagement.php"><i class="fas fa-tags me-1"></i>Category Management</a>
                <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i>Reports</a>
            </div>
        </div>
    </div>

    <!-- User Management Content -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users me-2"></i>User Management</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus me-1"></i>Add New User</button>
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

        <!-- User Stats and Filters -->
        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Role Filter</label>
                    <select class="form-select" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="Admin">Administrator</option>
                        <option value="ContentManager">Content Manager</option>
                        <option value="ReportViewer">Report Viewer</option>
                        <option value="Customer">Customer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Filter</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search Users</label>
                    <input type="text" class="form-control" id="searchUsers" placeholder="Search by name or email...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Actions</label>
                    <div>
                        <a href="usermanagement.php" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-times me-1"></i>Clear Filters</a>
                        <span class="text-muted d-block mt-1 text-center">
                            <?php echo $users_result->num_rows; ?> users total
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>EcoPoints</th>
                                <th>Activity</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users_result->num_rows > 0): ?>
                                <?php while($user = $users_result->fetch_assoc()): ?>
                                    <?php
                                    $roleColors = [
                                        'Admin' => 'bg-danger',
                                        'ContentManager' => 'bg-warning',
                                        'ReportViewer' => 'bg-info', 
                                        'Customer' => 'bg-secondary'
                                    ];
                                    $statusColor = $user['is_active'] ? 'bg-success' : 'bg-danger';
                                    $statusText = $user['is_active'] ? 'Active' : 'Inactive';
                                    $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                                    
                                    // Calculate session time with better formatting
                                    $total_hours = floor($user['total_session_seconds'] / 3600);
                                    $total_minutes = floor(($user['total_session_seconds'] % 3600) / 60);
                                    $session_time = $total_hours > 0 ? "{$total_hours}h {$total_minutes}m" : "{$total_minutes}m";
                                    
                                    // Format activity data
                                    $activity_count = $user['total_activities'] ?? 0;
                                    $last_activity = $user['last_activity'] ? date('M j, H:i', strtotime($user['last_activity'])) : 'Never';
                                    ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?php echo $initials; ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                    <br><small class="text-muted">ID: <?php echo $user['user_id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class='badge <?php echo $roleColors[$user['role']] ?? 'bg-primary'; ?> status-badge'>
                                                <i class="fas fa-user-shield me-1"></i><?php echo $user['role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class='badge bg-success status-badge'>
                                                <i class="fas fa-leaf me-1"></i><?php echo number_format($user['eco_points_balance'] ?? 0); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="session-time">
                                                <?php if ($activity_count > 0): ?>
                                                    <i class="fas fa-chart-line me-1"></i><?php echo $activity_count; ?> activities<br>
                                                    <i class="fas fa-clock me-1"></i><?php echo $session_time; ?> session<br>
                                                    <i class="fas fa-calendar me-1"></i><?php echo $last_activity; ?>
                                                <?php else: ?>
                                                    <span class="no-activity">
                                                        <i class="fas fa-info-circle me-1"></i>No activity recorded
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class='badge <?php echo $statusColor; ?> status-badge'>
                                                <i class="fas fa-circle me-1"></i><?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><i class="fas fa-calendar-alt me-1"></i><?php echo date('M j, Y', strtotime($user['date_created'])); ?></small>
                                        </td>
                                        <td class="action-buttons">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $user['user_id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?view_activity=<?php echo $user['user_id']; ?>" class="btn btn-outline-info" data-bs-toggle="tooltip" title="View Activity">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['first_name'] . ' ' . $user['last_name'])); ?>')" data-bs-toggle="tooltip" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <h5>No users found</h5>
                                            <p>Get started by adding your first user.</p>
                                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">Add First User</button>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Admin">Administrator</option>
                                <option value="ContentManager">Content Manager</option>
                                <option value="ReportViewer">Report Viewer</option>
                                <option value="Customer">Customer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <?php if ($edit_user): ?>
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User: <?php echo htmlspecialchars($edit_user['first_name'] . ' ' . $edit_user['last_name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($edit_user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($edit_user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="Admin" <?php echo $edit_user['role'] == 'Admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="ContentManager" <?php echo $edit_user['role'] == 'ContentManager' ? 'selected' : ''; ?>>Content Manager</option>
                                <option value="ReportViewer" <?php echo $edit_user['role'] == 'ReportViewer' ? 'selected' : ''; ?>>Report Viewer</option>
                                <option value="Customer" <?php echo $edit_user['role'] == 'Customer' ? 'selected' : ''; ?>>Customer</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $edit_user['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active User</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Activity Log Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chart-line me-2"></i>
                        <?php if ($activity_user): ?>
                            Activity Log: <?php echo htmlspecialchars($activity_user['first_name'] . ' ' . $activity_user['last_name']); ?>
                        <?php else: ?>
                            User Activity Log
                        <?php endif; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($activity_logs)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Activity Type</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_logs as $log): ?>
                                        <tr>
                                            <td><small><i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i:s', strtotime($log['activity_date'])); ?></small></td>
                                            <td>
                                                <span class="badge bg-info activity-badge">
                                                    <?php echo ucfirst(str_replace('_', ' ', $log['activity_type'])); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($log['activity_details']); ?></small></td>
                                            <td><small><i class="fas fa-network-wired me-1"></i><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No activity records found for this user.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone and will permanently remove the user account and all related data!`)) {
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Auto-show modals when URL parameters are present
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($show_edit_modal): ?>
                var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            <?php endif; ?>
            
            <?php if ($show_activity_modal): ?>
                var activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
                activityModal.show();
            <?php endif; ?>
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            // Simple search functionality
            document.getElementById('searchUsers').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
            
            // Role filter functionality
            document.getElementById('roleFilter').addEventListener('change', function(e) {
                const role = e.target.value;
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    if (!role) {
                        row.style.display = '';
                        return;
                    }
                    
                    const roleBadge = row.querySelector('.badge');
                    const roleText = roleBadge ? roleBadge.textContent.trim() : '';
                    row.style.display = roleText.includes(role) ? '' : 'none';
                });
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
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>