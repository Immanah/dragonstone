<?php
// Start session at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$conn = getDatabaseConnection();
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $product_id = intval($_POST['product_id']);
    
    if (isset($_SESSION['wishlist']) && !empty($_SESSION['wishlist'])) {
        $key = array_search($product_id, $_SESSION['wishlist']);
        if ($key !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Reindex array
            $_SESSION['wishlist_message'] = "Product removed from wishlist!";
        }
    }
    
    header('Location: wishlist.php');
    exit();
}

// Handle move to cart from wishlist - FIXED VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add to cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    // Remove from wishlist
    if (isset($_SESSION['wishlist']) && !empty($_SESSION['wishlist'])) {
        $key = array_search($product_id, $_SESSION['wishlist']);
        if ($key !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        }
    }
    
    $_SESSION['cart_message'] = "Product moved to cart!";
    header('Location: wishlist.php');
    exit();
}

// Handle clear wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_wishlist'])) {
    $_SESSION['wishlist'] = [];
    $_SESSION['wishlist_message'] = "Wishlist cleared!";
    header('Location: wishlist.php');
    exit();
}

// Get wishlist products
$wishlist_products = [];
$total_co2_saved = 0;
$total_value = 0;

if (isset($_SESSION['wishlist']) && !empty($_SESSION['wishlist'])) {
    $placeholders = str_repeat('?,', count($_SESSION['wishlist']) - 1) . '?';
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id IN ($placeholders) AND p.is_active = 1
            ORDER BY p.date_added DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $types = str_repeat('i', count($_SESSION['wishlist']));
    $stmt->bind_param($types, ...$_SESSION['wishlist']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while($product = $result->fetch_assoc()) {
            $wishlist_products[] = $product;
            $total_co2_saved += $product['co2_saved'];
            $total_value += $product['price'];
        }
    }
}

