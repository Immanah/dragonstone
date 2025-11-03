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

// Get reward details
$reward_type = $_POST['reward_type'] ?? '';
$reward_name = $_POST['reward_name'] ?? '';
$reward_cost = (int)($_POST['reward_cost'] ?? 0);
$reward_code = $_POST['reward_code'] ?? '';

// Start transaction
$conn->begin_transaction();

try {
    // Get user's current balance
    $user_sql = "SELECT eco_points_balance, first_name, email FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    
    // Double-check balance
    if ($user['eco_points_balance'] < $reward_cost) {
        throw new Exception("Insufficient EcoPoints balance.");
    }
    
    // Generate unique code for vouchers
    $voucher_code = '';
    if ($reward_type === 'voucher') {
        $voucher_code = 'DSV-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }
    
    // Deduct points from user's balance
    $update_sql = "UPDATE users SET eco_points_balance = eco_points_balance - ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $reward_cost, $user_id);
    $update_stmt->execute();
    
    // Record the transaction
    $transaction_sql = "INSERT INTO eco_point_transactions (user_id, points, transaction_type, reason) VALUES (?, ?, 'Spent', ?)";
    $transaction_stmt = $conn->prepare($transaction_sql);
    $reason = "Redeemed: " . $reward_name;
    $transaction_stmt->bind_param("iis", $user_id, $reward_cost, $reason);
    $transaction_stmt->execute();
    
    // Record the redemption
    $redemption_sql = "INSERT INTO reward_redemptions (user_id, reward_type, reward_name, points_cost, voucher_code, status) VALUES (?, ?, ?, ?, ?, 'completed')";
    $redemption_stmt = $conn->prepare($redemption_sql);
    $redemption_stmt->bind_param("issis", $user_id, $reward_type, $reward_name, $reward_cost, $voucher_code);
    $redemption_stmt->execute();
    
    $redemption_id = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    // Store success data in session for the success page
    $_SESSION['redemption_success'] = [
        'reward_name' => $reward_name,
        'reward_cost' => $reward_cost,
        'voucher_code' => $voucher_code,
        'redemption_id' => $redemption_id,
        'reward_type' => $reward_type
    ];
    
    header('Location: redemption-success.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Redemption failed: " . $e->getMessage();
    header('Location: eco-points.php');
    exit;
}
?>