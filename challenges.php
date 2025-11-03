<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Handle challenge participation
if (isset($_POST['join_challenge']) && isLoggedIn()) {
    $challenge_id = $_POST['challenge_id'];
    $user_id = $_SESSION['user_id'];
    
    // Award points for joining challenge
    $points_sql = "INSERT INTO eco_point_transactions (user_id, points, transaction_type, reason) VALUES (?, 50, 'Earned', 'Challenge Participation')";
    $points_stmt = $conn->prepare($points_sql);
    $points_stmt->bind_param("i", $user_id);
    $points_stmt->execute();
    
    // Update user's EcoPoints balance
    $update_sql = "UPDATE users SET eco_points_balance = eco_points_balance + 50 WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    
    $challenge_success = "Challenge joined! +50 EcoPoints awarded!";
}

$challenges = [
    [
        'id' => 1,
        'title' => 'Plastic-Free Week',
        'description' => 'Avoid single-use plastics for 7 consecutive days',
        'duration' => '7 days',
        'points' => 200,
        'participants' => 145,
        'difficulty' => 'Medium',
        'category' => 'Waste Reduction',
        'icon' => 'plastic-free'
    ],
    [
        'id' => 2,
        'title' => 'Meatless Mondays',
        'description' => 'Go vegetarian every Monday for a month',
        'duration' => '4 weeks',
        'points' => 150,
        'participants' => 89,
        'difficulty' => 'Easy',
        'category' => 'Food',
        'icon' => 'vegetarian'
    ],
    [
        'id' => 3,
        'title' => 'Zero Food Waste',
        'description' => 'No food waste for 2 weeks - compost or reuse everything',
        'duration' => '14 days',
        'points' => 300,
        'participants' => 67,
        'difficulty' => 'Hard',
        'category' => 'Food',
        'icon' => 'compost'
    ],
    [
        'id' => 4,
        'title' => 'Carbon-Free Commute',
        'description' => 'Walk, bike, or use public transport for all trips',
        'duration' => '5 days',
        'points' => 250,
        'participants' => 112,
        'difficulty' => 'Medium',
        'category' => 'Transport',
        'icon' => 'bike'
    ],
    [
        'id' => 5,
        'title' => 'Energy Saver',
        'description' => 'Reduce electricity consumption by 25% for a month',
        'duration' => '30 days',
        'points' => 400,
        'participants' => 78,
        'difficulty' => 'Hard',
        'category' => 'Energy',
        'icon' => 'energy'
    ],
    [
        'id' => 6,
        'title' => 'Water Warrior',
        'description' => 'Reduce water usage by 20% for two weeks',
        'duration' => '14 days',
        'points' => 180,
        'participants' => 93,
        'difficulty' => 'Medium',
        'category' => 'Water',
        'icon' => 'water'
    ]
];

