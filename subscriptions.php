<?php
// Start session at the very top - JUST LIKE PRODUCTS.PHP
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Handle category filtering
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Handle subscription creation
if (isset($_POST['create_subscription']) && isLoggedIn()) {
    $product_id = intval($_POST['product_id']);
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
        $frequency_enum = 'Monthly';
        switch($frequency) {
            case 7: $frequency_enum = 'Weekly'; break;
            case 14: $frequency_enum = 'Bi-Weekly'; break;
            case 30: $frequency_enum = 'Monthly'; break;
            case 60: $frequency_enum = 'Quarterly'; break;
        }
        
        // Insert subscription
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
            
            $_SESSION['subscription_success'] = "Subscription created successfully! +50 EcoPoints awarded!";
            
            // Simple redirect - JUST LIKE PRODUCTS.PHP
            $redirect_url = 'subscriptions.php';
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $redirect_url .= '?category=' . urlencode($_GET['category']);
            }
            
            header('Location: ' . $redirect_url);
            exit();
        }
    } else {
        $_SESSION['subscription_error'] = "You already have an active subscription for this product.";
        header('Location: subscriptions.php');
        exit();
    }
}

// Handle subscription cancellation
if (isset($_POST['cancel_subscription']) && isLoggedIn()) {
    $subscription_id = intval($_POST['subscription_id']);
    $user_id = $_SESSION['user_id'];
    
    $cancel_sql = "UPDATE subscriptions SET status = 'Cancelled' WHERE subscription_id = ? AND user_id = ?";
    $cancel_stmt = $conn->prepare($cancel_sql);
    $cancel_stmt->bind_param("ii", $subscription_id, $user_id);
    
    if ($cancel_stmt->execute()) {
        $_SESSION['cancellation_success'] = "Subscription cancelled successfully.";
    } else {
        $_SESSION['cancellation_error'] = "Error cancelling subscription. Please try again.";
    }
    
    header('Location: subscriptions.php');
    exit();
}

// Build SQL query for categories - USING CATEGORY ID LIKE PRODUCTS.PHP
if ($category_filter && $category_filter != 'all') {
    // Convert category name to ID for filtering
    $cat_sql = "SELECT category_id FROM categories WHERE name = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->bind_param("s", $category_filter);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    
    if ($cat_result->num_rows > 0) {
        $category_row = $cat_result->fetch_assoc();
        $category_id = $category_row['category_id'];
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.is_active = 1 AND p.stock_quantity > 0 AND p.category_id = ? 
                ORDER BY p.co2_saved DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $products_result = $stmt->get_result();
    } else {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.is_active = 1 AND p.stock_quantity > 0
                ORDER BY p.co2_saved DESC";
        $products_result = $conn->query($sql);
    }
} else {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_active = 1 AND p.stock_quantity > 0
            ORDER BY p.co2_saved DESC";
    $products_result = $conn->query($sql);
}

// Get available categories for filtering - EXACT SAME AS PRODUCTS.PHP
$categories_sql = "SELECT DISTINCT c.category_id, c.name 
                   FROM categories c 
                   INNER JOIN products p ON c.category_id = p.category_id 
                   WHERE p.is_active = 1 AND p.stock_quantity > 0
                   ORDER BY c.name";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get current category name if filtered
$current_category = '';
if ($category_filter && $category_filter != 'all') {
    $current_category = $category_filter;
}

// Get user's active subscriptions if logged in
$user_subscriptions = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $subs_sql = "SELECT s.*, p.name as product_name, p.price, p.co2_saved, p.image_path, p.image_url
                 FROM subscriptions s 
                 JOIN products p ON s.product_id = p.product_id 
                 WHERE s.user_id = ? AND s.status = 'Active' 
                 ORDER BY s.next_delivery_date ASC";
    $subs_stmt = $conn->prepare($subs_sql);
    $subs_stmt->bind_param("i", $user_id);
    $subs_stmt->execute();
    $user_subscriptions = $subs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Eco-Friendly Subscriptions - DragonStone";
