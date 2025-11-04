<?php 
require_once __DIR__ . '/includes/config.php';
requireLogin();

$conn = getDatabaseConnection();

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? 'test@example.com';

// Get basic user data
$user_data = [];
try {
    $user_stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc() ?? [];
} catch (Exception $e) {
    error_log("User data error: " . $e->getMessage());
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_checkout'])) {
    error_log("CHECKOUT PROCESS STARTED");
    
    // Get form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $delivery_method = $_POST['delivery_method'] ?? 'shipping';
    $store_pickup = $_POST['store_pickup'] ?? '';
    
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry_date = trim($_POST['expiry_date'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');

    $errors = [];
    
    // Basic validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($card_number)) $errors[] = "Card number is required";
    if (empty($expiry_date)) $errors[] = "Expiry date is required";
    if (empty($cvv)) $errors[] = "CVV is required";
    if (empty($card_name)) $errors[] = "Name on card is required";

    // Card validation
    $card_number = preg_replace('/\D/', '', $card_number);
    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        $errors[] = "Card number must be 13-19 digits";
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry_date)) {
        $errors[] = "Expiry date must be in MM/YY format";
    }

    if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
        $errors[] = "CVV must be 3-4 digits";
    }

    // Calculate total
    $total_amount = 0;
    $cart_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product_stmt = $conn->prepare("SELECT product_id, name, price, stock_quantity FROM products WHERE product_id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
        
        if ($product) {
            $subtotal = $product['price'] * $quantity;
            $total_amount += $subtotal;
            
            $cart_items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }
    }

    // If no errors, process order
    if (empty($errors)) {
        error_log("No validation errors, processing order...");
        
        $shipping_cost = ($delivery_method === 'pickup') ? 0 : 49.00;
        $final_total = $total_amount + $shipping_cost;
        
        $shipping_address = ($delivery_method === 'pickup') ? 
            "Store Pickup: " . htmlspecialchars($store_pickup) : 
            "$address, $city, $postal_code";

        // Start transaction
        $conn->query("BEGIN");
        
        try {
            error_log("Starting database transaction...");
            
            // Generate tracking number
            $tracking_number = 'DS' . date('YmdHis') . rand(100, 999);
            $customer_name = $first_name . ' ' . $last_name;

            // Create order
            $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_cost, final_amount, shipping_address, delivery_method, tracking_number, customer_phone, customer_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("idddsssss", $user_id, $total_amount, $shipping_cost, $final_total, $shipping_address, $delivery_method, $tracking_number, $phone, $customer_name);
            
            if (!$order_stmt->execute()) {
                throw new Exception("Order creation failed: " . $order_stmt->error);
            }
            
            $order_id = $conn->insert_id;
            error_log("Order created: #$order_id");

            // Add order items
            foreach ($cart_items as $item) {
                $product = $item['product'];
                
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param("iiid", $order_id, $product['product_id'], $item['quantity'], $product['price']);
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Order item insertion failed: " . $item_stmt->error);
                }
            }

            error_log("Order items added successfully");

            // Award EcoPoints
            $points_earned = calculateEcoPoints($cart_items);
            $points_sql = "INSERT INTO eco_point_transactions (user_id, points, transaction_type, reason, reference_id) VALUES (?, ?, 'Earned', ?, ?)";
            $reason = "Order #" . $order_id;
            $points_stmt = $conn->prepare($points_sql);
            $points_stmt->bind_param("iiss", $user_id, $points_earned, $reason, $order_id);
            $points_stmt->execute();

            error_log("EcoPoints awarded: $points_earned");

            // Record payment 
            $payment_sql = "INSERT INTO payments (order_id, amount, payment_method, status, transaction_id) VALUES (?, ?, 'Credit Card', 'Completed', ?)";
            $payment_stmt = $conn->prepare($payment_sql);
            $transaction_id = 'TXN_' . uniqid() . '_' . $order_id;
            $payment_stmt->bind_param("ids", $order_id, $final_total, $transaction_id);
            $payment_stmt->execute();

            error_log("Payment recorded: $transaction_id");

            // Send email
            $email_sent = sendSimpleConfirmationEmail($user_email, $order_id, $tracking_number, $cart_items, $final_total, $shipping_address);

            // Commit transaction
            $conn->query("COMMIT");
            error_log("Transaction committed successfully");

            // Clear cart and show success
            unset($_SESSION['cart']);
            $_SESSION['checkout_success'] = true;
            $_SESSION['order_data'] = [
                'order_id' => $order_id,
                'tracking_number' => $tracking_number,
                'total' => $final_total,
                'points' => $points_earned,
                'co2_saved' => calculateCO2Saved($cart_items),
                'email_sent' => $email_sent
            ];

            // Redirect to prevent form resubmission
            header('Location: checkout.php?success=1');
            exit();
            
        } catch (Exception $e) {
            $conn->query("ROLLBACK");
            $error = "Order failed: " . $e->getMessage();
            error_log("CHECKOUT ERROR: " . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
        error_log("VALIDATION ERRORS: " . $error);
    }
}

