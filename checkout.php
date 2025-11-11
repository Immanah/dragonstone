<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🚨 FIXED: Use output buffering to prevent header issues
ob_start();

include_once 'includes/database.php';
include_once 'includes/auth.php';
requireLogin();

$conn = getDatabaseConnection();

// Check connection
if (!$conn) {
    die("Database connection failed!");
}

// Check for success parameter FIRST
if (isset($_GET['success']) && isset($_SESSION['checkout_success'])) {
    $order_success = true;
    $order_data = $_SESSION['order_data'];
    unset($_SESSION['checkout_success']);
    unset($_SESSION['order_data']);
    
    ob_end_clean(); // Clear any buffered output
    include 'includes/header.php';
    ?>
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
    </style>

    <div class="success-container">
        <div class="container">
            <div class="text-center py-5">
                <div class="success-icon">✓</div>
                <h2 class="mb-3" style="color: var(--primary-green);">Order Successful!</h2>
                <p class="lead mb-4">Thank you for your sustainable purchase with DragonStone</p>
                
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
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Total Amount:</strong> R<?php echo number_format($order_data['total'], 2); ?></p>
                                        <p class="mb-2"><strong>Status:</strong> Processing</p>
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

                        <div class="eco-card mt-4">
                            <div class="eco-card-body">
                                <h5>Sustainability Impact</h5>
                                <div class="row text-center">
                                    <div class="col-md-6">
                                        <div class="p-3">
                                            <h4 class="text-success mb-1">+<?php echo $order_data['points']; ?></h4>
                                            <p class="mb-0">EcoPoints Earned</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3">
                                            <h4 class="text-success mb-1">~<?php echo number_format($order_data['co2_saved'], 1); ?>kg</h4>
                                            <p class="mb-0">CO2 Saved</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <a href="products.php" class="btn btn-dragon me-3">Continue Shopping</a>
                    <a href="orders.php" class="btn btn-outline-success">View My Orders</a>
                </div>
            </div>
        </div>
    </div>

    <?php
    include 'includes/footer.php';
    exit(); // Stop execution here for success page
}

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    ob_end_clean();
    header('Location: cart.php');
    exit();
}

