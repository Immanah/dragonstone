<?php
// Use absolute path to config.php to avoid path issues
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

requireLogin();

$conn = getDatabaseConnection();

$user_id = $_SESSION['user_id'];

// Get user's EcoPoints data for display
$user_sql = "SELECT eco_points_balance, first_name FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    die("Error preparing statement: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

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
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <div class="avatar-container">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="user-info">
                        <h5 class="user-name"><?php echo htmlspecialchars($user['first_name']); ?></h5>
                        <p class="user-email">Order History</p>
                        <div class="user-badges">
                            <span class="role-badge customer">
                                Member
                            </span>
                        </div>
                    </div>
                    <div class="user-points">
                        <div class="points-display">
                            <div class="points-amount"><?php echo number_format($user['eco_points_balance'] ?? 0); ?></div>
                            <div class="points-label">EcoPoints</div>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-menu">
                    <a href="profile.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                        </span>
                        Dashboard
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
                        EcoPoints
                    </a>
                    <a href="settings.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </span>
                        Settings
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
                <div class="orders-card dashboard-card">
                    <div class="card-header">
                        <div class="header-content">
                            <h3 class="card-title">Order History</h3>
                            <span class="order-count"><?php echo $orders->num_rows; ?> order(s)</span>
                        </div>
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
                                                <span class="meta-label">EcoPoints Earned</span>
                                                <span class="meta-value points">
                                                    +<?php echo calculateEcoPoints($order['total_amount']); ?> pts
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
                                            <?php if ($order['status'] === 'Processing' || $order['status'] === 'Pending'): ?>
                                                <button class="btn btn-outline">
                                                    <span class="btn-icon">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                                            <line x1="3" y1="6" x2="21" y2="6"></line>
                                                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                                                        </svg>
                                                    </span>
                                                    Track Order
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
                <div class="no-orders dashboard-card">
                    <div class="no-data-icon">
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
            
            <!-- Quick Actions Card -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="support-card dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Need Help?</h3>
                        </div>
                        <div class="card-body">
                            <div class="support-options">
                                <a href="contact.php" class="support-option">
                                    <div class="support-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="support-info">
                                        <h4>Contact Support</h4>
                                        <p>Get help with your orders</p>
                                    </div>
                                </a>
                                <a href="faq.php" class="support-option">
                                    <div class="support-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12" y2="17"></line>
                                        </svg>
                                    </div>
                                    <div class="support-info">
                                        <h4>FAQ</h4>
                                        <p>Find answers to common questions</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="eco-benefits-card dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Eco Benefits</h3>
                        </div>
                        <div class="card-body">
                            <div class="eco-benefits">
                                <div class="eco-benefit">
                                    <div class="benefit-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                                        </svg>
                                    </div>
                                    <span>Earn EcoPoints on every order</span>
                                </div>
                                <div class="eco-benefit">
                                    <div class="benefit-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                            <line x1="7" y1="7" x2="7" y2="7"></line>
                                        </svg>
                                    </div>
                                    <span>Carbon neutral shipping</span>
                                </div>
                                <div class="eco-benefit">
                                    <div class="benefit-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        </svg>
                                    </div>
                                    <span>Sustainable packaging</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Eco Points Page Styles - Updated with Sleek Professional Design */
:root {
    --color-forest-dark: #2d4a2d;
    --color-forest-medium: #3a5c3a;
    --color-forest-light: #4a7c4a;
    --color-sand-light: #f8f6f2;
    --color-white: #ffffff;
    --color-border: #e8e6e1;
    --color-text: #333333;
    --color-text-light: #666666;
    --border-radius: 12px;
    --border-radius-sm: 8px;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
}

.page-header {
    margin-bottom: 2.5rem;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 1.5rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 2rem;
}

.header-text {
    flex: 1;
}

.page-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--color-text-light);
    margin-bottom: 0;
    font-size: 1.125rem;
}

.header-actions .btn {
    white-space: nowrap;
}

