<?php
// Use the config file which already includes database.php
require_once __DIR__ . '/includes/config.php';

$conn = getDatabaseConnection();

// Handle new post submission
if (isset($_POST['create_post']) && isLoggedIn()) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'];
    $user_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($content)) {
        $sql = "INSERT INTO forum_posts (user_id, title, content, post_type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $user_id, $title, $content, $post_type);
        
        if ($stmt->execute()) {
            $success = "Post created successfully!";
        } else {
            $error = "Error creating post. Please try again.";
        }
    } else {
        $error = "Please fill in both title and content.";
    }
}

// Get forum posts
$posts_sql = "SELECT fp.*, u.first_name, u.last_name 
              FROM forum_posts fp 
              JOIN users u ON fp.user_id = u.user_id 
              ORDER BY fp.is_pinned DESC, fp.post_date DESC 
              LIMIT 20";
$posts_result = $conn->query($posts_sql);

// Get community stats
$stats_sql = "SELECT 
              (SELECT COUNT(*) FROM forum_posts) as total_posts,
              (SELECT COUNT(*) FROM users) as total_members,
              (SELECT COUNT(*) FROM reviews WHERE is_approved = 1) as total_reviews,
              (SELECT SUM(co2_saved) FROM products) as total_co2_saved";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

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
</style>

<div class="organic-shape shape-1"></div>
<div class="organic-shape shape-2"></div>
<div class="organic-shape shape-3"></div>

<div class="container py-5">
    <h1 class="text-center mb-5">Community Hub</h1>
    
    <!-- Community Stats -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2">👥</div>
                    <h3><?php echo $stats['total_members']; ?></h3>
                    <p class="text-muted">Community Members</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2">💬</div>
                    <h3><?php echo $stats['total_posts']; ?></h3>
                    <p class="text-muted">Discussions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2">⭐</div>
                    <h3><?php echo $stats['total_reviews']; ?></h3>
                    <p class="text-muted">Product Reviews</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="fs-2">🌱</div>
                    <h3><?php echo number_format($stats['total_co2_saved']); ?>kg</h3>
                    <p class="text-muted">CO2 Saved</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <!-- Create Post Card (only for logged in users) -->
            <?php if (isLoggedIn()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Create New Post</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Post Title</label>
                            <input type="text" class="form-control" name="title" placeholder="What would you like to discuss?" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Post Type</label>
                            <select class="form-select" name="post_type" required>
                                <option value="Discussion">💬 Discussion</option>
                                <option value="Question">❓ Question</option>
                                <option value="Tip">💡 Tip</option>
                                <option value="Announcement">📢 Announcement</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea class="form-control" name="content" rows="4" placeholder="Share your thoughts, questions, or sustainable living tips..." required></textarea>
                        </div>
                        <button type="submit" name="create_post" class="btn btn-dragon">Create Post</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5>Join the Conversation!</h5>
                    <p class="text-muted">Login to participate in community discussions and share your sustainable living journey.</p>
                    <a href="login.php" class="btn btn-dragon">Login to Participate</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Forum Posts -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Community Discussions</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary active">All</button>
                        <button class="btn btn-sm btn-outline-secondary">Questions</button>
                        <button class="btn btn-sm btn-outline-secondary">Tips</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($posts_result->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($post = $posts_result->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <?php if ($post['is_pinned']): ?>
                                                <span class="badge bg-warning me-2">📌 Pinned</span>
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
                                            Posted by <strong><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></strong>
                                        </small>
                                        <div>
                                            <small class="text-muted me-3">❤️ <?php echo $post['like_count']; ?> likes</small>
                                            <a href="post-details.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">View Discussion</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="fs-1">💬</div>
                            <h5>No discussions yet</h5>
                            <p class="text-muted">Be the first to start a conversation!</p>
                            <?php if (!isLoggedIn()): ?>
                                <a href="login.php" class="btn btn-dragon">Login to Post</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- EcoPoints Summary -->
            <?php if (isLoggedIn()): ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5>Your EcoPoints</h5>
                    <?php
                    $user_id = $_SESSION['user_id'];
                    $user_sql = "SELECT eco_points_balance FROM users WHERE user_id = ?";
                    $user_stmt = $conn->prepare($user_sql);
                    $user_stmt->bind_param("i", $user_id);
                    $user_stmt->execute();
                    $user = $user_stmt->get_result()->fetch_assoc();
                    ?>
                    <div class="display-6 text-success"><?php echo number_format($user['eco_points_balance']); ?></div>
                    <p class="text-muted">Points available</p>
                    <a href="eco-points.php" class="btn btn-dragon btn-sm">Manage Points</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Community Guidelines -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">📜 Community Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li>✅ Be respectful and kind</li>
                        <li>✅ Share sustainable living tips</li>
                        <li>✅ Ask questions and help others</li>
                        <li>✅ Keep discussions eco-focused</li>
                        <li>❌ No spam or self-promotion</li>
                    </ul>
                </div>
            </div>
            
            <!-- Popular Topics -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🔥 Popular Topics</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark">Zero Waste</span>
                        <span class="badge bg-light text-dark">Composting</span>
                        <span class="badge bg-light text-dark">Renewable Energy</span>
                        <span class="badge bg-light text-dark">Sustainable Fashion</span>
                        <span class="badge bg-light text-dark">Eco Cleaning</span>
                        <span class="badge bg-light text-dark">Plant Based</span>
                        <span class="badge bg-light text-dark">Upcycling</span>
                        <span class="badge bg-light text-dark">Carbon Footprint</span>
                    </div>
                </div>
            </div>
            
            <!-- Eco Challenges -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">🏆 Current Challenges</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Plastic-Free Week</h6>
                        <small class="text-muted">Avoid single-use plastic for 7 days</small>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 65%"></div>
                        </div>
                        <small>65% completed</small>
                    </div>
                    <div class="mb-3">
                        <h6>Meatless Monday</h6>
                        <small class="text-muted">Go vegetarian every Monday</small>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 40%"></div>
                        </div>
                        <small>40% completed</small>
                    </div>
                    <button class="btn btn-outline-success btn-sm w-100">Join Challenges</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
require_once __DIR__ . '/includes/footer.php'; 
?>