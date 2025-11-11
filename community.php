<?php
// Use the config file which already includes database.php
require_once __DIR__ . '/includes/config.php';

$conn = getDatabaseConnection();

// Initialize variables to prevent undefined errors
$testimonials_result = null;
$stats = null;
$top_members_result = null;
$posts_result = null;
$events_result = null;
$error = '';
$success = '';

// Handle new post submission
if (isset($_POST['create_post']) && isLoggedIn()) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'];
    $user_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($content)) {
        $sql = "INSERT INTO forum_posts (user_id, title, content, post_type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $title, $content, $post_type);
            
            if ($stmt->execute()) {
                header("Location: community.php?success=Post+created+successfully");
                exit();
            } else {
                $error = "Error creating post. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again.";
        }
    } else {
        $error = "Please fill in both title and content.";
    }
}

// Handle event joining/leaving with error handling
if (isset($_POST['event_action']) && isLoggedIn()) {
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];
    $action = $_POST['event_action'];
    
    try {
        // Check if event_participants table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'event_participants'");
        if ($table_check->num_rows > 0) {
            if ($action === 'join') {
                $sql = "INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ii", $event_id, $user_id);
                    if ($stmt->execute()) {
                        $success = "Successfully joined the event!";
                    } else {
                        $error = "Error joining event. You may have already joined.";
                    }
                    $stmt->close();
                }
            } elseif ($action === 'leave') {
                $sql = "DELETE FROM event_participants WHERE event_id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ii", $event_id, $user_id);
                    if ($stmt->execute()) {
                        $success = "Successfully left the event.";
                    } else {
                        $error = "Error leaving event.";
                    }
                    $stmt->close();
                }
            }
        } else {
            $error = "Event system is currently being set up. Please try again later.";
        }
    } catch (Exception $e) {
        $error = "Event system temporarily unavailable. Please try again later.";
    }
}

// Handle post filtering
$filter = $_GET['filter'] ?? 'all';
$filter_sql = "";
if ($filter === 'questions') {
    $filter_sql = "WHERE fp.post_type = 'Question'";
} elseif ($filter === 'tips') {
    $filter_sql = "WHERE fp.post_type = 'Tip'";
} elseif ($filter === 'announcements') {
    $filter_sql = "WHERE fp.post_type = 'Announcement'";
}

// Get forum posts with filtering - with error handling
$posts_sql = "SELECT fp.*, u.first_name, u.last_name 
              FROM forum_posts fp 
              JOIN users u ON fp.user_id = u.user_id 
              $filter_sql
              ORDER BY fp.is_pinned DESC, fp.post_date DESC 
              LIMIT 20";
$posts_result = $conn->query($posts_sql);

// Get community stats - with fallback values
$stats_sql = "SELECT 
              (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_members,
              (SELECT COUNT(*) FROM forum_posts) as total_posts,
              (SELECT COUNT(*) FROM reviews WHERE is_approved = 1) as total_reviews,
              (SELECT IFNULL(SUM(co2_saved * stock_quantity), 0) FROM products WHERE is_active = 1) as total_co2_saved,
              (SELECT COUNT(*) FROM orders WHERE status IN ('Delivered','Shipped')) as total_rentals,
              (SELECT IFNULL(SUM(eco_points_balance), 0) FROM users) as total_ecopoints";

$stats_result = $conn->query($stats_sql);
if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
} else {
    // Fallback stats if query fails
    $stats = [
        'total_members' => 0,
        'total_posts' => 0,
        'total_reviews' => 0,
        'total_co2_saved' => 0,
        'total_rentals' => 0,
        'total_ecopoints' => 0
    ];
}

// Get featured testimonials - with error handling
$testimonials_sql = "SELECT r.*, u.first_name, u.last_name, p.name as product_name 
                     FROM reviews r 
                     JOIN users u ON r.user_id = u.user_id 
                     LEFT JOIN products p ON r.product_id = p.product_id 
                     WHERE r.is_approved = 1 AND r.rating >= 4 
                     ORDER BY r.review_date DESC 
                     LIMIT 6";
$testimonials_result = $conn->query($testimonials_sql);

// Get top community members (by EcoPoints) - with error handling
$top_members_sql = "SELECT first_name, last_name, eco_points_balance 
                    FROM users 
                    WHERE is_active = 1 
                    ORDER BY eco_points_balance DESC 
                    LIMIT 5";
$top_members_result = $conn->query($top_members_sql);

