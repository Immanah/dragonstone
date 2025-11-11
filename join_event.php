<?php
// join_event.php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $conn = getDatabaseConnection();
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if already joined
    $check_sql = "SELECT * FROM event_participants WHERE event_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        // Join event
        $join_sql = "INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($join_sql);
        $stmt->bind_param("ii", $event_id, $user_id);
        
        if ($stmt->execute()) {
            header("Location: community.php?success=Successfully+joined+event");
        } else {
            header("Location: community.php?error=Error+joining+event");
        }
    } else {
        header("Location: community.php?error=Already+joined+this+event");
    }
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: community.php");
    exit();
}
?>