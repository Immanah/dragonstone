<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

$product_id = $_GET['id'] ?? 0;

// Get product details
$product_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE p.product_id = ? AND p.is_active = TRUE";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Handle review submission
if (isset($_POST['submit_review']) && isLoggedIn()) {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    // Check if user already reviewed this product
    $check_sql = "SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows == 0) {
        // Insert review
        $review_sql = "INSERT INTO reviews (user_id, product_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 1)";
        $review_stmt = $conn->prepare($review_sql);
        $review_stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
        
        if ($review_stmt->execute()) {
            // Award EcoPoints for review
            $points_sql = "INSERT INTO eco_point_transactions (user_id, points, transaction_type, reason) VALUES (?, 25, 'Earned', 'Product Review')";
            $points_stmt = $conn->prepare($points_sql);
            $points_stmt->bind_param("i", $user_id);
            $points_stmt->execute();
            
            // Update user's EcoPoints balance
            $update_sql = "UPDATE users SET eco_points_balance = eco_points_balance + 25 WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            
            $review_success = "Thank you for your review! +25 EcoPoints awarded!";
        }
    } else {
        $review_error = "You have already reviewed this product.";
    }
}

// Get product reviews
$reviews_sql = "SELECT r.*, u.first_name, u.last_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                WHERE r.product_id = ? AND r.is_approved = 1 
                ORDER BY r.review_date DESC";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

// Calculate average rating
$avg_rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                   FROM reviews 
                   WHERE product_id = ? AND is_approved = 1";
$avg_stmt = $conn->prepare($avg_rating_sql);
$avg_stmt->bind_param("i", $product_id);
$avg_stmt->execute();
$rating_data = $avg_stmt->get_result()->fetch_assoc();

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
    width: 150px;
    height: 150px;
    background: var(--primary-green);
    top: 15%;
    left: 8%;
    animation-delay: 0s;
}

.shape-2 {
    width: 120px;
    height: 120px;
    background: var(--secondary-green);
    top: 70%;
    right: 12%;
    animation-delay: -3s;
}

.shape-3 {
    width: 100px;
    height: 100px;
    background: var(--text-green);
    bottom: 20%;
    left: 20%;
    animation-delay: -6s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-15px) rotate(5deg);
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

.form-check-input:checked {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
}

/* Badges */
.badge {
    border-radius: 10px;
    font-weight: 500;
    padding: 6px 12px;
}

.bg-primary { background-color: var(--primary-green) !important; }
.bg-info { background: rgba(74, 107, 74, 0.1) !important; color: var(--text-green); border: 1px solid rgba(74, 107, 74, 0.2); }

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

/* Breadcrumb */
.breadcrumb {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 1rem;
    backdrop-filter: blur(10px);
}

