<?php
require_once __DIR__ . '/includes/config.php';

$conn = getDatabaseConnection();
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
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
    $_SESSION['cart_message'] = "Product added to cart!";
    
    // Redirect to prevent form resubmission
    header('Location: products.php' . ($_GET['category'] ? '?category=' . $_GET['category'] : ''));
    exit();
}

// Get category filter
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';

// Build SQL query
if ($category_filter) {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_active = TRUE AND p.category_id = ? 
            ORDER BY p.date_added DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_active = TRUE 
            ORDER BY p.date_added DESC";
    $result = $conn->query($sql);
}

// Get categories for filter
$categories_result = $conn->query("SELECT * FROM categories");
$categories = [];
if ($categories_result !== false) {
while($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}
}

// Get current category name if filtered
$current_category = '';
if ($category_filter) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $category_filter) {
            $current_category = $cat['name'];
            break;
        }
    }
}

$page_title = $current_category ? $current_category . " - Sustainable Products" : "Sustainable Products - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="organic-shape shape-1"></div>
<div class="organic-shape shape-2"></div>
<div class="organic-shape shape-3"></div>

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
    
    <!-- Category Filters -->
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
                        <?php echo $category['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

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
        <div class="products-sort">
            <select class="form-select" id="sortProducts" style="width: auto;">
                <option value="newest">Sort: Newest First</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
                <option value="co2_high">CO₂ Savings: High to Low</option>
                <option value="name">Name: A to Z</option>
            </select>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row g-4" id="productsGrid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($product = $result->fetch_assoc()): 
                $stock_class = $product['stock_quantity'] > 10 ? 'in-stock' : 
                              ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                $stock_text = $product['stock_quantity'] > 10 ? 'In Stock' : 
                             ($product['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
                
                // Different images for different products
                $product_images = [
                    1 => ['includes/products/cleaning1.jpg', 'includes/products/cleaning2.jpg', 'includes/products/cleaning3.jpg'],
                    2 => ['includes/products/kitchen1.jpg', 'includes/products/kitchen2.jpg', 'includes/products/kitchen3.jpg'],
                    3 => ['includes/products/home1.jpg', 'includes/products/home2.jpg', 'includes/products/home3.jpg'],
                    4 => ['includes/products/personal1.jpg', 'includes/products/personal2.jpg', 'includes/products/personal3.jpg'],
                    5 => ['includes/products/garden1.jpg', 'includes/products/garden2.jpg', 'includes/products/garden3.jpg'],
                    6 => ['includes/products/family1.jpg', 'includes/products/family2.jpg', 'includes/products/family3.jpg'],
                    7 => ['includes/products/outdoor1.jpg', 'includes/products/outdoor2.jpg', 'includes/products/outdoor3.jpg']
                ];
                
                $category_id = $product['category_id'];
                $product_id_mod = ($product['product_id'] - 1) % 3;
                $main_image = isset($product_images[$category_id][$product_id_mod]) 
                    ? $product_images[$category_id][$product_id_mod] 
                    : 'includes/products/default.jpg';
                
                // Fallback to your screenshot
                $main_image = 'includes/Screenshot 2025-10-30 145731.png';
            ?>
                <div class="col-lg-4 col-md-6" data-product data-price="<?php echo $product['price']; ?>" 
                     data-co2="<?php echo $product['co2_saved']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card h-100 product-card">
                        <div class="product-image-container">
                            <img src="<?php echo $main_image; ?>" 
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
                                    <strong class="text-success">Saves <?php echo $product['co2_saved']; ?>g CO₂</strong>
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
                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="quantity" value="1">
                                
                                <div class="d-grid gap-2">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button type="submit" class="btn btn-success btn-lg add-to-cart" 
                                                data-product-id="<?php echo $product['product_id']; ?>">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Add to Cart
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
                                We couldn't find any products in the <?php echo htmlspecialchars($current_category); ?> category.
                            <?php else: ?>
                                We're currently restocking our sustainable products collection.
                            <?php endif; ?>
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="products.php" class="btn btn-primary">
                                View All Products
                            </a>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'Admin'): ?>
                                <a href="admin/products.php" class="btn btn-outline-primary">
                                    Add Products
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
                                <div class="display-4 fw-bold"><?php echo $total_co2_saved; ?>g</div>
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

/* Organic Shapes */
.organic-shape {
    position: absolute;
    opacity: 0.25;
    border-radius: 60% 40% 30% 70%;
    animation: float 8s ease-in-out infinite;
    z-index: 1;
}

.shape-1 {
    width: 200px;
    height: 200px;
    background: var(--primary-green);
    top: 10%;
    left: 5%;
    animation-delay: 0s;
}

.shape-2 {
    width: 150px;
    height: 150px;
    background: var(--secondary-green);
    top: 60%;
    right: 10%;
    animation-delay: -2s;
}

.shape-3 {
    width: 180px;
    height: 180px;
    background: var(--text-green);
    bottom: 10%;
    left: 15%;
    animation-delay: -4s;
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
    margin-bottom: 0;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(45, 74, 45, 0.15);
    border: 1px solid rgba(74, 107, 74, 0.1);
}

/* Product Image */
.product-image-container {
    position: relative;
    overflow: hidden;
    border-radius: var(--border-radius-cards) var(--border-radius-cards) 0 0;
}

.product-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

/* Buttons */
.btn-success {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 107, 74, 0.4);
    background: linear-gradient(135deg, var(--secondary-green) 0%, var(--primary-green) 100%);
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
    
    .product-image {
        height: 200px;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .products-sort .form-select {
        width: 100% !important;
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
                        return 0;
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
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.disabled = false;
            }, 2000);
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