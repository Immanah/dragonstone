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
            $sql = "SELECT user_id, email, password_hash, first_name, last_name, role, eco_points_balance 
                    FROM users 
                    WHERE email = ? AND is_active = TRUE";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password_hash'])) {
                        // Login successful
                        loginUser($user['user_id'], $user['email'], $user['role'], $user['first_name']);
                        
                        // Set success message
                        $_SESSION['login_success'] = "Welcome back, " . htmlspecialchars($user['first_name']) . "!";
                        
                        // Redirect to intended page or home
                        $redirect = $_GET['redirect'] ?? 'index.php';
                        header('Location: ' . $redirect);
                        exit();
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
    
    // Verify Google token
    $user_data = verifyGoogleToken($google_token);
    
    if ($user_data) {
        $email = $user_data['email'];
        $first_name = $user_data['given_name'] ?? '';
        $last_name = $user_data['family_name'] ?? '';
        $google_id = $user_data['sub'] ?? '';
        
        $conn = getDatabaseConnection();
        if ($conn) {
            // Check if user exists by email
            $sql = "SELECT user_id, email, first_name, last_name, role, eco_points_balance 
                    FROM users 
                    WHERE email = ? AND is_active = TRUE";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                // Existing user - log them in
                $user = $result->fetch_assoc();
                loginUser($user['user_id'], $user['email'], $user['role'], $user['first_name']);
                $_SESSION['login_success'] = "Welcome back, " . htmlspecialchars($user['first_name']) . "!";
            } else {
                // New user - create account
                $sql = "INSERT INTO users (email, first_name, last_name, google_id, role, is_active, created_at, auth_provider) 
                        VALUES (?, ?, ?, ?, 'customer', 1, NOW(), 'google')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $email, $first_name, $last_name, $google_id);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    loginUser($user_id, $email, 'customer', $first_name);
                    $_SESSION['login_success'] = "Welcome to DragonStone, " . htmlspecialchars($first_name) . "!";
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
</style>

<?php 
function verifyGoogleToken($token) {
    $client_id = '739507838563-plnrf3qgu6a6ss7ma53efvddmf29ete7.apps.googleusercontent.com';
    
    $url = 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token;
    
    // Use cURL for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        
        
        if (isset($data['aud']) && $data['aud'] === $client_id && 
            isset($data['email']) && isset($data['email_verified']) && 
            $data['email_verified'] === 'true') {
            return $data;
        }
    }
    
    error_log("Google token verification failed. HTTP Code: " . $http_code . " Response: " . $response);
    return false;
}

require_once __DIR__ . '/includes/footer.php'; 
?>