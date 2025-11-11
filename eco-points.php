<?php
// Use require_once instead of include
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();

// Check if database connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get user's EcoPoints data
$user_sql = "SELECT eco_points_balance, first_name FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    die("Error preparing statement: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// If user not found, redirect to login
if (!$user) {
    header('Location: login.php');
    exit();
}

// Get EcoPoints transactions
$transactions_sql = "SELECT * FROM eco_point_transactions 
                     WHERE user_id = ? 
                     ORDER BY transaction_date DESC";
$transactions_stmt = $conn->prepare($transactions_sql);
if ($transactions_stmt) {
    $transactions_stmt->bind_param("i", $user_id);
    $transactions_stmt->execute();
    $transactions = $transactions_stmt->get_result();
} else {
    $transactions = false;
}

// Get available rewards with realistic point values
$rewards = [
    ['R20 Voucher', 200, 'Get R20 off your next order', 'voucher', 'r20'],
    ['Plant a Tree', 350, 'We\'ll plant a tree in your name', 'tree', 'tree'],
    ['R50 Voucher', 450, 'Get R50 off your next order', 'voucher', 'r50'],
    ['Eco Tote Bag', 300, 'DragonStone branded tote bag', 'physical', 'tote'],
    ['Carbon Offset', 600, 'Offset 50kg of carbon emissions', 'carbon', 'carbon'],
    ['Free Shipping', 150, 'Free shipping on your next order', 'shipping', 'shipping'],
    ['Eco Seed Pack', 100, 'Pack of organic vegetable seeds', 'physical', 'seeds']
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
                        <p class="user-email">Eco Warrior</p>
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
                <h1 class="page-title">My EcoPoints</h1>
                <p class="page-subtitle">Earn rewards for your sustainable choices</p>
            </div>
            
            <!-- Points Balance Card -->
            <div class="points-balance-card">
                <div class="balance-content">
                    <div class="balance-info">
                        <h2 class="balance-amount"><?php echo number_format($user['eco_points_balance'] ?? 0); ?></h2>
                        <p class="balance-label">Current EcoPoints Balance</p>
                        <div class="balance-actions">
                            <a href="products.php" class="btn btn-primary">Earn More Points</a>
                            <a href="#how-to-earn" class="btn btn-outline">How It Works</a>
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
                    <div class="earn-points-card dashboard-card" id="how-to-earn">
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
                                        <span class="point-value">+50 pts/order</span>
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
                                        <span class="point-action">Each eco-friendly product</span>
                                        <span class="point-value">+10 pts/item</span>
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
                                        <span class="point-value">+15 pts/review</span>
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
                                        <span class="point-value">+100 pts/friend</span>
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
                                        <span class="point-value">+25 pts/challenge</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Available Rewards -->
                <div class="col-md-6">
                    <div class="rewards-card dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Available Rewards</h3>
                        </div>
                        <div class="card-body">
                            <div class="rewards-list">
                                <?php foreach($rewards as $reward): 
                                    $can_redeem = ($user['eco_points_balance'] ?? 0) >= $reward[1];
                                    $points_needed = $reward[1] - ($user['eco_points_balance'] ?? 0);
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
                                                        <button type="submit" class="btn btn-primary btn-sm redeem-btn" 
                                                                onclick="return confirm('Redeem <?php echo $reward[0]; ?> for <?php echo $reward[1]; ?> points?')">
                                                            Redeem Now
                                                        </button>
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
            <div class="transactions-card dashboard-card">
                <div class="card-header">
                    <div class="header-content">
                        <h3 class="card-title">Points History</h3>
                        <span class="transaction-count">Last 30 days</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($transactions && $transactions->num_rows > 0): ?>
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

/* Profile Sidebar - Consistent with Profile Page */
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

/* Points Balance Card */
.points-balance-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-dark) 100%);
    color: var(--color-white);
    margin-bottom: 2.5rem;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    position: relative;
}

