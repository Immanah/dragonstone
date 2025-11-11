<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Create necessary tables if they don't exist
$tables_sql = [
    "CREATE TABLE IF NOT EXISTS carbon_impact (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT,
        product_name VARCHAR(255),
        quantity INT DEFAULT 1,
        emitted DECIMAL(10,2) DEFAULT 0,
        saved DECIMAL(10,2) DEFAULT 0,
        delivery_method VARCHAR(50) DEFAULT 'standard',
        source ENUM('manual', 'checkout') DEFAULT 'manual',
        order_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS user_carbon_goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        goal_type ENUM('weekly', 'monthly', 'custom') DEFAULT 'monthly',
        target_value DECIMAL(10,2) NOT NULL,
        current_value DECIMAL(10,2) DEFAULT 0,
        start_date DATE,
        end_date DATE,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS eco_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        points INT NOT NULL,
        reason VARCHAR(255),
        source_type VARCHAR(50),
        source_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS monthly_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        target_kg DECIMAL(10,2) NOT NULL,
        reward_points INT DEFAULT 0,
        start_date DATE,
        end_date DATE,
        status ENUM('active', 'upcoming', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS user_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        challenge_id INT NOT NULL,
        status ENUM('joined', 'completed') DEFAULT 'joined',
        progress DECIMAL(10,2) DEFAULT 0,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (challenge_id) REFERENCES monthly_challenges(id)
    )"
];

foreach ($tables_sql as $sql) {
    $conn->query($sql);
}

// Add missing columns if they don't exist
$check_columns_sql = [
    "ALTER TABLE carbon_impact ADD COLUMN IF NOT EXISTS source ENUM('manual', 'checkout') DEFAULT 'manual'",
    "ALTER TABLE carbon_impact ADD COLUMN IF NOT EXISTS order_id INT"
];

foreach ($check_columns_sql as $sql) {
    try {
        $conn->query($sql);
    } catch (Exception $e) {
        // Column might already exist, ignore error
    }
}

