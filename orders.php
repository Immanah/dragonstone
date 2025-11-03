<?php
// Use require_once instead of include
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

$conn = getDatabaseConnection();

$user_id = $_SESSION['user_id'];

// Get all user orders
$orders_sql = "SELECT o.*, 
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count,
               (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.order_id) as total_items
               FROM orders o 
               WHERE o.user_id = ? 
               ORDER BY o.order_date DESC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

$page_title = "My Orders - DragonStone";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="profile-sidebar">
                <div class="sidebar-header">
                    <h5>My Account</h5>
                </div>
                <div class="sidebar-menu">
                    <a href="profile.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        My Profile
                    </a>
                    <a href="orders.php" class="sidebar-item active">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </span>
                        My Orders
                    </a>
                    <a href="eco-points.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </span>
                        My EcoPoints
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="page-header">
                <div class="header-content">
                    <div class="header-text">
                        <h1 class="page-title">My Orders</h1>
                        <p class="page-subtitle">Track and manage your purchases</p>
                    </div>
                    <div class="header-actions">
                        <a href="products.php" class="btn btn-primary">
                            <span class="btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($orders->num_rows > 0): ?>
                <div class="orders-card">
                    <div class="card-header">
                        <h3 class="card-title">Order History</h3>
                    </div>
                    <div class="card-body">
                        <div class="orders-list">
                            <?php while($order = $orders->fetch_assoc()): 
                                $status_class = getStatusClass($order['status']);
                                $status_icon = getStatusIcon($order['status']);
                            ?>
                                <div class="order-item">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <div class="order-number">
                                                <span class="order-label">Order #</span>
                                                <strong><?php echo $order['order_id']; ?></strong>
                                            </div>
                                            <div class="order-date">
                                                <span class="order-label">Placed on</span>
                                                <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                                            </div>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <span class="status-icon"><?php echo $status_icon; ?></span>
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <div class="order-meta">
                                            <div class="meta-item">
                                                <span class="meta-label">Items</span>
                                                <span class="meta-value"><?php echo $order['total_items']; ?> items</span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Total Amount</span>
                                                <span class="meta-value price">R<?php echo number_format($order['total_amount'], 2); ?></span>
                                            </div>
                                            <div class="meta-item">
                                                <span class="meta-label">Shipping</span>
                                                <span class="meta-value">
                                                    <?php echo $order['status'] === 'Delivered' ? 'Delivered' : 'In Progress'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-outline">
                                                <span class="btn-icon">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </span>
                                                View Details
                                            </a>
                                            <?php if ($order['status'] === 'Delivered'): ?>
                                                <button class="btn btn-outline">
                                                    <span class="btn-icon">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                                        </svg>
                                                    </span>
                                                    Leave Review
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                            <path d="M8 14h8"></path>
                        </svg>
                    </div>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders with us yet.</p>
                    <div class="no-orders-actions">
                        <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        <a href="community.php" class="btn btn-outline">Browse Community</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
include 'includes/footer.php'; 

// Helper functions for status styling
function getStatusClass($status) {
    switch($status) {
        case 'Delivered': return 'delivered';
        case 'Shipped': return 'shipped';
        case 'Processing': return 'processing';
        case 'Paid': return 'paid';
        case 'Pending': return 'pending';
        case 'Cancelled': return 'cancelled';
        default: return 'unknown';
    }
}

function getStatusIcon($status) {
    switch($status) {
        case 'Delivered': return '✓';
        case 'Shipped': return '🚚';
        case 'Processing': return '⚙️';
        case 'Paid': return '💳';
        case 'Pending': return '⏳';
        case 'Cancelled': return '✕';
        default: return '📦';
    }
}
?>

<style>
/* Orders Page Styles */
.page-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.header-text {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: var(--color-text-light);
    margin-bottom: 0;
}

.header-actions .btn {
    white-space: nowrap;
}

/* Orders Card */
.orders-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.card-title {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 1.25rem;
}

.card-body {
    padding: 1.5rem;
}

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-item {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    transition: all 0.3s ease;
}

.order-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.order-info {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.order-number,
.order-date {
    display: flex;
    flex-direction: column;
}

.order-label {
    font-size: 0.75rem;
    color: var(--color-text-light);
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.order-number strong {
    font-size: 1.125rem;
    color: var(--color-forest-dark);
}

.order-date {
    color: var(--color-text);
    font-weight: 500;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid;
}

.status-icon {
    margin-right: 0.5rem;
    font-size: 0.75rem;
}

.status-badge.delivered {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.status-badge.shipped {
    background: #cce7ff;
    color: #004085;
    border-color: #b3d7ff;
}

.status-badge.processing {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.status-badge.paid {
    background: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
}

.status-badge.pending {
    background: #e2e3e5;
    color: #383d41;
    border-color: #d6d8db;
}

.status-badge.cancelled {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

/* Order Details */
.order-details {
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.order-meta {
    display: flex;
    gap: 2rem;
    flex: 1;
}

.meta-item {
    display: flex;
    flex-direction: column;
}

.meta-label {
    font-size: 0.75rem;
    color: var(--color-text-light);
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.meta-value {
    font-weight: 500;
    color: var(--color-forest-dark);
}

.meta-value.price {
    font-weight: 700;
    color: var(--color-forest-medium);
    font-size: 1.125rem;
}

/* Order Actions */
.order-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 1px solid;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.875rem;
}

.btn-primary {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
}

.btn-primary:hover {
    background: var(--color-forest-dark);
    border-color: var(--color-forest-dark);
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    color: var(--color-forest-medium);
    border-color: var(--color-forest-medium);
}

.btn-outline:hover {
    background: var(--color-forest-medium);
    color: var(--color-white);
}

.btn-icon {
    margin-right: 0.5rem;
}

/* No Orders State */
.no-orders {
    text-align: center;
    padding: 4rem 2rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
}

.no-orders-icon {
    margin-bottom: 1.5rem;
    color: var(--color-text-light);
}

.no-orders h3 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
}

.no-orders p {
    color: var(--color-text-light);
    margin-bottom: 2rem;
    font-size: 1.125rem;
}

.no-orders-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Profile Sidebar */
.profile-sidebar {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.sidebar-header h5 {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
}

.sidebar-menu {
    padding: 0.5rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.3s ease;
    margin-bottom: 0.25rem;
}

.sidebar-item:hover {
    background: var(--color-sand-light);
    border-color: var(--color-border);
    transform: translateX(4px);
}

.sidebar-item.active {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
}

.sidebar-icon {
    margin-right: 0.75rem;
    width: 16px;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .header-actions {
        align-self: flex-start;
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .order-info {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .order-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }
    
    .order-meta {
        flex-direction: column;
        gap: 1rem;
        width: 100%;
    }
    
    .order-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .no-orders-actions {
        flex-direction: column;
        align-items: center;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .order-header {
        padding: 1rem;
    }
    
    .order-details {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.625rem 0.875rem;
        font-size: 0.8125rem;
    }
}
</style>