// Check for success redirect
if (isset($_GET['success']) && isset($_SESSION['checkout_success'])) {
    $order_success = true;
    $order_data = $_SESSION['order_data'];
    unset($_SESSION['checkout_success']);
    unset($_SESSION['order_data']);
}

// Get cart items for display
$cart_items = [];
$total_amount = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product_stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
        
        if ($product) {
            $subtotal = $product['price'] * $quantity;
            $total_amount += $subtotal;
            
            $cart_items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }
    }
}

// Store pickup locations
$store_locations = [
    'capetown' => 'Cape Town CBD - 123 Green Street',
    'johannesburg' => 'Johannesburg - 45 Eco Park, Sandton',
    'durban' => 'Durban Beachfront - 78 Ocean View'
];

// HELPER FUNCTIONS
function calculateEcoPoints($cart_items) {
    $points = 0;
    foreach ($cart_items as $item) {
        $points += 100 + ($item['quantity'] * 50);
    }
    return $points;
}

function calculateCO2Saved($cart_items) {
    $total_co2 = 0;
    foreach ($cart_items as $item) {
        if (isset($item['product']['co2_saved'])) {
            $total_co2 += $item['product']['co2_saved'] * $item['quantity'];
        }
    }
    return $total_co2 / 1000;
}

function sendSimpleConfirmationEmail($email, $order_id, $tracking_number, $cart_items, $total, $shipping_address) {
    try {
        $subject = "🎉 DragonStone Order Confirmation #$order_id";
        
        $items_html = "";
        foreach ($cart_items as $item) {
            $items_html .= "<tr><td>{$item['product']['name']} x {$item['quantity']}</td><td>R" . number_format($item['subtotal'], 2) . "</td></tr>";
        }
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #4a6b4a; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🐉 DragonStone</h1>
                <h2>Order Confirmation #$order_id</h2>
            </div>
            
            <div class='content'>
                <p>Thank you for your sustainable purchase! Here are your order details:</p>
                
                <div class='order-details'>
                    <p><strong>Tracking Number:</strong> $tracking_number</p>
                    <p><strong>Shipping Address:</strong> $shipping_address</p>
                    
                    <h4>Order Items:</h4>
                    <table width='100%'>
                        $items_html
                    </table>
                    
                    <p><strong>Total Amount:</strong> R" . number_format($total, 2) . "</p>
                </div>
                
                <p>You can track your order anytime by visiting your account dashboard.</p>
                <p>Thank you for choosing sustainable products!</p>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: orders@dragonstone.com" . "\r\n";
        
        return mail($email, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

include 'includes/header.php'; 
?>

<style>
.delivery-option {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.delivery-option:hover {
    border-color: #4a6b4a;
}
.delivery-option.selected {
    border-color: #4a6b4a;
    background-color: #f8fff8;
}
.store-location {
    display: none;
    margin-top: 15px;
}
.security-badge {
    background: linear-gradient(135deg, #4a6b4a, #5a7a5a);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
</style>

<div class="container py-5">
    <?php if (isset($order_success) && $order_success): ?>
        <!-- SUCCESS PAGE -->
        <div class="text-center py-5">
            <div class="fs-1 text-success mb-4">🎉</div>
            <h2 class="text-success mb-3">Order Successful!</h2>
            
            <div class="alert alert-success mb-4">
                <h4 class="alert-heading">Order Confirmed!</h4>
                <p class="mb-2"><strong>Order #<?php echo $order_data['order_id']; ?></strong></p>
                <p class="mb-2"><strong>Tracking Number: <?php echo $order_data['tracking_number']; ?></strong></p>
                <?php if ($order_data['email_sent']): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-envelope me-2"></i>Confirmation email sent to <?php echo $user_email; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>Email confirmation failed, but your order was processed successfully.
                    </div>
                <?php endif; ?>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-success">
                        <div class="card-body">
                            <h5>Order Summary</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total:</span>
                                        <span class="fw-bold">R<?php echo number_format($order_data['total'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>EcoPoints:</span>
                                        <span class="text-success fw-bold">+<?php echo $order_data['points']; ?> pts</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>CO2 Saved:</span>
                                        <span class="text-success fw-bold">~<?php echo number_format($order_data['co2_saved'], 1); ?>kg</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="products.php" class="btn btn-dragon">Continue Shopping</a>
                <a href="orders.php" class="btn btn-outline-success">View My Orders</a>
                <a href="tracking.php?tracking=<?php echo $order_data['tracking_number']; ?>" class="btn btn-outline-info">
                    <i class="fas fa-shipping-fast me-2"></i>Track Order
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- CHECKOUT FORM -->
        <h1 class="text-center mb-5">✅ Secure Checkout</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Delivery Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="delivery-option <?php echo ($_POST['delivery_method'] ?? 'shipping') === 'shipping' ? 'selected' : ''; ?>" onclick="selectDelivery('shipping')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="delivery_method" value="shipping" id="shipping" <?php echo ($_POST['delivery_method'] ?? 'shipping') === 'shipping' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="shipping">
                                            <i class="fas fa-shipping-fast me-2"></i>Home Delivery
                                        </label>
                                    </div>
                                    <p class="text-muted mb-0 mt-2">R49.00 - 3-5 business days</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="delivery-option <?php echo ($_POST['delivery_method'] ?? '') === 'pickup' ? 'selected' : ''; ?>" onclick="selectDelivery('pickup')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="delivery_method" value="pickup" id="pickup" <?php echo ($_POST['delivery_method'] ?? '') === 'pickup' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="pickup">
                                            <i class="fas fa-store me-2"></i>Store Pickup
                                        </label>
                                    </div>
                                    <p class="text-muted mb-0 mt-2">FREE - Ready in 2 hours</p>
                                </div>
                            </div>
                        </div>

                        <div class="store-location" id="storeLocation" style="display: <?php echo ($_POST['delivery_method'] ?? '') === 'pickup' ? 'block' : 'none'; ?>;">
                            <label class="form-label fw-bold mt-3">Select Pickup Location</label>
                            <select class="form-select" name="store_pickup">
                                <option value="">Choose a location...</option>
                                <?php foreach($store_locations as $key => $location): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($_POST['store_pickup'] ?? '') === $key ? 'selected' : ''; ?>>
                                        <?php echo $location; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="checkoutForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                            </div>

                            <div id="shippingAddress" style="display: <?php echo ($_POST['delivery_method'] ?? 'shipping') === 'shipping' ? 'block' : 'none'; ?>;">
                                <div class="mb-3">
                                    <label class="form-label">Address *</label>
                                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">City *</label>
                                        <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Postal Code *</label>
                                        <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-header bg-light mt-4">
                                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Name on Card *</label>
                                <input type="text" class="form-control" name="card_name" value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Card Number *</label>
                                <input type="text" class="form-control card-number" name="card_number" placeholder="1234 5678 9012 3456" value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Date *</label>
                                    <input type="text" class="form-control expiry-date" name="expiry_date" placeholder="MM/YY" value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CVV *</label>
                                    <input type="text" class="form-control cvv" name="cvv" placeholder="123" value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-lock me-2"></i>
                                <strong>Secure Payment:</strong> Your payment information is encrypted and secure.
                            </div>
                            
                            <button type="submit" name="process_checkout" value="1" class="btn btn-dragon btn-lg w-100 py-3">
                                <i class="fas fa-lock me-2"></i>Complete Order & Earn EcoPoints
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach($cart_items as $item): ?>
                            <div class='d-flex justify-content-between mb-2'>
                                <div>
                                    <small class="fw-bold"><?php echo htmlspecialchars($item['product']['name']); ?></small>
                                    <br>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                </div>
                                <span>R<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>R<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="shippingCost">
                            <span>Shipping:</span>
                            <span>R49.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>EcoPoints to earn:</span>
                            <span class="text-success fw-bold">+<?php echo calculateEcoPoints($cart_items); ?> pts</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="fs-5 text-success" id="totalAmount">R<?php echo number_format($total_amount + 49, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function selectDelivery(method) {
    const shipping = document.getElementById('shippingAddress');
    const store = document.getElementById('storeLocation');
    const shippingCost = document.getElementById('shippingCost');
    const totalAmount = document.getElementById('totalAmount');
    const subtotal = <?php echo $total_amount; ?>;
    
    // Update delivery options UI
    document.querySelectorAll('.delivery-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelector(`.delivery-option[onclick="selectDelivery('${method}')"]`).classList.add('selected');
    
    // Update form fields
    document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
        radio.checked = radio.value === method;
    });
    
    if (method === 'pickup') {
        shipping.style.display = 'none';
        store.style.display = 'block';
        shippingCost.innerHTML = '<span>Shipping:</span><span class="text-success">FREE</span>';
        totalAmount.textContent = 'R' + subtotal.toFixed(2);
        
        // Make shipping fields not required
        document.querySelectorAll('#shippingAddress input').forEach(input => {
            input.required = false;
        });
        document.querySelector('select[name="store_pickup"]').required = true;
    } else {
        shipping.style.display = 'block';
        store.style.display = 'none';
        shippingCost.innerHTML = '<span>Shipping:</span><span>R49.00</span>';
        totalAmount.textContent = 'R' + (subtotal + 49).toFixed(2);
        
        // Make shipping fields required
        document.querySelectorAll('#shippingAddress input').forEach(input => {
            input.required = true;
        });
        document.querySelector('select[name="store_pickup"]').required = false;
    }
}

// Format card number
document.querySelector('.card-number')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = '';
    
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) formattedValue += ' ';
        formattedValue += value[i];
    }
    
    e.target.value = formattedValue;
});

// Format expiry date
document.querySelector('.expiry-date')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// Only numbers in CVV
document.querySelector('.cvv')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>