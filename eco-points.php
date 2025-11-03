<?php
// Use require_once instead of include
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

requireLogin();

$conn = getDatabaseConnection();

$user_id = $_SESSION['user_id'];

// Get user's EcoPoints data
$user_sql = "SELECT eco_points_balance, first_name FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Get EcoPoints transactions
$transactions_sql = "SELECT * FROM eco_point_transactions 
                     WHERE user_id = ? 
                     ORDER BY transaction_date DESC";
$transactions_stmt = $conn->prepare($transactions_sql);
$transactions_stmt->bind_param("i", $user_id);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();

// Get available rewards
$rewards = [
    ['R50 Voucher', 500, 'Get R50 off your next order', 'voucher', 'r50'],
    ['Plant a Tree', 1000, 'We\'ll plant a tree in your name', 'tree', 'tree'],
    ['R100 Voucher', 900, 'Get R100 off your next order', 'voucher', 'r100'],
    ['Eco Tote Bag', 750, 'DragonStone branded tote bag', 'physical', 'tote'],
    ['Carbon Offset', 1500, 'Offset 100kg of carbon emissions', 'carbon', 'carbon']
];

$page_title = "My EcoPoints - DragonStone";
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
                    <a href="orders.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </span>
                        My Orders
                    </a>
                    <a href="eco-points.php" class="sidebar-item active">
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
                <h1 class="page-title">My EcoPoints</h1>
                <p class="page-subtitle">Earn rewards for your sustainable choices</p>
            </div>
            
            <!-- Points Balance Card -->
            <div class="points-balance-card">
                <div class="balance-content">
                    <div class="balance-info">
                        <h2 class="balance-amount"><?php echo number_format($user['eco_points_balance']); ?></h2>
                        <p class="balance-label">Current EcoPoints Balance</p>
                        <div class="balance-actions">
                            <a href="products.php" class="btn btn-primary">Earn More Points</a>
                        </div>
                    </div>
                    <div class="balance-icon">
                        <div class="points-icon-container">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- How to Earn Points -->
                <div class="col-md-6">
                    <div class="earn-points-card">
                        <div class="card-header">
                            <h3 class="card-title">How to Earn Points</h3>
                        </div>
                        <div class="card-body">
                            <div class="earn-points-list">
                                <div class="earn-point-item">
                                    <div class="point-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="9" cy="21" r="1"></circle>
                                            <circle cx="20" cy="21" r="1"></circle>
                                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                        </svg>
                                    </div>
                                    <div class="point-details">
                                        <span class="point-action">Make a purchase</span>
                                        <span class="point-value">+100 pts/order</span>
                                    </div>
                                </div>
                                <div class="earn-point-item">
                                    <div class="point-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="9" y1="3" x2="9" y2="21"></line>
                                        </svg>
                                    </div>
                                    <div class="point-details">
                                        <span class="point-action">Each product in order</span>
                                        <span class="point-value">+50 pts/item</span>
                                    </div>
                                </div>
                                <div class="earn-point-item">
                                    <div class="point-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="point-details">
                                        <span class="point-action">Write a product review</span>
                                        <span class="point-value">+25 pts/review</span>
                                    </div>
                                </div>
                                <div class="earn-point-item">
                                    <div class="point-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                            <line x1="20" y1="8" x2="20" y2="14"></line>
                                            <line x1="23" y1="11" x2="17" y2="11"></line>
                                        </svg>
                                    </div>
                                    <div class="point-details">
                                        <span class="point-action">Refer a friend</span>
                                        <span class="point-value">+200 pts/friend</span>
                                    </div>
                                </div>
                                <div class="earn-point-item">
                                    <div class="point-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="8" r="7"></circle>
                                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                        </svg>
                                    </div>
                                    <div class="point-details">
                                        <span class="point-action">Complete eco-challenges</span>
                                        <span class="point-value">+100 pts/challenge</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Available Rewards -->
                <div class="col-md-6">
                    <div class="rewards-card">
                        <div class="card-header">
                            <h3 class="card-title">Available Rewards</h3>
                        </div>
                        <div class="card-body">
                            <div class="rewards-list">
                                <?php foreach($rewards as $reward): 
                                    $can_redeem = $user['eco_points_balance'] >= $reward[1];
                                    $points_needed = $reward[1] - $user['eco_points_balance'];
                                ?>
                                    <div class="reward-item <?php echo $can_redeem ? 'can-redeem' : ''; ?>">
                                        <div class="reward-content">
                                            <div class="reward-info">
                                                <h4 class="reward-name"><?php echo $reward[0]; ?></h4>
                                                <p class="reward-description"><?php echo $reward[2]; ?></p>
                                            </div>
                                            <div class="reward-actions">
                                                <div class="reward-cost">
                                                    <span class="cost-badge"><?php echo $reward[1]; ?> pts</span>
                                                </div>
                                                <?php if ($can_redeem): ?>
                                                    <form method="POST" action="redeem-reward.php" class="d-inline">
                                                        <input type="hidden" name="reward_type" value="<?php echo $reward[3]; ?>">
                                                        <input type="hidden" name="reward_name" value="<?php echo $reward[0]; ?>">
                                                        <input type="hidden" name="reward_cost" value="<?php echo $reward[1]; ?>">
                                                        <input type="hidden" name="reward_code" value="<?php echo $reward[4]; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Redeem <?php echo $reward[0]; ?> for <?php echo $reward[1]; ?> points?')">Redeem Now</button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="points-needed">
                                                        <small>Need <?php echo $points_needed; ?> more points</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transaction History -->
            <div class="transactions-card">
                <div class="card-header">
                    <h3 class="card-title">Points History</h3>
                </div>
                <div class="card-body">
                    <?php if ($transactions->num_rows > 0): ?>
                        <div class="transactions-table">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Points</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($transaction = $transactions->fetch_assoc()): 
                                            $is_earned = $transaction['transaction_type'] == 'Earned';
                                        ?>
                                            <tr class="transaction-row">
                                                <td class="transaction-date">
                                                    <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                                </td>
                                                <td class="transaction-description">
                                                    <?php echo htmlspecialchars($transaction['reason']); ?>
                                                </td>
                                                <td class="transaction-points">
                                                    <span class="points-badge <?php echo $is_earned ? 'earned' : 'spent'; ?>">
                                                        <?php echo $is_earned ? '+' : '-'; ?>
                                                        <?php echo $transaction['points']; ?>
                                                    </span>
                                                </td>
                                                <td class="transaction-type">
                                                    <span class="type-badge <?php echo $is_earned ? 'earned' : 'spent'; ?>">
                                                        <?php echo $transaction['transaction_type']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-transactions">
                            <div class="no-data-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                                </svg>
                            </div>
                            <h4>No EcoPoints Activity Yet</h4>
                            <p>Make a purchase to start earning EcoPoints!</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Eco Points Page Styles */
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