.breadcrumb-item a {
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb-item.active {
    color: var(--text-green);
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

.text-warning {
    color: #e9b949 !important;
}

.fs-1 {
    font-size: 4rem !important;
}

.fs-4 {
    font-size: 1.5rem !important;
}

/* Product specific styles */
.carbon-display {
    background: linear-gradient(135deg, rgba(90, 122, 90, 0.1), rgba(74, 107, 74, 0.05));
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 2px solid rgba(90, 122, 90, 0.2);
    transition: all 0.3s ease;
}

.carbon-display:hover {
    transform: translateY(-3px);
    border-color: var(--secondary-green);
}

.reviews-list .border-bottom {
    border-bottom: 1px solid rgba(74, 107, 74, 0.1) !important;
    transition: all 0.3s ease;
}

.reviews-list .border-bottom:hover {
    background-color: rgba(74, 107, 74, 0.05);
    transform: translateX(5px);
}

.bg-light {
    background: rgba(255, 255, 255, 0.7) !important;
    border-radius: 15px;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

/* Star ratings */
.text-warning {
    color: #e9b949 !important;
}

/* Form check inline spacing */
.form-check-inline {
    margin-right: 1rem;
}

/* Grid spacing */
.g-2 {
    gap: 0.5rem;
}

/* Stock status */
.stock-success {
    color: var(--primary-green) !important;
    font-weight: 600;
}

.stock-warning {
    color: #e9b949 !important;
    font-weight: 600;
}
</style>

<div class="organic-shape shape-1"></div>
<div class="organic-shape shape-2"></div>
<div class="organic-shape shape-3"></div>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images & Info -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="fs-1 mb-3">
                        <?php 
                        $icons = ['🧼', '🍴', '🏠', '🚿', '🌿', '👶', '🌳'];
                        echo $icons[$product['category_id']-1] ?? '📦'; 
                        ?>
                    </div>
                    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    
                    <!-- Rating -->
                    <?php if ($rating_data['review_count'] > 0): ?>
                        <div class="mb-3">
                            <div class="fs-4">
                                <?php
                                $avg_rating = round($rating_data['avg_rating'], 1);
                                $full_stars = floor($avg_rating);
                                $half_star = ($avg_rating - $full_stars) >= 0.5;
                                
                                for ($i = 0; $i < $full_stars; $i++) echo '⭐';
                                if ($half_star) echo '⭐';
                                ?>
                            </div>
                            <small class="text-muted"><?php echo $avg_rating; ?> (<?php echo $rating_data['review_count']; ?> reviews)</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Environmental Impact -->
                    <div class="carbon-display mb-4">
                        <h4 class="text-success">🌱 <?php echo $product['co2_saved']; ?>kg CO2 Saved</h4>
                        <p class="text-muted">Compared to conventional alternatives</p>
                    </div>
                    
                    <!-- Price & Stock -->
                    <div class="mb-4">
                        <h3 class="text-success">R<?php echo number_format($product['price'], 2); ?></h3>
                        <p class="<?php echo $product['stock_quantity'] > 10 ? 'stock-success' : 'stock-warning'; ?>">
                            <?php echo $product['stock_quantity']; ?> in stock
                        </p>
                    </div>
                    
                    <!-- Add to Cart -->
                    <form method="POST" action="cart.php" class="mb-3">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <input type="hidden" name="add_to_cart" value="1">
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            </div>
                            <div class="col-8">
                                <button type="submit" class="btn btn-dragon w-100">Add to Cart</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- EcoPoints Info -->
                    <div class="alert alert-info">
                        <small>🎁 Earn <strong>75 EcoPoints</strong> when you purchase this item!</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Details & Reviews -->
        <div class="col-md-6">
            <!-- Product Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Product Details</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <small><strong>Category:</strong><br><?php echo htmlspecialchars($product['category_name']); ?></small>
                        </div>
                        <div class="col-6">
                            <small><strong>SKU:</strong><br>DRG<?php echo str_pad($product['product_id'], 3, '0', STR_PAD_LEFT); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Customer Reviews</h5>
                    <span class="badge bg-primary"><?php echo $rating_data['review_count']; ?> reviews</span>
                </div>
                <div class="card-body">
                    <!-- Review Form -->
                    <?php if (isLoggedIn()): ?>
                        <div class="mb-4 p-3 bg-light rounded">
                            <h6>Write a Review</h6>
                            <?php if (isset($review_success)): ?>
                                <div class="alert alert-success"><?php echo $review_success; ?></div>
                            <?php endif; ?>
                            <?php if (isset($review_error)): ?>
                                <div class="alert alert-danger"><?php echo $review_error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="rating" value="<?php echo $i; ?>" id="rating<?php echo $i; ?>" required>
                                                <label class="form-check-label" for="rating<?php echo $i; ?>">
                                                    <?php echo str_repeat('⭐', $i); ?>
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Your Review</label>
                                    <textarea class="form-control" name="comment" rows="3" placeholder="Share your experience with this product..." required></textarea>
                                </div>
                                <button type="submit" name="submit_review" class="btn btn-dragon btn-sm">Submit Review +25 Points</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <a href="login.php" class="btn btn-sm btn-outline-primary">Login to write a review</a>
                            <small class="d-block mt-1">Earn 25 EcoPoints for each review!</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php if ($reviews->num_rows > 0): ?>
                            <?php while($review = $reviews->fetch_assoc()): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                            <div class="text-warning">
                                                <?php echo str_repeat('⭐', $review['rating']); ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($review['review_date'])); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
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