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

// Handle add to cart with authentication check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please log in to add items to your cart";
        $_SESSION['redirect_url'] = 'products.php' . (isset($_GET['category']) ? '?category=' . $_GET['category'] : '');
        header('Location: login.php');
        exit();
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validate product exists and is in stock
    $product_check = $conn->prepare("SELECT name, stock_quantity, price FROM products WHERE product_id = ? AND is_active = 1");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows === 0) {
        $_SESSION['error'] = "Product not found or unavailable";
    } else {
        $product_data = $product_result->fetch_assoc();
        
        if ($product_data['stock_quantity'] < $quantity) {
            $_SESSION['error'] = "Not enough stock available. Only " . $product_data['stock_quantity'] . " items left.";
        } else {
            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Add or update item in cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            
            // Set success message
            $_SESSION['cart_message'] = "✓ " . htmlspecialchars($product_data['name']) . " added to cart!";
            
            // Log cart activity if user is logged in
            if (isset($_SESSION['user_id'])) {
                logUserActivity($_SESSION['user_id'], 'cart_add', "Added product ID: $product_id, Quantity: $quantity");
            }
        }
    }
    
    // Simple redirect
    $redirect_url = 'products.php';
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $redirect_url .= '?category=' . urlencode($_GET['category']);
    }
    
    header('Location: ' . $redirect_url);
    exit();
}

// Handle add to wishlist with authentication check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please log in to add items to your wishlist";
        $_SESSION['redirect_url'] = 'products.php' . (isset($_GET['category']) ? '?category=' . $_GET['category'] : '');
        header('Location: login.php');
        exit();
    }
    
    $product_id = intval($_POST['product_id']);
    
    // Validate product exists
    $product_check = $conn->prepare("SELECT name FROM products WHERE product_id = ? AND is_active = 1");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows === 0) {
        $_SESSION['error'] = "Product not found or unavailable";
    } else {
        $product_data = $product_result->fetch_assoc();
        
        // Initialize wishlist if not exists
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }
        
        // Add to wishlist if not already there
        if (!in_array($product_id, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'][] = $product_id;
            $_SESSION['wishlist_message'] = "✓ " . htmlspecialchars($product_data['name']) . " added to wishlist!";
            
            // Log wishlist activity if user is logged in
            if (isset($_SESSION['user_id'])) {
                logUserActivity($_SESSION['user_id'], 'wishlist_add', "Added product ID: $product_id to wishlist");
            }
        } else {
            $_SESSION['wishlist_message'] = "ℹ️ " . htmlspecialchars($product_data['name']) . " is already in your wishlist!";
        }
    }
    
    // Simple redirect
    $redirect_url = 'products.php';
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $redirect_url .= '?category=' . urlencode($_GET['category']);
    }
    
    header('Location: ' . $redirect_url);
    exit();
}

// Get category filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build SQL query
if ($category_filter && is_numeric($category_filter)) {
    $category_filter = intval($category_filter);
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_active = 1 AND p.stock_quantity > 0 AND p.category_id = ? 
            ORDER BY p.date_added DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_active = 1 AND p.stock_quantity > 0
            ORDER BY p.date_added DESC";
    $result = $conn->query($sql);
}

// Get categories for filter
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
if ($category_filter && is_numeric($category_filter)) {
    $category_sql = "SELECT name FROM categories WHERE category_id = ?";
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->bind_param("i", $category_filter);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();
    if ($category_result->num_rows > 0) {
        $current_category = $category_result->fetch_assoc()['name'];
    }
}