/* Points Balance Card */
.points-balance-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    color: var(--color-white);
    margin-bottom: 2rem;
    overflow: hidden;
}

.balance-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
}

.balance-info {
    flex: 1;
}

.balance-amount {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.balance-label {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
}

.balance-actions .btn {
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: var(--color-white);
}

.balance-actions .btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
}

.points-icon-container {
    width: 80px;
    height: 80px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
}

/* Cards */
.earn-points-card,
.rewards-card,
.transactions-card {
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

.card-title {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 1.25rem;
}

.card-body {
    padding: 1.5rem;
}

/* Earn Points List */
.earn-points-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.earn-point-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    background: var(--color-white);
    transition: all 0.3s ease;
}

.earn-point-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-1px);
}

.point-icon {
    width: 40px;
    height: 40px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
}

.point-details {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.point-action {
    font-weight: 500;
    color: var(--color-forest-dark);
}

.point-value {
    font-weight: 600;
    color: var(--color-forest-medium);
    background: var(--color-sand-light);
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    border: 1px solid var(--color-border);
    font-size: 0.875rem;
}

/* Rewards List */
.rewards-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.reward-item {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: 1.25rem;
    background: var(--color-white);
    transition: all 0.3s ease;
}

.reward-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
}

.reward-item.can-redeem {
    border-color: var(--color-forest-medium);
    background: var(--color-sand-light);
}

.reward-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reward-info {
    flex: 1;
}

.reward-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
}

.reward-description {
    color: var(--color-text-light);
    margin: 0;
    font-size: 0.875rem;
}

.reward-actions {
    text-align: right;
}

.cost-badge {
    background: var(--color-forest-medium);
    color: var(--color-white);
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid var(--color-forest-medium);
    display: inline-block;
    margin-bottom: 0.5rem;
}

.points-needed {
    color: var(--color-text-light);
    font-size: 0.875rem;
}

/* Transactions Table */
.transactions-table .table {
    margin: 0;
}

.transaction-row {
    border-bottom: 1px solid var(--color-border);
}

.transaction-row:last-child {
    border-bottom: none;
}

.transaction-date {
    font-weight: 500;
    color: var(--color-forest-dark);
}

.transaction-description {
    color: var(--color-text);
}

.points-badge,
.type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid;
}

.points-badge.earned,
.type-badge.earned {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.points-badge.spent,
.type-badge.spent {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

/* No Transactions State */
.no-transactions {
    text-align: center;
    padding: 3rem 2rem;
}

.no-data-icon {
    margin-bottom: 1rem;
    color: var(--color-text-light);
}

.no-transactions h4 {
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.no-transactions p {
    color: var(--color-text-light);
    margin-bottom: 1.5rem;
}

/* Alert Styles */
.alert {
    border-radius: var(--border-radius);
    border: 1px solid;
    margin-bottom: 2rem;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* Responsive Design */
@media (max-width: 768px) {
    .balance-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .balance-amount {
        font-size: 2.5rem;
    }
    
    .reward-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .reward-actions {
        text-align: left;
        width: 100%;
    }
    
    .transactions-table .table {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .earn-point-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .point-details {
        width: 100%;
    }
}
</style>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
require_once __DIR__ . '/includes/footer.php'; 
?>