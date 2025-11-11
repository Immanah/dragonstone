<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Initialize variables
$user_data = [];
$update_success = '';
$update_error = '';
$user_preferences = [];

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Get user data - including EcoPoints balance (without phone column)
    $user_sql = "SELECT user_id, first_name, last_name, email, eco_points_balance, date_created FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    
    // Create user_preferences table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS user_preferences (
        user_id INT PRIMARY KEY,
        email_notifications BOOLEAN DEFAULT 1,
        sms_notifications BOOLEAN DEFAULT 0,
        marketing_emails BOOLEAN DEFAULT 0,
        profile_visibility ENUM('private', 'community', 'public') DEFAULT 'private',
        data_sharing BOOLEAN DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($create_table_sql)) {
        error_log("Error creating user_preferences table: " . $conn->error);
    }
    
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        // Update user profile (without phone)
        $update_sql = "UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $first_name, $last_name, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Refresh user data
            $user_stmt->execute();
            $user_data = $user_stmt->get_result()->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Error updating profile. Please try again.";
        }
        
        // Redirect to avoid form resubmission
        header("Location: settings.php");
        exit();
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password - check both 'password' and 'user_password' column names
        $verify_sql = "SELECT password, user_password FROM users WHERE user_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result()->fetch_assoc();
        
        // Check which password column exists
        $db_password = null;
        if (isset($result['password'])) {
            $db_password = $result['password'];
        } elseif (isset($result['user_password'])) {
            $db_password = $result['user_password'];
        }
        
        if ($db_password && password_verify($current_password, $db_password)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update the correct password column
                    $password_column = isset($result['password']) ? 'password' : 'user_password';
                    $password_sql = "UPDATE users SET $password_column = ? WHERE user_id = ?";
                    $password_stmt = $conn->prepare($password_sql);
                    $password_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($password_stmt->execute()) {
                        $_SESSION['success_message'] = "Password changed successfully!";
                    } else {
                        $_SESSION['error_message'] = "Error changing password. Please try again.";
                    }
                } else {
                    $_SESSION['error_message'] = "New password must be at least 8 characters long.";
                }
            } else {
                $_SESSION['error_message'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect.";
        }
        
        // Redirect to avoid form resubmission
        header("Location: settings.php");
        exit();
    }
    
    // Handle notification preferences
    if (isset($_POST['update_notifications'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;
        
        try {
            // Save notification preferences to database
            $notifications_sql = "INSERT INTO user_preferences (user_id, email_notifications, sms_notifications, marketing_emails, updated_at) 
                                 VALUES (?, ?, ?, ?, NOW()) 
                                 ON DUPLICATE KEY UPDATE 
                                 email_notifications = VALUES(email_notifications),
                                 sms_notifications = VALUES(sms_notifications),
                                 marketing_emails = VALUES(marketing_emails),
                                 updated_at = NOW()";
            
            $notifications_stmt = $conn->prepare($notifications_sql);
            $notifications_stmt->bind_param("iiii", $user_id, $email_notifications, $sms_notifications, $marketing_emails);
            
            if ($notifications_stmt->execute()) {
                $_SESSION['success_message'] = "Notification preferences updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating notification preferences.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating preferences. Please try again.";
            error_log("Notification preferences error: " . $e->getMessage());
        }
        
        header("Location: settings.php");
        exit();
    }
    
    // Handle privacy settings
    if (isset($_POST['update_privacy'])) {
        $profile_visibility = $_POST['profile_visibility'] ?? 'private';
        $data_sharing = isset($_POST['data_sharing']) ? 1 : 0;
        
        try {
            // Save privacy settings to database
            $privacy_sql = "INSERT INTO user_preferences (user_id, profile_visibility, data_sharing, updated_at) 
                           VALUES (?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE 
                           profile_visibility = VALUES(profile_visibility),
                           data_sharing = VALUES(data_sharing),
                           updated_at = NOW()";
            
            $privacy_stmt = $conn->prepare($privacy_sql);
            $privacy_stmt->bind_param("isi", $user_id, $profile_visibility, $data_sharing);
            
            if ($privacy_stmt->execute()) {
                $_SESSION['success_message'] = "Privacy settings updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating privacy settings.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating privacy settings. Please try again.";
            error_log("Privacy settings error: " . $e->getMessage());
        }
        
        header("Location: settings.php");
        exit();
    }
    
    // Get user preferences - with error handling
    try {
        $preferences_sql = "SELECT * FROM user_preferences WHERE user_id = ?";
        $preferences_stmt = $conn->prepare($preferences_sql);
        if ($preferences_stmt) {
            $preferences_stmt->bind_param("i", $user_id);
            if ($preferences_stmt->execute()) {
                $result = $preferences_stmt->get_result();
                if ($result) {
                    $user_preferences = $result->fetch_assoc() ?: [];
                }
            }
        }
    } catch (Exception $e) {
        // If table doesn't exist or other error, use empty preferences
        $user_preferences = [];
        error_log("User preferences fetch error: " . $e->getMessage());
    }
}

$page_title = "Account Settings - DragonStone";
include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <div class="avatar-container">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="user-info">
                        <h5 class="user-name"><?php echo isLoggedIn() ? htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) : 'Guest'; ?></h5>
                        <p class="user-email"><?php echo isLoggedIn() ? htmlspecialchars($user_data['email']) : 'Not logged in'; ?></p>
                        <div class="user-badges">
                            <span class="role-badge customer">
                                <?php echo isLoggedIn() ? 'Member' : 'Visitor'; ?>
                            </span>
                        </div>
                    </div>
                    <?php if (isLoggedIn()): ?>
                    <div class="user-points">
                        <div class="points-display">
                            <div class="points-amount"><?php echo number_format($user_data['eco_points_balance'] ?? 0); ?></div>
                            <div class="points-label">EcoPoints</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="sidebar-menu">
                    <a href="profile.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                        </span>
                        Dashboard
                    </a>
                    <a href="orders.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </span>
                        My Orders
                    </a>
                    <a href="eco-points.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </span>
                        EcoPoints
                    </a>
                    <a href="settings.php" class="sidebar-item active">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </span>
                        Settings
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="page-header">
                <h1 class="page-title">Account Settings</h1>
                <p class="page-subtitle">Manage your account preferences and security settings</p>
            </div>
            
            <?php if (!isLoggedIn()): ?>
                <!-- Login Prompt for non-logged in users -->
                <div class="dashboard-card">
                    <div class="card-body text-center py-5">
                        <div class="no-data-icon mb-4">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </div>
                        <h3>Manage Your Account</h3>
                        <p class="text-muted mb-4">Login to access your account settings and profile information.</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="login.php" class="btn btn-primary">Login to Settings</a>
                            <a href="register.php" class="btn btn-outline">Create Account</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Settings Navigation -->
                <div class="settings-navigation mb-4">
                    <div class="nav nav-pills" id="settingsTabs" role="tablist">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                            <span class="nav-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            Profile
                        </button>
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                            <span class="nav-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            Password
                        </button>
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                            <span class="nav-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                            </span>
                            Notifications
                        </button>
                        <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">
                            <span class="nav-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            Privacy
                        </button>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="tab-content" id="settingsTabContent">
                    <!-- Profile Information -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Profile Information</h3>
                                <p class="card-subtitle">Update your personal information and contact details</p>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                                       value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                                       value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" disabled>
                                        <div class="form-text">Contact support to change your email address</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="Phone feature coming soon" disabled>
                                        <div class="form-text">Phone number feature will be available in a future update</div>
                                    </div>
                                    
                                    <div class="account-info-section">
                                        <h5 class="section-title">Account Information</h5>
                                        <div class="info-grid">
                                            <div class="info-item">
                                                <label class="info-label">Member Since</label>
                                                <p class="info-value"><?php echo date('F j, Y', strtotime($user_data['date_created'])); ?></p>
                                            </div>
                                            <div class="info-item">
                                                <label class="info-label">EcoPoints Balance</label>
                                                <p class="info-value"><?php echo number_format($user_data['eco_points_balance'] ?? 0); ?> points</p>
                                            </div>
                                            <div class="info-item">
                                                <label class="info-label">User ID</label>
                                                <p class="info-value">#<?php echo $user_data['user_id']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Password & Security -->
                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Password & Security</h3>
                                <p class="card-subtitle">Change your password and manage security settings</p>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Must be at least 8 characters long</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="security-features">
                                        <h5 class="section-title">Security Features</h5>
                                        <div class="feature-list">
                                            <div class="feature-item coming-soon">
                                                <div class="feature-icon">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                    </svg>
                                                </div>
                                                <div class="feature-content">
                                                    <h6>Two-Factor Authentication</h6>
                                                    <p>Add an extra layer of security to your account</p>
                                                    <span class="badge">Coming Soon</span>
                                                </div>
                                            </div>
                                            <div class="feature-item coming-soon">
                                                <div class="feature-icon">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                                    </svg>
                                                </div>
                                                <div class="feature-content">
                                                    <h6>Login Activity</h6>
                                                    <p>View recent login attempts and device history</p>
                                                    <span class="badge">Coming Soon</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="tab-pane fade" id="notifications" role="tabpanel">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Notification Preferences</h3>
                                <p class="card-subtitle">Choose how you want to receive notifications</p>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="notification-category">
                                        <h5 class="section-title">Email Notifications</h5>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                                   <?php echo ($user_preferences['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_notifications">
                                                <strong>Order Updates</strong>
                                                <p class="form-text">Receive emails about order confirmations, shipping updates, and deliveries</p>
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="marketing_emails" name="marketing_emails"
                                                   <?php echo ($user_preferences['marketing_emails'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="marketing_emails">
                                                <strong>Marketing Communications</strong>
                                                <p class="form-text">Receive updates about new products, special offers, and EcoPoints opportunities</p>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="notification-category">
                                        <h5 class="section-title">SMS Notifications</h5>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications"
                                                   <?php echo ($user_preferences['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="sms_notifications">
                                                <strong>Delivery Alerts</strong>
                                                <p class="form-text">Receive SMS notifications for delivery updates and order status changes</p>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_notifications" class="btn btn-primary">Save Preferences</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Settings -->
                    <div class="tab-pane fade" id="privacy" role="tabpanel">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Privacy & Data</h3>
                                <p class="card-subtitle">Manage your privacy settings and data preferences</p>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="privacy-category">
                                        <h5 class="section-title">Profile Visibility</h5>
                                        <div class="mb-3">
                                            <select class="form-control" id="profile_visibility" name="profile_visibility">
                                                <option value="private" <?php echo ($user_preferences['profile_visibility'] ?? 'private') == 'private' ? 'selected' : ''; ?>>Private - Only you can see your profile</option>
                                                <option value="community" <?php echo ($user_preferences['profile_visibility'] ?? 'private') == 'community' ? 'selected' : ''; ?>>Community - Visible to other DragonStone members</option>
                                                <option value="public" <?php echo ($user_preferences['profile_visibility'] ?? 'private') == 'public' ? 'selected' : ''; ?>>Public - Visible to everyone</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="privacy-category">
                                        <h5 class="section-title">Data Preferences</h5>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="data_sharing" name="data_sharing"
                                                   <?php echo ($user_preferences['data_sharing'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="data_sharing">
                                                <strong>Share anonymous usage data</strong>
                                                <p class="form-text">Help us improve DragonStone by sharing anonymous usage data</p>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="data-actions">
                                        <h5 class="section-title">Data Management</h5>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#exportDataModal">
                                                Export My Data
                                            </button>
                                            <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                                Delete Account
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_privacy" class="btn btn-primary">Save Privacy Settings</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Export Data Modal -->
<div class="modal fade" id="exportDataModal" tabindex="-1" aria-labelledby="exportDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportDataModalLabel">Export Your Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This will generate a file containing all your personal data stored on DragonStone, including:</p>
                <ul>
                    <li>Profile information</li>
                    <li>Order history</li>
                    <li>EcoPoints transactions</li>
                    <li>Saved locations</li>
                    <li>Account preferences</li>
                </ul>
                <p>The export process may take a few minutes. You'll receive an email with a download link when your data is ready.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Request Data Export</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Delete Your Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
                <p>Deleting your account will:</p>
                <ul>
                    <li>Permanently remove all your personal data</li>
                    <li>Delete your order history and EcoPoints</li>
                    <li>Cancel any pending orders</li>
                    <li>Remove your saved locations and preferences</li>
                </ul>
                <p>If you're sure you want to proceed, please confirm by typing <strong>DELETE MY ACCOUNT</strong> below:</p>
                <input type="text" class="form-control" id="deleteConfirmation" placeholder="Type DELETE MY ACCOUNT to confirm">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Permanently Delete Account</button>
            </div>
        </div>
    </div>
</div>

<script>
// Delete account confirmation
document.getElementById('deleteConfirmation').addEventListener('input', function() {
    const confirmButton = document.getElementById('confirmDelete');
    confirmButton.disabled = this.value !== 'DELETE MY ACCOUNT';
});

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tabs are already initialized via data attributes
    
    // Add smooth transitions
    const tabPanes = document.querySelectorAll('.tab-pane');
    tabPanes.forEach(pane => {
        pane.style.transition = 'opacity 0.3s ease';
    });
});

// Form validation for password change
const passwordForm = document.querySelector('form[action*="change_password"]');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match.');
            return false;
        }
    });
}
</script>

<style>
/* Settings Page Styles - Consistent with EcoPoints Design */
:root {
    --color-forest-dark: #2d4a2d;
    --color-forest-medium: #3a5c3a;
    --color-forest-light: #4a7c4a;
    --color-sand-light: #f8f6f2;
    --color-white: #ffffff;
    --color-border: #e8e6e1;
    --color-text: #333333;
    --color-text-light: #666666;
    --border-radius: 12px;
    --border-radius-sm: 8px;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
}

.page-header {
    margin-bottom: 2.5rem;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 1.5rem;
}

.page-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--color-text-light);
    margin-bottom: 0;
    font-size: 1.125rem;
}

/* Profile Sidebar - Consistent with Other Pages */
.profile-sidebar {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.user-card {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
    text-align: center;
    background: linear-gradient(135deg, var(--color-sand-light) 0%, #ffffff 100%);
}

.user-avatar {
    margin-bottom: 1rem;
}

.avatar-container {
    width: 80px;
    height: 80px;
    border: 2px solid var(--color-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: var(--color-white);
    color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.user-name {
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
    font-weight: 600;
    font-size: 1.125rem;
}

.user-email {
    color: var(--color-text-light);
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.user-badges {
    margin-bottom: 1.5rem;
}

.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    border: 1px solid;
    letter-spacing: 0.02em;
}

.role-badge.customer {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
}

.user-points {
    border-top: 1px solid var(--color-border);
    padding-top: 1.5rem;
}

.points-display {
    text-align: center;
}

.points-amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-forest-medium);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.points-label {
    font-size: 0.875rem;
    color: var(--color-text-light);
    font-weight: 500;
}

.sidebar-menu {
    padding: 1rem 0.5rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
    font-weight: 500;
    background: var(--color-white);
}

.sidebar-item:hover {
    background: var(--color-sand-light);
    border-color: var(--color-border);
    transform: translateX(4px);
    box-shadow: var(--shadow-sm);
}

.sidebar-item.active {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.sidebar-icon {
    margin-right: 0.75rem;
    width: 16px;
    text-align: center;
}

/* Dashboard Cards */
.dashboard-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    margin-bottom: 2rem;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.card-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.card-title {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 1.25rem;
    letter-spacing: -0.01em;
}

.card-subtitle {
    margin: 0.25rem 0 0 0;
    color: var(--color-text-light);
    font-size: 0.875rem;
}

.card-body {
    padding: 2rem;
}

/* Settings Navigation */
.settings-navigation {
    border-bottom: 1px solid var(--color-border);
    margin-bottom: 2rem;
}

.nav-pills {
    gap: 0.5rem;
}

.nav-pills .nav-link {
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius);
    color: var(--color-text);
    font-weight: 500;
    border: 1px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.nav-pills .nav-link:hover {
    background: var(--color-sand-light);
    border-color: var(--color-border);
}

.nav-pills .nav-link.active {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.nav-icon {
    width: 16px;
    height: 16px;
}

/* Forms */
.form-label {
    font-weight: 500;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.form-control {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: 0.75rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--color-forest-medium);
    box-shadow: 0 0 0 3px rgba(58, 92, 58, 0.1);
    outline: none;
}

.form-text {
    color: var(--color-text-light);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-check-input {
    margin-top: 0.25rem;
}

.form-check-input:checked {
    background-color: var(--color-forest-medium);
    border-color: var(--color-forest-medium);
}

.form-check-label {
    font-weight: 500;
    color: var(--color-forest-dark);
}

/* Sections */
.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--color-border);
}

.account-info-section,
.security-features,
.notification-category,
.privacy-category,
.data-actions {
    margin: 2rem 0;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-sand-light);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    padding: 1rem;
    background: var(--color-white);
    border-radius: var(--border-radius-sm);
    border: 1px solid var(--color-border);
}

.info-label {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: var(--color-forest-dark);
    margin: 0;
}

/* Feature List */
.feature-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-white);
    border-radius: var(--border-radius-sm);
    border: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

.feature-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-1px);
}

.feature-item.coming-soon {
    opacity: 0.7;
}

.feature-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    color: var(--color-forest-medium);
}

.feature-content {
    flex: 1;
}

.feature-content h6 {
    margin: 0 0 0.25rem 0;
    color: var(--color-forest-dark);
    font-weight: 600;
}

.feature-content p {
    margin: 0;
    color: var(--color-text-light);
    font-size: 0.875rem;
}

.badge {
    background: var(--color-forest-light);
    color: var(--color-white);
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
    display: inline-block;
}

/* Action Buttons */
.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-border);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border: 2px solid;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    letter-spacing: 0.02em;
    cursor: pointer;
}