$page_title = "My Wishlist - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-success mb-4">
                <i class="fas fa-heart me-3"></i>My Wishlist
            </h1>
            <p class="lead text-muted">
                Your curated collection of sustainable favorites
            </p>
        </div>
    </div>
    
    <!-- Success Messages -->
    <?php if (isset($_SESSION['wishlist_message'])): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo $_SESSION['wishlist_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['wishlist_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['cart_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>

    <!-- Wishlist Summary -->
    <?php if (!empty($wishlist_products)): ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="display-6 fw-bold text-success"><?php echo count($wishlist_products); ?></div>
                            <p class="mb-0 text-muted">Items in Wishlist</p>
                        </div>
                        <div class="col-md-4">
                            <div class="display-6 fw-bold text-success">R<?php echo number_format($total_value, 2); ?></div>
                            <p class="mb-0 text-muted">Total Value</p>
                        </div>
                        <div class="col-md-4">
                            <div class="display-6 fw-bold text-success"><?php echo $total_co2_saved; ?>kg</div>
                            <p class="mb-0 text-muted">CO₂ Savings</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Wishlist Actions -->
    <?php if (!empty($wishlist_products)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Your Wishlisted Items</h5>
                </div>
                <div>
                    <form method="POST" action="wishlist.php" class="d-inline">
                        <input type="hidden" name="clear_wishlist" value="1">
                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                onclick="return confirm('Are you sure you want to clear your entire wishlist?')">
                            <i class="fas fa-trash me-2"></i>Clear Wishlist
                        </button>
                    </form>
                    <a href="products.php" class="btn btn-success btn-sm ms-2">
                        <i class="fas fa-plus me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Wishlist Products -->
    <div class="row g-4">
        <?php if (!empty($wishlist_products)): ?>
            <?php foreach($wishlist_products as $product): 
                $stock_class = $product['stock_quantity'] > 10 ? 'in-stock' : 
                              ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                $stock_text = $product['stock_quantity'] > 10 ? 'In Stock' : 
                             ($product['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
                
                $main_image = 'includes/Screenshot 2025-10-30 145731.png';
                if (!empty($product['image_path'])) {
                    $main_image = $product['image_path'];
                } elseif (!empty($product['image_url'])) {
                    $main_image = $product['image_url'];
                }
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 product-card">
                        <div class="product-image-container position-relative">
                            <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="card-img-top product-image"
                                 onerror="this.src='includes/Screenshot 2025-10-30 145731.png'">
                            
                            <!-- Remove from Wishlist Button -->
                            <form method="POST" action="wishlist.php" class="position-absolute top-0 end-0 m-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="remove_from_wishlist" value="1">
                                <button type="submit" class="btn btn-danger rounded-circle shadow-sm wishlist-remove-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <!-- Category Badge -->
                            <div class="mb-2">
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            
                            <!-- Product Name -->
                            <h5 class="card-title">
                                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
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
                                    <span class="h4 text-success mb-0">R<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="badge <?php echo $stock_class == 'in-stock' ? 'bg-success' : ($stock_class == 'low-stock' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo $stock_text; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons - FIXED: Added proper classes for JavaScript -->
                            <div class="d-grid gap-2">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <form method="POST" action="wishlist.php" class="d-grid">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="move_to_cart" value="1">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-success btn-lg add-to-cart-btn">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Move to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
                                        <i class="fas fa-times me-2"></i>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-outline-primary flex-fill">
                                        <i class="fas fa-info-circle me-2"></i>
                                        View Details
                                    </a>
                                    <form method="POST" action="wishlist.php" class="flex-fill">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="remove_from_wishlist" value="1">
                                        <button type="submit" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-trash me-2"></i>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty Wishlist State -->
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                        <h3>Your Wishlist is Empty</h3>
                        <p class="text-muted mb-4">
                            Start building your sustainable collection by adding products you love to your wishlist.
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="products.php" class="btn btn-success btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Explore Products
                            </a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="profile.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-user me-2"></i>
                                    View Profile
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Environmental Impact Summary -->
    <?php if (!empty($wishlist_products)): ?>
        <?php
        $trees_equivalent = ceil($total_co2_saved / 20);
        ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-4">
                        <h4 class="card-title mb-4">🌍 Your Wishlist's Environmental Impact</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="display-4 fw-bold"><?php echo count($wishlist_products); ?></div>
                                <p class="mb-0">Sustainable Choices</p>
                            </div>
                            <div class="col-md-4">
                                <div class="display-4 fw-bold"><?php echo $total_co2_saved; ?>kg</div>
                                <p class="mb-0">Potential CO₂ Savings</p>
                            </div>
                            <div class="col-md-4">
                                <div class="display-4 fw-bold"><?php echo $trees_equivalent; ?></div>
                                <p class="mb-0">Trees Equivalent</p>
                            </div>
                        </div>
                        <p class="mt-4 mb-0 opacity-75">
                            By choosing these sustainable products, you're making a positive impact on our planet!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

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

/* Organic Shapes - Static */
.organic-shape {
    position: absolute;
    opacity: 0.15;
    border-radius: 60% 40% 30% 70%;
    z-index: 1;
}

.shape-1 {
    width: 200px;
    height: 200px;
    background: var(--primary-green);
    top: 10%;
    left: 5%;
}

.shape-2 {
    width: 150px;
    height: 150px;
    background: var(--secondary-green);
    top: 60%;
    right: 10%;
}

.shape-3 {
    width: 180px;
    height: 180px;
    background: var(--text-green);
    bottom: 10%;
    left: 15%;
}

/* Cards - Static */
.card {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: var(--border-radius-cards);
    box-shadow: 0 8px 32px rgba(45, 74, 45, 0.1);
    backdrop-filter: blur(10px);
    margin-bottom: 0;
}

/* Product Image */
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

/* Wishlist Remove Button */
.wishlist-remove-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
}

/* Buttons */
.btn-success {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    font-weight: 600;
}

.btn-outline-primary {
    border: 2px solid var(--primary-green);
    color: var(--primary-green);
    border-radius: 50px;
}

.btn-outline-primary:hover {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
    color: white;
}

.btn-outline-danger {
    border: 2px solid #dc3545;
    color: #dc3545;
    border-radius: 50px;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

/* Environmental Impact */
.environmental-impact {
    background: rgba(74, 107, 74, 0.05) !important;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

/* Badges */
.badge {
    border-radius: 10px;
    font-weight: 500;
    padding: 6px 12px;
}

.bg-light {
    background-color: rgba(255, 255, 255, 0.7) !important;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

/* Stock Status */
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

/* Grid Spacing */
.row.g-4 {
    margin-bottom: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2rem;
    }
    
    .product-image-container {
        height: 200px;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .wishlist-remove-btn {
        width: 35px;
        height: 35px;
    }
    
    .btn-lg {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}
</style>

<!-- Wishlist Specific JavaScript -->
<script>
// Enhanced wishlist functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('❤️ Initializing wishlist functionality...');
    
    // Add loading states to wishlist buttons
    const moveToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    moveToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Moving...';
            this.disabled = true;
            
            // Let the form submit naturally
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.disabled = false;
            }, 2000);
        });
    });
    
    // Add loading to remove buttons
    const removeButtons = document.querySelectorAll('.wishlist-remove-btn, .btn-outline-danger');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.disabled = false;
            }, 1500);
        });
    });
    
    // Confirm before clearing wishlist
    const clearWishlistBtn = document.querySelector('button[type="submit"][onclick]');
    if (clearWishlistBtn) {
        clearWishlistBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    }
});

// Update cart count when items are moved to cart
function updateCartAfterMove() {
    // This will be called by the header's updateCartCount function
    console.log('Updating cart count after moving item from wishlist...');
}
</script>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
require_once __DIR__ . '/includes/footer.php'; 
?>