<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $conn = getDatabaseConnection();
        
        if ($conn) {
            // Check if email already exists
            $check_sql = "SELECT user_id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "An account with this email already exists.";
            } else {
                // Create new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'Customer';
                
                $insert_sql = "INSERT INTO users (first_name, last_name, email, password_hash, role, eco_points_balance) 
                              VALUES (?, ?, ?, ?, ?, 100)"; // Give 100 bonus points for signing up
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sssss", $first_name, $last_name, $email, $password_hash, $role);
                
                if ($insert_stmt->execute()) {
                    $success = "Account created successfully! You've earned 100 bonus EcoPoints. Please login.";
                    $_POST = array(); // Clear form
                } else {
                    $error = "Registration failed. Please try again.";
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
            $conn->close();
        } else {
            $error = "Database connection failed. Please try again later.";
        }
    }
}

$page_title = "Register - DragonStone";
include 'includes/header.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="text-center mb-4">Join DragonStone</h1>
            <p class="text-center text-muted mb-4">Create your account and start your sustainable journey</p>
            
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
                    <form method="POST" id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                       placeholder="John" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                       placeholder="Doe" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="your@email.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="At least 6 characters" required>
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Repeat your password" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="register" value="1" class="btn btn-dragon btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Already have an account? 
                            <a href="login.php" class="text-success fw-bold">Sign in here</a>
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
            
            <!-- Benefits -->
            <div class="mt-4 p-4 bg-light rounded">
                <h6 class="text-center mb-3">Why Join DragonStone?</h6>
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <i class="fas fa-leaf text-success fs-4 mb-2"></i>
                        <p class="small mb-0">Earn EcoPoints on every purchase</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <i class="fas fa-truck text-success fs-4 mb-2"></i>
                        <p class="small mb-0">Free shipping on orders over R500</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <i class="fas fa-users text-success fs-4 mb-2"></i>
                        <p class="small mb-0">Join our eco-community</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>