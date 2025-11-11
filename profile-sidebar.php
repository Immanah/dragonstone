<?php
include 'includes/auth.php';
requireLogin();

include 'includes/database.php';
$conn = getDatabaseConnection();

$order_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get order details
$order_sql = "SELECT o.* FROM orders o WHERE o.order_id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Get order items
$items_sql = "SELECT oi.*, p.name, p.co2_saved 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.product_id 
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <?php include 'includes/profile-sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Order #<?php echo $order['order_id']; ?></h1>
                <a href="orders.php" class="btn btn-outline-secondary">← Back to Orders</a>
            </div>
            
            <!-- Order Summary -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5>Order Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Order Date:</strong><br>
                                    <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Order Status:</strong><br>
                                    <span class="badge bg-<?php 
                                        switch($order['status']) {
                                            case 'Delivered': echo 'success'; break;
                                            case 'Shipped': echo 'info'; break;
                                            case 'Processing': echo 'warning'; break;
                                            case 'Pending': echo 'secondary'; break;
                                            default: echo 'dark';
                                        }
                                    ?>"><?php echo $order['status']; ?></span>
                                </div>
                            </div>
                            
                            <?php if ($order['shipping_address']): ?>
                                <div class="mt-3">
                                    <strong>Shipping Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Order Total</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>R<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span>R49.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>EcoPoints Earned:</span>
                                <span class="text-success">+<?php echo $order_items->num_rows * 100; ?> pts</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>R<?php echo number_format($order['total_amount'] + 49, 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Items (<?php echo $order_items->num_rows; ?>)</h5>
                </div>
                <div class="card-body">
                    <?php while($item = $order_items->fetch_assoc()): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-1 text-center">
                                <div class="fs-4">
                                    <?php 
                                    $icons = ['🧼', '🍴', '🏠', '🚿', '🌿', '👶', '🌳'];
                                    // This would need category_id from products - using placeholder for now
                                    echo $icons[array_rand($icons)]; 
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-success">🌱 <?php echo $item['co2_saved']; ?>kg CO2 saved</small>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">R<?php echo number_format($item['unit_price'], 2); ?> each</small>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong>R<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></strong>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Eco Impact -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h5>🌿 Your Environmental Impact</h5>
                    <p class="lead">
                        This order saved approximately 
                        <strong class="text-success">
                            <?php
                            $total_co2 = 0;
                            $order_items->data_seek(0); // Reset pointer
                            while($item = $order_items->fetch_assoc()) {
                                $total_co2 += $item['co2_saved'] * $item['quantity'];
                            }
                            echo $total_co2;
                            ?>kg of CO2
                        </strong>
                    </p>
                    <small class="text-muted">That's equivalent to planting <?php echo ceil($total_co2 / 20); ?> trees!</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>