// Get events for the interactive section - with error handling
$fallback_events = [
    [
        'event_id' => 1,
        'event_name' => 'Eco-Warrior Challenge',
        'event_description' => 'Join our monthly sustainability challenge! Complete eco-friendly tasks and earn bonus EcoPoints while reducing your carbon footprint.',
        'event_schedule' => 'Monthly Challenge',
        'event_location' => 'Online & Local Communities',
        'participant_count' => 156,
        'max_participants' => 500
    ],
    [
        'event_id' => 2,
        'event_name' => 'Sustainable Living Workshop',
        'event_description' => 'Learn practical tips for reducing waste, energy conservation, and making eco-conscious purchasing decisions in our interactive workshop.',
        'event_schedule' => 'Every Saturday',
        'event_location' => 'Community Center & Online',
        'participant_count' => 89,
        'max_participants' => 100
    ],
    [
        'event_id' => 3,
        'event_name' => 'Product Recycling Drive',
        'event_description' => 'Bring your used electronics, batteries, and other recyclables to our community recycling event. Proper disposal ensures materials get a second life!',
        'event_schedule' => 'Bi-weekly',
        'event_location' => 'Local Collection Centers',
        'participant_count' => 234,
        'max_participants' => 300
    ],
    [
        'event_id' => 4,
        'event_name' => 'Green Tech Innovation Forum',
        'event_description' => 'Connect with innovators and sustainability experts discussing the latest in green technology and circular economy solutions.',
        'event_schedule' => 'Monthly Meetup',
        'event_location' => 'Innovation Hub & Virtual',
        'participant_count' => 67,
        'max_participants' => 150
    ],
    [
        'event_id' => 5,
        'event_name' => 'Community Garden Initiative',
        'event_description' => 'Help us plant and maintain community gardens that provide fresh produce while promoting biodiversity and sustainable agriculture.',
        'event_schedule' => 'Weekly Sessions',
        'event_location' => 'Local Community Gardens',
        'participant_count' => 178,
        'max_participants' => 200
    ],
    [
        'event_id' => 6,
        'event_name' => 'Eco-Product Demo Day',
        'event_description' => 'Test and learn about our latest sustainable products. See how energy-efficient devices and eco-friendly alternatives can transform your daily life.',
        'event_schedule' => 'Monthly Showcase',
        'event_location' => 'Demo Centers & Online',
        'participant_count' => 112,
        'max_participants' => 200
    ]
];

// Try to get events from database, use fallback if table doesn't exist
$events_result = null;
$use_db_events = false;
try {
    // Check if events table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'community_events'");
    if ($table_check->num_rows > 0) {
        $events_sql = "SELECT ce.*, 
                       COUNT(ep.user_id) as participant_count
                       FROM community_events ce
                       LEFT JOIN event_participants ep ON ce.event_id = ep.event_id
                       WHERE ce.is_active = TRUE
                       GROUP BY ce.event_id
                       ORDER BY ce.created_date DESC 
                       LIMIT 6";
        $events_result = $conn->query($events_sql);
        $use_db_events = $events_result && $events_result->num_rows > 0;
    }
} catch (Exception $e) {
    // Use fallback events if table doesn't exist
    $use_db_events = false;
}

require_once __DIR__ . '/includes/header.php';
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
    width: 200px;
    height: 200px;
    background: var(--primary-green);
    top: 10%;
    left: 5%;
    animation-delay: 0s;
}

.shape-2 {
    width: 150px;
    height: 150px;
    background: var(--secondary-green);
    top: 60%;
    right: 10%;
    animation-delay: -2s;
}