include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Floating Shapes -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    
    <h1 class="hero-title text-center mb-5">Eco-Challenges</h1>
    
    <?php if (isset($challenge_success)): ?>
        <div class="alert alert-success glassmorphism"><?php echo $challenge_success; ?></div>
    <?php endif; ?>
    
    <!-- Challenge Stats -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card text-center glassmorphism">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-primary"><?php echo count($challenges); ?></h3>
                    <p class="text-muted">Active Challenges</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center glassmorphism">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M16,4C18.21,4 20,5.79 20,8C20,9.86 18.96,11.41 17.46,12.11C16.62,12.67 15.46,13.47 15.46,13.47L14.55,14.38C13.76,15.17 13.76,16.45 14.55,17.24C15.34,18.03 16.62,18.03 17.41,17.24C17.82,16.83 18.07,16.29 18.07,15.71C18.07,15.14 17.82,14.6 17.41,14.19L18.82,12.78C19.98,13.94 19.98,15.81 18.82,16.97C17.66,18.13 15.79,18.13 14.63,16.97C13.47,15.81 13.47,13.94 14.63,12.78L15.54,11.87C15.54,11.87 14.61,11.14 13.83,10.36C12.29,8.82 12.29,6.53 13.83,5C14.61,4.22 15.67,4 16,4Z"/>
                        </svg>
                    </div>
                    <h3 class="text-primary">584</h3>
                    <p class="text-muted">Total Participants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center glassmorphism">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12,2L12,2C16.97,2 21,6.03 21,11C21,13.17 20.27,15.16 19.05,16.73L12,22L4.95,16.73C3.73,15.16 3,13.17 3,11C3,6.03 7.03,2 12,2M11,15L17,9L15.59,7.59L11,12.17L8.91,10.09L7.5,11.5L11,15Z"/>
                        </svg>
                    </div>
                    <h3 class="text-primary">12,450kg</h3>
                    <p class="text-muted">CO2 Reduced</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center glassmorphism">
                <div class="card-body">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/>
                        </svg>
                    </div>
                    <h3 class="text-primary">45,600</h3>
                    <p class="text-muted">Points Awarded</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Challenges Grid -->
    <div class="row g-4">
        <?php foreach($challenges as $challenge): ?>
            <div class="col-md-6">
                <div class="card challenge-card glassmorphism h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="challenge-icon">
                                <?php if($challenge['icon'] == 'plastic-free'): ?>
                                    <svg viewBox="0 0 24 24" width="48" height="48">
                                        <path fill="currentColor" d="M3,4H21V6H19L18.33,9H5.67L5,6H3V4M18.33,9L17.75,11H6.25L5.67,9H18.33M6,14H18L17,22H7L6,14Z"/>
                                    </svg>
                                <?php elseif($challenge['icon'] == 'vegetarian'): ?>
                                    <svg viewBox="0 0 24 24" width="48" height="48">
                                        <path fill="currentColor" d="M20.84,22.73L19.46,21.35C19.13,21.83 18.5,22.1 17.84,22.1C16.91,22.1 16.07,21.5 15.84,20.6L14.17,15.77L12.64,17.3C12.84,17.69 13,18.1 13,18.5C13,19.9 12,21 10.65,21C9.27,21 8.25,19.88 8.25,18.5C8.25,17.83 8.5,17.23 8.91,16.75L1.11,8.96L2.5,7.57L20.84,22.73M9.25,18.5C9.25,19.19 9.81,19.75 10.5,19.75C11.19,19.75 11.75,19.19 11.75,18.5C11.75,17.81 11.19,17.25 10.5,17.25C9.81,17.25 9.25,17.81 9.25,18.5M14.5,1.88L13.5,3.64L14.5,5.25C16.5,8.25 20,9.33 20,9.33L20,11.29C18.5,11.9 16.36,12.6 14.5,13.5L14.5,1.88M9.25,13.5L8.25,11.29L8.25,9.33C8.25,9.33 11.75,8.25 13.75,5.25L14.75,3.64L13.75,1.88L12.75,3.64L11.75,5.25C9.75,8.25 6.25,9.33 6.25,9.33L6.25,11.29L7.75,12.9L9.25,13.5Z"/>
                                    </svg>
                                <?php elseif($challenge['icon'] == 'compost'): ?>
                                    <svg viewBox="0 0 24 24" width="48" height="48">
                                        <path fill="currentColor" d="M12,3C7,3 3,7 3,12C3,17 7,21 12,21C17,21 21,17 21,12C21,7 17,3 12,3M12,19C8.1,19 5,15.9 5,12C5,8.1 8.1,5 12,5C15.9,5 19,8.1 19,12C19,15.9 15.9,19 12,19M12,17L16,13L12,9V12H8V14H12V17Z"/>
                                    </svg>
                                <?php elseif($challenge['icon'] == 'bike'): ?>
                                    <svg viewBox="0 0 24 24" width="48" height="48">
                                        <path fill="currentColor" d="M5,20.5A3.5,3.5 0 0,1 1.5,17A3.5,3.5 0 0,1 5,13.5A3.5,3.5 0 0,1 8.5,17A3.5,3.5 0 0,1 5,20.5M5,12A5,5 0 0,0 0,17A5,5 0 0,0 5,22A5,5 0 0,0 10,17A5,5 0 0,0 5,12M14.8,10H19V8.2H15.8L13.87,4.93C13.34,4.34 12.53,4 11.72,4C11.29,4 10.87,4.12 10.5,4.32L5.5,7.28C4.85,7.64 4.5,8.36 4.5,9.14V12.85C4.5,13.63 4.85,14.35 5.5,14.7L10.5,17.67C10.87,17.87 11.29,18 11.72,18C12.53,18 13.34,17.66 13.87,17.07L15.5,15V10M19,20.5A3.5,3.5 0 0,1 15.5,17A3.5,3.5 0 0,1 19,13.5A3.5,3.5 0 0,1 22.5,17A3.5,3.5 0 0,1 19,20.5M11.72,13C11.85,13 11.97,13 12.1,12.93L16.5,10.5V13.5L12.1,15.93C11.65,16.18 11.1,16.18 10.65,15.93L6.5,13.63V9.37L10.65,7.07C11.1,6.82 11.65,6.82 12.1,7.07L15.43,9.07L14.8,9.5L11.72,8L9.5,9.33L11.72,10.63V13Z"/>
                                    </svg>
                                <?php elseif($challenge['icon'] == 'energy'): ?>
                                    <svg viewBox="0 0 24 24" width="48" height="48">
                                        <path fill="currentColor" d="M11.5,20L16.36,10.27H13V4L8,13.73H11.5V20M12,2C14.75,2 17.1,3 19.05,4.95C21,6.9 22,9.25 22,12C22,14.75 21,17.1 19.05,19.05C17.1,21 14.75,22 12,22C9.25,22 6.9,21 4.95,19.05C3,17.1 2,14.75 2,12C2,9.25 3,6.9 4.95,4.95C6.9,3 9.25,2 12,2Z"/>
                                    </svg>
                                <?php elseif($challenge['icon'] == 'water'): ?>
                                    <svg viewBox="0 0 24 24" width="48" height="48">
                                        <path fill="currentColor" d="M12,20A6,6 0 0,1 6,14C6,10 12,3.25 12,3.25C12,3.25 18,10 18,14A6,6 0 0,1 12,20Z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-<?php 
                                switch($challenge['difficulty']) {
                                    case 'Easy': echo 'success'; break;
                                    case 'Medium': echo 'warning'; break;
                                    case 'Hard': echo 'danger'; break;
                                }
                            ?>"><?php echo $challenge['difficulty']; ?></span>
                        </div>
                        
                        <h5 class="card-title"><?php echo $challenge['title']; ?></h5>
                        <span class="badge bg-light text-dark mb-2"><?php echo $challenge['category']; ?></span>
                        
                        <p class="card-text text-muted"><?php echo $challenge['description']; ?></p>
                        
                        <div class="challenge-meta mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted">Duration</small>
                                    <div><strong><?php echo $challenge['duration']; ?></strong></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Points</small>
                                    <div><strong class="text-success">+<?php echo $challenge['points']; ?></strong></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Participants</small>
                                    <div><strong><?php echo $challenge['participants']; ?></strong></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <form method="POST">
                                <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                <button type="submit" name="join_challenge" class="btn btn-primary w-100">Join Challenge</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-secondary w-100">Login to Join</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Community Impact -->
    <div class="card mt-5 glassmorphism">
        <div class="card-body text-center">
            <h3 class="section-title">Community Impact</h3>
            <p class="lead">Together, we've made a significant environmental impact!</p>
            
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12,2C8.13,2 5,5.13 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9C19,5.13 15.87,2 12,2Z"/>
                        </svg>
                    </div>
                    <h4 class="text-primary">1,234</h4>
                    <p class="text-muted">Trees Equivalent</p>
                </div>
                <div class="col-md-3">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12,20A6,6 0 0,1 6,14C6,10 12,3.25 12,3.25C12,3.25 18,10 18,14A6,6 0 0,1 12,20Z"/>
                        </svg>
                    </div>
                    <h4 class="text-primary">45,600L</h4>
                    <p class="text-muted">Water Saved</p>
                </div>
                <div class="col-md-3">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M11.5,20L16.36,10.27H13V4L8,13.73H11.5V20M12,2C14.75,2 17.1,3 19.05,4.95C21,6.9 22,9.25 22,12C22,14.75 21,17.1 19.05,19.05C17.1,21 14.75,22 12,22C9.25,22 6.9,21 4.95,19.05C3,17.1 2,14.75 2,12C2,9.25 3,6.9 4.95,4.95C6.9,3 9.25,2 12,2Z"/>
                        </svg>
                    </div>
                    <h4 class="text-primary">23,400kWh</h4>
                    <p class="text-muted">Energy Saved</p>
                </div>
                <div class="col-md-3">
                    <div class="icon-wrapper">
                        <svg class="feature-icon" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M3,4H21V6H19L18.33,9H5.67L5,6H3V4M18.33,9L17.75,11H6.25L5.67,9H18.33M6,14H18L17,22H7L6,14Z"/>
                        </svg>
                    </div>
                    <h4 class="text-primary">890kg</h4>
                    <p class="text-muted">Plastic Reduced</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Design System Styles */
