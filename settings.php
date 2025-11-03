<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Initialize variables
$user_data = [];
$update_success = '';
$update_error = '';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Get user data - ONLY basic columns that definitely exist
    $user_sql = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        // Only update basic columns
        $update_sql = "UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $first_name, $last_name, $user_id);
        
        if ($update_stmt->execute()) {
            $update_success = "Profile updated successfully!";
            // Refresh user data
            $user_stmt->execute();
            $user_data = $user_stmt->get_result()->fetch_assoc();
        } else {
            $update_error = "Error updating profile. Please try again.";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $verify_sql = "SELECT password FROM users WHERE user_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $db_password = $verify_stmt->get_result()->fetch_assoc()['password'];
        
        if (password_verify($current_password, $db_password)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                    $password_stmt = $conn->prepare($password_sql);
                    $password_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($password_stmt->execute()) {
                        $update_success = "Password changed successfully!";
                    } else {
                        $update_error = "Error changing password. Please try again.";
                    }
                } else {
                    $update_error = "New password must be at least 8 characters long.";
                }
            } else {
                $update_error = "New passwords do not match.";
            }
        } else {
            $update_error = "Current password is incorrect.";
        }
    }
}

include 'includes/header.php';
?>

<style>
:root {
    --bg-sandy-light: #d4c4a8;
    --bg-sandy-dark: #c2b299;
    --primary-green: #4a6b4a;
    --secondary-green: #5a7a5a;
    --text-green: #2d4a2d;
    --surface-white: #ffffff;
    --border-radius-organic: 30px;
    --border-radius-cards: 20px;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--bg-sandy-light) 0%, var(--bg-sandy-dark) 100%);
    color: var(--text-green);
    line-height: 1.6;
    min-height: 100vh;
    position: relative;
}

.container {
    position: relative;
    z-index: 2;
}

/* Organic Shapes */
.organic-shape {
    position: absolute;
    opacity: 0.25;
    border-radius: 60% 40% 30% 70%;
    animation: float 8s ease-in-out infinite;
    z-index: 1;
}

.shape-1 {
    width: 180px;
    height: 180px;
    background: var(--primary-green);
    top: 10%;
    left: 5%;
    animation-delay: 0s;
}

.shape-2 {
    width: 140px;
    height: 140px;
    background: var(--secondary-green);
    top: 60%;
    right: 8%;
    animation-delay: -3s;
}

.shape-3 {
    width: 160px;
    height: 160px;
    background: var(--text-green);
    bottom: 15%;
    left: 12%;
    animation-delay: -6s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(5deg);
    }
}

/* Cards */
.card {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: var(--border-radius-cards);
    box-shadow: 0 8px 32px rgba(45, 74, 45, 0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(45, 74, 45, 0.15);
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.card-header {
    background: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    border-radius: var(--border-radius-cards) var(--border-radius-cards) 0 0 !important;
    padding: 1.25rem;
}

/* Buttons */
.btn-dragon {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(74, 107, 74, 0.3);
}

.btn-dragon:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 107, 74, 0.4);
    background: linear-gradient(135deg, var(--secondary-green) 0%, var(--primary-green) 100%);
    color: white;
}

.btn-outline-primary {
    border: 2px solid var(--primary-green);
    color: var(--primary-green);
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
    transform: translateY(-1px);
    color: white;
}

/* Forms */
.form-control, .form-select {
    border: 2px solid rgba(74, 107, 74, 0.1);
    border-radius: 15px;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(74, 107, 74, 0.1);
}

/* Alerts */
.alert {
    border-radius: 15px;
    border: none;
}

.alert-success {
    background: rgba(90, 122, 90, 0.1);
    color: var(--text-green);
    border-left: 4px solid var(--secondary-green);
}

.alert-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--text-green);
    border-left: 4px solid #ff6b6b;
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
    color: var(--text-green);
    font-weight: 600;
}

.text-muted {
    color: rgba(45, 74, 45, 0.7) !important;
}

