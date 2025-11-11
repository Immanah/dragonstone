<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'login.php') {
    $bypass_auth = true;
}

if ($current_page === 'login.php' && isLoggedIn()) {
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < 86400) {
        $redirect = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit();
    } else {
        // Stale session - clear it
        session_destroy();
        session_start();
    }
}

$error = '';
$success = '';

// Handle resend verification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address to resend verification code.";
    } else {
        $conn = getDatabaseConnection();
        
        if ($conn) {
            // Check if user exists and is not verified
            $sql = "SELECT user_id, first_name, is_verified FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (!$user['is_verified']) {
                    // Generate new verification code
                    $new_verification_code = sprintf("%06d", mt_rand(1, 999999));
                    
                    // Update verification code
                    $update_sql = "UPDATE users SET verification_code = ? WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $new_verification_code, $user['user_id']);
                    
                    if ($update_stmt->execute()) {
                        // Resend verification email
                        if (sendVerificationEmail($email, $user['first_name'], $new_verification_code)) {
                            $success = "Verification code has been resent to your email!";
                        } else {
                            $error = "Failed to send verification email. Please try again.";
                        }
                    } else {
                        $error = "Failed to generate new verification code. Please try again.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "This email is already verified. Please login normally.";
                }
            } else {
                $error = "No account found with this email address.";
            }
            $stmt->close();
            $conn->close();
        } else {
            $error = "Database connection failed. Please try again later.";
        }
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $conn = getDatabaseConnection();
        
        if ($conn) {
            $sql = "SELECT user_id, email, password_hash, first_name, last_name, role, eco_points_balance, is_verified, verification_code 
                    FROM users 
                    WHERE email = ? AND is_active = 1";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password_hash'])) {
                        // Check if email is verified
                        if (!$user['is_verified']) {
                            $error = "Please verify your email address before logging in. 
                                     <br><small>Didn't receive the code? 
                                     <form method='POST' style='display: inline;'>
                                         <input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>
                                         <button type='submit' name='resend_verification' value='1' class='btn btn-link p-0 text-warning' style='text-decoration: underline;'>Resend verification code</button>
                                     </form>
                                     </small>";
                        } else {
                            // Login successful
                            loginUser($user['user_id'], $user['email'], $user['role'], $user['first_name']);
                            
                            // Set success message
                            $_SESSION['login_success'] = "Welcome back, " . htmlspecialchars($user['first_name']) . "!";
                            
                            // Redirect to intended page or home
                            $redirect = $_GET['redirect'] ?? 'index.php';
                            header('Location: ' . $redirect);
                            exit();
                        }
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "No account found with that email or account is inactive.";
                }
                $stmt->close();
            } else {
                $error = "Database error. Please try again.";
            }
            $conn->close();
        } else {
            $error = "Database connection failed. Please try again later.";
        }
    }
}

// Handle Google Sign-In callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_token'])) {
    $google_token = $_POST['google_token'];
    
    // Verify Google token using function from auth.php
    $user_data = verifyGoogleToken($google_token);
    
    if ($user_data) {
        $email = $user_data['email'];
        $first_name = $user_data['given_name'] ?? '';
        $last_name = $user_data['family_name'] ?? '';
        $google_id = $user_data['sub'] ?? '';
        
        $conn = getDatabaseConnection();
        if ($conn) {
            // Check if user exists by email
            $sql = "SELECT user_id, email, first_name, last_name, role, eco_points_balance, is_verified 
                    FROM users 
                    WHERE email = ? AND is_active = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Existing user - check verification and log them in
                $user = $result->fetch_assoc();
                
                // For Google users, auto-verify if not already verified
                if (!$user['is_verified']) {
                    $update_sql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $user['user_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                loginUser($user['user_id'], $user['email'], $user['role'], $user['first_name']);
                $_SESSION['login_success'] = "Welcome back, " . htmlspecialchars($user['first_name']) . "!";
            } else {
                // New user - create account (auto-verified for Google)
                $sql = "INSERT INTO users (email, first_name, last_name, google_id, role, is_active, is_verified, created_at, auth_provider, eco_points_balance) 
                        VALUES (?, ?, ?, ?, 'customer', 1, 1, NOW(), 'google', 100)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $email, $first_name, $last_name, $google_id);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    loginUser($user_id, $email, 'customer', $first_name);
                    $_SESSION['login_success'] = "Welcome to DragonStone, " . htmlspecialchars($first_name) . "! You've earned 100 bonus EcoPoints!";
                } else {
                    $error = "Failed to create account. Please try again.";
                }
            }
            $stmt->close();
            $conn->close();
            
            if (!$error) {
                $redirect = $_GET['redirect'] ?? 'index.php';
                header('Location: ' . $redirect);
                exit();
            }
        }
    } else {
        $error = "Google Sign-In failed. Please try again.";
    }
}

