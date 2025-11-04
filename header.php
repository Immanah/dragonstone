<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--secondary-color) !important;
        }
        
        .cart-badge {
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-leaf"></i> Dragonstone
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="community.php">Community</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="subscriptions.php">Subscriptions</a>
                </li>
                
                <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(getCurrentUserFirstName()); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                            <li><a class="dropdown-item" href="eco-points.php"><i class="fas fa-star"></i> EcoPoints</a></li>
                            <li><a class="dropdown-item" href="carbon-tracking.php"><i class="fas fa-chart-line"></i> Carbon Tracking</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php 
                            $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                            if ($cart_count > 0): 
                            ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['login_success'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo htmlspecialchars($_SESSION['login_success']); 
            unset($_SESSION['login_success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['cart_message'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo htmlspecialchars($_SESSION['cart_message']); 
            unset($_SESSION['cart_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<main>