.shape-3 {
    width: 180px;
    height: 180px;
    background: var(--text-green);
    bottom: 10%;
    left: 15%;
    animation-delay: -4s;
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

.btn-outline-primary {
    border: 2px solid var(--primary-green);
    color: var(--primary-green);
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
    transform: translateY(-1px);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover,
.btn-outline-secondary.active {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    transform: translateY(-1px);
}

.btn-outline-success {
    border: 2px solid var(--secondary-green);
    color: var(--secondary-green);
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-success:hover {
    background-color: var(--secondary-green);
    border-color: var(--secondary-green);
    transform: translateY(-1px);
}

/* Forms */
.form-control, .form-select {
    border: 2px solid rgba(74, 107, 74, 0.1);
    border-radius: 15px;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(74, 107, 74, 0.1);
}

/* Badges */
.badge {
    border-radius: 10px;
    font-weight: 500;
    padding: 6px 12px;
}

.bg-info { background-color: var(--primary-green) !important; }
.bg-success { background-color: var(--secondary-green) !important; }
.bg-danger { background: linear-gradient(135deg, #ff6b6b, #ee5a52) !important; }
.bg-warning { background: linear-gradient(135deg, #ffd93d, #ffcd38) !important; color: var(--text-green) !important; }

/* List Group */
.list-group-item {
    border: none;
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: rgba(74, 107, 74, 0.05);
    transform: translateX(5px);
}

/* Progress Bars */
.progress {
    border-radius: 10px;
    background-color: rgba(74, 107, 74, 0.1);
    height: 8px;
}

.progress-bar {
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
}

/* Typography */
h1 {
    font-size: 3.5rem;
    font-weight: 700;
    color: var(--text-green);
    margin-bottom: 2rem;
}

h5, h6 {
    color: var(--text-green);
    font-weight: 600;
}

.text-muted {
    color: rgba(45, 74, 45, 0.7) !important;
}

.display-6 {
    color: var(--primary-green);
    font-weight: 700;
}

.fs-2 {
    color: var(--primary-green);
}

/* Alerts */
.alert {
    border-radius: 15px;
    border: none;
}

.alert-success {
    background: rgba(90, 122, 90, 0.1);
    color: var(--text-green);
    border-left: 4px solid var(--secondary-green);
}

.alert-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--text-green);
    border-left: 4px solid #ff6b6b;
}

.bg-light {
    background-color: rgba(255, 255, 255, 0.7) !important;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.text-success {
    color: var(--primary-green) !important;
}

/* New Community Styles */
.hero-section {
    text-align: center;
    padding: 4rem 0;
    background: linear-gradient(135deg, rgba(74, 107, 74, 0.1), rgba(90, 122, 90, 0.05));
    border-radius: var(--border-radius-cards);
    margin-bottom: 3rem;
    border: 2px solid rgba(74, 107, 74, 0.1);
}

.testimonial-card {
    text-align: center;
    padding: 2rem;
    height: 100%;
}

.testimonial-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 2rem;
    font-weight: 600;
}

.photo-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.gallery-item {
    border-radius: 15px;
    overflow: hidden;
    height: 150px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.impact-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.8);
    border-radius: var(--border-radius-cards);
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-green);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-green);
    font-weight: 500;
}

.partner-logo {
    height: 60px;
    margin: 1rem;
    filter: grayscale(100%);
    opacity: 0.7;
    transition: all 0.3s ease;
}

.partner-logo:hover {
    filter: grayscale(0%);
    opacity: 1;
}

.event-card {
    padding: 1.5rem;
    border-left: 4px solid var(--primary-green);
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    margin-bottom: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(74, 107, 74, 0.1);
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(45, 74, 45, 0.2);
}

.leaderboard-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid rgba(74, 107, 74, 0.1);
    transition: all 0.3s ease;
}

.leaderboard-item:hover {
    background-color: rgba(74, 107, 74, 0.05);
}

.leaderboard-rank {
    width: 35px;
    height: 35px;
    background: var(--primary-green);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.cta-section {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 4rem 3rem;
    border-radius: var(--border-radius-cards);
    text-align: center;
    margin: 4rem 0;
    box-shadow: 0 10px 40px rgba(45, 74, 45, 0.2);
}

.cta-section h3 {
    color: white;
    margin-bottom: 1.5rem;
    font-size: 2.5rem;
}

/* Icon Styles */
.icon-large {
    font-size: 2.5rem;
    color: var(--primary-green);
    margin-bottom: 1rem;
}

.icon-medium {
    font-size: 1.5rem;
    color: var(--primary-green);
    margin-right: 0.5rem;
}

/* Section Backgrounds */
.section-light {
    background: linear-gradient(135deg, rgba(212, 196, 168, 0.3), rgba(194, 178, 153, 0.2));
    padding: 4rem 0;
    margin: 3rem 0;
    border-radius: var(--border-radius-cards);
}

.section-medium {
    background: linear-gradient(135deg, rgba(74, 107, 74, 0.05), rgba(90, 122, 90, 0.03));
    padding: 4rem 0;
    margin: 3rem 0;
    border-radius: var(--border-radius-cards);
}

.section-dark {
    background: linear-gradient(135deg, rgba(74, 107, 74, 0.1), rgba(90, 122, 90, 0.05));
    padding: 4rem 0;
    margin: 3rem 0;
    border-radius: var(--border-radius-cards);
}

/* Filter Buttons */
.filter-buttons .btn {
    margin: 0.25rem;
}

/* Success Message */
.success-message {
    background: rgba(90, 122, 90, 0.1);
    border: 1px solid var(--secondary-green);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    color: var(--text-green);
}

.participants-list {
    max-height: 200px;
    overflow-y: auto;
    padding: 1rem;
    background: rgba(74, 107, 74, 0.05);
    border-radius: 10px;
}

.participant-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    margin-right: 10px;
    font-size: 0.9rem;
}

.event-progress {
    margin: 1rem 0;
}

.event-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.event-capacity {
    color: var(--text-green);
    font-weight: 500;
}

.event-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.eco-badge {
    background: linear-gradient(135deg, #ffd93d, #ffcd38);
    color: var(--text-green);
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.featured-badge {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.event-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--primary-green);
}

.community-highlight {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.05));
    border: 2px solid rgba(255, 193, 7, 0.3);
    border-radius: var(--border-radius-cards);
    padding: 2rem;
    margin: 2rem 0;
    text-align: center;
}

