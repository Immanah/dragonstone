<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/database.php';

requireLogin();

if (!isset($_SESSION['redemption_success'])) {
    header('Location: eco-points.php');
    exit;
}

$success_data = $_SESSION['redemption_success'];
unset($_SESSION['redemption_success']); // Clear the success data

$page_title = "Redemption Successful - DragonStone";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="success-card text-center">
                <div class="success-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                
                <h1 class="success-title">Redemption Successful!</h1>
                <p class="success-subtitle">You've successfully redeemed your reward</p>
                
                <div class="success-details">
                    <div class="detail-item">
                        <strong>Reward:</strong> <?php echo htmlspecialchars($success_data['reward_name']); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Points Deducted:</strong> <?php echo number_format($success_data['reward_cost']); ?> EcoPoints
                    </div>
                    <div class="detail-item">
                        <strong>Redemption ID:</strong> #<?php echo $success_data['redemption_id']; ?>
                    </div>
                    
                    <?php if ($success_data['reward_type'] === 'voucher' && !empty($success_data['voucher_code'])): ?>
                        <div class="voucher-code">
                            <h5>Your Voucher Code:</h5>
                            <div class="code-display"><?php echo $success_data['voucher_code']; ?></div>
                            <p class="voucher-instructions">Use this code at checkout to apply your discount</p>
                        </div>
                    <?php elseif ($success_data['reward_type'] === 'tree'): ?>
                        <div class="reward-info">
                            <h5>🌱 Tree Planting Confirmed</h5>
                            <p>A tree will be planted in your name within the next 7 days. You'll receive a digital certificate via email.</p>
                        </div>
                    <?php elseif ($success_data['reward_type'] === 'physical'): ?>
                        <div class="reward-info">
                            <h5>📦 Shipping Confirmed</h5>
                            <p>Your Eco Tote Bag will be shipped to your registered address within 5-7 business days.</p>
                        </div>
                    <?php elseif ($success_data['reward_type'] === 'carbon'): ?>
                        <div class="reward-info">
                            <h5>🌍 Carbon Offset Confirmed</h5>
                            <p>100kg of carbon emissions have been offset in your name through verified environmental projects.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="success-actions">
                    <a href="eco-points.php" class="btn btn-outline-primary">Back to EcoPoints</a>
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    padding: 3rem 2rem;
}

.success-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    border: 3px solid var(--color-success);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-success);
}

.success-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.success-subtitle {
    color: var(--color-text-light);
    font-size: 1.125rem;
    margin-bottom: 2rem;
}

.success-details {
    background: var(--color-sand-light);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.detail-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-border-light);
}

.detail-item:last-child {
    border-bottom: none;
}

.voucher-code {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px dashed var(--color-border);
}

.code-display {
    font-family: 'Courier New', monospace;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-forest-medium);
    background: var(--color-white);
    padding: 0.75rem;
    border: 2px solid var(--color-forest-light);
    border-radius: var(--border-radius-sm);
    margin: 0.5rem 0;
}

.voucher-instructions {
    color: var(--color-text-light);
    font-size: 0.875rem;
    margin: 0;
}

.reward-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px dashed var(--color-border);
}

.reward-info h5 {
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

@media (max-width: 576px) {
    .success-actions {
        flex-direction: column;
    }
    
    .success-card {
        padding: 2rem 1rem;
    }
}
</style>

<?php 
include 'includes/footer.php'; 
?>