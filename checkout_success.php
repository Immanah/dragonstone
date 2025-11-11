<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'includes/database.php';
include_once 'includes/auth.php';
include_once 'includes/header.php';

// Check if we have success data
if (!isset($_SESSION['checkout_success']) || !isset($_SESSION['order_data'])) {
    header('Location: checkout.php');
    exit();
}

$order_data = $_SESSION['order_data'];

// Clear the success data so it doesn't show again on refresh
unset($_SESSION['checkout_success']);
unset($_SESSION['order_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - DragonStone</title>
    <style>
    :root {
        --primary-green: #2d5016;
        --secondary-green: #4a7c3a;
        --accent-green: #6b8e23;
        --light-green: #8fbc8f;
        --sand-light: #f5f1e6;
        --sand-dark: #e8e0d0;
        --text-dark: #2c3e50;
        --text-light: #7f8c8d;
        --border-color: #e9ecef;
        --success-color: #28a745;
        --warning-color: #ffc107;
    }

    .success-container {
        background: linear-gradient(135deg, var(--sand-light) 0%, var(--sand-dark) 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .eco-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(45, 80, 22, 0.1);
        border: 1px solid var(--border-color);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .eco-card-header {
        background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }

    .eco-card-body {
        padding: 1.5rem;
    }

    .success-icon {
        font-size: 4rem;
        color: var(--primary-green);
        margin-bottom: 1rem;
    }

    .btn-dragon {
        background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(45, 80, 22, 0.2);
        text-decoration: none;
        display: inline-block;
    }

    .btn-dragon:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(45, 80, 22, 0.3);
        background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
        color: white;
    }

    .btn-outline-success {
        border: 2px solid var(--primary-green);
        color: var(--primary-green);
        border-radius: 12px;
        padding: 0.875rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-outline-success:hover {
        background: var(--primary-green);
        color: white;
        transform: translateY(-2px);
    }

    .free-shipping-banner {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 2px solid #28a745;
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        margin: 1rem 0;
    }

    .order-timeline {
        display: flex;
        justify-content: space-between;
        margin: 2rem 0;
        position: relative;
    }

    .order-timeline::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 10%;
        right: 10%;
        height: 3px;
        background: var(--border-color);
        z-index: 1;
    }

    .timeline-step {
        text-align: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }

    .timeline-step.active .step-icon {
        background: var(--primary-green);
        color: white;
        border-color: var(--primary-green);
    }

    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: white;
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: bold;
    }

    .step-label {
        font-size: 0.85rem;
        color: var(--text-light);
    }

    .timeline-step.active .step-label {
        color: var(--primary-green);
        font-weight: 600;
    }

    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        background: var(--sand-light);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
    }

    .impact-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .impact-stat {
        text-align: center;
        padding: 1rem;
        background: var(--sand-light);
        border-radius: 12px;
    }

    .impact-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-green);
        margin-bottom: 0.25rem;
    }

    .impact-label {
        font-size: 0.85rem;
        color: var(--text-light);
    }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="container">
            <div class="text-center py-5">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="mb-3" style="color: var(--primary-green);">Order Successful!</h2>
                <p class="lead mb-4">Thank you for your sustainable purchase with DragonStone</p>
                
                <!-- Order Timeline -->
                <div class="order-timeline">
                    <div class="timeline-step active">
                        <div class="step-icon">1</div>
                        <div class="step-label">Order Placed</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon">2</div>
                        <div class="step-label">Processing</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon">3</div>
                        <div class="step-label">Shipped</div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon">4</div>
                        <div class="step-label">Delivered</div>
                    </div>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="eco-card">
                            <div class="eco-card-header">
                                <h4 class="mb-0">Order Confirmed!</h4>
                            </div>
                            <div class="eco-card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Order #:</strong> <?php echo $order_data['order_id']; ?></p>
                                        <p class="mb-2"><strong>Tracking Number:</strong> <?php echo $order_data['tracking_number']; ?></p>
                                        <p class="mb-2"><strong>Order Date:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Total Amount:</strong> R<?php echo number_format($order_data['total'], 2); ?></p>
                                        <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success">Processing</span></p>
                                        <p class="mb-2"><strong>Payment:</strong> <span class="badge bg-success">Completed</span></p>
                                    </div>
                                </div>
                                
                                <?php if ($order_data['free_shipping']): ?>
                                    <div class="free-shipping-banner">
                                        <h6 class="mb-0">🎉 You qualified for FREE shipping!</h6>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($order_data['email_sent']): ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-envelope me-2"></i>Confirmation email sent to your registered email address
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Email confirmation failed, but your order was processed successfully.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sustainability Impact -->
                        <div class="eco-card mt-4">
                            <div class="eco-card-body">
                                <h5 class="mb-4">🌱 Your Sustainability Impact</h5>
                                <div class="impact-stats">
                                    <div class="impact-stat">
                                        <div class="impact-value">+<?php echo $order_data['points']; ?></div>
                                        <div class="impact-label">EcoPoints Earned</div>
                                    </div>
                                    <div class="impact-stat">
                                        <div class="impact-value">~<?php echo number_format($order_data['co2_saved'], 1); ?>kg</div>
                                        <div class="impact-label">CO2 Saved</div>
                                    </div>
                                    <div class="impact-stat">
                                        <div class="impact-value"><?php echo rand(3, 8); ?></div>
                                        <div class="impact-label">Plastic Items Saved</div>
                                    </div>
                                    <div class="impact-stat">
                                        <div class="impact-value"><?php echo rand(5, 15); ?>L</div>
                                        <div class="impact-label">Water Conserved</div>
                                    </div>
                                </div>
                                <p class="text-muted text-center mt-3">
                                    <small>Your purchase supports sustainable practices and reduces environmental impact</small>
                                </p>
                            </div>
                        </div>

                        <!-- Next Steps -->
                        <div class="eco-card mt-4">
                            <div class="eco-card-body">
                                <h5 class="mb-3">What's Next?</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-success">
                                                <i class="fas fa-shipping-fast fa-2x"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Track Your Order</h6>
                                                <p class="mb-0 text-muted small">Use tracking #: <strong><?php echo $order_data['tracking_number']; ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-success">
                                                <i class="fas fa-envelope-open-text fa-2x"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Check Your Email</h6>
                                                <p class="mb-0 text-muted small">Order confirmation and receipt</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-5">
                    <a href="products.php" class="btn btn-dragon me-3">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                    <a href="orders.php" class="btn btn-outline-success me-3">
                        <i class="fas fa-clipboard-list me-2"></i>View My Orders
                    </a>
                    <a href="index.php" class="btn btn-outline-success">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>

                <!-- Support Info -->
                <div class="mt-4">
                    <p class="text-muted">
                        <small>
                            Need help? <a href="contact.php" class="text-success">Contact Support</a> or 
                            call us at <strong>1-800-DRAGONSTONE</strong>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & Font Awesome -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <script>
    // Confetti effect for celebration
    document.addEventListener('DOMContentLoaded', function() {
        console.log(' Order success page loaded!');
        
        // Simple celebration effect
        setTimeout(() => {
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }
        }, 1000);
    });
    </script>

    <!-- Optional: Add confetti library for celebration -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
</body>
</html>