<?php
// verify_email.php - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

$error = '';
$success = '';

// Check for messages from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get email from URL or session
$prefilled_email = $_GET['email'] ?? $_SESSION['registered_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $email = trim($_POST['email'] ?? '');
    $verification_code = trim($_POST['verification_code'] ?? '');
    
    if (empty($email) || empty($verification_code)) {
        $error = "Please enter both email and verification code.";
    } else {
        $conn = getDatabaseConnection();
        
        if ($conn) {
            // FIX: Use the exact same email case (convert to lowercase)
            $email = strtolower($email);
            
            // Check if user exists
            $sql = "SELECT user_id, first_name, verification_code, is_verified FROM users WHERE LOWER(email) = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // If already verified, redirect to login
                    if ($user['is_verified'] == 1) {
                        $_SESSION['success'] = "Your email is already verified! You can login now.";
                        header('Location: login.php');
                        exit();
                    }
                    // FIX: Trim and compare codes properly
                    elseif (trim($user['verification_code']) === trim($verification_code)) {
                        // Mark as verified
                        $update_sql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        
                        if ($update_stmt) {
                            $update_stmt->bind_param("i", $user['user_id']);
                            
                            if ($update_stmt->execute()) {
                                // Clear session data
                                unset($_SESSION['verification_code']);
                                unset($_SESSION['registered_email']);
                                
                                $_SESSION['success'] = "Email verified successfully! You can now login to your account.";
                                header('Location: login.php');
                                exit();
                            } else {
                                $error = "Verification failed. Please try again.";
                            }
                            $update_stmt->close();
                        } else {
                            $error = "Database error. Please try again.";
                        }
                    } else {
                        $error = "Invalid verification code. Please check and try again.";
                    }
                } else {
                    $error = "No account found with this email.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - DragonStone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <h1 class="text-center mb-4">Verify Your Email</h1>
                <p class="text-center text-muted mb-4">Enter the verification code sent to your email</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form method="POST" id="verifyForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($prefilled_email ?: ($_POST['email'] ?? '')); ?>" 
                                       placeholder="your@email.com" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="verification_code" class="form-label">Verification Code</label>
                                <input type="text" class="form-control" id="verification_code" name="verification_code" 
                                       placeholder="Enter 6-digit code" maxlength="6" required>
                                <div class="form-text">Check your email inbox for the 6-digit verification code.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="verify" value="1" class="btn btn-success btn-lg">
                                    <i class="fas fa-check-circle me-2"></i>Verify Email
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <a href="login.php" class="text-success fw-bold">Try logging in</a> if already verified
                            </small>
                            <br>
                            <small class="text-muted">
                                <a href="register.php" class="text-muted">
                                    <i class="fas fa-arrow-left me-1"></i>Back to registration
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const verifyForm = document.getElementById('verifyForm');
        
        if (verifyForm) {
            verifyForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
                submitBtn.disabled = true;
            });
        }
        
        document.getElementById('verification_code')?.focus();
    });
    </script>
</body>
</html>