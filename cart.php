<?php

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$conn = getDatabaseConnection();

// Handle add to cart (from POST requests)
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
    header('Location: ' . ($_POST['return_url'] ?? 'cart.php'));
    exit();
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['cart_message'] = "Product removed from cart!";
    }
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $_SESSION['cart_message'] = "Cart updated!";
    }
    header('Location: cart.php');
    exit();
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['cart_message'] = "Cart cleared!";
    header('Location: cart.php');
    exit();
}

// Get cart products
$cart_items = [];
$total_amount = 0;
$total_items = 0;
$total_eco_points = 0;
$total_co2_saved = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id IN ($placeholders) AND is_active = TRUE");
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($product_ids));
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($product = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$product['product_id']];
        $subtotal = $product['price'] * $quantity;
        $total_amount += $subtotal;
        $total_items += $quantity;
        $total_co2_saved += $product['co2_saved'] * $quantity;
        $total_eco_points += ($product['co2_saved'] * $quantity) / 10; // 10g CO2 = 1 point
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
    $stmt->close();
}

$page_title = "Shopping Cart - " . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<style>
:root {
    --bg-sandy-light: #d4c4a8;
    --bg-sandy-dark: #c2b299;
    --primary-green: #4a6b4a;
    --secondary-green: #5a7a5a;
    --text-green: #2d4a2d;
    --surface-white: #ffffff;
    --border-radius-soft: 30px;
    --border-radius-softer: 50px;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--bg-sandy-light), var(--bg-sandy-dark));
    color: var(--text-green);
    min-height: 100vh;
}

.eco-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.eco-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius-soft);
    box-shadow: 0 8px 32px rgba(45, 74, 45, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    overflow: hidden;
}

.eco-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(45, 74, 45, 0.15);
}

.eco-card-header {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 20px 30px;
    border-bottom: none;
}

.eco-card-body {
    padding: 30px;
}

.eco-btn {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    border: none;
    border-radius: var(--border-radius-soft);
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(74, 107, 74, 0.2);
}

.eco-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 107, 74, 0.3);
    background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
    color: white;
}

.eco-btn-outline {
    background: transparent;
    border: 2px solid var(--primary-green);
    color: var(--primary-green);
    border-radius: var(--border-radius-soft);
    padding: 10px 22px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.eco-btn-outline:hover {
    background: var(--primary-green);
    color: white;
    transform: translateY(-2px);
}

.eco-btn-danger {
    background: transparent;
    border: 2px solid #dc3545;
    color: #dc3545;
    border-radius: var(--border-radius-soft);
    padding: 10px 22px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.eco-btn-danger:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
}

.eco-badge {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    border-radius: 20px;
    padding: 5px 15px;
    font-size: 0.85rem;
    font-weight: 600;
}

.eco-badge-secondary {
    background: rgba(90, 122, 90, 0.15);
    color: var(--secondary-green);
    border-radius: 20px;
    padding: 5px 15px;
    font-size: 0.85rem;
    font-weight: 600;
}

.eco-shape {
    position: absolute;
    border-radius: 60% 40% 30% 70%;
    opacity: 0.25;
    z-index: -1;
    animation: float 8s ease-in-out infinite;
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
    animation-delay: 2s;
}

.shape-3 {
    width: 100px;
    height: 100px;
    background: var(--text-green);
    bottom: 10%;
    left: 15%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(5deg);
    }
}

.eco-product-icon {
    font-size: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: rgba(74, 107, 74, 0.1);
    border-radius: 50%;
    margin-right: 20px;
}

.eco-impact-stats {
    display: flex;
    justify-content: space-around;
    text-align: center;
    margin: 30px 0;
    padding: 20px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: var(--border-radius-soft);
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.eco-stat-item {
    flex: 1;
    padding: 10px;
}

.eco-stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-green);
    margin-bottom: 5px;
}

.eco-stat-label {
    font-size: 0.9rem;
    color: var(--text-green);
    opacity: 0.8;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(74, 107, 74, 0.1);
    border: 1px solid rgba(74, 107, 74, 0.2);
    color: var(--primary-green);
    transition: all 0.2s ease;
}

.quantity-btn:hover {
    background: var(--primary-green);
    color: white;
}

.quantity-input {
    width: 60px;
    text-align: center;
    border-radius: 20px;
    border: 1px solid rgba(74, 107, 74, 0.3);
    padding: 8px;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 25px;
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    transition: all 0.3s ease;
}