// 🚨 FIXED: Better session variable handling
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
if (!$user_id) {
    ob_end_clean();
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? 'test@example.com';

// Get user's saved addresses
$saved_addresses = [];
try {
    $address_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $address_stmt->bind_param("i", $user_id);
    $address_stmt->execute();
    $saved_addresses = $address_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet, we'll create it later
}

// Get basic user data
$user_data = [];
try {
    $user_stmt = $conn->prepare("SELECT username, email, first_name, last_name, phone FROM users WHERE user_id = ?");
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
    $use_saved_address = $_POST['use_saved_address'] ?? '';
    $saved_address_id = $_POST['saved_address_id'] ?? '';
    
    if ($use_saved_address && $saved_address_id) {
        // Use saved address
        $address_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $address_stmt->bind_param("ii", $saved_address_id, $user_id);
        $address_stmt->execute();
        $saved_address = $address_stmt->get_result()->fetch_assoc();
        
        if ($saved_address) {
            $first_name = $saved_address['first_name'];
            $last_name = $saved_address['last_name'];
            $address = $saved_address['address_line1'];
            $address2 = $saved_address['address_line2'];
            $city = $saved_address['city'];
            $postal_code = $saved_address['postal_code'];
            $phone = $saved_address['phone'];
            $address_type = $saved_address['address_type'];
            $delivery_notes = $saved_address['delivery_notes'];
            $save_address = 0; // Don't save again
        }
    } else {
        // Use new address
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $address2 = trim($_POST['address2'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address_type = $_POST['address_type'] ?? 'house';
        $delivery_notes = trim($_POST['delivery_notes'] ?? '');
        $save_address = isset($_POST['save_address']) ? 1 : 0;
    }
    
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

    // Address validation for shipping
    if ($delivery_method === 'shipping' && !$use_saved_address) {
        if (empty($address)) $errors[] = "Address is required";
        if (empty($city)) $errors[] = "City is required";
        if (empty($postal_code)) $errors[] = "Postal code is required";
    }

    // Store pickup validation
    if ($delivery_method === 'pickup' && empty($store_pickup)) {
        $errors[] = "Please select a store pickup location";
    }

    // Calculate total and get cart items
    $total_amount = 0;
    $cart_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product_stmt = $conn->prepare("SELECT product_id, name, price, stock_quantity FROM products WHERE product_id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
        
        if ($product) {
            // Check stock availability
            if ($product['stock_quantity'] < $quantity) {
                $errors[] = "Sorry, '{$product['name']}' only has {$product['stock_quantity']} items in stock (you requested {$quantity})";
                continue;
            }
            
            $subtotal = $product['price'] * $quantity;
            $total_amount += $subtotal;
            
            $cart_items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }
    }

    // If cart items are empty due to stock issues
    if (empty($cart_items)) {
        $errors[] = "No items available for purchase due to stock issues. Please update your cart.";
    }

    // If no errors, process order
    if (empty($errors)) {
        error_log("No validation errors, processing order...");
        
        $shipping_cost = ($delivery_method === 'pickup') ? 0 : 49.00;
        $free_shipping_threshold = 500.00;
        
        // Apply free shipping if eligible
        if ($delivery_method === 'shipping' && $total_amount >= $free_shipping_threshold) {
            $shipping_cost = 0;
        }
        
        $final_total = $total_amount + $shipping_cost;
        
        $shipping_address = ($delivery_method === 'pickup') ? 
            "Store Pickup: " . ($store_locations[$store_pickup] ?? $store_pickup) : 
            formatAddress($address, $address2, $city, $postal_code, $address_type, $delivery_notes);

        // Start transaction
        $conn->begin_transaction();
        
        try {
            error_log("Starting database transaction...");
            
            // Create user_addresses table if not exists
            $conn->query("CREATE TABLE IF NOT EXISTS user_addresses (
                address_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                address_line1 TEXT,
                address_line2 TEXT,
                city VARCHAR(100),
                postal_code VARCHAR(20),
                phone VARCHAR(20),
                address_type ENUM('house', 'apartment', 'complex', 'office') DEFAULT 'house',
                delivery_notes TEXT,
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Save address if requested
            if ($save_address && !$use_saved_address) {
                $save_sql = "INSERT INTO user_addresses (user_id, first_name, last_name, address_line1, address_line2, city, postal_code, phone, address_type, delivery_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $save_stmt = $conn->prepare($save_sql);
                $save_stmt->bind_param("isssssssss", $user_id, $first_name, $last_name, $address, $address2, $city, $postal_code, $phone, $address_type, $delivery_notes);
                $save_stmt->execute();
            }

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

            // Add order items AND UPDATE STOCK QUANTITIES
            foreach ($cart_items as $item) {
                $product = $item['product'];
                
                // Add order item
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param("iiid", $order_id, $product['product_id'], $item['quantity'], $product['price']);
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Order item insertion failed: " . $item_stmt->error);
                }
                
                // UPDATE PRODUCT STOCK QUANTITY
                $update_stock_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
                $update_stmt = $conn->prepare($update_stock_sql);
                $update_stmt->bind_param("ii", $item['quantity'], $product['product_id']);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Stock update failed for product {$product['product_id']}: " . $update_stmt->error);
                }
                
                error_log("Stock updated: Product #{$product['product_id']} -{$item['quantity']} units");
            }

            error_log("Order items added and stock updated successfully");

            // Award EcoPoints
            $points_earned = calculateEcoPoints($cart_items);
            $points_sql = "INSERT INTO eco_point_transactions (user_id, points, transaction_type, reason, reference_id) VALUES (?, ?, 'Earned', ?, ?)";
            $reason = "Order #" . $order_id;
            $points_stmt = $conn->prepare($points_sql);
            $points_stmt->bind_param("iiss", $user_id, $points_earned, $reason, $order_id);
            $points_stmt->execute();

            error_log("EcoPoints awarded: $points_earned");

            // Record payment with card info
            $payment_sql = "INSERT INTO payments (order_id, amount, payment_method, status, transaction_id, card_type, card_last4) VALUES (?, ?, 'Credit Card', 'Completed', ?, ?, ?)";
            $payment_stmt = $conn->prepare($payment_sql);
            $transaction_id = 'TXN_' . uniqid() . '_' . $order_id;
            $card_last4 = substr($card_number, -4);
            $card_type = detectCardType($card_number) ?: 'unknown';
            $payment_stmt->bind_param("idssss", $order_id, $final_total, $transaction_id, $card_type, $card_last4);
            $payment_stmt->execute();

            error_log("Payment recorded: $transaction_id");

            // Send email
            $email_sent = sendSimpleConfirmationEmail($user_email, $order_id, $tracking_number, $cart_items, $final_total, $shipping_address, $shipping_cost);

            // Commit transaction
            $conn->commit();
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
                'email_sent' => $email_sent,
                'free_shipping' => $shipping_cost == 0 && $delivery_method === 'shipping'
            ];

            // Redirect to prevent form resubmission
            ob_end_clean();
            header('Location: checkout.php?success=1');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order failed: " . $e->getMessage();
            error_log("CHECKOUT ERROR: " . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
        error_log("VALIDATION ERRORS: " . $error);
    }
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
        } else {
            // Default CO2 saved if not specified
            $total_co2 += 250 * $item['quantity']; // 250g per product as default
        }
    }
    return $total_co2 / 1000; // Convert to kg
}

function validateCardNumber($cardNumber) {
    // Remove any non-digit characters
    $cardNumber = preg_replace('/\D/', '', $cardNumber);
    
    // Check if the card number is empty or too short
    if (empty($cardNumber) || strlen($cardNumber) < 13) {
        return false;
    }
    
    // Luhn algorithm validation
    $sum = 0;
    $reverseDigits = strrev($cardNumber);
    
    for ($i = 0; $i < strlen($reverseDigits); $i++) {
        $digit = intval($reverseDigits[$i]);
        
        if ($i % 2 == 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
    }
    
    return $sum % 10 == 0;
}

function detectCardType($cardNumber) {
    $cardNumber = preg_replace('/\D/', '', $cardNumber);
    
    // Visa
    if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $cardNumber)) {
        return 'visa';
    }
    // Mastercard
    elseif (preg_match('/^5[1-5][0-9]{14}$/', $cardNumber) || preg_match('/^2[2-7][0-9]{14}$/', $cardNumber)) {
        return 'mastercard';
    }
    // American Express
    elseif (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
        return 'american-express';
    }
    // Discover
    elseif (preg_match('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $cardNumber)) {
        return 'discover';
    }
    
    return false;
}

function formatAddress($address1, $address2, $city, $postal_code, $type, $notes) {
    $address = $address1;
    if (!empty($address2)) {
        $address .= ", " . $address2;
    }
    $address .= ", " . $city . ", " . $postal_code;
    
    if (!empty($type) && $type !== 'house') {
        $address .= " (" . ucfirst($type) . ")";
    }
    
    if (!empty($notes)) {
        $address .= " - Note: " . $notes;
    }
    
    return $address;
}

function sendSimpleConfirmationEmail($email, $order_id, $tracking_number, $cart_items, $total, $shipping_address, $shipping_cost) {
    try {
        $subject = "DragonStone Order Confirmation #$order_id";
        
        $items_html = "";
        foreach ($cart_items as $item) {
            $items_html .= "<tr><td>{$item['product']['name']} x {$item['quantity']}</td><td>R" . number_format($item['subtotal'], 2) . "</td></tr>";
        }
        
        $shipping_display = $shipping_cost == 0 ? "FREE" : "R" . number_format($shipping_cost, 2);
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #2d5016; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .free-shipping { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>DragonStone</h1>
                <h2>Order Confirmation #$order_id</h2>
            </div>
            
            <div class='content'>
                <p>Thank you for your sustainable purchase! Here are your order details:</p>
                
                <div class='order-details'>
                    <p><strong>Tracking Number:</strong> $tracking_number</p>
                    <p><strong>Shipping Address:</strong> $shipping_address</p>
                    " . ($shipping_cost == 0 ? "<div class='free-shipping'><strong>You qualified for FREE shipping!</strong></div>" : "") . "
                    
                    <h4>Order Items:</h4>
                    <table width='100%'>
                        $items_html
                        <tr><td><strong>Shipping</strong></td><td><strong>$shipping_display</strong></td></tr>
                        <tr><td><strong>Total</strong></td><td><strong>R" . number_format($total, 2) . "</strong></td></tr>
                    </table>
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

ob_end_clean(); // Clear debug output before including header
include 'includes/header.php'; 
?>

<!-- 🚨 FIXED: COMPREHENSIVE CHECKOUT FORM WITH ALL FEATURES -->
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

.checkout-container {
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

.delivery-option {
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.delivery-option:hover {
    border-color: var(--accent-green);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 142, 35, 0.1);
}

.delivery-option.selected {
    border-color: var(--primary-green);
    background: linear-gradient(135deg, #f8fff8, #f0f8f0);
}

.store-location {
    display: none;
    margin-top: 1rem;
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
}

.btn-dragon:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(45, 80, 22, 0.3);
    background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
    color: white;
}

.address-type-selector {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.address-type-btn {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.address-type-btn:hover {
    border-color: var(--accent-green);
}

.address-type-btn.selected {
    border-color: var(--primary-green);
    background: var(--sand-light);
    font-weight: 600;
}

.saved-addresses {
    margin-bottom: 1.5rem;
}

.saved-address-item {
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.saved-address-item:hover {
    border-color: var(--accent-green);
}

.saved-address-item.selected {
    border-color: var(--primary-green);
    background: var(--sand-light);
}

.address-default-badge {
    background: var(--primary-green);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    margin-left: 0.5rem;
}

.free-shipping-banner {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border: 2px solid #28a745;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    margin: 1rem 0;
}

.shipping-progress {
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    margin: 0.75rem 0;
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

.form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 0.75rem;
}

.form-control:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(45, 80, 22, 0.1);
}
</style>

<div class="checkout-container">
    <div class="container">
        <h1 class="text-center mb-4" style="color: var(--primary-green);">Secure Checkout</h1>
        
        <?php if (isset($error)): ?>
            <div class="eco-card">
                <div class="eco-card-body">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Saved Addresses -->
                <?php if (!empty($saved_addresses)): ?>
                <div class="eco-card">
                    <div class="eco-card-header">
                        <h5 class="mb-0">Saved Addresses</h5>
                    </div>
                    <div class="eco-card-body">
                        <div class="saved-addresses">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="use_saved_address" id="new_address" value="0" checked>
                                <label class="form-check-label fw-bold" for="new_address">
                                    Use new address
                                </label>
                            </div>
                            
                            <?php foreach($saved_addresses as $address): ?>
                            <div class="saved-address-item" onclick="selectSavedAddress(<?php echo $address['address_id']; ?>)">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="use_saved_address" id="address_<?php echo $address['address_id']; ?>" value="1" data-address-id="<?php echo $address['address_id']; ?>">
                                    <label class="form-check-label fw-bold" for="address_<?php echo $address['address_id']; ?>">
                                        <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                                        <?php if ($address['is_default']): ?>
                                            <span class="address-default-badge">Default</span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($address['address_line1']); ?>
                                        <?php if (!empty($address['address_line2'])) echo ', ' . htmlspecialchars($address['address_line2']); ?>
                                        <?php echo ', ' . htmlspecialchars($address['city'] . ' ' . $address['postal_code']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Delivery Method -->
                <div class="eco-card">
                    <div class="eco-card-header">
                        <h5 class="mb-0">Delivery Method</h5>
                    </div>
                    <div class="eco-card-body">
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
                            <label class="form-label fw-bold mt-3">Select Pickup Location *</label>
                            <select class="form-select" name="store_pickup" required>
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

                <!-- Contact & Shipping Information -->
                <div class="eco-card">
                    <div class="eco-card-header">
                        <h5 class="mb-0">Contact & Shipping Information</h5>
                    </div>
                    <div class="eco-card-body">
                        <form method="POST" id="checkoutForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <input type="hidden" name="saved_address_id" id="saved_address_id" value="">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? $user_data['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? $user_data['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user_data['phone'] ?? ''); ?>" required>
                            </div>

                            <div id="shippingAddress" style="display: <?php echo ($_POST['delivery_method'] ?? 'shipping') === 'shipping' ? 'block' : 'none'; ?>;">
                                <!-- Address Type -->
                                <label class="form-label fw-bold">Address Type</label>
                                <div class="address-type-selector">
                                    <div class="address-type-btn <?php echo ($_POST['address_type'] ?? 'house') === 'house' ? 'selected' : ''; ?>" onclick="selectAddressType('house')">
                                        <input type="radio" name="address_type" value="house" id="house" <?php echo ($_POST['address_type'] ?? 'house') === 'house' ? 'checked' : ''; ?> hidden>
                                        <label for="house" class="mb-0">House</label>
                                    </div>
                                    <div class="address-type-btn <?php echo ($_POST['address_type'] ?? '') === 'apartment' ? 'selected' : ''; ?>" onclick="selectAddressType('apartment')">
                                        <input type="radio" name="address_type" value="apartment" id="apartment" <?php echo ($_POST['address_type'] ?? '') === 'apartment' ? 'checked' : ''; ?> hidden>
                                        <label for="apartment" class="mb-0">Apartment</label>
                                    </div>
                                    <div class="address-type-btn <?php echo ($_POST['address_type'] ?? '') === 'complex' ? 'selected' : ''; ?>" onclick="selectAddressType('complex')">
                                        <input type="radio" name="address_type" value="complex" id="complex" <?php echo ($_POST['address_type'] ?? '') === 'complex' ? 'checked' : ''; ?> hidden>
                                        <label for="complex" class="mb-0">Complex</label>
                                    </div>
                                    <div class="address-type-btn <?php echo ($_POST['address_type'] ?? '') === 'office' ? 'selected' : ''; ?>" onclick="selectAddressType('office')">
                                        <input type="radio" name="address_type" value="office" id="office" <?php echo ($_POST['address_type'] ?? '') === 'office' ? 'checked' : ''; ?> hidden>
                                        <label for="office" class="mb-0">Office</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Street Address *</label>
                                    <input type="text" class="form-control" name="address" id="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="123 Main Street">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Apartment/Unit/Suite (Optional)</label>
                                    <input type="text" class="form-control" name="address2" value="<?php echo htmlspecialchars($_POST['address2'] ?? ''); ?>" placeholder="Apt 4B, Unit 12, etc.">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">City *</label>
                                        <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Postal Code *</label>
                                        <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Delivery Notes (Optional)</label>
                                    <textarea class="form-control" name="delivery_notes" rows="2" placeholder="Gate code, building instructions, safe place, etc."><?php echo htmlspecialchars($_POST['delivery_notes'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="save_address" id="save_address" <?php echo isset($_POST['save_address']) ? 'checked' : 'checked'; ?>>
                                    <label class="form-check-label" for="save_address">
                                        Save this address for future orders
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Payment Information -->
                            <div class="eco-card mt-4">
                                <div class="eco-card-header">
                                    <h5 class="mb-0">Payment Information</h5>
                                </div>
                                <div class="eco-card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Name on Card *</label>
                                        <input type="text" class="form-control" name="card_name" value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Card Number *</label>
                                        <input type="text" class="form-control" name="card_number" placeholder="1234 5678 9012 3456" value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Expiry Date *</label>
                                            <input type="text" class="form-control" name="expiry_date" placeholder="MM/YY" value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CVV *</label>
                                            <input type="text" class="form-control" name="cvv" placeholder="123" value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        <strong>Secure Payment:</strong> Your payment information is encrypted and secure. We never store your full card details.
                                    </div>
                                    
                                    <button type="submit" name="process_checkout" value="1" class="btn btn-dragon btn-lg w-100 py-3">
                                        <i class="fas fa-lock me-2"></i>Complete Order & Earn EcoPoints
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="eco-card sticky-summary">
                    <div class="eco-card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="eco-card-body">
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
                        
                        <!-- Free Shipping Logic -->
                        <?php 
                        $free_shipping_threshold = 500.00;
                        $shipping_cost = ($_POST['delivery_method'] ?? 'shipping') === 'pickup' ? 0 : 49.00;
                        $needs_shipping = ($_POST['delivery_method'] ?? 'shipping') === 'shipping';
                        $qualifies_free_shipping = $total_amount >= $free_shipping_threshold && $needs_shipping;
                        
                        if ($qualifies_free_shipping) {
                            $shipping_cost = 0;
                        }
                        ?>
                        
                        <div class="d-flex justify-content-between mb-2" id="shippingCost">
                            <span>Shipping:</span>
                            <span class="<?php echo $qualifies_free_shipping ? 'text-success fw-bold' : ''; ?>">
                                <?php echo $qualifies_free_shipping ? 'FREE' : 'R49.00'; ?>
                            </span>
                        </div>
                        
                        <?php if ($needs_shipping && !$qualifies_free_shipping): ?>
                            <div class="shipping-progress">
                                <div class="shipping-progress-bar" style="width: <?php echo min(100, ($total_amount / $free_shipping_threshold) * 100); ?>%"></div>
                            </div>
                            <div class="text-center small text-muted mb-3">
                                Spend R<?php echo number_format($free_shipping_threshold - $total_amount, 2); ?> more for free shipping!
                            </div>
                        <?php elseif ($qualifies_free_shipping): ?>
                            <div class="free-shipping-banner">
                                <h6 class="mb-0">You qualify for FREE shipping!</h6>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>EcoPoints to earn:</span>
                            <span class="fw-bold text-success">+<?php echo calculateEcoPoints($cart_items); ?> pts</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong class="fs-5">Total:</strong>
                            <strong class="fs-5 text-success" id="totalAmount">
                                R<?php echo number_format($total_amount + $shipping_cost, 2); ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🚨 FIXED: JavaScript with proper form handling -->
<script>
// FORM SUBMISSION HANDLER
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkoutForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        console.log('✅ Checkout form submitting...');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Your Order...';
        submitBtn.disabled = true;
        return true; // Allow form submission
    });
});

// DELIVERY METHOD SELECTION
function selectDelivery(method) {
    const shipping = document.getElementById('shippingAddress');
    const store = document.getElementById('storeLocation');
    
    // Update radio buttons
    document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
        radio.checked = radio.value === method;
    });
    
    // Show/hide sections
    if (shipping) shipping.style.display = method === 'pickup' ? 'none' : 'block';
    if (store) store.style.display = method === 'pickup' ? 'block' : 'none';
    
    // Update UI
    document.querySelectorAll('.delivery-option').forEach(opt => opt.classList.remove('selected'));
    const selected = document.querySelector(`.delivery-option[onclick="selectDelivery('${method}")]`);
    if (selected) selected.classList.add('selected');
}

// ADDRESS TYPE SELECTION
function selectAddressType(type) {
    document.querySelectorAll('.address-type-btn').forEach(btn => btn.classList.remove('selected'));
    const selected = document.querySelector(`.address-type-btn[onclick="selectAddressType('${type}")]`);
    if (selected) selected.classList.add('selected');
    
    // Update radio button
    const radio = document.querySelector(`input[value="${type}"]`);
    if (radio) radio.checked = true;
}

// SAVED ADDRESS SELECTION
function selectSavedAddress(addressId) {
    document.querySelectorAll('.saved-address-item').forEach(item => item.classList.remove('selected'));
    const selected = document.querySelector(`.saved-address-item[onclick="selectSavedAddress(${addressId})"]`);
    if (selected) selected.classList.add('selected');
    
    // Update form fields
    const radio = document.querySelector(`input[data-address-id="${addressId}"]`);
    if (radio) radio.checked = true;
    
    const savedAddressId = document.getElementById('saved_address_id');
    if (savedAddressId) savedAddressId.value = addressId;
    
    // Hide new address form
    const shippingAddress = document.getElementById('shippingAddress');
    if (shippingAddress) shippingAddress.style.display = 'none';
}
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>