// Get or create sample challenges
$challenge_check = $conn->query("SELECT COUNT(*) as count FROM monthly_challenges");
if ($challenge_check->fetch_assoc()['count'] == 0) {
    $sample_challenges = [
        ["Eco Warrior Week", "Save 5kg of CO₂ this week", 5.0, 50, date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))],
        ["Monthly Green Hero", "Save 20kg of CO₂ this month", 20.0, 200, date('Y-m-01'), date('Y-m-t')],
        ["Carbon Crusher", "Make 10 sustainable purchases", 10.0, 100, date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]
    ];
    
    $stmt = $conn->prepare("INSERT INTO monthly_challenges (title, description, target_kg, reward_points, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($sample_challenges as $challenge) {
        $stmt->bind_param("ssdiss", ...$challenge);
        $stmt->execute();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    if (isset($_POST['record_purchase'])) {
        // Record carbon impact from purchase
        $product_name = $conn->real_escape_string($_POST['product_name']);
        $quantity = intval($_POST['quantity']);
        $emitted = floatval($_POST['emitted']);
        $saved = floatval($_POST['saved']);
        $delivery = $conn->real_escape_string($_POST['delivery_method']);
        
        $stmt = $conn->prepare("INSERT INTO carbon_impact (user_id, product_name, quantity, emitted, saved, delivery_method, source) VALUES (?, ?, ?, ?, ?, ?, 'manual')");
        $stmt->bind_param("isidds", $user_id, $product_name, $quantity, $emitted, $saved, $delivery);
        
        if ($stmt->execute()) {
            // Award eco points
            $points = intval($saved * 10); // 10 points per kg saved
            if ($points > 0) {
                $points_stmt = $conn->prepare("INSERT INTO eco_points (user_id, points, reason, source_type) VALUES (?, ?, 'Carbon savings from purchase', 'carbon_saving')");
                $points_stmt->bind_param("ii", $user_id, $points);
                $points_stmt->execute();
                
                // Update user's total points
                $conn->query("UPDATE users SET total_points = total_points + $points WHERE user_id = $user_id");
            }
            
            $_SESSION['success_message'] = "Purchase recorded! You saved " . number_format($saved, 2) . "kg CO₂ and earned $points EcoPoints!";
        }
    }
    
    if (isset($_POST['set_goal'])) {
        // Set carbon reduction goal
        $goal_type = $conn->real_escape_string($_POST['goal_type']);
        $target_value = floatval($_POST['target_value']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        
        // Deactivate previous active goals of same type
        $conn->query("UPDATE user_carbon_goals SET status = 'cancelled' WHERE user_id = $user_id AND goal_type = '$goal_type' AND status = 'active'");
        
        $stmt = $conn->prepare("INSERT INTO user_carbon_goals (user_id, goal_type, target_value, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $user_id, $goal_type, $target_value, $start_date, $end_date);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Goal set successfully!";
        } else {
            $_SESSION['error_message'] = "Error setting goal: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_goal'])) {
        // Update existing goal
        $goal_id = intval($_POST['goal_id']);
        $target_value = floatval($_POST['target_value']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        
        $stmt = $conn->prepare("UPDATE user_carbon_goals SET target_value = ?, start_date = ?, end_date = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("dssii", $target_value, $start_date, $end_date, $goal_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Goal updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating goal: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_goal'])) {
        // Delete goal
        $goal_id = intval($_POST['goal_id']);
        
        $stmt = $conn->prepare("DELETE FROM user_carbon_goals WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $goal_id, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Goal deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting goal: " . $conn->error;
        }
    }
    
    if (isset($_POST['join_challenge'])) {
        // Join monthly challenge
        $challenge_id = intval($_POST['challenge_id']);
        
        $check_stmt = $conn->prepare("SELECT id FROM user_challenges WHERE user_id = ? AND challenge_id = ?");
        $check_stmt->bind_param("ii", $user_id, $challenge_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows == 0) {
            $join_stmt = $conn->prepare("INSERT INTO user_challenges (user_id, challenge_id) VALUES (?, ?)");
            $join_stmt->bind_param("ii", $user_id, $challenge_id);
            
            if ($join_stmt->execute()) {
                $_SESSION['success_message'] = "Challenge joined successfully!";
            }
        }
    }
    
    header("Location: carbon-tracking.php");
    exit();
}

// Get user data
$user_carbon_data = [];
$user_goals = [];
$user_challenges = [];
$eco_points = 0;
$leaderboard = [];
$category_breakdown = [];
$all_products = [];
$recent_checkout_impacts = [];
$checkout_saved = 0;
$manual_saved = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // First check if source column exists
    $check_source_column = $conn->query("SHOW COLUMNS FROM carbon_impact LIKE 'source'");
    $source_column_exists = $check_source_column->num_rows > 0;
    
    // Build carbon impact query based on available columns
    if ($source_column_exists) {
        $carbon_sql = "SELECT 
            COALESCE(SUM(emitted), 0) as total_emitted,
            COALESCE(SUM(saved), 0) as total_saved,
            COUNT(*) as total_purchases,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN saved ELSE 0 END), 0) as week_saved,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN saved ELSE 0 END), 0) as month_saved,
            COALESCE(AVG(saved), 0) as avg_saving_per_purchase,
            COALESCE(SUM(CASE WHEN source = 'checkout' THEN saved ELSE 0 END), 0) as checkout_saved,
            COALESCE(SUM(CASE WHEN source = 'manual' THEN saved ELSE 0 END), 0) as manual_saved
        FROM carbon_impact WHERE user_id = ?";
    } else {
        // Fallback query without source column
        $carbon_sql = "SELECT 
            COALESCE(SUM(emitted), 0) as total_emitted,
            COALESCE(SUM(saved), 0) as total_saved,
            COUNT(*) as total_purchases,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN saved ELSE 0 END), 0) as week_saved,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN saved ELSE 0 END), 0) as month_saved,
            COALESCE(AVG(saved), 0) as avg_saving_per_purchase,
            0 as checkout_saved,
            COALESCE(SUM(saved), 0) as manual_saved
        FROM carbon_impact WHERE user_id = ?";
    }
    
    $stmt = $conn->prepare($carbon_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_carbon_data = $stmt->get_result()->fetch_assoc() ?? [];
    
    // Get user goals
    $goals_sql = "SELECT * FROM user_carbon_goals WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC";
    $stmt = $conn->prepare($goals_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Update goal progress
    foreach ($user_goals as &$goal) {
        if ($goal['goal_type'] === 'weekly') {
            $progress_sql = "SELECT COALESCE(SUM(saved), 0) as progress 
                            FROM carbon_impact 
                            WHERE user_id = ? 
                            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } else {
            $progress_sql = "SELECT COALESCE(SUM(saved), 0) as progress 
                            FROM carbon_impact 
                            WHERE user_id = ? 
                            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        
        $progress_stmt = $conn->prepare($progress_sql);
        $progress_stmt->bind_param("i", $user_id);
        $progress_stmt->execute();
        $progress_result = $progress_stmt->get_result()->fetch_assoc();
        $goal['current_value'] = $progress_result['progress'] ?? 0;
        
        // Update goal in database
        $update_stmt = $conn->prepare("UPDATE user_carbon_goals SET current_value = ? WHERE id = ?");
        $update_stmt->bind_param("di", $goal['current_value'], $goal['id']);
        $update_stmt->execute();
    }
    
    // Get user challenges
    $challenges_sql = "SELECT mc.*, uc.status as user_status, uc.progress 
                      FROM monthly_challenges mc 
                      LEFT JOIN user_challenges uc ON mc.id = uc.challenge_id AND uc.user_id = ?
                      WHERE mc.status = 'active'";
    $stmt = $conn->prepare($challenges_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_challenges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get eco points
    $points_sql = "SELECT COALESCE(SUM(points), 0) as total_points FROM eco_points WHERE user_id = ?";
    $stmt = $conn->prepare($points_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $eco_points_result = $stmt->get_result()->fetch_assoc();
    $eco_points = $eco_points_result['total_points'] ?? 0;
    
    // Get leaderboard - ONLY CUSTOMERS (role = 'customer')
    $leaderboard_sql = "SELECT u.user_id, u.first_name, u.last_name, 
                               COALESCE(SUM(ep.points), 0) as total_points,
                               COALESCE(SUM(ci.saved), 0) as total_saved
                        FROM users u
                        LEFT JOIN eco_points ep ON u.user_id = ep.user_id
                        LEFT JOIN carbon_impact ci ON u.user_id = ci.user_id
                        WHERE u.role = 'customer'  -- ONLY SHOW CUSTOMERS
                        GROUP BY u.user_id, u.first_name, u.last_name
                        ORDER BY total_points DESC, total_saved DESC
                        LIMIT 10";
    $leaderboard_result = $conn->query($leaderboard_sql);
    $leaderboard = $leaderboard_result->fetch_all(MYSQLI_ASSOC);
    
    // Get category breakdown
    $category_sql = "SELECT 
        CASE 
            WHEN product_name LIKE '%bamboo%' OR product_name LIKE '%Bamboo%' THEN 'Bamboo Products'
            WHEN product_name LIKE '%reusable%' OR product_name LIKE '%Reusable%' THEN 'Reusable Items'
            WHEN product_name LIKE '%organic%' OR product_name LIKE '%Organic%' THEN 'Organic Products'
            WHEN product_name LIKE '%recycled%' OR product_name LIKE '%Recycled%' THEN 'Recycled Materials'
            ELSE 'Other Sustainable'
        END as category,
        COALESCE(SUM(saved), 0) as total_saved,
        COUNT(*) as purchase_count
    FROM carbon_impact 
    WHERE user_id = ?
    GROUP BY category
    ORDER BY total_saved DESC";
    
    $stmt = $conn->prepare($category_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $category_breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get ALL products from database for calculator
    $products_sql = "SELECT product_id, name, description, price, co2_saved, image_url 
                     FROM products 
                     WHERE is_active = 1 
                     ORDER BY name";
    $products_result = $conn->query($products_sql);
    $all_products = $products_result->fetch_all(MYSQLI_ASSOC);
    
    // Get recent checkout impacts (auto-recorded from orders) - only if source column exists
    if ($source_column_exists) {
        $recent_impacts_sql = "SELECT ci.*, o.order_date 
                              FROM carbon_impact ci 
                              LEFT JOIN orders o ON ci.order_id = o.order_id 
                              WHERE ci.user_id = ? AND ci.source = 'checkout' 
                              ORDER BY ci.created_at DESC 
                              LIMIT 5";
        $stmt = $conn->prepare($recent_impacts_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $recent_checkout_impacts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Calculate manual vs checkout savings
    $checkout_saved = $user_carbon_data['checkout_saved'] ?? 0;
    $manual_saved = $user_carbon_data['manual_saved'] ?? $user_carbon_data['total_saved'] ?? 0;
}

// Get community totals
$community_sql = "SELECT 
    COUNT(DISTINCT user_id) as total_users,
    COALESCE(SUM(saved), 0) as total_community_saved,
    COUNT(*) as total_purchases
FROM carbon_impact";
$community_result = $conn->query($community_sql);
$community_data = $community_result->fetch_assoc() ?? [];

// If no products found in database, use sample products as fallback
if (empty($all_products)) {
    $all_products = [
        ['product_id' => 1, 'name' => 'Bamboo Toothbrush', 'description' => 'Eco-friendly bamboo toothbrush', 'price' => 4.99, 'co2_saved' => 0.2, 'image_url' => ''],
        ['product_id' => 2, 'name' => 'Reusable Coffee Cup', 'description' => 'Stainless steel reusable cup', 'price' => 19.99, 'co2_saved' => 0.6, 'image_url' => ''],
        ['product_id' => 3, 'name' => 'Organic Cotton T-shirt', 'description' => 'Sustainable organic cotton shirt', 'price' => 29.99, 'co2_saved' => 5.5, 'image_url' => ''],
        ['product_id' => 4, 'name' => 'LED Light Bulb', 'description' => 'Energy efficient LED bulb', 'price' => 8.99, 'co2_saved' => 0.7, 'image_url' => ''],
        ['product_id' => 5, 'name' => 'Recycled Paper Notebook', 'description' => 'Notebook made from recycled paper', 'price' => 12.99, 'co2_saved' => 0.6, 'image_url' => '']
    ];
}

include 'includes/header.php';
?>

<style>
:root {
    --primary-green: #2d5016;
    --secondary-green: #4a7c3a;
    --accent-green: #6b8e23;
    --text-dark: #2c3e50;
    --text-light: #7f8c8d;
    --bg-light: #f8f9fa;
    --border-color: #e9ecef;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg-light);
    color: var(--text-dark);
    line-height: 1.6;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    transition: transform 0.3s ease;
    border: 1px solid var(--border-color);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.live-badge {
    background: #dc3545;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.progress {
    height: 12px;
    border-radius: 10px;
    background-color: var(--border-color);
}

.progress-bar {
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
}

.challenge-card {
    border-left: 4px solid var(--primary-green);
    transition: all 0.3s ease;
}

.challenge-card:hover {
    transform: translateX(5px);
}

.leaderboard-item {
    border-bottom: 1px solid var(--border-color);
    padding: 12px 0;
    transition: all 0.3s ease;
}

.leaderboard-item:hover {
    background-color: var(--bg-light);
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
    font-size: 0.8rem;
}

.rank-1 { 
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
}
.rank-2 { 
    background: linear-gradient(135deg, #C0C0C0, #A0A0A0);
}
.rank-3 { 
    background: linear-gradient(135deg, #CD7F32, #A56C27);
}

.carbon-calc-result {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-green);
    line-height: 1;
}

.stat-label {
    color: var(--text-light);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-success {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(45, 80, 22, 0.3);
}

.interactive-hover {
    transition: all 0.3s ease;
    cursor: pointer;
}

.interactive-hover:hover {
    transform: translateY(-2px);
}

.card-header {
    background: linear-gradient(135deg, var(--bg-light), #ffffff);
    border-bottom: 1px solid var(--border-color);
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-header h4 {
    color: var(--primary-green);
    font-weight: 600;
    margin: 0;
}

.bg-light-success {
    background-color: rgba(45, 80, 22, 0.1) !important;
}

.product-option {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 8px 12px;
}

.product-name {
    font-weight: 600;
    color: var(--text-dark);
}

.product-co2 {
    font-size: 0.85rem;
    color: var(--text-light);
}

.challenge-link {
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 600;
}

.challenge-link:hover {
    color: var(--secondary-green);
    text-decoration: underline;
}

.source-badge {
    font-size: 0.7rem;
    padding: 2px 8px;
}

.edit-goal-btn {
    font-size: 0.8rem;
    padding: 4px 8px;
}

.impact-source {
    font-size: 0.8rem;
    color: var(--text-light);
}
</style>

<div class="container py-5">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="card bg-light mb-5">
        <div class="card-body p-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3 text-success">
                        Carbon Impact Tracker 
                        <span class="live-badge">LIVE DATA</span>
                    </h1>
                    <p class="lead mb-4">
                        Track your environmental impact in real-time. Every sustainable purchase reduces your carbon footprint and earns you EcoPoints!
                    </p>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="stat-number"><?php echo number_format($community_data['total_community_saved'] ?? 0, 0); ?>kg</div>
                            <div class="stat-label">Community CO₂ Saved</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-number"><?php echo $community_data['total_users'] ?? 0; ?></div>
                            <div class="stat-label">Active Eco-Shoppers</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-number"><?php echo $community_data['total_purchases'] ?? 0; ?></div>
                            <div class="stat-label">Sustainable Purchases</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <?php 
                            $user_rank = '-';
                            foreach ($leaderboard as $index => $user) {
                                if ($user['user_id'] == $user_id) {
                                    $user_rank = $index + 1;
                                    break;
                                }
                            }
                            ?>
                            <div class="stat-number">#<?php echo $user_rank; ?></div>
                            <div class="stat-label">Your Rank</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="icon-carbon mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h4 class="mt-3 text-success">Making a Real Difference</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Sources Overview -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center p-3">
                <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                <h4 class="text-primary"><?php echo number_format($checkout_saved, 1); ?>kg</h4>
                <p class="text-muted mb-0">Auto-tracked from Checkout</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <i class="fas fa-edit fa-2x text-success mb-2"></i>
                <h4 class="text-success"><?php echo number_format($manual_saved, 1); ?>kg</h4>
                <p class="text-muted mb-0">Manually Recorded</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
                <h4 class="text-warning"><?php echo number_format($user_carbon_data['total_saved'] ?? 0, 1); ?>kg</h4>
                <p class="text-muted mb-0">Total Combined Savings</p>
            </div>
        </div>
    </div>

    <!-- Overview Summary -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card text-center p-4 interactive-hover">
                <i class="fas fa-calendar-week fa-2x text-success mb-3"></i>
                <div class="stat-number"><?php echo number_format($user_carbon_data['week_saved'] ?? 0, 1); ?>kg</div>
                <div class="stat-label">This Week CO₂ Saved</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-4 interactive-hover">
                <i class="fas fa-calendar-alt fa-2x text-success mb-3"></i>
                <div class="stat-number"><?php echo number_format($user_carbon_data['month_saved'] ?? 0, 1); ?>kg</div>
                <div class="stat-label">This Month CO₂ Saved</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-4 interactive-hover">
                <i class="fas fa-chart-line fa-2x text-success mb-3"></i>
                <div class="stat-number"><?php echo number_format($user_carbon_data['total_saved'] ?? 0, 1); ?>kg</div>
                <div class="stat-label">All Time CO₂ Saved</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-4 interactive-hover">
                <i class="fas fa-trophy fa-2x text-warning mb-3"></i>
                <div class="stat-number"><?php echo number_format($eco_points); ?></div>
                <div class="stat-label">EcoPoints Earned</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Interactive Features -->
        <div class="col-lg-6">
            <!-- Carbon Calculator -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Carbon Calculator</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Manually calculate and record carbon savings for individual products.</p>
                    <form id="carbonCalculator">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select class="form-select" id="calcProduct" required>
                                <option value="">Select a product...</option>
                                <?php foreach ($all_products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['name']); ?>" 
                                            data-co2="<?php echo $product['co2_saved'] ?? 0.1; ?>"
                                            data-baseline="<?php echo ($product['co2_saved'] ?? 0.1) * 3; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> (Saves <?php echo $product['co2_saved'] ?? 0.1; ?>kg CO₂)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="calcQuantity" value="1" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Method</label>
                            <select class="form-select" id="calcDelivery">
                                <option value="standard">Standard Delivery</option>
                                <option value="express">Express Delivery (+25% emissions)</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="calculateCarbon()">
                            <i class="fas fa-calculator"></i> Calculate Carbon Impact
                        </button>
                    </form>
                    
                    <div id="calcResult" class="carbon-calc-result" style="display: none;">
                        <h6 class="mb-3">Calculation Results:</h6>
                        <div class="row">
                            <div class="col-6">
                                <strong>Emissions:</strong><br>
                                <span id="resultEmitted" class="text-danger fs-5">0 kg</span>
                            </div>
                            <div class="col-6">
                                <strong>Savings:</strong><br>
                                <span id="resultSaved" class="text-success fs-5">0 kg</span>
                            </div>
                        </div>
                        <p class="mt-3 mb-2"><small id="resultEquivalent" class="text-muted"></small></p>
                        
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="record_purchase" value="1">
                            <input type="hidden" name="product_name" id="recordProduct">
                            <input type="hidden" name="quantity" id="recordQuantity">
                            <input type="hidden" name="emitted" id="recordEmitted">
                            <input type="hidden" name="saved" id="recordSaved">
                            <input type="hidden" name="delivery_method" id="recordDelivery">
                            <button type="submit" class="btn btn-primary w-100" id="recordButton" disabled>
                                <i class="fas fa-save"></i> Record This Purchase
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Checkout Impacts -->
            <?php if (!empty($recent_checkout_impacts)): ?>
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Recent Checkout Impacts</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Automatically tracked from your orders.</p>
                    <?php foreach ($recent_checkout_impacts as $impact): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div>
                                <strong><?php echo htmlspecialchars($impact['product_name']); ?></strong>
                                <div class="text-success">+<?php echo number_format($impact['saved'], 2); ?>kg saved</div>
                                <small class="text-muted">Qty: <?php echo $impact['quantity']; ?> • <?php echo $impact['delivery_method']; ?></small>
                            </div>
                            <span class="badge bg-primary source-badge">Auto</span>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center mt-3">
                        <a href="order-history.php" class="btn btn-outline-primary btn-sm">View All Orders</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Monthly Challenges -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Monthly Challenges</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_challenges)): ?>
                        <?php foreach ($user_challenges as $challenge): ?>
                            <div class="challenge-card p-3 mb-3">
                                <h6 class="text-success">
                                    <a href="challenge-details.php?id=<?php echo $challenge['id']; ?>" class="challenge-link">
                                        <?php echo htmlspecialchars($challenge['title']); ?>
                                    </a>
                                </h6>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($challenge['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-success"><?php echo $challenge['reward_points']; ?> Points</span>
                                    <?php if ($challenge['user_status']): ?>
                                        <span class="badge bg-info">Joined</span>
                                    <?php else: ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="join_challenge" value="1">
                                            <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Join Challenge</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active challenges available at the moment.</p>
                            <a href="challenges.php" class="btn btn-outline-success">View All Challenges</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column - Data Visualization -->
        <div class="col-lg-6">
            <!-- Category Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Savings by Category</h4>
                </div>
                <div class="card-body">
                    <div id="categoryChart">
                        <?php if (!empty($category_breakdown)): ?>
                            <?php foreach ($category_breakdown as $category): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold"><?php echo htmlspecialchars($category['category']); ?></span>
                                        <span class="text-success"><?php echo number_format($category['total_saved'], 1); ?>kg</span>
                                    </div>
                                    <div class="progress mb-2">
                                        <?php $percentage = min(100, ($category['total_saved'] / max(1, $user_carbon_data['total_saved'])) * 100); ?>
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $category['purchase_count']; ?> purchase<?php echo $category['purchase_count'] != 1 ? 's' : ''; ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No data yet. Record your first purchase to see your impact breakdown!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Goals Progress -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Your Goal Progress</h4>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#goalModal">
                        <i class="fas fa-plus"></i> New Goal
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_goals)): ?>
                        <?php foreach ($user_goals as $goal): ?>
                            <div class="mb-4 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong class="text-capitalize"><?php echo $goal['goal_type']; ?> Goal</strong>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary edit-goal-btn" 
                                                data-goal-id="<?php echo $goal['id']; ?>"
                                                data-goal-type="<?php echo $goal['goal_type']; ?>"
                                                data-target-value="<?php echo $goal['target_value']; ?>"
                                                data-start-date="<?php echo $goal['start_date']; ?>"
                                                data-end-date="<?php echo $goal['end_date']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="delete_goal" value="1">
                                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this goal?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-success fw-bold"><?php echo number_format($goal['current_value'], 1); ?> / <?php echo number_format($goal['target_value'], 1); ?>kg</span>
                                </div>
                                <div class="progress mb-2">
                                    <?php $progress = min(100, ($goal['current_value'] / $goal['target_value']) * 100); ?>
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%">
                                        <?php echo number_format($progress, 0); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Target: <?php echo number_format($goal['target_value'], 1); ?>kg CO₂ • 
                                    Ends: <?php echo date('M j, Y', strtotime($goal['end_date'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active goals. Set a goal to start tracking your progress!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Community Leaderboard -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Community Leaderboard</h4>
                    <span class="badge bg-success">Top Customers</span>
                </div>
                <div class="card-body">
                    <div id="leaderboard">
                        <?php if (!empty($leaderboard)): ?>
                            <?php foreach ($leaderboard as $index => $user): ?>
                                <div class="leaderboard-item d-flex align-items-center interactive-hover <?php echo $user['user_id'] == $user_id ? 'bg-light-success' : ''; ?>">
                                    <div class="rank-badge me-3 <?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?>">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong class="<?php echo $user['user_id'] == $user_id ? 'text-success' : ''; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            <?php if ($user['user_id'] == $user_id): ?>
                                                <span class="badge bg-success ms-2">You</span>
                                            <?php endif; ?>
                                        </strong>
                                        <div class="text-success fw-bold"><?php echo number_format($user['total_points']); ?> EcoPoints</div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block"><?php echo number_format($user['total_saved'], 1); ?>kg saved</small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No community data available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Download Report -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Export Your Data</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Download your complete carbon impact history for analysis or reporting purposes.</p>
                    <a href="api/download-carbon-report.php" class="btn btn-outline-success w-100">
                        <i class="fas fa-download"></i> Download CSV Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Goal Modal -->
<div class="modal fade" id="goalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="goalModalTitle">Set New Carbon Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="goalForm" method="POST">
                    <input type="hidden" name="set_goal" value="1">
                    <div class="mb-3">
                        <label class="form-label">Goal Type</label>
                        <select class="form-select" name="goal_type" required id="goal_type">
                            <option value="weekly">Weekly Goal</option>
                            <option value="monthly" selected>Monthly Goal</option>
                            <option value="custom">Custom Goal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target CO₂ Reduction (kg)</label>
                        <input type="number" class="form-control" name="target_value" step="0.1" min="1" value="10" required id="target_value">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required id="start_date">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required id="end_date">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="goalForm" class="btn btn-success">Save Goal</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Goal Modal -->
<div class="modal fade" id="editGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Carbon Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editGoalForm" method="POST">
                    <input type="hidden" name="update_goal" value="1">
                    <input type="hidden" id="edit_goal_id" name="goal_id">
                    <div class="mb-3">
                        <label class="form-label">Goal Type</label>
                        <input type="text" class="form-control" id="edit_goal_type" readonly>
                        <small class="text-muted">Goal type cannot be changed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target CO₂ Reduction (kg)</label>
                        <input type="number" class="form-control" name="target_value" step="0.1" min="1" required id="edit_target_value">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required id="edit_start_date">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required id="edit_end_date">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editGoalForm" class="btn btn-success">Update Goal</button>
            </div>
        </div>
    </div>
</div>

<script>
function calculateCarbon() {
    const productSelect = document.getElementById('calcProduct');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (!selectedOption.value) {
        alert('Please select a product');
        return;
    }
    
    const productName = selectedOption.value;
    const co2PerUnit = parseFloat(selectedOption.getAttribute('data-co2'));
    const baselineCO2 = parseFloat(selectedOption.getAttribute('data-baseline'));
    const quantity = parseInt(document.getElementById('calcQuantity').value);
    const delivery = document.getElementById('calcDelivery').value;
    
    // Calculate emissions
    let emitted = co2PerUnit * quantity;
    if (delivery === 'express') {
        emitted *= 1.25; // 25% more for express delivery
    }
    
    // Calculate savings vs conventional
    const conventionalEmissions = baselineCO2 * quantity;
    const saved = conventionalEmissions - emitted;
    
    // Display results
    document.getElementById('resultEmitted').textContent = emitted.toFixed(2) + ' kg CO₂';
    document.getElementById('resultSaved').textContent = saved.toFixed(2) + ' kg CO₂';
    
    // Calculate equivalents
    const treesEquivalent = (saved / 21.77).toFixed(1); // kg CO2 per tree per year
    const kmEquivalent = (saved * 4).toFixed(0); // 1kg CO2 ≈ 4 km car travel
    document.getElementById('resultEquivalent').textContent = 
        `Equivalent to ${treesEquivalent} trees planted or ${kmEquivalent} km not driven by car`;
    
    // Show results section
    document.getElementById('calcResult').style.display = 'block';
    
    // Prepare data for recording
    document.getElementById('recordProduct').value = productName;
    document.getElementById('recordQuantity').value = quantity;
    document.getElementById('recordEmitted').value = emitted.toFixed(2);
    document.getElementById('recordSaved').value = saved.toFixed(2);
    document.getElementById('recordDelivery').value = delivery;
    document.getElementById('recordButton').disabled = false;
    
    // Scroll to results
    document.getElementById('calcResult').scrollIntoView({ behavior: 'smooth' });
}

function refreshData() {
    location.reload();
}

// Set default dates for goal form
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    const nextMonth = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (input.name === 'start_date' && !input.id.includes('edit')) {
            input.value = today;
        } else if (input.name === 'end_date' && !input.id.includes('edit')) {
            input.value = nextMonth;
        }
    });
    
    // Edit goal button handlers
    document.querySelectorAll('.edit-goal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const goalId = this.getAttribute('data-goal-id');
            const goalType = this.getAttribute('data-goal-type');
            const targetValue = this.getAttribute('data-target-value');
            const startDate = this.getAttribute('data-start-date');
            const endDate = this.getAttribute('data-end-date');
            
            document.getElementById('edit_goal_id').value = goalId;
            document.getElementById('edit_goal_type').value = goalType.charAt(0).toUpperCase() + goalType.slice(1);
            document.getElementById('edit_target_value').value = targetValue;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate;
            
            const editModal = new bootstrap.Modal(document.getElementById('editGoalModal'));
            editModal.show();
        });
    });
});

// Add auto-refresh every 30 seconds for live data
setInterval(function() {
    const liveBadges = document.querySelectorAll('.live-badge');
    liveBadges.forEach(badge => {
        badge.style.animation = 'none';
        setTimeout(() => {
            badge.style.animation = 'pulse 2s infinite';
        }, 10);
    });
}, 30000);
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>