<?php
session_start();

// If already logged in, redirect immediately
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: adminindex.php');
    exit();
}

// Define admin users with different access levels
$admin_users = [
    'superadmin@dragonstone.com' => [
        'password' => 'SuperAdmin123!',
        'role' => 'superadmin',
        'name' => 'Super Administrator',
        'permissions' => ['all']
    ],
    'admin@dragonstone.com' => [
        'password' => 'Admin123!',
        'role' => 'admin',
        'name' => 'Administrator',
        'permissions' => ['dashboard', 'products', 'users', 'orders', 'reports', 'content']
    ],
    'manager@dragonstone.com' => [
        'password' => 'Manager123!',
        'role' => 'manager',
        'name' => 'Content Manager',
        'permissions' => ['dashboard', 'products', 'content', 'reports_view']
    ],
    'reports@dragonstone.com' => [
        'password' => 'Reports123!',
        'role' => 'reports',
        'name' => 'Report Viewer',
        'permissions' => ['dashboard', 'reports_view']
    ]
];

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $selected_role = trim($_POST['role'] ?? '');
    
    // Check if user exists
    if (isset($admin_users[$username])) {
        $user = $admin_users[$username];
        
        // Verify password and role
        if ($password === $user['password'] && $selected_role === $user['role']) {
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_permissions'] = $user['permissions'];
            
            // Debug output
            error_log("SUCCESS: Logging in user: $username with role: $selected_role");
            
            // Force session write and redirect
            session_write_close();
            header('Location: adminindex.php');
            exit();
        } else {
            $error = "Invalid credentials or role selection!";
            error_log("FAIL: Invalid credentials for: $username");
        }
    } else {
        $error = "User not found!";
        error_log("FAIL: User not found: $username");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DragonStone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-login-bg {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c3a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .permission-badge {
            font-size: 0.7rem;
            margin: 0.1rem;
        }
        .account-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .account-card:hover {
            transform: translateY(-2px);
        }
        .superadmin-card { border-left-color: #dc3545; }
        .admin-card { border-left-color: #fd7e14; }
        .manager-card { border-left-color: #20c997; }
        .reports-card { border-left-color: #6f42c1; }
    </style>
</head>
<body class="admin-login-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <h2 class="text-success">🐉 DragonStone</h2>
                            <p class="text-muted">Admin Portal - Role-Based Access</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                                <br><small>Check the browser console for details</small>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Login Form -->
                            <div class="col-md-6">
                                <h5 class="mb-4">Admin Login</h5>
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Username/Email</label>
                                        <input type="text" class="form-control" name="username" required 
                                               placeholder="Use credentials from legend"
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" name="role" required>
                                            <option value="">Select Role</option>
                                            <option value="superadmin" <?php echo ($_POST['role'] ?? '') === 'superadmin' ? 'selected' : ''; ?>>Super Administrator</option>
                                            <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            <option value="manager" <?php echo ($_POST['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Content Manager</option>
                                            <option value="reports" <?php echo ($_POST['role'] ?? '') === 'reports' ? 'selected' : ''; ?>>Report Viewer</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100 py-2">Login to Admin</button>
                                </form>

                                <div class="text-center mt-3">
                                    <small><a href="../index.php">← Back to Main Site</a></small>
                                </div>

                                <!-- Debug Info -->
                                <div class="mt-3 p-2 bg-light rounded small">
                                    <strong>Debug:</strong><br>
                                    Current file: <?php echo $_SERVER['PHP_SELF']; ?><br>
                                    Target: adminindex.php (same directory)<br>
                                    Session status: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active'; ?>
                                </div>
                            </div>

                            <!-- Test Accounts Legend -->
                            <div class="col-md-6">
                                <h5 class="mb-4">Test Accounts & Permissions</h5>
                                <div class="row g-3">
                                    <!-- Super Admin -->
                                    <div class="col-12">
                                        <div class="card account-card superadmin-card">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-danger">🚀 Super Administrator</h6>
                                                <p class="mb-1"><strong>Email:</strong> superadmin@dragonstone.com</p>
                                                <p class="mb-1"><strong>Password:</strong> SuperAdmin123!</p>
                                                <p class="mb-2"><strong>Role:</strong> superadmin</p>
                                                <div>
                                                    <span class="badge bg-danger permission-badge">Full Access</span>
                                                    <span class="badge bg-danger permission-badge">User Management</span>
                                                    <span class="badge bg-danger permission-badge">System Settings</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Admin -->
                                    <div class="col-12">
                                        <div class="card account-card admin-card">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-warning">👑 Administrator</h6>
                                                <p class="mb-1"><strong>Email:</strong> admin@dragonstone.com</p>
                                                <p class="mb-1"><strong>Password:</strong> Admin123!</p>
                                                <p class="mb-2"><strong>Role:</strong> admin</p>
                                                <div>
                                                    <span class="badge bg-warning permission-badge">Dashboard</span>
                                                    <span class="badge bg-warning permission-badge">Products</span>
                                                    <span class="badge bg-warning permission-badge">Users</span>
                                                    <span class="badge bg-warning permission-badge">Orders</span>
                                                    <span class="badge bg-warning permission-badge">Reports</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Manager -->
                                    <div class="col-12">
                                        <div class="card account-card manager-card">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-success">📝 Content Manager</h6>
                                                <p class="mb-1"><strong>Email:</strong> manager@dragonstone.com</p>
                                                <p class="mb-1"><strong>Password:</strong> Manager123!</p>
                                                <p class="mb-2"><strong>Role:</strong> manager</p>
                                                <div>
                                                    <span class="badge bg-success permission-badge">Dashboard</span>
                                                    <span class="badge bg-success permission-badge">Products</span>
                                                    <span class="badge bg-success permission-badge">Content</span>
                                                    <span class="badge bg-success permission-badge">View Reports</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reports -->
                                    <div class="col-12">
                                        <div class="card account-card reports-card">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-primary">📊 Report Viewer</h6>
                                                <p class="mb-1"><strong>Email:</strong> reports@dragonstone.com</p>
                                                <p class="mb-1"><strong>Password:</strong> Reports123!</p>
                                                <p class="mb-2"><strong>Role:</strong> reports</p>
                                                <div>
                                                    <span class="badge bg-primary permission-badge">Dashboard</span>
                                                    <span class="badge bg-primary permission-badge">View Reports</span>
                                                    <span class="badge bg-secondary permission-badge">Read Only</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Access Level Info -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6>🔒 Access Levels:</h6>
                                    <ul class="small mb-0">
                                        <li><strong>Super Admin:</strong> Full system access</li>
                                        <li><strong>Admin:</strong> Full content & user management</li>
                                        <li><strong>Manager:</strong> Content management + view reports</li>
                                        <li><strong>Report Viewer:</strong> Dashboard & reports only (read-only)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Debug script -->
    <script>
        console.log('=== ADMIN LOGIN DEBUG ===');
        console.log('Current URL:', window.location.href);
        console.log('Form action:', document.querySelector('form').action);
        console.log('Target redirect: adminindex.php');
        
        // Log form submissions
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.querySelector('[name="username"]').value;
            const role = document.querySelector('[name="role"]').value;
            console.log('Form submitting with:', { username, role });
        });
    </script>
</body>
</html>