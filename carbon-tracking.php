<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Get user's carbon savings if logged in
$user_carbon_saved = 0;
$user_products_count = 0;
$carbon_leaderboard = [];
$monthly_savings = [];

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Get user's total carbon savings - simplified without order_status
    $carbon_sql = "SELECT SUM(p.co2_saved * oi.quantity) as total_carbon_saved,
                          COUNT(DISTINCT oi.product_id) as products_count
                   FROM order_items oi 
                   JOIN orders o ON oi.order_id = o.order_id 
                   JOIN products p ON oi.product_id = p.product_id 
                   WHERE o.user_id = ?";
    $carbon_stmt = $conn->prepare($carbon_sql);
    $carbon_stmt->bind_param("i", $user_id);
    $carbon_stmt->execute();
    $carbon_result = $carbon_stmt->get_result()->fetch_assoc();
    
    $user_carbon_saved = $carbon_result['total_carbon_saved'] ?? 0;
    $user_products_count = $carbon_result['products_count'] ?? 0;
    
    // Get carbon leaderboard - simplified without order_status
    $leaderboard_sql = "SELECT u.first_name, u.last_name, 
                               SUM(p.co2_saved * oi.quantity) as total_carbon_saved
                        FROM users u
                        JOIN orders o ON u.user_id = o.user_id
                        JOIN order_items oi ON o.order_id = oi.order_id
                        JOIN products p ON oi.product_id = p.product_id
                        GROUP BY u.user_id
                        ORDER BY total_carbon_saved DESC
                        LIMIT 10";
    $leaderboard_result = $conn->query($leaderboard_sql);
    while ($row = $leaderboard_result->fetch_assoc()) {
        $carbon_leaderboard[] = $row;
    }
    
    // Get monthly carbon savings for current year - simplified without order_status
    $monthly_sql = "SELECT MONTH(o.order_date) as month,
                           SUM(p.co2_saved * oi.quantity) as monthly_saved
                    FROM orders o
                    JOIN order_items oi ON o.order_id = oi.order_id
                    JOIN products p ON oi.product_id = p.product_id
                    WHERE o.user_id = ? 
                    AND YEAR(o.order_date) = YEAR(CURDATE())
                    GROUP BY MONTH(o.order_date)
                    ORDER BY month";
    $monthly_stmt = $conn->prepare($monthly_sql);
    $monthly_stmt->bind_param("i", $user_id);
    $monthly_stmt->execute();
    $monthly_result = $monthly_stmt->get_result();
    
    // Initialize all months with 0
    for ($i = 1; $i <= 12; $i++) {
        $monthly_savings[$i] = 0;
    }
    
    while ($row = $monthly_result->fetch_assoc()) {
        $monthly_savings[$row['month']] = $row['monthly_saved'];
    }
}

// Get community total carbon savings - simplified without order_status
$community_sql = "SELECT SUM(p.co2_saved * oi.quantity) as total_community_carbon
                  FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.order_id 
                  JOIN products p ON oi.product_id = p.product_id";
$community_result = $conn->query($community_sql);
$community_carbon = $community_result->fetch_assoc()['total_community_carbon'] ?? 0;

// Carbon equivalents data
$carbon_equivalents = [
    'trees' => $user_carbon_saved / 21.77, // kg CO2 absorbed by one tree per year
    'cars' => $user_carbon_saved / 4600,   // kg CO2 from average car per year
    'flights' => $user_carbon_saved / 200,  // kg CO2 per short-haul flight
    'smartphones' => $user_carbon_saved / 55 // kg CO2 to manufacture one smartphone
];

include 'includes/header.php';
?>

<style>
:root {
    --bg-sandy-light: #d4c4a8;
    --bg-sandy-dark: #c2b299;
    --primary-green: #4a6b4a;
    --secondary-green: #5a7a5a;
    --text-green: #2d4a2d;
    --surface-white: #ffffff;
    --border-radius-organic: 30px;
    --border-radius-cards: 20px;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--bg-sandy-light) 0%, var(--bg-sandy-dark) 100%);
    color: var(--text-green);
    line-height: 1.6;
    min-height: 100vh;
    position: relative;
}

.container {
    position: relative;
    z-index: 2;
}

