<?php
require_once __DIR__ . '/includes/config.php';
$conn = getDatabaseConnection();

// Get post ID from URL
$post_id = $_GET['id'] ?? 0;

// Get post details
$post_sql = "SELECT fp.*, u.first_name, u.last_name 
             FROM forum_posts fp 
             JOIN users u ON fp.user_id = u.user_id 
             WHERE fp.post_id = ?";
$stmt = $conn->prepare($post_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post_result = $stmt->get_result();
$post = $post_result->fetch_assoc();

// Handle new reply submission
if (isset($_POST['create_reply']) && isLoggedIn()) {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content)) {
        $sql = "INSERT INTO forum_replies (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $post_id, $user_id, $content);
        
        if ($stmt->execute()) {
            header("Location: post-details.php?id=$post_id&success=Reply+added+successfully");
            exit();
        } else {
            $error = "Error adding reply. Please try again.";
        }
    } else {
        $error = "Please enter reply content.";
    }
}

// Get replies for this post
$replies_sql = "SELECT fr.*, u.first_name, u.last_name 
                FROM forum_replies fr 
                JOIN users u ON fr.user_id = u.user_id 
                WHERE fr.post_id = ? 
                ORDER BY fr.reply_date ASC";
$stmt = $conn->prepare($replies_sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$replies_result = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Add to your existing styles */
.reply-section {
    border-left: 3px solid var(--primary-green);
    padding-left: 1rem;
    margin-left: 1rem;
}

.event-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(45, 74, 45, 0.15);
}

.participants-list {
    max-height: 200px;
    overflow-y: auto;
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
}
</style>

<div class="container py-5">
    <?php if (!$post): ?>
        <div class="alert alert-danger">Post not found.</div>
    <?php else: ?>
        <!-- Back to Community -->
        <a href="community.php" class="btn btn-outline-primary mb-4">
            <i class="fas fa-arrow-left"></i> Back to Community
        </a>

        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
            </div>
        <?php endif; ?>

        <!-- Post Details -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start">
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
            </div>
            <div class="card-body">
                <h3 class="mb-3"><?php echo htmlspecialchars($post['title']); ?></h3>
                <div class="d-flex align-items-center mb-4">
                    <div class="participant-avatar me-3">
                        <?php echo strtoupper(substr($post['first_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></strong>
                        <div class="text-muted small">Community Member</div>
                    </div>
                </div>
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
            </div>
        </div>

        <!-- Reply Form -->
        <?php if (isLoggedIn()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-reply"></i> Post a Reply</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="content" rows="4" placeholder="Share your thoughts..." required></textarea>
                        </div>
                        <button type="submit" name="create_reply" class="btn btn-dragon">Post Reply</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5><i class="fas fa-sign-in-alt"></i> Login to Participate</h5>
                    <p class="text-muted">Please login to reply to this discussion.</p>
                    <a href="login.php" class="btn btn-dragon">Login</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Replies Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-comments"></i> 
                    Replies 
                    <span class="badge bg-primary"><?php echo $replies_result->num_rows; ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($replies_result->num_rows > 0): ?>
                    <div class="replies-list">
                        <?php while($reply = $replies_result->fetch_assoc()): ?>
                            <div class="reply-section mb-4 pb-4 border-bottom">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="participant-avatar me-3">
                                        <?php echo strtoupper(substr($reply['first_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?></strong>
                                                <div class="text-muted small">
                                                    <?php echo date('M j, Y g:i A', strtotime($reply['reply_date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5>No replies yet</h5>
                        <p class="text-muted">Be the first to reply to this discussion!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$conn->close();
require_once __DIR__ . '/includes/footer.php'; 
?>