/* Profile Sidebar - Consistent with EcoPoints Page */
.profile-sidebar {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.user-card {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
    text-align: center;
    background: linear-gradient(135deg, var(--color-sand-light) 0%, #ffffff 100%);
}

.user-avatar {
    margin-bottom: 1rem;
}

.avatar-container {
    width: 80px;
    height: 80px;
    border: 2px solid var(--color-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: var(--color-white);
    color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.user-name {
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
    font-weight: 600;
    font-size: 1.125rem;
}

.user-email {
    color: var(--color-text-light);
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.user-badges {
    margin-bottom: 1.5rem;
}

.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    border: 1px solid;
    letter-spacing: 0.02em;
}

.role-badge.admin {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.role-badge.customer {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
}

.user-points {
    border-top: 1px solid var(--color-border);
    padding-top: 1.5rem;
}

.points-display {
    text-align: center;
}

.points-amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-forest-medium);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.points-label {
    font-size: 0.875rem;
    color: var(--color-text-light);
    font-weight: 500;
}

.sidebar-menu {
    padding: 1rem 0.5rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
    font-weight: 500;
    background: var(--color-white);
}

.sidebar-item:hover {
    background: var(--color-sand-light);
    border-color: var(--color-border);
    transform: translateX(4px);
    box-shadow: var(--shadow-sm);
}

.sidebar-item.active {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.sidebar-icon {
    margin-right: 0.75rem;
    width: 16px;
    text-align: center;
}

/* Cards - Consistent Dashboard Style */
.dashboard-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    margin-bottom: 2rem;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.card-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 1.25rem;
    letter-spacing: -0.01em;
}

.order-count {
    color: var(--color-text-light);
    font-size: 0.875rem;
    font-weight: 500;
}

.card-body {
    padding: 2rem;
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
    position: relative;
}

.order-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.order-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--color-forest-light);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.order-item:hover::before {
    opacity: 1;
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
    letter-spacing: 0.02em;
}

.status-icon {
    margin-right: 0.5rem;
    font-size: 0.75rem;
}

.status-badge.delivered {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
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

.meta-value.points {
    font-weight: 700;
    color: var(--color-forest-light);
    background: var(--color-sand-light);
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    border: 1px solid var(--color-border);
    font-size: 0.875rem;
}

/* Order Actions */
.order-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border: 2px solid;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    letter-spacing: 0.02em;
    cursor: pointer;
}

.btn-primary {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
}

.btn-primary:hover {
    background: var(--color-forest-dark);
    border-color: var(--color-forest-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(58, 92, 58, 0.3);
}

.btn-outline {
    background: transparent;
    color: var(--color-forest-medium);
    border-color: var(--color-forest-medium);
}

.btn-outline:hover {
    background: var(--color-forest-medium);
    color: var(--color-white);
    transform: translateY(-2px);
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

.no-data-icon {
    margin-bottom: 1.5rem;
    color: var(--color-text-light);
    opacity: 0.5;
}

.no-orders h3 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    font-weight: 600;
}

.no-orders p {
    color: var(--color-text-light);
    margin-bottom: 2rem;
    font-size: 1rem;
}

.no-orders-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Support Card */
.support-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.support-option {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.3s ease;
    background: var(--color-white);
}

.support-option:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    color: var(--color-text);
}

.support-icon {
    width: 48px;
    height: 48px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.25rem;
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.support-option:hover .support-icon {
    background: var(--color-forest-light);
    color: var(--color-white);
    border-color: var(--color-forest-light);
}

.support-info h4 {
    margin: 0 0 0.25rem 0;
    color: var(--color-forest-dark);
    font-weight: 600;
}

.support-info p {
    margin: 0;
    color: var(--color-text-light);
    font-size: 0.875rem;
}

/* Eco Benefits Card */
.eco-benefits {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.eco-benefit {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    background: var(--color-white);
    transition: all 0.3s ease;
}

.eco-benefit:hover {
    border-color: var(--color-forest-light);
    transform: translateX(4px);
}

.benefit-icon {
    width: 40px;
    height: 40px;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.eco-benefit:hover .benefit-icon {
    background: var(--color-forest-light);
    color: var(--color-white);
    border-color: var(--color-forest-light);
}

.eco-benefit span {
    font-weight: 500;
    color: var(--color-forest-dark);
}

/* Alert Styles */
.alert {
    border-radius: var(--border-radius);
    border: 1px solid;
    margin-bottom: 2rem;
    padding: 1.25rem 1.5rem;
    font-weight: 500;
}

.alert-success {
    background-color: #e8f5e8;
    border-color: #d4edda;
    color: var(--color-forest-dark);
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
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
        flex-wrap: wrap;
    }
    
    .no-orders-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .support-option {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .support-icon {
        margin-right: 0;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .order-header {
        padding: 1rem;
    }
    
    .order-details {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.625rem 1.25rem;
        font-size: 0.8125rem;
    }
}
</style>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
require_once __DIR__ . '/includes/footer.php'; 

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

function calculateEcoPoints($totalAmount) {
    // Calculate EcoPoints: 10 points per R100 spent
    return intval($totalAmount / 100) * 10 + 50; // Base 50 points + points based on amount
}
?>