/* Settings specific styles */
.settings-nav {
    background: rgba(255, 255, 255, 0.9);
    border-radius: var(--border-radius-cards);
    padding: 1.5rem;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.nav-link {
    color: var(--text-green);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
    text-align: left;
    width: 100%;
    margin-bottom: 0.5rem;
}

.nav-link:hover, .nav-link.active {
    background: var(--primary-green);
    color: white;
}

.settings-section {
    display: none;
}

.settings-section.active {
    display: block;
}

/* Coming soon notice */
.coming-soon {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
    border: 2px solid rgba(255, 193, 7, 0.3);
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1rem;
}

.coming-soon h6 {
    color: #e9b949;
    margin-bottom: 0.5rem;
}

/* Login prompt */
.login-prompt {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.8));
    border-radius: var(--border-radius-cards);
    padding: 3rem;
    text-align: center;
    border: 2px solid rgba(74, 107, 74, 0.2);
}
</style>

<div class="organic-shape shape-1"></div>
<div class="organic-shape shape-2"></div>
<div class="organic-shape shape-3"></div>

<div class="container py-5">
    <h1 class="text-center mb-5">Account Settings</h1>
    
    <?php if (!isLoggedIn()): ?>
        <!-- Login Prompt for non-logged in users -->
        <div class="login-prompt mb-5">
            <div class="fs-1 mb-3">⚙️</div>
            <h3>Manage Your Account</h3>
            <p class="text-muted mb-4">Login to access your account settings and profile information.</p>
            <a href="login.php" class="btn btn-dragon me-3">Login to Settings</a>
            <a href="register.php" class="btn btn-outline-primary">Create Account</a>
        </div>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
        <!-- Success/Error Messages -->
        <?php if ($update_success): ?>
            <div class="alert alert-success"><?php echo $update_success; ?></div>
        <?php endif; ?>
        <?php if ($update_error): ?>
            <div class="alert alert-danger"><?php echo $update_error; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-md-3">
                <div class="settings-nav sticky-top" style="top: 100px;">
                    <div class="nav flex-column" id="settingsTabs" role="tablist">
                        <button class="nav-link active" data-bs-target="#profile" type="button">
                            👤 Profile
                        </button>
                        <button class="nav-link" data-bs-target="#password" type="button">
                            🔒 Password
                        </button>
                        <button class="nav-link" data-bs-target="#comingsoon" type="button">
                            🌱 More Features
                        </button>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-md-9">
                <!-- Profile Information -->
                <div class="settings-section active" id="profile">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">👤 Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" disabled>
                                    <small class="text-muted">Contact support to change your email address</small>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-dragon">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password & Security -->
                <div class="settings-section" id="password">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">🔒 Password & Security</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                    <small class="text-muted">Must be at least 8 characters long</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-dragon">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Coming Soon Features -->
                <div class="settings-section" id="comingsoon">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">🌱 More Features Coming Soon</h5>
                        </div>
                        <div class="card-body">
                            <div class="coming-soon">
                                <h6>📊 EcoPoints Tracking</h6>
                                <p class="text-muted mb-3">Track your environmental impact and see detailed EcoPoints history.</p>
                            </div>
                            
                            <div class="coming-soon">
                                <h6>📱 Enhanced Profile</h6>
                                <p class="text-muted mb-3">Add phone numbers, addresses, and shipping preferences to your profile.</p>
                            </div>
                            
                            <div class="coming-soon">
                                <h6>🔔 Notification Preferences</h6>
                                <p class="text-muted mb-3">Customize which notifications you receive about orders, products, and community updates.</p>
                            </div>
                            
                            <div class="coming-soon">
                                <h6>🛡️ Privacy Controls</h6>
                                <p class="text-muted mb-3">Download your data and manage your privacy settings.</p>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p class="text-muted">These features will be available in upcoming updates as we continue to improve DragonStone.</p>
                                <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Simple tab navigation
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    const settingsSections = document.querySelectorAll('.settings-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove active class from all links and sections
            navLinks.forEach(nl => nl.classList.remove('active'));
            settingsSections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('data-bs-target').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
});
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>