function sendVerificationEmail($email, $name, $verification_code) {
    $subject = "Verify Your DragonStone Account";
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
            .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
            .code { background: #f8f9fa; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 2px; margin: 20px 0; border-radius: 5px; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2 style='color: #198754;'>Welcome to DragonStone, $name!</h2>
            <p>Thank you for creating an account. Please use the verification code below to verify your email address:</p>
            <div class='code'>$verification_code</div>
            <p>Enter this code on the verification page to complete your registration.</p>
            <p>You can also verify your account by clicking the link below:</p>
            <p><a href='http://localhost/dragonstone/verify_email.php?email=" . urlencode($email) . "' style='background: #198754; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a></p>
            <div class='footer'>
                <p><small>If you didn't create an account, please ignore this email.</small></p>
                <p><small>This is an automated message, please do not reply to this email.</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DragonStone <no-reply@dragonstone.com>" . "\r\n";
    $headers .= "Reply-To: no-reply@dragonstone.com" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

$page_title = "Login - DragonStone";
require_once __DIR__ . '/includes/header.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Success Message (if redirected from elsewhere) -->
            <?php if (isset($_SESSION['login_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['login_success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>
            
            <h1 class="text-center mb-4">Welcome Back</h1>
            <p class="text-center text-muted mb-4">Sign in to your DragonStone account</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <!-- Google Sign-In Button -->
                    <div class="mb-4">
                        <div id="googleSignInBtn"></div>
                    </div>
                    
                    <div class="divider mb-4">
                        <span class="divider-text">or sign in with email</span>
                    </div>
                    
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="login" value="1">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="your@email.com" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button type="button" class="input-group-text toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dragon btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    
                    <!-- Hidden form for Google Sign-In -->
                    <form method="POST" id="googleSignInForm" style="display: none;">
                        <input type="hidden" name="google_token" id="google_token">
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Don't have an account? 
                            <a href="register.php" class="text-success fw-bold">Create one here</a>
                        </small>
                        <br>
                        <small class="text-muted">
                            <a href="forgot-password.php" class="text-muted">Forgot your password?</a>
                        </small>
                        <br>
                        <small class="text-muted">
                            Need to verify your account?
                            <a href="verify_email.php" class="text-success fw-bold">Verify email here</a>
                        </small>
                        <br>
                        <small class="text-muted">
                            <a href="index.php" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>Back to home
                            </a>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Demo Accounts -->
            <div class="mt-4 p-4 bg-light rounded text-center">
                <h6 class="mb-3">Demo Accounts</h6>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <div class="card border-success">
                            <div class="card-body py-2">
                                <small class="fw-bold text-success">Admin Account</small><br>
                                <small class="text-muted">admin@dragonstone.com</small><br>
                                <small class="text-muted">Password: admin123</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="card border-primary">
                            <div class="card-body py-2">
                                <small class="fw-bold text-primary">Customer Account</small><br>
                                <small class="text-muted">john@email.com</small><br>
                                <small class="text-muted">Password: customer123</small>
                            </div>
                        </div>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">
                    Note: Demo accounts are pre-verified for testing
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Google Sign-In API -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
            submitBtn.disabled = true;
            
            // Allow form to submit normally
            return true;
        });
    }
    
    // Google Sign-In Configuration
    window.handleGoogleSignIn = function(response) {
        console.log('Google Sign-In response:', response);
        
        // Show loading state
        const googleBtn = document.getElementById('googleSignInBtn');
        if (googleBtn) {
            googleBtn.innerHTML = '<div style="padding: 10px; text-align: center;"><i class="fas fa-spinner fa-spin me-2"></i>Signing In...</div>';
        }
        
        // Send the token to server
        document.getElementById('google_token').value = response.credential;
        document.getElementById('googleSignInForm').submit();
    };
    
    window.onload = function() {
        if (typeof google !== 'undefined') {
            google.accounts.id.initialize({
                client_id: '739507838563-plnrf3qgu6a6ss7ma53efvddmf29ete7.apps.googleusercontent.com',
                callback: handleGoogleSignIn,
                context: 'signin'
            });
            
            // Render Google Sign-In button
            const container = document.getElementById('googleSignInBtn');
            if (container) {
                google.accounts.id.renderButton(container, {
                    type: 'standard',
                    theme: 'outline',
                    size: 'large',
                    width: '100%',
                    text: 'continue_with'
                });
            }
        }
    };
    
    // Auto-focus email field
    document.getElementById('email')?.focus();
});
</script>

<style>
.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 20px 0;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #dee2e6;
}

.divider-text {
    padding: 0 10px;
    color: #6c757d;
    font-size: 0.875rem;
}

.btn-dragon {
    background: linear-gradient(45deg, #198754, #20c997);
    border: none;
    color: white;
    font-weight: 600;
}

.btn-dragon:hover {
    background: linear-gradient(45deg, #157347, #1aa179);
    color: white;
}

#googleSignInBtn {
    width: 100%;
}

/* Style for inline resend verification button */
.btn-link.text-warning:hover {
    color: #ffc107 !important;
    text-decoration: underline !important;
}
</style>

<?php 
// REMOVED THE DUPLICATE verifyGoogleToken() FUNCTION FROM HERE
// It's now only in auth.php

require_once __DIR__ . '/includes/footer.php'; 
?>