include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-success mb-4">
                <?php if ($current_category): ?>
                    <?php echo htmlspecialchars($current_category); ?> Subscriptions
                <?php else: ?>
                    Eco-Friendly Subscriptions
                <?php endif; ?>
            </h1>
            <p class="lead text-muted">
                <?php if ($current_category): ?>
                    Subscribe to eco-friendly <?php echo strtolower($current_category); ?> for automatic deliveries and savings
                <?php else: ?>
                    Automate your sustainable shopping and save 15% on every delivery
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <!-- Success Messages - USING SESSION MESSAGES LIKE PRODUCTS.PHP -->
    <?php if (isset($_SESSION['subscription_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['subscription_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['subscription_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['subscription_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['subscription_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['subscription_error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['cancellation_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['cancellation_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['cancellation_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['cancellation_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['cancellation_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['cancellation_error']); ?>
    <?php endif; ?>

    <!-- Benefits Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-4">Why Subscribe? Save 15% on Every Delivery</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-percentage text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">15% Exclusive Discount</h6>
                                    <p class="text-muted mb-0">Save on every automated delivery</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-truck text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Free Carbon-Neutral Shipping</h6>
                                    <p class="text-muted mb-0">Always free, always eco-friendly</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-leaf text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">+50 EcoPoints Bonus</h6>
                                    <p class="text-muted mb-0">Earn points when you subscribe</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-bolt text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Never Run Out</h6>
                                    <p class="text-muted mb-0">Automatic delivery of essentials</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-box text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Plastic-Free Packaging</h6>
                                    <p class="text-muted mb-0">Zero waste delivery every time</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-lock text-success fa-2x me-3"></i>
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
    </div>

    <?php if (!isLoggedIn()): ?>
        <!-- Login Prompt -->
        <div class="card text-center mb-5">
            <div class="card-body py-5">
                <i class="fas fa-box-open display-1 text-success mb-3"></i>
                <h3>Start Your Eco-Friendly Subscription</h3>
                <p class="text-muted mb-4">Login to create subscriptions and enjoy automatic delivery of your favorite sustainable products.</p>
                <a href="login.php" class="btn btn-success me-3">Login to Subscribe</a>
                <a href="products.php" class="btn btn-outline-success">Browse Products</a>
            </div>
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
                                <?php
                                // EXACT SAME IMAGE LOGIC AS PRODUCTS.PHP
                                $main_image = 'includes/Screenshot 2025-10-30 145731.png'; // Fallback image
                                if (!empty($subscription['image_path'])) {
                                    $main_image = $subscription['image_path'];
                                } elseif (!empty($subscription['image_url'])) {
                                    $main_image = $subscription['image_url'];
                                }
                                ?>
                                <div class="subscription-item d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <!-- EXACT SAME IMAGE CONTAINER AS PRODUCTS.PHP -->
                                        <div class="product-image-container me-3" style="width: 80px; height: 80px;">
                                            <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                                 alt="<?php echo htmlspecialchars($subscription['product_name']); ?>"
                                                 class="product-image w-100 h-100"
                                                 style="object-fit: cover; border-radius: 8px;"
                                                 onerror="this.src='includes/Screenshot 2025-10-30 145731.png'">
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($subscription['product_name']); ?></h6>
                                            <div class="d-flex gap-3 mb-2 flex-wrap">
                                                <span class="badge bg-white text-dark border">
                                                    <i class="fas fa-sync me-1"></i><?php echo $subscription['frequency']; ?>
                                                </span>
                                                <span class="text-success">
                                                    <i class="fas fa-leaf me-1"></i><?php echo $subscription['co2_saved']; ?>kg CO₂ saved
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Next delivery: <?php echo date('M j, Y', strtotime($subscription['next_delivery_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="delivery-countdown mb-2 bg-success text-white px-3 py-1 rounded-pill">
                                            <?php 
                                            $days_until = ceil((strtotime($subscription['next_delivery_date']) - time()) / (60 * 60 * 24));
                                            echo $days_until > 0 ? "In $days_until days" : "Delivery today!";
                                            ?>
                                        </div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="subscription_id" value="<?php echo $subscription['subscription_id']; ?>">
                                            <button type="submit" name="cancel_subscription" class="btn btn-outline-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to cancel this subscription?')">
                                                <i class="fas fa-times me-1"></i>Cancel
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
                <h5><i class="fas fa-info-circle me-2"></i>No Active Subscriptions</h5>
                <p class="mb-0">You don't have any active subscriptions yet. Browse our products below to get started!</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Category Filters - EXACT SAME STYLING AS PRODUCTS.PHP -->
    <?php if (!empty($categories)): ?>
    <div class="card mb-5">
        <div class="card-body">
            <h5 class="card-title mb-3">Filter by Category</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="subscriptions.php?category=all" class="btn btn-outline-success <?php echo $category_filter == 'all' ? 'active' : ''; ?>">
                    All Products
                </a>
                <?php foreach($categories as $category): ?>
                    <a href="subscriptions.php?category=<?php echo urlencode($category['name']); ?>" 
                       class="btn btn-outline-success <?php echo $category_filter == $category['name'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products Count -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="products-count">
            <p class="text-muted mb-0">
                <?php 
                $product_count = $products_result->num_rows;
                if ($current_category && $current_category != 'all') {
                    echo "Showing <strong>$product_count</strong> subscription product" . ($product_count != 1 ? 's' : '') . " in <strong>$current_category</strong>";
                } else {
                    echo "Showing <strong>$product_count</strong> subscription product" . ($product_count != 1 ? 's' : '') . "";
                }
                ?>
            </p>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row g-4" id="productsGrid">
        <?php if ($products_result->num_rows > 0): ?>
            <?php while($product = $products_result->fetch_assoc()): 
                // EXACT SAME IMAGE LOGIC AS PRODUCTS.PHP
                $main_image = 'includes/Screenshot 2025-10-30 145731.png'; // Fallback image
                if (!empty($product['image_path'])) {
                    $main_image = $product['image_path'];
                } elseif (!empty($product['image_url'])) {
                    $main_image = $product['image_url'];
                }
                
                $stock_class = $product['stock_quantity'] > 10 ? 'in-stock' : 
                              ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                $stock_text = $product['stock_quantity'] > 10 ? 'In Stock' : 
                             ($product['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 product-card">
                        <!-- EXACT SAME IMAGE CONTAINER AS PRODUCTS.PHP -->
                        <div class="product-image-container position-relative">
                            <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="card-img-top product-image"
                                 onerror="this.src='includes/Screenshot 2025-10-30 145731.png'">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <!-- Category Badge -->
                            <div class="mb-2">
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            
                            <!-- Product Name -->
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h5>
                            
                            <!-- Product Description -->
                            <p class="card-text text-muted flex-grow-1">
                                <?php 
                                $description = htmlspecialchars($product['description']);
                                echo strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                                ?>
                            </p>
                            
                            <!-- Environmental Impact -->
                            <div class="environmental-impact mb-3 p-3 bg-light rounded">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-leaf text-success me-2"></i>
                                    <strong class="text-success">Saves <?php echo $product['co2_saved']; ?>kg CO₂</strong>
                                </div>
                                <small class="text-muted">vs. conventional alternatives</small>
                            </div>
                            
                            <!-- Price and Stock -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="h4 text-success mb-0">R<?php echo number_format($product['price'] * 0.85, 2); ?></span>
                                    <small class="text-muted d-block"><s>R<?php echo number_format($product['price'], 2); ?></s> (15% off)</small>
                                </div>
                                <div>
                                    <span class="badge <?php echo $stock_class == 'in-stock' ? 'bg-success' : ($stock_class == 'low-stock' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo $stock_text; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Subscription Form -->
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" class="mt-auto">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Delivery Frequency</label>
                                        <select class="form-select" name="frequency" required>
                                            <option value="7">Weekly</option>
                                            <option value="14" selected>Every 2 Weeks</option>
                                            <option value="30">Monthly</option>
                                            <option value="60">Every 2 Months</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="create_subscription" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-sync me-2"></i>
                                        Subscribe & Save
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-success btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Login to Subscribe
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h3>No Products Found</h3>
                        <p class="text-muted mb-4">
                            <?php if ($current_category && $current_category != 'all'): ?>
                                We couldn't find any subscription products in the <?php echo htmlspecialchars($current_category); ?> category.
                            <?php else: ?>
                                We're currently restocking our subscription products. Please check back soon!
                            <?php endif; ?>
                        </p>
                        <a href="subscriptions.php?category=all" class="btn btn-success">
                            View All Subscriptions
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Subscription FAQ</h5>
                    <a href="faq.php" class="btn btn-outline-success btn-sm">View Full FAQ</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-sync text-success me-2"></i>Can I change my delivery frequency?</h6>
                            <p class="text-muted small mb-3">Yes! You can change your delivery frequency at any time from your subscription settings.</p>
                            
                            <h6><i class="fas fa-times text-success me-2"></i>How do I cancel my subscription?</h6>
                            <p class="text-muted small mb-3">You can cancel anytime with one click. No questions asked.</p>
                            
                            <h6><i class="fas fa-box text-success me-2"></i>Is packaging really plastic-free?</h6>
                            <p class="text-muted small">Absolutely! We use 100% compostable and recyclable materials.</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-percentage text-success me-2"></i>Do I get discounts on subscriptions?</h6>
                            <p class="text-muted small mb-3">Yes! All subscriptions include 15% off and free carbon-neutral shipping.</p>
                            
                            <h6><i class="fas fa-pause text-success me-2"></i>Can I skip a delivery?</h6>
                            <p class="text-muted small mb-3">Yes, you can pause or skip deliveries anytime from your account.</p>
                            
                            <h6><i class="fas fa-leaf text-success me-2"></i>How are EcoPoints calculated?</h6>
                            <p class="text-muted small">You earn 50 points when you subscribe and regular points on each delivery.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* EXACT SAME CSS VARIABLES AS PRODUCTS.PHP */
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

/* EXACT SAME CARD STYLING AS PRODUCTS.PHP */
.card {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: var(--border-radius-cards);
    box-shadow: 0 8px 32px rgba(45, 74, 45, 0.1);
    backdrop-filter: blur(10px);
    margin-bottom: 0;
}

/* EXACT SAME PRODUCT IMAGE CONTAINER AS PRODUCTS.PHP */
.product-image-container {
    position: relative;
    overflow: hidden;
    border-radius: var(--border-radius-cards) var(--border-radius-cards) 0 0;
    height: 250px;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* EXACT SAME BUTTON STYLING AS PRODUCTS.PHP */
.btn-success {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    font-weight: 600;
}

.btn-outline-success {
    border: 2px solid var(--primary-green);
    color: var(--primary-green);
    border-radius: 50px;
}

.btn-outline-success:hover {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
    color: white;
}

/* Environmental Impact - SAME AS PRODUCTS.PHP */
.environmental-impact {
    background: rgba(74, 107, 74, 0.05) !important;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

/* Badges - SAME AS PRODUCTS.PHP */
.badge {
    border-radius: 10px;
    font-weight: 500;
    padding: 6px 12px;
}

.bg-light {
    background-color: rgba(255, 255, 255, 0.7) !important;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

/* Stock Status - SAME AS PRODUCTS.PHP */
.in-stock { background-color: #d4edda !important; color: #155724 !important; }
.low-stock { background-color: #fff3cd !important; color: #856404 !important; }
.out-of-stock { background-color: #f8d7da !important; color: #721c24 !important; }

/* Typography */
.display-4 {
    color: var(--text-green);
    font-weight: 700;
}

.text-success {
    color: var(--primary-green) !important;
}

/* Subscription Items */
.subscription-item {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1rem 0;
}

.subscription-item:last-child {
    border-bottom: none;
}

.delivery-countdown {
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2rem;
    }
    
    .product-image-container {
        height: 200px;
    }
    
    .subscription-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
}
</style>

<script>
// Add to subscription animation - SIMILAR TO PRODUCTS.PHP
document.addEventListener('DOMContentLoaded', function() {
    const subscribeButtons = document.querySelectorAll('button[name="create_subscription"]');
    subscribeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Subscription...';
            this.disabled = true;
            
            // Form will submit automatically
        });
    });
});
</script>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
include 'includes/footer.php'; 
?>