.btn-primary {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
}

.btn-primary:hover {
    background: var(--color-forest-dark);
    border-color: var(--color-forest-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(58, 92, 58, 0.3);
}

.btn-outline {
    background: transparent;
    color: var(--color-forest-medium);
    border-color: var(--color-forest-medium);
}

.btn-outline:hover {
    background: var(--color-forest-medium);
    color: var(--color-white);
    transform: translateY(-2px);
}

.btn-danger {
    background: #dc3545;
    color: var(--color-white);
    border-color: #dc3545;
}

.btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
    transform: translateY(-2px);
}

/* Alert Styles */
.alert {
    border-radius: var(--border-radius);
    border: 1px solid;
    margin-bottom: 2rem;
    padding: 1.25rem 1.5rem;
    font-weight: 500;
}

.alert-success {
    background-color: #e8f5e8;
    border-color: #d4edda;
    color: var(--color-forest-dark);
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* No Data States */
.no-data-icon {
    margin-bottom: 1.5rem;
    color: var(--color-text-light);
    opacity: 0.5;
}

/* Modal Styles */
.modal-content {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    border-bottom: 1px solid var(--color-border);
    padding: 1.5rem 2rem;
}

.modal-title {
    color: var(--color-forest-dark);
    font-weight: 600;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid var(--color-border);
    padding: 1.5rem 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .settings-navigation .nav-pills {
        flex-direction: column;
    }
    
    .nav-pills .nav-link {
        justify-content: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .feature-item {
        flex-direction: column;
        text-align: center;
    }
    
    .card-body {
        padding: 1.5rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .modal-body,
    .modal-header,
    .modal-footer {
        padding: 1rem;
    }
}
</style>

<?php 
if (isset($conn)) {
    $conn->close();
}
include 'includes/footer.php'; 
?>