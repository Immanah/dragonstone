<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: eco-points.php');
    exit;
}

$conn = getDatabaseConnection();
$user_id = $_SESSION['user_id'];

// Get reward details from form
$reward_type = $_POST['reward_type'] ?? '';
$reward_name = $_POST['reward_name'] ?? '';
$reward_cost = (int)($_POST['reward_cost'] ?? 0);
$reward_code = $_POST['reward_code'] ?? '';

// Validate reward data
$valid_rewards = [
    'voucher' => ['r50', 'r100'],
    'tree' => ['tree'],
    'physical' => ['tote'],
    'carbon' => ['carbon']
];

if (!isset($valid_rewards[$reward_type]) || !in_array($reward_code, $valid_rewards[$reward_type])) {
    $_SESSION['error'] = "Invalid reward selected.";
    header('Location: eco-points.php');
    exit;
}

// Get user's current balance
$user_sql = "SELECT eco_points_balance, first_name, email FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Check if user has enough points
if ($user['eco_points_balance'] < $reward_cost) {
    $_SESSION['error'] = "Insufficient EcoPoints balance.";
    header('Location: eco-points.php');
    exit;
}

$page_title = "Redeem Reward - DragonStone";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="redeem-card">
                <div class="redeem-header text-center">
                    <div class="redeem-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h1 class="redeem-title">Redeem Your Reward</h1>
                    <p class="redeem-subtitle">Confirm your redemption details</p>
                </div>
                
                <div class="redeem-body">
                    <div class="reward-summary">
                        <div class="summary-item">
                            <span class="summary-label">Reward:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($reward_name); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Cost:</span>
                            <span class="summary-value text-danger">-<?php echo number_format($reward_cost); ?> EcoPoints</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">New Balance:</span>
                            <span class="summary-value text-success"><?php echo number_format($user['eco_points_balance'] - $reward_cost); ?> EcoPoints</span>
                        </div>
                    </div>
                    
                    <div class="reward-instructions">
                        <h5>How to claim:</h5>
                        <?php if ($reward_type === 'voucher'): ?>
                            <p>Your voucher code will be generated immediately and can be used at checkout.</p>
                        <?php elseif ($reward_type === 'tree'): ?>
                            <p>We'll plant a tree in your name and email you a digital certificate.</p>
                        <?php elseif ($reward_type === 'physical'): ?>
                            <p>Your Eco Tote Bag will be shipped to your registered address within 5-7 business days.</p>
                        <?php elseif ($reward_type === 'carbon'): ?>
                            <p>100kg of carbon emissions will be offset in your name through verified projects.</p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="process-redemption.php" class="redeem-form">
                        <input type="hidden" name="reward_type" value="<?php echo htmlspecialchars($reward_type); ?>">
                        <input type="hidden" name="reward_name" value="<?php echo htmlspecialchars($reward_name); ?>">
                        <input type="hidden" name="reward_cost" value="<?php echo $reward_cost; ?>">
                        <input type="hidden" name="reward_code" value="<?php echo htmlspecialchars($reward_code); ?>">
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="confirmRedemption" required>
                            <label class="form-check-label" for="confirmRedemption">
                                I confirm that I want to redeem <?php echo htmlspecialchars($reward_name); ?> for <?php echo number_format($reward_cost); ?> EcoPoints
                            </label>
                        </div>
                        
                        <div class="redeem-actions">
                            <a href="eco-points.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Confirm Redemption</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.redeem-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
}

.redeem-header {
    padding: 2rem;
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    color: var(--color-white);
}

.redeem-icon {
    margin-bottom: 1rem;
}

.redeem-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.redeem-subtitle {
    opacity: 0.9;
    margin: 0;
}

.redeem-body {
    padding: 2rem;
}

.reward-summary {
    background: var(--color-sand-light);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-border-light);
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    font-weight: 600;
    color: var(--color-forest-dark);
}

.summary-value {
    font-weight: 600;
}

.reward-instructions {
    background: var(--color-sand-light);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.reward-instructions h5 {
    color: var(--color-forest-dark);
    margin-bottom: 0.75rem;
}

.redeem-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.redeem-actions .btn {
    min-width: 120px;
}

@media (max-width: 576px) {
    .redeem-actions {
        flex-direction: column;
    }
    
    .redeem-actions .btn {
        min-width: auto;
    }
}
</style>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>