<?php
// Include files with correct paths
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

requireLogin();

$conn = getDatabaseConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Safely get user's recent orders
$recent_orders = [];
$orders_count = 0;

try {
    $orders_sql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                   FROM orders o 
                   LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                   WHERE o.user_id = ? 
                   GROUP BY o.order_id 
                   ORDER BY o.order_date DESC 
                   LIMIT 5";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $recent_orders = $orders_stmt->get_result();
    $orders_count = $recent_orders->num_rows;
} catch (Exception $e) {
    // If order_items table doesn't exist yet, use a simpler query
    $orders_sql = "SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC LIMIT 5";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $recent_orders = $orders_stmt->get_result();
    $orders_count = $recent_orders->num_rows;
}

// Safely get EcoPoints transactions
$points_history = [];
try {
    $points_sql = "SELECT * FROM eco_point_transactions 
                   WHERE user_id = ? 
                   ORDER BY transaction_date DESC 
                   LIMIT 10";
    $points_stmt = $conn->prepare($points_sql);
    $points_stmt->bind_param("i", $user_id);
    $points_stmt->execute();
    $points_history = $points_stmt->get_result();
} catch (Exception $e) {
    // If eco_point_transactions table doesn't exist yet, create empty result
    $points_history = new stdClass();
    $points_history->num_rows = 0;
}

$page_title = "My Profile - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
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
                        <h5 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="user-badges">
                            <span class="role-badge <?php echo $user['role'] == 'Admin' ? 'admin' : 'customer'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="user-points">
                        <div class="points-display">
                            <div class="points-amount"><?php echo number_format($user['eco_points_balance']); ?></div>
                            <div class="points-label">EcoPoints</div>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-menu">
                    <a href="profile.php" class="sidebar-item active">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                        </span>
                        Dashboard
                    </a>
                    <a href="orders.php" class="sidebar-item">
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
                <h1 class="page-title">My Account Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! Here's your account overview.</p>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $orders_count; ?></div>
                        <div class="stat-label">Recent Orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon points">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($user['eco_points_balance']); ?></div>
                        <div class="stat-label">EcoPoints</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">Member</div>
                        <div class="stat-label">Since <?php echo date('M Y', strtotime($user['date_created'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="header-content">
                        <h3 class="card-title">Recent Orders</h3>
                        <a href="orders.php" class="btn btn-outline">
                            <span class="btn-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($orders_count > 0): ?>
                        <div class="orders-table">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($order = $recent_orders->fetch_assoc()): 
                                            $status_class = getStatusClass($order['status']);
                                        ?>
                                            <tr class="order-row">
                                                <td class="order-id">#<?php echo $order['order_id']; ?></td>
                                                <td class="order-date"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                                <td class="order-amount">R<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td class="order-status">
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo $order['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="order-action">
                                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                            <h4>No Orders Yet</h4>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- EcoPoints History -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Recent EcoPoints Activity</h3>
                </div>
                <div class="card-body">
                    <?php if ($points_history->num_rows > 0): ?>
                        <div class="points-list">
                            <?php while($transaction = $points_history->fetch_assoc()): 
                                $is_earned = $transaction['transaction_type'] == 'Earned';
                            ?>
                                <div class="points-item">
                                    <div class="points-info">
                                        <div class="points-reason"><?php echo htmlspecialchars($transaction['reason']); ?></div>
                                        <div class="points-date"><?php echo date('M j, Y g:i A', strtotime($transaction['transaction_date'])); ?></div>
                                    </div>
                                    <div class="points-amount <?php echo $is_earned ? 'earned' : 'spent'; ?>">
                                        <span class="points-badge">
                                            <?php echo $is_earned ? '+' : '-'; ?>
                                            <?php echo $transaction['points']; ?> pts
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                                </svg>
                            </div>
                            <h4>No EcoPoints Activity</h4>
                            <p>Earn points by making purchases and completing eco-challenges!</p>
                            <a href="products.php" class="btn btn-primary">Start Earning</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
require_once __DIR__ . '/includes/footer.php'; 

// Helper function for status styling
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
?>

<style>
/* Profile Page Styles */
.page-header {
    margin-bottom: 2rem;
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

/* Profile Sidebar */
.profile-sidebar {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
}

.user-card {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    text-align: center;
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
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
}

.user-name {
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
    font-weight: 600;
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
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    border: 1px solid;
}

.role-badge.admin {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.role-badge.customer {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.user-points {
    border-top: 1px solid var(--color-border);
    padding-top: 1rem;
}

.points-display {
    text-align: center;
}

.points-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-forest-medium);
    line-height: 1;
}

.points-label {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin-top: 0.25rem;
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
}

.stat-icon.points {
    color: var(--color-forest-medium);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--color-text-light);
    font-size: 0.875rem;
}

/* Dashboard Cards */
.dashboard-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
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
}

.card-body {
    padding: 1.5rem;
}

/* Orders Table */
.orders-table .table {
    margin: 0;
}

.order-row {
    border-bottom: 1px solid var(--color-border);
}

.order-row:last-child {
    border-bottom: none;
}

.order-id,
.order-date,
.order-amount {
    font-weight: 500;
    color: var(--color-forest-dark);
}

.order-amount {
    font-weight: 600;
    color: var(--color-forest-medium);
}

/* Status Badges */
.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    border: 1px solid;
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

/* Points List */
.points-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.points-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    background: var(--color-white);
    transition: all 0.3s ease;
}

.points-item:hover {
    border-color: var(--color-forest-light);
    transform: translateX(4px);
}

.points-info {
    flex: 1;
}

.points-reason {
    font-weight: 500;
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
}

.points-date {
    font-size: 0.75rem;
    color: var(--color-text-light);
}

.points-amount.earned .points-badge {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.points-amount.spent .points-badge {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.points-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid;
}

/* No Data States */
.no-data {
    text-align: center;
    padding: 3rem 2rem;
}

.no-data-icon {
    margin-bottom: 1rem;
    color: var(--color-text-light);
}

.no-data h4 {
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.no-data p {
    color: var(--color-text-light);
    margin-bottom: 1.5rem;
}

/* Buttons */
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

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
}

.btn-icon {
    margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .orders-table .table {
        font-size: 0.875rem;
    }
    
    .points-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .points-amount {
        align-self: flex-end;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
}
</style>