:root {
    --bg-sand-light: #d4c4a8;
    --bg-sand-dark: #c2b299;
    --primary-green: #4a6b4a;
    --secondary-green: #5a7a5a;
    --text-green: #2d4a2d;
    --surface-white: #ffffff;
}

body {
    background: linear-gradient(135deg, var(--bg-sand-light), var(--bg-sand-dark));
    font-family: 'Inter', sans-serif;
    color: var(--text-green);
}

.hero-title {
    font-size: 56px;
    font-weight: 700;
    color: var(--text-green);
}

.section-title {
    font-size: 42px;
    font-weight: 600;
    color: var(--text-green);
}

.card {
    border-radius: 40px;
    border: none;
    background: var(--surface-white);
}

.glassmorphism {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.challenge-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 2px solid transparent;
}

.challenge-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(74, 107, 74, 0.2);
    border-color: var(--primary-green);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(74, 107, 74, 0.3);
    background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
}

.icon-wrapper {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon {
    width: 30px;
    height: 30px;
    color: white;
}

.challenge-icon svg {
    color: var(--primary-green);
}

.floating-shape {
    position: absolute;
    border-radius: 60% 40% 30% 70%;
    background: rgba(74, 107, 74, 0.1);
    animation: float 8s ease-in-out infinite;
    z-index: -1;
}

.shape-1 {
    width: 150px;
    height: 150px;
    top: 10%;
    left: 5%;
    animation-delay: 0s;
}

.shape-2 {
    width: 100px;
    height: 100px;
    top: 60%;
    right: 10%;
    animation-delay: 2s;
}

.shape-3 {
    width: 120px;
    height: 120px;
    bottom: 10%;
    left: 15%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(5deg);
    }
}

.badge {
    border-radius: 20px;
    padding: 8px 16px;
    font-weight: 600;
}

.text-primary {
    color: var(--primary-green) !important;
}

.text-success {
    color: var(--secondary-green) !important;
}

.bg-success {
    background-color: var(--secondary-green) !important;
}

.bg-warning {
    background-color: #e9b949 !important;
}

.bg-danger {
    background-color: #d64545 !important;
}

.alert-success {
    background: rgba(90, 122, 90, 0.1);
    border: 1px solid var(--secondary-green);
    color: var(--text-green);
    border-radius: 20px;
}
</style>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>