.cart-item:hover {
    background: rgba(74, 107, 74, 0.05);
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 5px;
    color: var(--text-green);
}

.cart-item-desc {
    color: var(--text-green);
    opacity: 0.7;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.cart-item-price {
    font-weight: 700;
    color: var(--primary-green);
    font-size: 1.1rem;
}

.cart-item-subtotal {
    font-weight: 700;
    color: var(--text-green);
    font-size: 1.1rem;
}

.eco-alert {
    border-radius: var(--border-radius-soft);
    border: none;
    padding: 15px 20px;
}

.eco-alert-success {
    background: rgba(74, 107, 74, 0.1);
    color: var(--text-green);
    border-left: 4px solid var(--primary-green);
}

.eco-alert-info {
    background: rgba(90, 122, 90, 0.1);
    color: var(--text-green);
    border-left: 4px solid var(--secondary-green);
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
}

.empty-cart-icon {
    font-size: 5rem;
    margin-bottom: 20px;
    opacity: 0.7;
}

.empty-cart-title {
    font-size: 1.8rem;
    margin-bottom: 15px;
    color: var(--text-green);
}

.empty-cart-text {
    font-size: 1.1rem;
    color: var(--text-green);
    opacity: 0.7;
    margin-bottom: 30px;
}

.shipping-progress {
    height: 8px;
    background: rgba(74, 107, 74, 0.1);
    border-radius: 4px;
    margin: 15px 0;
    overflow: hidden;
}

.shipping-progress-bar {
    height: 100%;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    border-radius: 4px;
    transition: width 0.5s ease;
}

.sticky-summary {
    position: sticky;
    top: 120px;
}

@media (max-width: 768px) {
    .eco-container {
        padding: 10px;
    }
    
    .eco-card-body {
        padding: 20px;
    }
    
    .cart-item {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px;
    }
    
    .eco-product-icon {
        margin-bottom: 15px;
        margin-right: 0;
    }
    
    .quantity-control {
        margin: 15px 0;
    }
    
    .eco-impact-stats {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<div class="eco-container py-4">
    <!-- Floating Organic Shapes -->
    <div class="eco-shape shape-1"></div>
    <div class="eco-shape shape-2"></div>
    <div class="eco-shape shape-3"></div>
    
    <!-- Cart Message -->
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="eco-alert eco-alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>
                <div><?php echo $_SESSION['cart_message']; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>
    
    <h1 class="text-center mb-4" style="font-size: 42px; color: var(--text-green);">🛒 Shopping Cart</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="eco-card empty-cart">
            <div class="empty-cart-icon">🌱</div>
            <h2 class="empty-cart-title">Your cart is empty</h2>
            <p class="empty-cart-text">Discover our sustainable products and start your eco-friendly journey!</p>
            <a href="products.php" class="eco-btn">
                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <!-- Environmental Impact Stats -->
        <div class="eco-impact-stats">
            <div class="eco-stat-item">
                <div class="eco-stat-value"><?php echo $total_items; ?></div>
                <div class="eco-stat-label">Items in Cart</div>
            </div>
            <div class="eco-stat-item">
                <div class="eco-stat-value"><?php echo number_format($total_co2_saved); ?>g</div>
                <div class="eco-stat-label">CO₂ Saved</div>
            </div>
            <div class="eco-stat-item">
                <div class="eco-stat-value">+<?php echo number_format($total_eco_points); ?></div>
                <div class="eco-stat-label">EcoPoints</div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="eco-card">
                    <div class="eco-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Cart Items (<?php echo $total_items; ?> items)</h5>
                        <a href="cart.php?clear_cart=1" class="eco-btn-danger btn-sm" 
                           onclick="return confirm('Are you sure you want to clear your cart?')">
                            <i class="fas fa-trash me-1"></i>Clear Cart
                        </a>
                    </div>
                    <div class="eco-card-body p-0">
                        <?php foreach($cart_items as $item): 
                            $product = $item['product'];
                            $icons = ['🧼', '🍴', '🏠', '🚿', '🌿', '👶', '🌳'];
                            $icon = $icons[$product['category_id']-1] ?? '📦';
                            
                            // Safely check for product attributes with fallbacks
                            $is_vegan = isset($product['is_vegan']) ? $product['is_vegan'] : false;
                            $is_biodegradable = isset($product['is_biodegradable']) ? $product['is_biodegradable'] : false;
                            $co2_saved = isset($product['co2_saved']) ? $product['co2_saved'] : 0;
                        ?>
                            <div class="cart-item">
                                <div class="eco-product-icon">
                                    <?php echo $icon; ?>
                                </div>
                                <div class="cart-item-details">
                                    <div class="cart-item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="cart-item-desc"><?php echo htmlspecialchars($product['description']); ?></div>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                                        <span class="eco-badge">
                                            <i class="fas fa-leaf me-1"></i>
                                            Saves <?php echo $co2_saved; ?>g CO₂
                                        </span>
                                        <?php if ($is_vegan): ?>
                                            <span class="eco-badge-secondary">🌱 Vegan</span>
                                        <?php endif; ?>
                                        <?php if ($is_biodegradable): ?>
                                            <span class="eco-badge-secondary">♻️ Biodegradable</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="cart-item-price me-4">
                                    R<?php echo number_format($product['price'], 2); ?>
                                </div>
                                <div class="quantity-control me-4">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <button type="button" class="quantity-btn" data-action="decrease" data-product="<?php echo $product['product_id']; ?>">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" 
                                               class="quantity-input" data-product="<?php echo $product['product_id']; ?>">
                                        <button type="button" class="quantity-btn" data-action="increase" data-product="<?php echo $product['product_id']; ?>">+</button>
                                        <button type="submit" name="update_quantity" class="quantity-btn ms-2" title="Update Quantity">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <div class="cart-item-subtotal mb-2">
                                        R<?php echo number_format($item['subtotal'], 2); ?>
                                    </div>
                                    <a href="cart.php?remove=<?php echo $product['product_id']; ?>" 
                                       class="quantity-btn"
                                       onclick="return confirm('Remove this item from cart?')"
                                       title="Remove Item">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="eco-card sticky-summary">
                    <div class="eco-card-header">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                    </div>
                    <div class="eco-card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal (<?php echo $total_items; ?> items):</span>
                            <span class="fw-bold">R<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span class="<?php echo $total_amount > 500 ? 'text-success fw-bold' : ''; ?>">
                                    <?php echo $total_amount > 500 ? 'FREE' : 'R49.00'; ?>
                                </span>
                            </div>
                            
                            <?php if ($total_amount > 500): ?>
                                <div class="eco-alert eco-alert-success">
                                    <i class="fas fa-truck me-2"></i>
                                    You qualify for FREE shipping!
                                </div>
                            <?php else: ?>
                                <div class="shipping-progress">
                                    <div class="shipping-progress-bar" style="width: <?php echo min(100, ($total_amount / 500) * 100); ?>%"></div>
                                </div>
                                <div class="eco-alert eco-alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Spend R<?php echo number_format(500 - $total_amount, 2); ?> more for free shipping!
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>EcoPoints to earn:</span>
                            <span class="fw-bold text-success">+<?php echo number_format($total_eco_points); ?> pts</span>
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong class="fs-5">Total:</strong>
                            <strong class="fs-5 text-success">
                                R<?php echo number_format($total_amount + ($total_amount > 500 ? 0 : 49), 2); ?>
                            </strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="eco-btn btn-lg">
                                <i class="fas fa-lock me-2"></i>Proceed to Checkout
                            </a>
                            <a href="products.php" class="eco-btn-outline">
                                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                            </a>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Secure checkout · 30-day returns
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Quantity update functionality
document.addEventListener('DOMContentLoaded', function() {
    // Quantity buttons
    const quantityButtons = document.querySelectorAll('.quantity-btn');
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    quantityButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const productId = this.getAttribute('data-product');
            const input = document.querySelector(`.quantity-input[data-product="${productId}"]`);
            
            if (!input) return;
            
            let value = parseInt(input.value);
            
            if (action === 'increase') {
                value++;
            } else if (action === 'decrease' && value > 1) {
                value--;
            }
            
            input.value = value;
            
            // Auto-submit if it's not an update button
            if (!this.closest('form').querySelector('button[type="submit"]').contains(this)) {
                setTimeout(() => {
                    input.closest('form').querySelector('button[type="submit"]').click();
                }, 300);
            }
        });
    });
    
    // Prevent negative numbers and auto-update on blur
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 1) {
                this.value = 1;
            }
            if (this.value > 99) {
                this.value = 99;
            }
            
            if (this.value !== this.defaultValue) {
                this.closest('form').querySelector('button[type="submit"]').click();
            }
        });
    });
});
</script>

<?php 
$conn->close();
require_once __DIR__ . '/includes/footer.php'; 
?>