.highlight-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.highlight-stat {
    text-align: center;
    padding: 1rem;
}

.highlight-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-green);
    margin-bottom: 0.5rem;
}

.highlight-label {
    color: var(--text-green);
    font-size: 0.9rem;
    font-weight: 500;
}

.demo-notice {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 10px;
    padding: 0.75rem;
    margin: 0.5rem 0;
    text-align: center;
    font-size: 0.9rem;
    color: var(--text-green);
}
</style>

<div class="organic-shape shape-1"></div>
<div class="organic-shape shape-2"></div>
<div class="organic-shape shape-3"></div>

<div class="container py-5">
    
    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle text-success me-2"></i>
            <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="display-4 fw-bold">Together for a Sustainable Future</h1>
        <p class="lead fs-4">Our Eco-Conscious Community Drives Real Change</p>
        <p class="fs-5 text-muted max-w-2xl mx-auto">
            Every sustainable choice creates ripple effects. Here, we celebrate individuals making a difference — 
            reducing waste, supporting green innovation, and inspiring others to choose eco-friendly alternatives.
        </p>
        <div class="mt-4">
            <a href="#join" class="btn btn-dragon btn-lg me-3">Join Our Eco-Community</a>
            <a href="products.php" class="btn btn-outline-primary btn-lg">Explore Sustainable Products</a>
        </div>
    </div>

    <!-- Community Highlights -->
    <div class="community-highlight">
        <h3 class="text-center mb-4">🌱 This Month's Community Impact</h3>
        <div class="highlight-stats">
            <div class="highlight-stat">
                <div class="highlight-value">2.4T</div>
                <div class="highlight-label">CO₂ Prevented</div>
            </div>
            <div class="highlight-stat">
                <div class="highlight-value">156</div>
                <div class="highlight-label">Active Challenges</div>
            </div>
            <div class="highlight-stat">
                <div class="highlight-value">892</div>
                <div class="highlight-label">Eco-Actions Taken</div>
            </div>
            <div class="highlight-stat">
                <div class="highlight-value">45K</div>
                <div class="highlight-label">EcoPoints Earned</div>
            </div>
        </div>
    </div>

    <!-- Featured Stories / Testimonials -->
    <div class="section-light">
        <div class="container">
            <h2 class="text-center mb-4">Community Success Stories</h2>
            <div class="row">
                <?php if ($testimonials_result && $testimonials_result->num_rows > 0): ?>
                    <?php while($testimonial = $testimonials_result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card testimonial-card">
                                <div class="testimonial-avatar">
                                    <?php echo strtoupper(substr($testimonial['first_name'], 0, 1)); ?>
                                </div>
                                <h5><?php echo htmlspecialchars($testimonial['first_name'] . ' ' . $testimonial['last_name']); ?></h5>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($testimonial['product_name'] ?? 'Eco-Friendly Product'); ?></p>
                                <div class="text-warning mb-3">
                                    <?php echo str_repeat('★', $testimonial['rating']); ?>
                                </div>
                                <p class="fst-italic">"<?php echo htmlspecialchars($testimonial['comment']); ?>"</p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback testimonials -->
                    <div class="col-md-4 mb-4">
                        <div class="card testimonial-card">
                            <div class="testimonial-avatar">M</div>
                            <h5>Michael T.</h5>
                            <p class="text-muted mb-3">Energy-Saving Devices</p>
                            <p class="fst-italic">"Switching to solar-powered gadgets through your rental program cut my energy bill by 40%. The quality and support have been exceptional!"</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card testimonial-card">
                            <div class="testimonial-avatar">S</div>
                            <h5>Sarah L.</h5>
                            <p class="text-muted mb-3">Eco Home Solutions</p>
                            <p class="fst-italic">"The EcoPoints program motivated our entire family to adopt sustainable habits. We've saved over 200kg of CO₂ and earned rewards too!"</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card testimonial-card">
                            <div class="testimonial-avatar">J</div>
                            <h5>James K.</h5>
                            <p class="text-muted mb-3">Green Tech</p>
                            <p class="fst-italic">"Renting high-efficiency appliances instead of buying has transformed our home's carbon footprint while saving us money monthly."</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photo Gallery / Social Wall -->
    <div class="section-medium">
        <div class="container">
            <h2 class="text-center mb-4">Eco-Action Gallery</h2>
            <p class="text-center text-muted mb-4">
                Share your sustainable journey with #EcoWarriorLife #SustainableLiving
            </p>
            <div class="photo-gallery">
                <div class="gallery-item"><i class="fas fa-solar-panel"></i></div>
                <div class="gallery-item"><i class="fas fa-recycle"></i></div>
                <div class="gallery-item"><i class="fas fa-leaf"></i></div>
                <div class="gallery-item"><i class="fas fa-seedling"></i></div>
                <div class="gallery-item"><i class="fas fa-wind"></i></div>
                <div class="gallery-item"><i class="fas fa-bolt"></i></div>
            </div>
        </div>
    </div>

    <!-- Community Goals & Achievements -->
    <div class="section-dark">
        <div class="container">
            <h2 class="text-center mb-4">Our Collective Environmental Impact</h2>
            <div class="impact-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total_rentals']); ?>+</div>
                    <div class="stat-label">Sustainable Rentals</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total_co2_saved']); ?>kg</div>
                    <div class="stat-label">CO₂ Emissions Prevented</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total_members']); ?>+</div>
                    <div class="stat-label">Eco-Conscious Members</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['total_ecopoints']); ?></div>
                    <div class="stat-label">Total EcoPoints Earned</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Community Stats -->
    <div class="section-light">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="icon-large"><i class="fas fa-users"></i></div>
                            <h3><?php echo $stats['total_members']; ?></h3>
                            <p class="text-muted">Community Members</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="icon-large"><i class="fas fa-comments"></i></div>
                            <h3><?php echo $stats['total_posts']; ?></h3>
                            <p class="text-muted">Eco-Discussions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="icon-large"><i class="fas fa-star"></i></div>
                            <h3><?php echo $stats['total_reviews']; ?></h3>
                            <p class="text-muted">Product Reviews</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="icon-large"><i class="fas fa-seedling"></i></div>
                            <h3><?php echo number_format($stats['total_co2_saved']); ?>kg</h3>
                            <p class="text-muted">CO₂ Saved</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Partner & Collaboration Highlights -->
    <div class="section-medium">
        <div class="container">
            <h2 class="text-center mb-4">Our Sustainability Partners</h2>
            <p class="text-center text-muted mb-4">
                Collaborating with innovators and environmental organizations to drive meaningful change.
            </p>
            <div class="text-center">
                <div class="d-flex flex-wrap justify-content-center align-items-center">
                    <div class="partner-logo"><i class="fas fa-solar-panel fa-2x"></i> SolarTech Solutions</div>
                    <div class="partner-logo"><i class="fas fa-recycle fa-2x"></i> GreenCycle Inc</div>
                    <div class="partner-logo"><i class="fas fa-wind fa-2x"></i> Renewable Energy Co</div>
                    <div class="partner-logo"><i class="fas fa-tree fa-2x"></i> Forest Guardians</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Events & Campaigns -->
    <div class="section-dark">
        <div class="container">
            <h2 class="text-center mb-4">Upcoming Eco-Events & Campaigns</h2>
            <p class="text-center text-muted mb-4">Join our community events and make a tangible environmental impact</p>
            
            <?php if (!$use_db_events): ?>
                <div class="demo-notice">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Demo Mode:</strong> Events are currently displaying sample data. The full event system will be available after database setup.
                </div>
            <?php endif; ?>
            
            <div class="row">
                <?php if ($use_db_events): ?>
                    <?php while($event = $events_result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card event-card" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['event_id']; ?>">
                                <div class="card-body">
                                    <div class="event-icon text-center">
                                        <?php 
                                        $icon = 'fas fa-leaf';
                                        if (strpos($event['event_name'], 'Challenge') !== false) $icon = 'fas fa-trophy';
                                        elseif (strpos($event['event_name'], 'Workshop') !== false) $icon = 'fas fa-graduation-cap';
                                        elseif (strpos($event['event_name'], 'Recycling') !== false) $icon = 'fas fa-recycle';
                                        elseif (strpos($event['event_name'], 'Innovation') !== false) $icon = 'fas fa-lightbulb';
                                        elseif (strpos($event['event_name'], 'Garden') !== false) $icon = 'fas fa-seedling';
                                        elseif (strpos($event['event_name'], 'Demo') !== false) $icon = 'fas fa-desktop';
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <h5 class="card-title text-center"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($event['event_description']); ?></p>
                                    
                                    <div class="event-progress">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Participation</small>
                                            <small class="text-muted"><?php echo $event['participant_count']; ?>/<?php echo $event['max_participants']; ?></small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo ($event['participant_count'] / $event['max_participants']) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="event-meta">
                                        <div>
                                            <i class="fas fa-calendar text-success"></i>
                                            <small class="text-muted"><?php echo htmlspecialchars($event['event_schedule']); ?></small>
                                        </div>
                                        <div class="badge bg-primary">
                                            <i class="fas fa-users"></i> 
                                            <?php echo $event['participant_count']; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (isLoggedIn()): ?>
                                        <?php
                                        $already_joined = false;
                                        if ($use_db_events) {
                                            $check_sql = "SELECT * FROM event_participants WHERE event_id = ? AND user_id = ?";
                                            $stmt = $conn->prepare($check_sql);
                                            if ($stmt) {
                                                $stmt->bind_param("ii", $event['event_id'], $_SESSION['user_id']);
                                                $stmt->execute();
                                                $already_joined = $stmt->get_result()->num_rows > 0;
                                                $stmt->close();
                                            }
                                        }
                                        ?>
                                        <div class="event-actions">
                                            <?php if ($already_joined): ?>
                                                <button class="btn btn-success btn-sm w-100" disabled>
                                                    <i class="fas fa-check"></i> Joined
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" class="w-100">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                    <input type="hidden" name="event_action" value="join">
                                                    <button type="submit" class="btn btn-dragon btn-sm w-100">
                                                        <i class="fas fa-plus"></i> Join Event
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary btn-sm w-100">Login to Join</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Event Modal -->
                        <div class="modal fade" id="eventModal<?php echo $event['event_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="<?php echo $icon; ?> me-2"></i>
                                            <?php echo htmlspecialchars($event['event_name']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description']); ?></p>
                                                <div class="mb-3">
                                                    <strong><i class="fas fa-calendar text-success me-2"></i>Schedule:</strong> 
                                                    <?php echo htmlspecialchars($event['event_schedule']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong><i class="fas fa-map-marker-alt text-success me-2"></i>Location:</strong> 
                                                    <?php echo htmlspecialchars($event['event_location']); ?>
                                                </div>
                                                
                                                <div class="event-progress mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <strong>Event Capacity</strong>
                                                        <span><?php echo $event['participant_count']; ?>/<?php echo $event['max_participants']; ?> participants</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" style="width: <?php echo ($event['participant_count'] / $event['max_participants']) * 100; ?>%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>Event Details</h6>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Duration: 2-3 hours</small>
                                                        </div>
                                                        <div class="mb-2">
                                                            <small class="text-muted">EcoPoints: +50</small>
                                                        </div>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Impact: High</small>
                                                        </div>
                                                        <?php if ($event['participant_count'] >= $event['max_participants'] * 0.8): ?>
                                                            <div class="badge bg-warning mt-2">Almost Full!</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <h6>Recent Participants (<?php echo min(10, $event['participant_count']); ?> shown):</h6>
                                            <div class="participants-list">
                                                <?php
                                                // Get actual participants from database
                                                $participants_sql = "SELECT u.first_name, u.last_name 
                                                                   FROM event_participants ep 
                                                                   JOIN users u ON ep.user_id = u.user_id 
                                                                   WHERE ep.event_id = ? 
                                                                   ORDER BY ep.join_date DESC 
                                                                   LIMIT 10";
                                                $stmt = $conn->prepare($participants_sql);
                                                if ($stmt) {
                                                    $stmt->bind_param("i", $event['event_id']);
                                                    $stmt->execute();
                                                    $participants_result = $stmt->get_result();
                                                    
                                                    if ($participants_result->num_rows > 0) {
                                                        while($participant = $participants_result->fetch_assoc()) {
                                                            echo '<div class="d-flex align-items-center mb-2">';
                                                            echo '<div class="participant-avatar">';
                                                            echo strtoupper(substr($participant['first_name'], 0, 1));
                                                            echo '</div>';
                                                            echo '<div>';
                                                            echo '<div>' . htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']) . '</div>';
                                                            echo '<small class="text-muted">Active member</small>';
                                                            echo '</div>';
                                                            echo '</div>';
                                                        }
                                                    } else {
                                                        echo '<p class="text-muted">No participants yet. Be the first to join!</p>';
                                                    }
                                                    $stmt->close();
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <?php if (isLoggedIn()): ?>
                                            <?php if ($already_joined): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                    <input type="hidden" name="event_action" value="leave">
                                                    <button type="submit" class="btn btn-outline-danger">
                                                        <i class="fas fa-times"></i> Leave Event
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                                    <input type="hidden" name="event_action" value="join">
                                                    <button type="submit" class="btn btn-dragon">
                                                        <i class="fas fa-plus"></i> Join Event
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-dragon">Login to Join</a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Use fallback events if database table doesn't exist -->
                    <?php foreach($fallback_events as $event): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card event-card" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['event_id']; ?>">
                                <div class="card-body">
                                    <div class="event-icon text-center">
                                        <?php 
                                        $icon = 'fas fa-leaf';
                                        if (strpos($event['event_name'], 'Challenge') !== false) $icon = 'fas fa-trophy';
                                        elseif (strpos($event['event_name'], 'Workshop') !== false) $icon = 'fas fa-graduation-cap';
                                        elseif (strpos($event['event_name'], 'Recycling') !== false) $icon = 'fas fa-recycle';
                                        elseif (strpos($event['event_name'], 'Innovation') !== false) $icon = 'fas fa-lightbulb';
                                        elseif (strpos($event['event_name'], 'Garden') !== false) $icon = 'fas fa-seedling';
                                        elseif (strpos($event['event_name'], 'Demo') !== false) $icon = 'fas fa-desktop';
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <h5 class="card-title text-center"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($event['event_description']); ?></p>
                                    
                                    <div class="event-progress">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Participation</small>
                                            <small class="text-muted"><?php echo $event['participant_count']; ?>/<?php echo $event['max_participants']; ?></small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?php echo ($event['participant_count'] / $event['max_participants']) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="event-meta">
                                        <div>
                                            <i class="fas fa-calendar text-success"></i>
                                            <small class="text-muted"><?php echo htmlspecialchars($event['event_schedule']); ?></small>
                                        </div>
                                        <div class="badge bg-primary">
                                            <i class="fas fa-users"></i> 
                                            <?php echo $event['participant_count']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="event-actions">
                                        <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                            <i class="fas fa-info-circle"></i> Demo Event
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Event Modal -->
                        <div class="modal fade" id="eventModal<?php echo $event['event_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="<?php echo $icon; ?> me-2"></i>
                                            <?php echo htmlspecialchars($event['event_name']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="demo-notice">
                                            <i class="fas fa-info-circle me-2"></i>
                                            This is a demo event. The full event system with join/leave functionality will be available after database setup.
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-8">
                                                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description']); ?></p>
                                                <div class="mb-3">
                                                    <strong><i class="fas fa-calendar text-success me-2"></i>Schedule:</strong> 
                                                    <?php echo htmlspecialchars($event['event_schedule']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong><i class="fas fa-map-marker-alt text-success me-2"></i>Location:</strong> 
                                                    <?php echo htmlspecialchars($event['event_location']); ?>
                                                </div>
                                                
                                                <div class="event-progress mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <strong>Event Capacity</strong>
                                                        <span><?php echo $event['participant_count']; ?>/<?php echo $event['max_participants']; ?> participants</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" style="width: <?php echo ($event['participant_count'] / $event['max_participants']) * 100; ?>%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>Event Details</h6>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Duration: 2-3 hours</small>
                                                        </div>
                                                        <div class="mb-2">
                                                            <small class="text-muted">EcoPoints: +50</small>
                                                        </div>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Impact: High</small>
                                                        </div>
                                                        <?php if ($event['participant_count'] >= $event['max_participants'] * 0.8): ?>
                                                            <div class="badge bg-warning mt-2">Almost Full!</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <h6>Sample Participants (<?php echo min(10, $event['participant_count']); ?> shown):</h6>
                                            <div class="participants-list">
                                                <?php for($i = 0; $i < min(10, $event['participant_count']); $i++): ?>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="participant-avatar">
                                                            <?php echo chr(65 + $i); ?>
                                                        </div>
                                                        <div>
                                                            <div>Community Member <?php echo $i + 1; ?></div>
                                                            <small class="text-muted">Active participant</small>
                                                        </div>
                                                    </div>
                                                <?php endfor; ?>
                                                <?php if ($event['participant_count'] > 10): ?>
                                                    <div class="text-center text-muted">
                                                        <small>And <?php echo $event['participant_count'] - 10; ?> more participants...</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div class="demo-notice w-100 text-center">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Event joining functionality will be available after database setup
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Leaderboard & Discussion Forum -->
    <div class="section-light">
        <div class="container">
            <div class="row">
                <!-- Leaderboard -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-trophy icon-medium"></i>Top Eco Contributors</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($top_members_result && $top_members_result->num_rows > 0): ?>
                                <?php $rank = 1; ?>
                                <?php while($member = $top_members_result->fetch_assoc()): ?>
                                    <div class="leaderboard-item">
                                        <div class="leaderboard-rank"><?php echo $rank; ?></div>
                                        <div class="flex-grow-1">
                                            <strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></strong>
                                            <?php if ($rank <= 3): ?>
                                                <span class="eco-badge ms-2">Top Performer</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-success fw-bold">
                                            <?php echo number_format($member['eco_points_balance']); ?> pts
                                        </div>
                                    </div>
                                    <?php $rank++; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users icon-large text-muted"></i>
                                    <p class="text-muted">Community leaderboard will appear here as members earn EcoPoints</p>
                                    <a href="eco-points.php" class="btn btn-outline-primary btn-sm">Learn About EcoPoints</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Discussion Forum -->
                <div class="col-md-6">
                    <?php if (isLoggedIn()): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-edit icon-medium"></i>Start an Eco-Discussion</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <input type="text" class="form-control" name="title" placeholder="What sustainable topic would you like to discuss?" required>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select" name="post_type" required>
                                        <option value="Discussion">General Discussion</option>
                                        <option value="Question">Eco-Question</option>
                                        <option value="Tip">Sustainability Tip</option>
                                        <option value="Announcement">Community News</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="3" placeholder="Share your thoughts, questions, or sustainable living advice..." required></textarea>
                                </div>
                                <button type="submit" name="create_post" class="btn btn-dragon">Create Post</button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <h5><i class="fas fa-comments icon-medium"></i>Join the Eco-Conversation!</h5>
                            <p class="text-muted">Login to participate in community discussions and share your sustainability journey.</p>
                            <a href="login.php" class="btn btn-dragon">Login to Participate</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quick Stats -->
                    <div class="card">
                        <div class="card-body">
                            <h6><i class="fas fa-chart-line icon-medium"></i>Community Momentum</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-success fw-bold">+24%</div>
                                    <small class="text-muted">Engagement</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-success fw-bold">156</div>
                                    <small class="text-muted">Active Now</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-success fw-bold">89</div>
                                    <small class="text-muted">New Members</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Discussions -->
    <div class="section-dark">
        <div class="container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-comments icon-medium"></i>Recent Community Discussions</h5>
                    <div class="filter-buttons">
                        <a href="community.php?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                        <a href="community.php?filter=questions" class="btn btn-sm <?php echo $filter === 'questions' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Questions</a>
                        <a href="community.php?filter=tips" class="btn btn-sm <?php echo $filter === 'tips' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Tips</a>
                        <a href="community.php?filter=announcements" class="btn btn-sm <?php echo $filter === 'announcements' ? 'btn-primary' : 'btn-outline-secondary'; ?>">News</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($posts_result && $posts_result->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($post = $posts_result->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <?php if ($post['is_pinned']): ?>
                                                <span class="badge bg-warning me-2"><i class="fas fa-thumbtack"></i> Pinned</span>
                                            <?php endif; ?>
                                            <span class="badge bg-<?php 
                                                switch($post['post_type']) {
                                                    case 'Question': echo 'info'; break;
                                                    case 'Tip': echo 'success'; break;
                                                    case 'Announcement': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo $post['post_type']; ?></span>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($post['post_date'])); ?></small>
                                    </div>
                                    
                                    <h6 class="mb-2">
                                        <a href="post-details.php?id=<?php echo $post['post_id']; ?>" class="text-decoration-none" style="color: var(--text-green);">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h6>
                                    
                                    <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200) . (strlen($post['content']) > 200 ? '...' : ''))); ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-user icon-medium"></i>
                                            <strong><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></strong>
                                        </small>
                                        <div>
                                            <small class="text-muted me-3"><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> likes</small>
                                            <small class="text-muted me-3"><i class="fas fa-comment"></i> <?php echo $post['comment_count'] ?? 0; ?> comments</small>
                                            <a href="post-details.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">Join Discussion</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="forum.php" class="btn btn-outline-primary">View All Discussions</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="icon-large"><i class="fas fa-comments"></i></div>
                            <h5>Start the conversation!</h5>
                            <p class="text-muted">Be the first to share your sustainable living experiences and questions.</p>
                            <?php if (!isLoggedIn()): ?>
                                <a href="login.php" class="btn btn-dragon">Login to Post</a>
                            <?php else: ?>
                                <a href="#discussion-form" class="btn btn-dragon">Create First Post</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action Section -->
    <div class="cta-section" id="join">
        <h3>Join the Sustainability Revolution</h3>
        <p class="fs-5 mb-4">
            Every eco-friendly choice, every product review, every shared idea builds a greener future.<br>
            Together, we're proving that sustainable living is not just possible — it's powerful.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="register.php" class="btn btn-light btn-lg">Become an Eco-Member</a>
            <a href="products.php" class="btn btn-outline-light btn-lg">Explore Products</a>
            <a href="eco-points.php" class="btn btn-outline-light btn-lg">Earn EcoPoints</a>
        </div>
    </div>

</div>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-buttons .btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
        });
    });

    // Event card animations
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-5px) scale(1)';
        });
    });

    // Progress bar animation
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-in-out';
            bar.style.width = width;
        }, 100);
    });
});
</script>

<?php 
$conn->close();
require_once __DIR__ . '/includes/footer.php'; 
?>