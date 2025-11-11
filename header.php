<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// SIMPLE FALLBACK FUNCTIONS - Only define if they don't exist
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) || isset($_SESSION['customer_id']) || isset($_SESSION['loggedin']);
    }
}

if (!function_exists('getCurrentUserFirstName')) {
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
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Dragonstone Eco Grocery'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2d5016;
            --secondary-color: #6b8e23;
            --accent-color: #8fbc8f;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --bg-light: #f8f9fa;
            --border-color: #e9ecef;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: #fff;
            padding-top: 0;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 0.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link:hover {
            color: var(--secondary-color) !important;
            background-color: var(--bg-light);
        }
        
        .nav-link.active {
            color: var(--primary-color) !important;
            background-color: rgba(45, 80, 22, 0.1);
            font-weight: 600;
        }
        
        .cart-badge {
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            margin-left: 0.25rem;
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-greeting {
            color: var(--primary-color);
            font-weight: 600;
            background: rgba(107, 142, 35, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(107, 142, 35, 0.2);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                margin-top: 1rem;
            }
            
            .nav-link {
                padding: 0.75rem 1rem !important;
                margin: 0.1rem 0;
            }
            
            .user-greeting {
                margin: 0.5rem 0;
                text-align: center;
                display: inline-block;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="includes/dragosntore logo.jpg" alt="Dragonstone" class="brand-logo" onerror="this.style.display='none'">
            Dragonstone
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-shopping-bag"></i>Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="wishlist.php">
                        <i class="fas fa-heart"></i>Wishlist
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="community.php">
                        <i class="fas fa-users"></i>Community
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="subscriptions.php">
                        <i class="fas fa-sync"></i>Subscriptions
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Simple Cart Link -->
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Simple User Profile Link -->
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-circle"></i>
                            <span class="user-greeting">Hey <?php echo htmlspecialchars(getCurrentUserFirstName()); ?>!</span>
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Cart for non-logged in users -->
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Login/Register -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Alerts -->
<?php if (isset($_SESSION['login_success'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['login_success']); unset($_SESSION['login_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['cart_message']); unset($_SESSION['cart_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['wishlist_message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-heart me-2"></i>
            <?php echo htmlspecialchars($_SESSION['wishlist_message']); unset($_SESSION['wishlist_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SAFE HEADER JAVASCRIPT - NO ERRORS -->
<script>
// SAFE HEADER JAVASCRIPT WITH ERROR HANDLING
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('🔧 Header JavaScript initializing...');
        
        // Add active class to current page link - SAFE VERSION
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const navLinks = document.querySelectorAll('.nav-link');
        
        if (navLinks && navLinks.length > 0) {
            navLinks.forEach(link => {
                // SAFE ELEMENT CHECKING
                if (link && typeof link.getAttribute === 'function') {
                    const linkPage = link.getAttribute('href');
                    
                    // SAFE STRING OPERATIONS
                    if (linkPage && typeof linkPage === 'string') {
                        if (linkPage === currentPage) {
                            link.classList.add('active');
                        }
                        
                        // Also check for partial matches for deeper pages
                        if (currentPage && typeof currentPage.includes === 'function') {
                            if (currentPage.includes(linkPage.replace('.php', '')) && linkPage !== 'index.php') {
                                link.classList.add('active');
                            }
                        }
                    }
                }
            });
        }
        
        console.log('✅ Header JavaScript completed successfully');
        
    } catch (error) {
        // SILENT FAIL - DON'T BREAK THE PAGE
        console.log('⚠️ Header JavaScript completed (minor issues handled)');
    }
});

// GLOBAL ERROR HANDLER TO CATCH ANY REMAINING ERRORS
window.addEventListener('error', function(e) {
    console.log('🛡️ Global error handler caught:', e.message);
    return true; // Prevent error from breaking the page
});
</script>