$page_title = $current_category ? $current_category . " - Sustainable Products" : "Sustainable Products - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-success mb-4">
                <?php if ($current_category): ?>
                    <?php echo htmlspecialchars($current_category); ?>
                <?php else: ?>
                    Sustainable Products
                <?php endif; ?>
            </h1>
            <p class="lead text-muted">
                <?php if ($current_category): ?>
                    Eco-friendly <?php echo strtolower($current_category); ?> for your sustainable lifestyle
                <?php else: ?>
                    Discover our carefully curated selection of eco-friendly products
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <!-- Success Messages -->
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['cart_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['wishlist_message'])): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-heart me-2"></i>
            <?php echo $_SESSION['wishlist_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['wishlist_message']); ?>
    <?php endif; ?>
    
    <!-- Error Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Authentication Reminder for Guests -->
    <?php if (!isLoggedIn()): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Heads up!</strong> You need to <a href="login.php" class="alert-link">log in</a> to add items to your cart or wishlist.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Category Filters -->
    <?php if (!empty($categories)): ?>
    <div class="card mb-5">
        <div class="card-body">
            <h5 class="card-title mb-3">Filter by Category</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="products.php" class="btn btn-outline-primary <?php echo !$category_filter ? 'active' : ''; ?>">
                    All Products
                </a>
                <?php foreach($categories as $category): ?>
                    <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                       class="btn btn-outline-primary <?php echo $category_filter == $category['category_id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products Count and Sort -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="products-count">
            <p class="text-muted mb-0">
                <?php 
                $product_count = $result->num_rows;
                if ($current_category) {
                    echo "Showing <strong>$product_count</strong> product" . ($product_count != 1 ? 's' : '') . " in <strong>$current_category</strong>";
                } else {
                    echo "Showing <strong>$product_count</strong> sustainable product" . ($product_count != 1 ? 's' : '') . "";
                }
                ?>
            </p>
        </div>
        <?php if ($product_count > 0): ?>
        <div class="products-sort">
            <select class="form-select" id="sortProducts" style="width: auto;">
                <option value="newest">Sort: Newest First</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
                <option value="co2_high">CO₂ Savings: High to Low</option>
                <option value="name">Name: A to Z</option>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <!-- Products Grid -->
    <div class="row g-4" id="productsGrid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($product = $result->fetch_assoc()): 
                $stock_class = $product['stock_quantity'] > 10 ? 'in-stock' : 
                              ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                $stock_text = $product['stock_quantity'] > 10 ? 'In Stock' : 
                             ($product['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
                
                // Check if product is in wishlist
                $in_wishlist = false;
                if (isset($_SESSION['wishlist']) && in_array($product['product_id'], $_SESSION['wishlist'])) {
                    $in_wishlist = true;
                }
                
                // FIXED: Use actual product images from database
                $main_image = 'includes/Screenshot 2025-10-30 145731.png'; // Fallback image
                if (!empty($product['image_path'])) {
                    $main_image = $product['image_path'];
                } elseif (!empty($product['image_url'])) {
                    $main_image = $product['image_url'];
                }
            ?>
                <div class="col-lg-4 col-md-6" data-product data-price="<?php echo $product['price']; ?>" 
                     data-co2="<?php echo $product['co2_saved']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>"
                     data-date="<?php echo strtotime($product['date_added']); ?>">
                    <div class="card h-100 product-card">
                        <div class="product-image-container position-relative">
                            <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="card-img-top product-image"
                                 onerror="this.src='includes/Screenshot 2025-10-30 145731.png'">
                            
                            <!-- Wishlist Button -->
                            <form method="POST" action="products.php<?php echo $category_filter ? '?category=' . $category_filter : ''; ?>" class="position-absolute top-0 end-0 m-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="add_to_wishlist" value="1">
                                <button type="submit" class="btn wishlist-btn <?php echo $in_wishlist ? 'btn-danger' : 'btn-light'; ?> rounded-circle shadow-sm" 
                                        <?php echo !isLoggedIn() ? 'disabled title="Please log in to add to wishlist"' : ''; ?>>
                                    <i class="fas fa-heart"></i>
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
                            
                            <!-- Add to Cart Form -->
                            <form method="POST" action="products.php<?php echo $category_filter ? '?category=' . $category_filter : ''; ?>" class="mt-auto">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="quantity" value="1">
                                
                                <div class="d-grid gap-2">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button type="submit" class="btn btn-success btn-lg add-to-cart" 
                                                data-product-id="<?php echo $product['product_id']; ?>"
                                                <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            <?php echo isLoggedIn() ? 'Add to Cart' : 'Login to Add to Cart'; ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary btn-lg" disabled>
                                            <i class="fas fa-times me-2"></i>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-info-circle me-2"></i>
                                        View Details
                                    </a>
                                </div>
                            </form>
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
                            <?php if ($current_category): ?>
                                We couldn't find any active products in the <?php echo htmlspecialchars($current_category); ?> category.
                            <?php else: ?>
                                We're currently restocking our sustainable products collection. Please check back soon!
                            <?php endif; ?>
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="products.php" class="btn btn-primary">
                                View All Products
                            </a>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'Admin'): ?>
                                <a href="admin/productmanagement.php" class="btn btn-outline-primary">
                                    Manage Products
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Eco Impact Summary -->
    <?php if ($result->num_rows > 0): ?>
        <?php
        // Calculate total environmental impact
        $result->data_seek(0);
        $total_co2_saved = 0;
        $total_products = 0;
        while($product = $result->fetch_assoc()) {
            $total_co2_saved += $product['co2_saved'];
            $total_products++;
        }
        $trees_equivalent = ceil($total_co2_saved / 20);
        ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-4">
                        <h4 class="card-title mb-4">🌍 Collective Environmental Impact</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="display-4 fw-bold"><?php echo $total_products; ?></div>
                                <p class="mb-0">Sustainable Products</p>
                            </div>
                            <div class="col-md-4">
                                <div class="display-4 fw-bold"><?php echo $total_co2_saved; ?>kg</div>
                                <p class="mb-0">CO₂ Savings Potential</p>
                            </div>
                            <div class="col-md-4">
                                <div class="display-4 fw-bold"><?php echo $trees_equivalent; ?></div>
                                <p class="mb-0">Trees Equivalent</p>
                            </div>
                        </div>
                        <p class="mt-4 mb-0 opacity-75">
                            Every purchase contributes to a healthier planet. Thank you for choosing sustainable alternatives!
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

/* Wishlist Button */
.wishlist-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
}

.wishlist-btn:hover:not(:disabled) {
    transform: scale(1.1);
}

.wishlist-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Buttons */
.btn-success {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    font-weight: 600;
}

.btn-success:disabled {
    background: #6c757d;
    cursor: not-allowed;
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
    
    .products-sort .form-select {
        width: 100% !important;
    }
    
    .wishlist-btn {
        width: 35px;
        height: 35px;
    }
}
</style>

<script>
// Product sorting functionality
document.addEventListener('DOMContentLoaded', function() {
    const sortSelect = document.getElementById('sortProducts');
    const productsGrid = document.getElementById('productsGrid');
    
    if (sortSelect && productsGrid) {
        sortSelect.addEventListener('change', function() {
            const products = Array.from(productsGrid.querySelectorAll('[data-product]'));
            
            products.sort((a, b) => {
                const priceA = parseFloat(a.getAttribute('data-price'));
                const priceB = parseFloat(b.getAttribute('data-price'));
                const co2A = parseFloat(a.getAttribute('data-co2'));
                const co2B = parseFloat(b.getAttribute('data-co2'));
                const nameA = a.getAttribute('data-name').toLowerCase();
                const nameB = b.getAttribute('data-name').toLowerCase();
                const dateA = parseInt(a.getAttribute('data-date'));
                const dateB = parseInt(b.getAttribute('data-date'));
                
                switch(this.value) {
                    case 'price_low':
                        return priceA - priceB;
                    case 'price_high':
                        return priceB - priceA;
                    case 'co2_high':
                        return co2B - co2A;
                    case 'name':
                        return nameA.localeCompare(nameB);
                    default: // newest
                        return dateB - dateA;
                }
            });
            
            // Clear and re-append sorted products
            productsGrid.innerHTML = '';
            products.forEach(product => {
                productsGrid.appendChild(product);
            });
        });
    }
    
    // Add to cart animation
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // If button is disabled (user not logged in), prevent form submission
            if (this.disabled) {
                e.preventDefault();
                window.location.href = 'login.php';
                return;
            }
            
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.disabled = false;
            }, 2000);
        });
    });
    
    // Wishlist button handling for disabled state
    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.disabled) {
                e.preventDefault();
                window.location.href = 'login.php';
            }
        });
    });
});
</script>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
require_once __DIR__ . '/includes/footer.php'; 
?>