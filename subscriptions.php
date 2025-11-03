<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Handle subscription creation
if (isset($_POST['create_subscription']) && isLoggedIn()) {
    $product_id = $_POST['product_id'];
    $frequency = $_POST['frequency'];
    $user_id = $_SESSION['user_id'];
    
    // Check if user already has subscription for this product
    $check_sql = "SELECT subscription_id FROM subscriptions WHERE user_id = ? AND product_id = ? AND status = 'Active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows == 0) {
        // Calculate next delivery date based on frequency
        $next_delivery = date('Y-m-d', strtotime("+$frequency days"));
        
        // Map numeric frequency to ENUM values
        $frequency_enum = 'Monthly'; // Default
        switch($frequency) {
            case 7: $frequency_enum = 'Weekly'; break;
            case 14: $frequency_enum = 'Weekly'; break; // Map to Weekly for bi-weekly
            case 30: $frequency_enum = 'Monthly'; break;
            case 60: $frequency_enum = 'Quarterly'; break;
        }
        
        // Insert subscription - using correct column names from your database
        $sub_sql = "INSERT INTO subscriptions (user_id, product_id, frequency, next_delivery_date, status) 
                    VALUES (?, ?, ?, ?, 'Active')";
        $sub_stmt = $conn->prepare($sub_sql);
        $sub_stmt->bind_param("iiss", $user_id, $product_id, $frequency_enum, $next_delivery);
        
        if ($sub_stmt->execute()) {
            // Award EcoPoints for subscription
            $points_sql = "INSERT INTO eco_point_transactions (user_id, points, transaction_type, reason) VALUES (?, 50, 'Earned', 'Subscription Created')";
            $points_stmt = $conn->prepare($points_sql);
            $points_stmt->bind_param("i", $user_id);
            $points_stmt->execute();
            
            // Update user's EcoPoints balance
            $update_sql = "UPDATE users SET eco_points_balance = eco_points_balance + 50 WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            
            $subscription_success = "Subscription created successfully! +50 EcoPoints awarded!";
        }
    } else {
        $subscription_error = "You already have an active subscription for this product.";
    }
}

// Handle subscription cancellation
if (isset($_POST['cancel_subscription']) && isLoggedIn()) {
    $subscription_id = $_POST['subscription_id'];
    $user_id = $_SESSION['user_id'];
    
    $cancel_sql = "UPDATE subscriptions SET status = 'Cancelled' WHERE subscription_id = ? AND user_id = ?";
    $cancel_stmt = $conn->prepare($cancel_sql);
    $cancel_stmt->bind_param("ii", $subscription_id, $user_id);
    
    if ($cancel_stmt->execute()) {
        $cancellation_success = "Subscription cancelled successfully.";
    } else {
        $cancellation_error = "Error cancelling subscription. Please try again.";
    }
}

// Get popular subscription products
$products_sql = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.category_id 
                 WHERE p.is_active = 1 AND p.stock_quantity > 0
                 ORDER BY p.co2_saved DESC 
                 LIMIT 6";
$products_result = $conn->query($products_sql);

// Get user's active subscriptions if logged in
$user_subscriptions = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $subs_sql = "SELECT s.*, p.name as product_name, p.price, p.co2_saved 
                 FROM subscriptions s 
                 JOIN products p ON s.product_id = p.product_id 
                 WHERE s.user_id = ? AND s.status = 'Active' 
                 ORDER BY s.next_delivery_date ASC";
    $subs_stmt = $conn->prepare($subs_sql);
    $subs_stmt->bind_param("i", $user_id);
    $subs_stmt->execute();
    $user_subscriptions = $subs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