/* Organic Shapes */
.organic-shape {
    position: absolute;
    opacity: 0.25;
    border-radius: 60% 40% 30% 70%;
    animation: float 8s ease-in-out infinite;
    z-index: 1;
}

.shape-1 {
    width: 180px;
    height: 180px;
    background: var(--primary-green);
    top: 10%;
    left: 5%;
    animation-delay: 0s;
}

.shape-2 {
    width: 140px;
    height: 140px;
    background: var(--secondary-green);
    top: 60%;
    right: 8%;
    animation-delay: -3s;
}

.shape-3 {
    width: 160px;
    height: 160px;
    background: var(--text-green);
    bottom: 15%;
    left: 12%;
    animation-delay: -6s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(5deg);
    }
}

/* Cards */
.card {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: var(--border-radius-cards);
    box-shadow: 0 8px 32px rgba(45, 74, 45, 0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(45, 74, 45, 0.15);
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.card-header {
    background: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    border-radius: var(--border-radius-cards) var(--border-radius-cards) 0 0 !important;
    padding: 1.25rem;
}

/* Buttons */
.btn-dragon {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(74, 107, 74, 0.3);
}

.btn-dragon:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 107, 74, 0.4);
    background: linear-gradient(135deg, var(--secondary-green) 0%, var(--primary-green) 100%);
    color: white;
}

/* Progress bars */
.progress {
    border-radius: 10px;
    background-color: rgba(74, 107, 74, 0.1);
    height: 20px;
}

.progress-bar {
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
}

/* Badges */
.badge {
    border-radius: 10px;
    font-weight: 500;
    padding: 6px 12px;
}

.bg-primary { background-color: var(--primary-green) !important; }
.bg-success { background-color: var(--secondary-green) !important; }

/* Typography */
h1, h2, h3, h4, h5, h6 {
    color: var(--text-green);
    font-weight: 600;
}

.text-muted {
    color: rgba(45, 74, 45, 0.7) !important;
}

.text-success {
    color: var(--primary-green) !important;
}

.display-4 {
    font-size: 3.5rem;
    color: var(--primary-green);
    font-weight: 700;
}

.fs-2 {
    font-size: 2rem;
    color: var(--primary-green);
}

/* Carbon equivalents */
.carbon-equivalent {
    background: linear-gradient(135deg, rgba(90, 122, 90, 0.1), rgba(74, 107, 74, 0.05));
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    border: 2px solid rgba(90, 122, 90, 0.2);
    transition: all 0.3s ease;
}

.carbon-equivalent:hover {
    transform: translateY(-3px);
    border-color: var(--secondary-green);
}

/* Leaderboard */
.leaderboard-item {
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    padding: 1rem 0;
    transition: all 0.3s ease;
}

.leaderboard-item:hover {
    background-color: rgba(74, 107, 74, 0.05);
    transform: translateX(5px);
}

.rank-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary-green);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

/* Chart container */
.chart-container {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.month-bar {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    border-radius: 5px;
    transition: all 0.3s ease;
    min-height: 20px;
}

.month-bar:hover {
    opacity: 0.8;
}

/* Login prompt */
.login-prompt {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.8));
    border-radius: var(--border-radius-cards);
    padding: 3rem;
    text-align: center;
    border: 2px solid rgba(74, 107, 74, 0.2);
}
</style>

<div class="organic-shape shape-1"></div>
<div class="organic-shape shape-2"></div>
<div class="organic-shape shape-3"></div>

