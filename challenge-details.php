<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$conn = getDatabaseConnection();

// Get challenge ID from URL
$challenge_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$challenge_id) {
    header("Location: carbon-tracking.php");
    exit();
}

// Get challenge details
$challenge_sql = "SELECT * FROM monthly_challenges WHERE id = ?";
$stmt = $conn->prepare($challenge_sql);
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$challenge = $stmt->get_result()->fetch_assoc();

if (!$challenge) {
    header("Location: carbon-tracking.php");
    exit();
}

// Get user participation status
$user_participation = null;
$user_progress = 0;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $participation_sql = "SELECT * FROM user_challenges WHERE user_id = ? AND challenge_id = ?";
    $stmt = $conn->prepare($participation_sql);
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $user_participation = $stmt->get_result()->fetch_assoc();
    
    // Calculate user progress for this challenge
    if ($user_participation) {
        if ($challenge['title'] === 'Carbon Crusher') {
            // Count sustainable purchases
            $progress_sql = "SELECT COUNT(*) as progress 
                            FROM carbon_impact 
                            WHERE user_id = ? 
                            AND created_at BETWEEN ? AND ?";
            $stmt = $conn->prepare($progress_sql);
            $stmt->bind_param("iss", $user_id, $challenge['start_date'], $challenge['end_date']);
            $stmt->execute();
            $progress_result = $stmt->get_result()->fetch_assoc();
            $user_progress = $progress_result['progress'] ?? 0;
        } else {
            // Calculate CO₂ savings
            $progress_sql = "SELECT COALESCE(SUM(saved), 0) as progress 
                            FROM carbon_impact 
                            WHERE user_id = ? 
                            AND created_at BETWEEN ? AND ?";
            $stmt = $conn->prepare($progress_sql);
            $stmt->bind_param("iss", $user_id, $challenge['start_date'], $challenge['end_date']);
            $stmt->execute();
            $progress_result = $stmt->get_result()->fetch_assoc();
            $user_progress = $progress_result['progress'] ?? 0;
        }
        
        // Update progress in database
        $update_sql = "UPDATE user_challenges SET progress = ? WHERE user_id = ? AND challenge_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("dii", $user_progress, $user_id, $challenge_id);
        $stmt->execute();
    }
}

// Handle join challenge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isset($_POST['join_challenge'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check if already joined
    $check_sql = "SELECT id FROM user_challenges WHERE user_id = ? AND challenge_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows == 0) {
        $join_sql = "INSERT INTO user_challenges (user_id, challenge_id) VALUES (?, ?)";
        $stmt = $conn->prepare($join_sql);
        $stmt->bind_param("ii", $user_id, $challenge_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "You've successfully joined the challenge!";
            header("Location: challenge-details.php?id=" . $challenge_id);
            exit();
        }
    }
}

// Get challenge participants
$participants_sql = "SELECT u.user_id, u.first_name, u.last_name, uc.progress, uc.joined_at 
                    FROM user_challenges uc 
                    JOIN users u ON uc.user_id = u.user_id 
                    WHERE uc.challenge_id = ? 
                    ORDER BY uc.progress DESC, uc.joined_at ASC";
$stmt = $conn->prepare($participants_sql);
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get similar challenges
$similar_sql = "SELECT * FROM monthly_challenges 
                WHERE id != ? AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT 3";
$stmt = $conn->prepare($similar_sql);
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$similar_challenges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

.challenge-hero {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    border-radius: 20px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.challenge-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.challenge-badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.progress-container {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--border-color);
}

.progress-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: conic-gradient(var(--primary-green) <?php echo ($user_progress / $challenge['target_kg']) * 360; ?>deg, var(--bg-light) 0deg);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin: 0 auto 1rem;
}

.progress-circle::before {
    content: '';
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
    position: absolute;
}

.progress-text {
    position: relative;
    z-index: 2;
    text-align: center;
}

.participant-card {
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.participant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.participant-rank {
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

.rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; }
.rank-2 { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); }
.rank-3 { background: linear-gradient(135deg, #CD7F32, #A56C27); }

.challenge-card {
    border: 1px solid var(--border-color);
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    height: 100%;
}

.challenge-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.benefit-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--bg-light);
    border-radius: 10px;
    border-left: 4px solid var(--primary-green);
}

.benefit-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-green);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.tip-card {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: 1px solid #ffeaa7;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.time-remaining {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-green);
}

.impact-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.stat-box {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-green);
    line-height: 1;
}

.stat-label {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.join-btn {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    border: none;
    border-radius: 10px;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.join-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(45, 80, 22, 0.3);
}

.leaderboard-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    border: 1px solid var(--border-color);
}
</style>