.btn-outline-danger {
    border: 2px solid #dc3545;
    color: #dc3545;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
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

/* Badges */
.badge {
    border-radius: 10px;
    font-weight: 500;
    padding: 6px 12px;
}

.bg-primary { background-color: var(--primary-green) !important; }
.bg-success { background-color: var(--secondary-green) !important; }
.bg-warning { background: linear-gradient(135deg, #ffd93d, #ffcd38) !important; color: var(--text-green) !important; }

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

.alert-info {
    background: rgba(74, 107, 74, 0.1);
    color: var(--text-green);
    border-left: 4px solid var(--primary-green);
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
    color: var(--text-green);
    font-weight: 600;
}

.text-muted {
    color: rgba(45, 74, 45, 0.7) !important;
}

.text-success {
    color: var(--primary-green) !important;
}

.fs-1 {
    font-size: 4rem !important;
}

/* Subscription specific styles */
.subscription-benefits {
    background: linear-gradient(135deg, rgba(90, 122, 90, 0.1), rgba(74, 107, 74, 0.05));
    border-radius: 15px;
    padding: 2rem;
    border: 2px solid rgba(90, 122, 90, 0.2);
}

.benefit-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.benefit-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    flex-shrink: 0;
}

.product-card {
    text-align: center;
    padding: 1.5rem;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.product-card:hover {
    border-color: var(--primary-green);
    transform: translateY(-5px);
}

.product-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.frequency-badge {
    background: rgba(90, 122, 90, 0.1);
    color: var(--text-green);
    border: 1px solid rgba(90, 122, 90, 0.3);
}

.subscription-item {
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    padding: 1.5rem 0;
    transition: all 0.3s ease;
}

.subscription-item:hover {
    background-color: rgba(74, 107, 74, 0.05);
    transform: translateX(5px);
}

.delivery-countdown {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.875rem;
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
    <h1 class="text-center mb-5">Eco-Friendly Subscriptions</h1>
    
    <!-- Benefits Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="subscription-benefits">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="mb-4">Why Subscribe?</h3>
                        <div class="benefit-item">
                            <div class="benefit-icon">🎁</div>
                            <div>
                                <h6 class="mb-1">Save 15% on Every Order</h6>
                                <p class="text-muted mb-0">Exclusive discount for subscribers</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">🚚</div>
                            <div>
                                <h6 class="mb-1">Free Carbon-Neutral Shipping</h6>
                                <p class="text-muted mb-0">Always free, always eco-friendly</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">🌱</div>
                            <div>
                                <h6 class="mb-1">Extra EcoPoints</h6>
                                <p class="text-muted mb-0">+50 points when you subscribe</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="benefit-item">
                            <div class="benefit-icon">⚡</div>
                            <div>
                                <h6 class="mb-1">Never Run Out</h6>
                                <p class="text-muted mb-0">Automatic delivery of essentials</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">📦</div>
                            <div>
                                <h6 class="mb-1">Plastic-Free Packaging</h6>
                                <p class="text-muted mb-0">Zero waste delivery every time</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">🔒</div>
                            <div>
                                <h6 class="mb-1">Flexible & Cancel Anytime</h6>
                                <p class="text-muted mb-0">Pause or cancel with one click</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!isLoggedIn()): ?>
        <!-- Login Prompt for non-logged in users -->
        <div class="login-prompt mb-5">
            <div class="fs-1 mb-3">📦</div>
            <h3>Start Your Eco-Friendly Subscription</h3>
            <p class="text-muted mb-4">Login to create subscriptions and enjoy automatic delivery of your favorite sustainable products.</p>
            <a href="login.php" class="btn btn-dragon me-3">Login to Subscribe</a>
            <a href="products.php" class="btn btn-outline-primary">Browse Products</a>
        </div>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
        <!-- User's Active Subscriptions -->
        <?php if (!empty($user_subscriptions)): ?>
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Your Active Subscriptions</h5>
                            <span class="badge bg-success"><?php echo count($user_subscriptions); ?> active</span>
                        </div>
                        <div class="card-body">
                            <?php foreach ($user_subscriptions as $subscription): ?>
                                <div class="subscription-item d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($subscription['product_name']); ?></h6>
                                        <div class="d-flex gap-3 mb-2">
                                            <span class="frequency-badge badge">
                                                <?php echo $subscription['frequency']; ?>
                                            </span>
                                            <span class="text-success">
                                                🌱 <?php echo $subscription['co2_saved']; ?>kg CO₂ saved per delivery
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            Next delivery: <?php echo date('M j, Y', strtotime($subscription['next_delivery_date'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="delivery-countdown mb-2">
                                            <?php 
                                            $days_until = ceil((strtotime($subscription['next_delivery_date']) - time()) / (60 * 60 * 24));
                                            echo $days_until > 0 ? "In $days_until days" : "Delivery today!";
                                            ?>
                                        </div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="subscription_id" value="<?php echo $subscription['subscription_id']; ?>">
                                            <button type="submit" name="cancel_subscription" class="btn btn-outline-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to cancel this subscription?')">
                                                Cancel
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <h5>No Active Subscriptions</h5>
                <p class="mb-0">You don't have any active subscriptions yet. Browse our popular products below to get started!</p>
            </div>
        <?php endif; ?>

        <!-- Alerts for subscription actions -->
        <?php if (isset($subscription_success)): ?>
            <div class="alert alert-success"><?php echo $subscription_success; ?></div>
        <?php endif; ?>
        <?php if (isset($subscription_error)): ?>
            <div class="alert alert-danger"><?php echo $subscription_error; ?></div>
        <?php endif; ?>
        <?php if (isset($cancellation_success)): ?>
            <div class="alert alert-success"><?php echo $cancellation_success; ?></div>
        <?php endif; ?>
        <?php if (isset($cancellation_error)): ?>
            <div class="alert alert-danger"><?php echo $cancellation_error; ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Popular Subscription Products -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Popular Subscription Products</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($products_result->num_rows > 0): ?>
                            <?php while($product = $products_result->fetch_assoc()): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card product-card">
                                        <div class="card-body">
                                            <div class="product-icon">
                                                <?php 
                                                $icons = ['🧼', '🍴', '🏠', '🚿', '🌿', '👶', '🌳'];
                                                echo $icons[$product['category_id']-1] ?? '📦'; 
                                                ?>
                                            </div>
                                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                            
                                            <div class="mb-3">
                                                <h4 class="text-success">R<?php echo number_format($product['price'] * 0.85, 2); ?></h4>
                                                <small class="text-muted"><s>R<?php echo number_format($product['price'], 2); ?></s> (15% off)</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <span class="badge bg-success">🌱 <?php echo $product['co2_saved']; ?>kg CO₂ saved</span>
                                            </div>
                                            
                                            <?php if (isLoggedIn()): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label small">Delivery Frequency</label>
                                                        <select class="form-select" name="frequency" required>
                                                            <option value="7">Weekly</option>
                                                            <option value="14" selected>Every 2 Weeks</option>
                                                            <option value="30">Monthly</option>
                                                            <option value="60">Every 2 Months</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="create_subscription" class="btn btn-dragon w-100">
                                                        Subscribe & Save
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-outline-primary w-100">Login to Subscribe</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted text-center">No products available for subscription at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Subscription FAQ</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Can I change my delivery frequency?</h6>
                            <p class="text-muted small mb-3">Yes! You can change your delivery frequency at any time from your subscription settings.</p>
                            
                            <h6>How do I cancel my subscription?</h6>
                            <p class="text-muted small mb-3">You can cancel anytime with one click. No questions asked.</p>
                            
                            <h6>Is packaging really plastic-free?</h6>
                            <p class="text-muted small">Absolutely! We use 100% compostable and recyclable materials.</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Do I get discounts on subscriptions?</h6>
                            <p class="text-muted small mb-3">Yes! All subscriptions include 15% off and free carbon-neutral shipping.</p>
                            
                            <h6>Can I skip a delivery?</h6>
                            <p class="text-muted small mb-3">Yes, you can pause or skip deliveries anytime from your account.</p>
                            
                            <h6>How are EcoPoints calculated?</h6>
                            <p class="text-muted small">You earn 50 points when you subscribe and regular points on each delivery.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>