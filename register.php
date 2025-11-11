<?php
// register.php - SIMPLIFIED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic includes
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

// Redirect if logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    error_log("REGISTRATION: Form submitted");
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $conn = getDatabaseConnection();
        
        if ($conn) {
            // Check if email exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                // Create user
                $verification_code = sprintf("%06d", mt_rand(1, 999999));
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, eco_points_balance, verification_code) VALUES (?, ?, ?, ?, 'Customer', 100, ?)");
                $stmt->bind_param("sssss", $first_name, $last_name, $email, $password_hash, $verification_code);
                
                if ($stmt->execute()) {
                    // Send email
                    $subject = "Verify Your Account";
                    $message = "Your verification code: " . $verification_code;
                    $headers = "From: " . FROM_EMAIL;
                    
                    mail($email, $subject, $message, $headers);
                    
                    // Redirect to verify page
                    $_SESSION['registered_email'] = $email;
                    $_SESSION['verification_code'] = $verification_code;
                    header('Location: verify_email.php');
                    exit();
                } else {
                    $error = "Registration failed: " . $stmt->error;
                }
            }
            $conn->close();
        } else {
            $error = "Database connection failed.";
        }
    }
    
    // If we get here, there was an error
    error_log("REGISTRATION ERROR: " . $error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DragonStone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <h1 class="text-center mb-4">Join DragonStone</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form method="POST" action="" id="registerForm">
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
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Repeat your password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="register" value="1" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="diagnostic.php" class="btn btn-outline-secondary btn-sm">Run Diagnostic</a>
                </div>
            </div>
        </div>
    </div>

    <!-- NO JAVASCRIPT VALIDATION -->
    <script>
    console.log("Register page loaded");
    </script>
</body>
</html>