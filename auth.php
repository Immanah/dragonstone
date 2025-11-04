<?php
// Authentication functions

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

// Login user
function loginUser($user_id, $email, $role = 'customer', $first_name = '') {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['login_time'] = time();
}

// Logout user
function logoutUser() {
    session_unset();
    session_destroy();
}

// Require login - redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $current_page = $_SERVER['PHP_SELF'];
        header('Location: login.php?redirect=' . urlencode($current_page));
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user email
function getCurrentUserEmail() {
    return $_SESSION['email'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? 'customer';
}

// Get current user first name
function getCurrentUserFirstName() {
    return $_SESSION['first_name'] ?? '';
}

// Verify Google token (placeholder - requires Google Client Library)
function verifyGoogleToken($token) {
    // This would normally verify with Google's API
    // For now, return false to disable Google login
    return false;
}
?>