<div class="container py-5">
    <h1 class="text-center mb-5">Carbon Tracking Dashboard</h1>
    
    <?php if (!isLoggedIn()): ?>
        <!-- Login Prompt for non-logged in users -->
        <div class="login-prompt mb-5">
            <div class="fs-1 mb-3">🌱</div>
            <h3>Track Your Carbon Impact</h3>
            <p class="text-muted mb-4">Login to see your personal carbon savings and join our community of eco-conscious shoppers.</p>
            <a href="login.php" class="btn btn-dragon me-3">Login to View Dashboard</a>
            <a href="products.php" class="btn btn-outline-primary">Browse Eco Products</a>
        </div>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
        <!-- Personal Carbon Stats -->
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Carbon Savings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-4">
                                <div class="display-4"><?php echo number_format($user_carbon_saved, 1); ?>kg</div>
                                <p class="text-muted">Total CO₂ Saved</p>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="display-4"><?php echo $user_products_count; ?></div>
                                <p class="text-muted">Eco Products Purchased</p>
                            </div>
                        </div>
                        
                        <!-- Progress towards goals -->
                        <div class="mb-4">
                            <h6>Annual Goal Progress (100kg CO₂)</h6>
                            <div class="progress mb-2">
                                <div class="progress-bar" style="width: <?php echo min(($user_carbon_saved / 100) * 100, 100); ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo number_format(min($user_carbon_saved, 100), 1); ?>kg / 100kg</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Community Impact</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="fs-2 mb-3">👥</div>
                        <h3><?php echo number_format($community_carbon, 1); ?>kg</h3>
                        <p class="text-muted">Total Community CO₂ Saved</p>
                        <div class="mt-3">
                            <small class="text-success">Together we're making a difference! 🌍</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carbon Equivalents -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Impact in Perspective</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="carbon-equivalent">
                                    <div class="fs-2 mb-2">🌳</div>
                                    <h4><?php echo number_format($carbon_equivalents['trees'], 1); ?></h4>
                                    <p class="text-muted mb-0">Trees planted for one year</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="carbon-equivalent">
                                    <div class="fs-2 mb-2">🚗</div>
                                    <h4><?php echo number_format($carbon_equivalents['cars'], 1); ?></h4>
                                    <p class="text-muted mb-0">Cars off the road for one year</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="carbon-equivalent">
                                    <div class="fs-2 mb-2">✈️</div>
                                    <h4><?php echo number_format($carbon_equivalents['flights'], 1); ?></h4>
                                    <p class="text-muted mb-0">Short-haul flights avoided</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="carbon-equivalent">
                                    <div class="fs-2 mb-2">📱</div>
                                    <h4><?php echo number_format($carbon_equivalents['smartphones'], 1); ?></h4>
                                    <p class="text-muted mb-0">Smartphones not manufactured</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Progress Chart -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Carbon Savings</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <div class="row align-items-end" style="height: 200px;">
                                <?php
                                $month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                $max_savings = max($monthly_savings) ?: 1;
                                ?>
                                <?php foreach ($monthly_savings as $month => $savings): ?>
                                    <div class="col text-center">
                                        <div class="mb-2">
                                            <small class="text-muted"><?php echo $month_names[$month-1]; ?></small>
                                        </div>
                                        <div class="month-bar mx-auto" style="height: <?php echo ($savings / $max_savings) * 150; ?>px; width: 30px;"></div>
                                        <div class="mt-2">
                                            <small class="text-success"><?php echo number_format($savings, 1); ?>kg</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Carbon Savings Leaderboard</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($carbon_leaderboard)): ?>
                            <?php foreach ($carbon_leaderboard as $index => $user): ?>
                                <div class="leaderboard-item d-flex align-items-center">
                                    <div class="rank-badge me-3">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        <div class="text-success">
                                            <?php echo number_format($user['total_carbon_saved'], 1); ?>kg CO₂ saved
                                        </div>
                                    </div>
                                    <?php if ($index < 3): ?>
                                        <div class="fs-5">
                                            <?php echo ['🥇', '🥈', '🥉'][$index]; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tips to Increase Your Impact</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>🛒 Shop Smart</h6>
                            <p class="text-muted small">Choose products with higher CO₂ savings to maximize your impact with each purchase.</p>
                        </div>
                        <div class="mb-3">
                            <h6>📊 Track Regularly</h6>
                            <p class="text-muted small">Monitor your carbon savings monthly to stay motivated and on track with your goals.</p>
                        </div>
                        <div class="mb-3">
                            <h6>👥 Spread Awareness</h6>
                            <p class="text-muted small">Share your progress with friends and encourage them to join the eco-friendly movement.</p>
                        </div>
                        <div class="mb-3">
                            <h6>🎯 Set Goals</h6>
                            <p class="text-muted small">Aim for specific carbon saving targets each quarter to maintain consistent progress.</p>
                        </div>
                        <a href="products.php" class="btn btn-dragon w-100">Browse Eco Products</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>