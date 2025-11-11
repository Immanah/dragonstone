<?php
// auth.php - SIMPLE AUTHENTICATION FUNCTIONS

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['customer_id']) || isset($_SESSION['loggedin']);
}

// Require login - redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Get current user info
function getCurrentUserFirstName() {
    if (isset($_SESSION['user_first_name'])) {
        return $_SESSION['user_first_name'];
    } elseif (isset($_SESSION['customer_first_name'])) {
        return $_SESSION['customer_first_name'];
    } elseif (isset($_SESSION['first_name'])) {
        return $_SESSION['first_name'];
    }
    return 'User';
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
}

// Get current user email
function getCurrentUserEmail() {
    return $_SESSION['email'] ?? $_SESSION['user_email'] ?? null;
}

// Simple login function
function loginUser($user_id, $email, $first_name = '') {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['login_time'] = time();
}

// Logout function
function logoutUser() {
    $_SESSION = array();
    session_destroy();
}
?>