<div class="container py-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Challenge Hero Section -->
    <div class="challenge-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-3">
                    <span class="challenge-badge me-3">
                        <i class="fas fa-trophy me-2"></i>Monthly Challenge
                    </span>
                    <span class="challenge-badge">
                        <i class="fas fa-users me-2"></i><?php echo count($participants); ?> Participants
                    </span>
                </div>
                <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($challenge['title']); ?></h1>
                <p class="lead mb-4"><?php echo htmlspecialchars($challenge['description']); ?></p>
                
                <div class="impact-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $challenge['target_kg']; ?>kg</div>
                        <div class="stat-label">Target CO₂</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $challenge['reward_points']; ?></div>
                        <div class="stat-label">EcoPoints Reward</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">
                            <?php 
                            $days_remaining = max(0, floor((strtotime($challenge['end_date']) - time()) / (60 * 60 * 24)));
                            echo $days_remaining;
                            ?>
                        </div>
                        <div class="stat-label">Days Remaining</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="bg-white rounded-3 p-4 shadow">
                    <h4 class="text-success mb-3">Challenge Progress</h4>
                    <?php if (isLoggedIn() && $user_participation): ?>
                        <div class="progress-circle">
                            <div class="progress-text">
                                <div class="h4 mb-0 text-success">
                                    <?php echo number_format(($user_progress / $challenge['target_kg']) * 100, 0); ?>%
                                </div>
                            </div>
                        </div>
                        <p class="mb-2">
                            <strong><?php echo number_format($user_progress, 1); ?>kg</strong> of 
                            <strong><?php echo $challenge['target_kg']; ?>kg</strong>
                        </p>
                        <p class="text-muted small">You joined on <?php echo date('M j, Y', strtotime($user_participation['joined_at'])); ?></p>
                    <?php else: ?>
                        <div class="py-4">
                            <i class="fas fa-flag fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Join the challenge to track your progress!</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn() && !$user_participation): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="join_challenge" value="1">
                            <button type="submit" class="btn join-btn btn-lg w-100 text-white">
                                <i class="fas fa-plus-circle me-2"></i>Join Challenge
                            </button>
                        </form>
                    <?php elseif (isLoggedIn() && $user_participation): ?>
                        <div class="mt-3">
                            <span class="badge bg-success p-2 fs-6 w-100">
                                <i class="fas fa-check-circle me-2"></i>Challenge Joined
                            </span>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn join-btn btn-lg w-100 text-white">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Join
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Challenge Details -->
            <div class="progress-container mb-4">
                <h3 class="text-success mb-4">Challenge Details</h3>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-calendar text-success me-2"></i>Timeline</h5>
                        <p class="mb-2">
                            <strong>Starts:</strong> <?php echo date('F j, Y', strtotime($challenge['start_date'])); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Ends:</strong> <?php echo date('F j, Y', strtotime($challenge['end_date'])); ?>
                        </p>
                        <p class="time-remaining mt-2">
                            <i class="fas fa-clock me-2"></i>
                            <?php 
                            $days_left = max(0, floor((strtotime($challenge['end_date']) - time()) / (60 * 60 * 24)));
                            echo $days_left . ' days remaining';
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-award text-success me-2"></i>Rewards</h5>
                        <p class="mb-2">
                            <strong>EcoPoints:</strong> 
                            <span class="badge bg-warning text-dark fs-6">+<?php echo $challenge['reward_points']; ?> points</span>
                        </p>
                        <p class="mb-2">
                            <strong>Recognition:</strong> Featured on leaderboard
                        </p>
                        <p class="mb-0">
                            <strong>Badge:</strong> Exclusive challenge badge
                        </p>
                    </div>
                </div>

                <!-- How to Participate -->
                <h5 class="mt-4 mb-3">How to Participate</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="benefit-icon mx-auto mb-3">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h6>Shop Sustainable</h6>
                            <p class="small text-muted mb-0">Purchase eco-friendly products from our store</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="benefit-icon mx-auto mb-3">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <h6>Track Impact</h6>
                            <p class="small text-muted mb-0">Use carbon calculator for manual entries</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="benefit-icon mx-auto mb-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h6>Monitor Progress</h6>
                            <p class="small text-muted mb-0">Watch your impact grow on the leaderboard</p>
                        </div>
                    </div>
                </div>

                <!-- Benefits -->
                <h5 class="mt-4 mb-3">Benefits of Participating</h5>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Positive Environmental Impact</h6>
                        <p class="mb-0 text-muted">Every kilogram of CO₂ saved makes a real difference for our planet.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Join a Community</h6>
                        <p class="mb-0 text-muted">Be part of a growing community of eco-conscious shoppers.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Earn Rewards</h6>
                        <p class="mb-0 text-muted">Collect EcoPoints that can be redeemed for exclusive benefits.</p>
                    </div>
                </div>
            </div>

            <!-- Tips for Success -->
            <div class="progress-container">
                <h3 class="text-success mb-4">Tips for Success</h3>
                
                <div class="tip-card">
                    <h6><i class="fas fa-lightbulb text-warning me-2"></i>Focus on High-Impact Products</h6>
                    <p class="mb-0">Choose products with higher CO₂ savings like reusable items, organic cotton, and energy-efficient devices to reach your goal faster.</p>
                </div>
                
                <div class="tip-card">
                    <h6><i class="fas fa-shipping-fast text-warning me-2"></i>Optimize Your Deliveries</h6>
                    <p class="mb-0">Use standard delivery instead of express to reduce transportation emissions and increase your carbon savings.</p>
                </div>
                
                <div class="tip-card">
                    <h6><i class="fas fa-bullseye text-warning me-2"></i>Set Mini-Goals</h6>
                    <p class="mb-0">Break down the challenge into weekly targets to stay motivated and track your progress more effectively.</p>
                </div>
                
                <div class="tip-card">
                    <h6><i class="fas fa-share-alt text-warning me-2"></i>Share Your Journey</h6>
                    <p class="mb-0">Share your progress with friends and family to inspire others and build accountability.</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Leaderboard -->
            <div class="leaderboard-section mb-4">
                <h4 class="text-success mb-3">
                    <i class="fas fa-trophy me-2"></i>Challenge Leaderboard
                </h4>
                
                <?php if (!empty($participants)): ?>
                    <?php foreach ($participants as $index => $participant): ?>
                        <div class="participant-card <?php echo $participant['user_id'] == ($_SESSION['user_id'] ?? 0) ? 'border-success' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="participant-rank me-3 <?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <strong class="<?php echo $participant['user_id'] == ($_SESSION['user_id'] ?? 0) ? 'text-success' : ''; ?>">
                                        <?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?>
                                        <?php if ($participant['user_id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                            <span class="badge bg-success ms-2">You</span>
                                        <?php endif; ?>
                                    </strong>
                                    <div class="text-success fw-bold">
                                        <?php echo number_format($participant['progress'], 1); ?>kg
                                    </div>
                                    <small class="text-muted">
                                        <?php echo number_format(($participant['progress'] / $challenge['target_kg']) * 100, 0); ?>% complete
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Be the first to join this challenge!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Similar Challenges -->
            <div class="leaderboard-section">
                <h4 class="text-success mb-3">More Challenges</h4>
                
                <?php foreach ($similar_challenges as $similar): ?>
                    <div class="challenge-card mb-3">
                        <h6 class="text-success"><?php echo htmlspecialchars($similar['title']); ?></h6>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($similar['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-success"><?php echo $similar['reward_points']; ?> Points</span>
                            <a href="challenge-details.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm btn-outline-success">
                                View Challenge
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($similar_challenges)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-flag fa-2x text-muted mb-2"></i>
                        <p class="text-muted small">No other challenges available</p>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="carbon-tracking.php" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to Carbon Tracker
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="leaderboard-section">
                <h4 class="text-success mb-3">Quick Actions</h4>
                <div class="d-grid gap-2">
                    <a href="products.php" class="btn btn-outline-success">
                        <i class="fas fa-shopping-bag me-2"></i>Shop Eco Products
                    </a>
                    <a href="carbon-tracking.php" class="btn btn-outline-primary">
                        <i class="fas fa-calculator me-2"></i>Carbon Calculator
                    </a>
                    <a href="order-history.php" class="btn btn-outline-info">
                        <i class="fas fa-history me-2"></i>View Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Progress animation
document.addEventListener('DOMContentLoaded', function() {
    const progressCircle = document.querySelector('.progress-circle');
    if (progressCircle) {
        // Animate progress circle
        setTimeout(() => {
            progressCircle.style.transition = 'all 1s ease-in-out';
        }, 500);
    }
    
    // Countdown timer update
    function updateCountdown() {
        const endDate = new Date('<?php echo $challenge['end_date']; ?>').getTime();
        const now = new Date().getTime();
        const distance = endDate - now;
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        
        const countdownElement = document.querySelector('.time-remaining');
        if (countdownElement && distance > 0) {
            countdownElement.innerHTML = `<i class="fas fa-clock me-2"></i>${days}d ${hours}h remaining`;
        } else if (countdownElement) {
            countdownElement.innerHTML = `<i class="fas fa-flag-checkered me-2"></i>Challenge ended`;
        }
    }
    
    updateCountdown();
    setInterval(updateCountdown, 60000); // Update every minute
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php 
$conn->close();
include 'includes/footer.php';
?>