.points-balance-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
}

.balance-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2.5rem;
}

.balance-info {
    flex: 1;
}

.balance-amount {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.balance-label {
    font-size: 1.25rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.balance-actions {
    display: flex;
    gap: 1rem;
}

.balance-actions .btn {
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: var(--color-white);
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.balance-actions .btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.balance-actions .btn-outline {
    background: transparent;
    border: 2px solid rgba(255,255,255,0.3);
}

.points-icon-container {
    width: 100px;
    height: 100px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
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

.transaction-count {
    color: var(--color-text-light);
    font-size: 0.875rem;
    font-weight: 500;
}

.card-body {
    padding: 2rem;
}

/* Earn Points List */
.earn-points-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.earn-point-item {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    background: var(--color-white);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.earn-point-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--color-forest-light);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.earn-point-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.earn-point-item:hover::before {
    opacity: 1;
}

.point-icon {
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

.earn-point-item:hover .point-icon {
    background: var(--color-forest-light);
    color: var(--color-white);
    border-color: var(--color-forest-light);
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
    font-size: 1rem;
}

.point-value {
    font-weight: 700;
    color: var(--color-forest-medium);
    background: var(--color-sand-light);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    border: 1px solid var(--color-border);
    font-size: 0.875rem;
    letter-spacing: 0.02em;
}

/* Rewards List */
.rewards-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.reward-item {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: 1.5rem;
    background: var(--color-white);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.reward-item::before {
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

.reward-item:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.reward-item:hover::before {
    opacity: 1;
}

.reward-item.can-redeem {
    border-color: var(--color-forest-medium);
    background: var(--color-sand-light);
    box-shadow: var(--shadow-sm);
}

.reward-item.can-redeem::before {
    opacity: 1;
    background: var(--color-forest-medium);
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
    margin-bottom: 0.5rem;
    letter-spacing: -0.01em;
}

.reward-description {
    color: var(--color-text-light);
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
}

.reward-actions {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.cost-badge {
    background: var(--color-forest-medium);
    color: var(--color-white);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.875rem;
    border: 1px solid var(--color-forest-medium);
    display: inline-block;
    letter-spacing: 0.02em;
}

.redeem-btn {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border: 2px solid var(--color-forest-medium);
    background: var(--color-forest-medium);
    color: white;
    border-radius: 50px;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 0.875rem;
}

.redeem-btn:hover {
    background: var(--color-forest-dark);
    border-color: var(--color-forest-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(58, 92, 58, 0.3);
}

.points-needed {
    color: var(--color-text-light);
    font-size: 0.875rem;
    font-style: italic;
}

/* Transactions Table */
.transactions-table .table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.transaction-row {
    border-bottom: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

.transaction-row:hover {
    background: var(--color-sand-light);
}

.transaction-row:last-child {
    border-bottom: none;
}

.transaction-date {
    font-weight: 500;
    color: var(--color-forest-dark);
    white-space: nowrap;
}

.transaction-description {
    color: var(--color-text);
    max-width: 300px;
}

.points-badge,
.type-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid;
    letter-spacing: 0.02em;
}

.points-badge.earned,
.type-badge.earned {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
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
    margin-bottom: 1.5rem;
    color: var(--color-text-light);
    opacity: 0.5;
}

.no-transactions h4 {
    color: var(--color-forest-dark);
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.no-transactions p {
    color: var(--color-text-light);
    margin-bottom: 2rem;
    font-size: 1rem;
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

/* Buttons */
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

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .balance-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
        padding: 2rem;
    }
    
    .balance-amount {
        font-size: 2.5rem;
    }
    
    .balance-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .balance-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .reward-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .reward-actions {
        text-align: left;
        align-items: flex-start;
        width: 100%;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .transactions-table .table {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .earn-point-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        text-align: left;
    }
    
    .point-details {
        width: 100%;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .points-icon-container {
        width: 80px;
        height: 80px;
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