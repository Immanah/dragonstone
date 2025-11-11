<?php
// Include files with correct paths
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

requireLogin();

$conn = getDatabaseConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle location form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_location'])) {
        $location_data = [
            'address_type' => $_POST['address_type'] ?? 'home',
            'street_address' => $_POST['street_address'] ?? '',
            'apartment_unit' => $_POST['apartment_unit'] ?? '',
            'house_number' => $_POST['house_number'] ?? '',
            'complex_name' => $_POST['complex_name'] ?? '',
            'city' => $_POST['city'] ?? '',
            'province' => $_POST['province'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'country' => $_POST['country'] ?? 'South Africa',
            'latitude' => $_POST['latitude'] ?? '',
            'longitude' => $_POST['longitude'] ?? '',
            'delivery_instructions' => $_POST['delivery_instructions'] ?? '',
            'is_primary' => isset($_POST['is_primary']) ? 1 : 0
        ];

        // Save to user_locations table
        $location_sql = "INSERT INTO user_locations 
                        (user_id, address_type, street_address, apartment_unit, house_number, complex_name, 
                         city, province, postal_code, country, latitude, longitude, delivery_instructions, is_primary, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        address_type = VALUES(address_type),
                        street_address = VALUES(street_address),
                        apartment_unit = VALUES(apartment_unit),
                        house_number = VALUES(house_number),
                        complex_name = VALUES(complex_name),
                        city = VALUES(city),
                        province = VALUES(province),
                        postal_code = VALUES(postal_code),
                        country = VALUES(country),
                        latitude = VALUES(latitude),
                        longitude = VALUES(longitude),
                        delivery_instructions = VALUES(delivery_instructions),
                        is_primary = VALUES(is_primary)";
        
        $location_stmt = $conn->prepare($location_sql);
        $location_stmt->bind_param("isssssssssddss", 
            $user_id,
            $location_data['address_type'],
            $location_data['street_address'],
            $location_data['apartment_unit'],
            $location_data['house_number'],
            $location_data['complex_name'],
            $location_data['city'],
            $location_data['province'],
            $location_data['postal_code'],
            $location_data['country'],
            $location_data['latitude'],
            $location_data['longitude'],
            $location_data['delivery_instructions'],
            $location_data['is_primary']
        );
        
        if ($location_stmt->execute()) {
            $_SESSION['success_message'] = "Location saved successfully!";
        } else {
            $_SESSION['error_message'] = "Error saving location: " . $conn->error;
        }
    }
}

// Get user's saved locations
$saved_locations = [];
try {
    $locations_sql = "SELECT * FROM user_locations WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC";
    $locations_stmt = $conn->prepare($locations_sql);
    $locations_stmt->bind_param("i", $user_id);
    $locations_stmt->execute();
    $saved_locations = $locations_stmt->get_result();
} catch (Exception $e) {
    // Table might not exist yet
    $saved_locations = new stdClass();
    $saved_locations->num_rows = 0;
}

// NEW: Get user's active subscriptions
$user_subscriptions = [];
$subscriptions_count = 0;
try {
    $subs_sql = "SELECT s.*, p.name as product_name, p.price, p.co2_saved, p.image_path, p.image_url
                 FROM subscriptions s 
                 JOIN products p ON s.product_id = p.product_id 
                 WHERE s.user_id = ? AND s.status = 'Active' 
                 ORDER BY s.next_delivery_date ASC";
    $subs_stmt = $conn->prepare($subs_sql);
    $subs_stmt->bind_param("i", $user_id);
    $subs_stmt->execute();
    $user_subscriptions = $subs_stmt->get_result();
    $subscriptions_count = $user_subscriptions->num_rows;
} catch (Exception $e) {
    // If subscriptions table doesn't exist yet
    $user_subscriptions = new stdClass();
    $user_subscriptions->num_rows = 0;
}

// Handle subscription cancellation from profile
if (isset($_POST['cancel_subscription']) && isLoggedIn()) {
    $subscription_id = intval($_POST['subscription_id']);
    $user_id = $_SESSION['user_id'];
    
    $cancel_sql = "UPDATE subscriptions SET status = 'Cancelled' WHERE subscription_id = ? AND user_id = ?";
    $cancel_stmt = $conn->prepare($cancel_sql);
    $cancel_stmt->bind_param("ii", $subscription_id, $user_id);
    
    if ($cancel_stmt->execute()) {
        $_SESSION['success_message'] = "Subscription cancelled successfully.";
        header('Location: profile.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Error cancelling subscription. Please try again.";
    }
}

// Safely get user's recent orders
$recent_orders = [];
$orders_count = 0;

try {
    $orders_sql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                   FROM orders o 
                   LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                   WHERE o.user_id = ? 
                   GROUP BY o.order_id 
                   ORDER BY o.order_date DESC 
                   LIMIT 5";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $recent_orders = $orders_stmt->get_result();
    $orders_count = $recent_orders->num_rows;
} catch (Exception $e) {
    // If order_items table doesn't exist yet, use a simpler query
    $orders_sql = "SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC LIMIT 5";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $recent_orders = $orders_stmt->get_result();
    $orders_count = $recent_orders->num_rows;
}

// Safely get EcoPoints transactions
$points_history = [];
try {
    $points_sql = "SELECT * FROM eco_point_transactions 
                   WHERE user_id = ? 
                   ORDER BY transaction_date DESC 
                   LIMIT 10";
    $points_stmt = $conn->prepare($points_sql);
    $points_stmt->bind_param("i", $user_id);
    $points_stmt->execute();
    $points_history = $points_stmt->get_result();
} catch (Exception $e) {
    // If eco_point_transactions table doesn't exist yet, create empty result
    $points_history = new stdClass();
    $points_history->num_rows = 0;
}

$page_title = "My Profile - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <div class="avatar-container">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="user-info">
                        <h5 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="user-badges">
                            <span class="role-badge customer">
                                <?php echo $user['role']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="user-points">
                        <div class="points-display">
                            <div class="points-amount"><?php echo number_format($user['eco_points_balance']); ?></div>
                            <div class="points-label">EcoPoints</div>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar-menu">
                    <a href="profile.php" class="sidebar-item active">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                        </span>
                        Dashboard
                    </a>
                    <a href="orders.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </span>
                        My Orders
                    </a>
                    <a href="subscriptions.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 9h16"></path>
                                <path d="M4 15h16"></path>
                                <path d="M10 3L8 21"></path>
                                <path d="M16 3l-2 18"></path>
                            </svg>
                        </span>
                        My Subscriptions
                    </a>
                    <a href="eco-points.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                            </svg>
                        </span>
                        EcoPoints
                    </a>
                    <a href="settings.php" class="sidebar-item">
                        <span class="sidebar-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </span>
                        Settings
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="page-header">
                <h1 class="page-title">My Account Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! Here's your account overview.</p>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $orders_count; ?></div>
                        <div class="stat-label">Recent Orders</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon points">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($user['eco_points_balance']); ?></div>
                        <div class="stat-label">EcoPoints</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 9h16"></path>
                            <path d="M4 15h16"></path>
                            <path d="M10 3L8 21"></path>
                            <path d="M16 3l-2 18"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $subscriptions_count; ?></div>
                        <div class="stat-label">Active Subscriptions</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">Member</div>
                        <div class="stat-label">Since <?php echo date('M Y', strtotime($user['date_created'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- NEW: Active Subscriptions Section -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="header-content">
                        <h3 class="card-title">My Active Subscriptions</h3>
                        <a href="subscriptions.php" class="btn btn-outline">
                            <span class="btn-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                            Manage All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($subscriptions_count > 0): ?>
                        <div class="subscriptions-grid">
                            <?php while($subscription = $user_subscriptions->fetch_assoc()): ?>
                                <?php
                                // Use same image logic as products page
                                $main_image = 'includes/Screenshot 2025-10-30 145731.png';
                                if (!empty($subscription['image_path'])) {
                                    $main_image = $subscription['image_path'];
                                } elseif (!empty($subscription['image_url'])) {
                                    $main_image = $subscription['image_url'];
                                }
                                
                                $days_until = ceil((strtotime($subscription['next_delivery_date']) - time()) / (60 * 60 * 24));
                                ?>
                                <div class="subscription-card">
                                    <div class="subscription-header">
                                        <div class="subscription-image">
                                            <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                                 alt="<?php echo htmlspecialchars($subscription['product_name']); ?>"
                                                 onerror="this.src='includes/Screenshot 2025-10-30 145731.png'">
                                        </div>
                                        <div class="subscription-info">
                                            <h5 class="subscription-name"><?php echo htmlspecialchars($subscription['product_name']); ?></h5>
                                            <div class="subscription-details">
                                                <span class="frequency-badge"><?php echo $subscription['frequency']; ?></span>
                                                <span class="co2-saved">🌱 <?php echo $subscription['co2_saved']; ?>kg CO₂ saved</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="subscription-body">
                                        <div class="delivery-info">
                                            <div class="next-delivery">
                                                <strong>Next Delivery:</strong> 
                                                <?php echo date('M j, Y', strtotime($subscription['next_delivery_date'])); ?>
                                            </div>
                                            <div class="delivery-countdown <?php echo $days_until <= 3 ? 'soon' : ''; ?>">
                                                <?php echo $days_until > 0 ? "In $days_until days" : "Delivery today!"; ?>
                                            </div>
                                        </div>
                                        <div class="subscription-actions">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="subscription_id" value="<?php echo $subscription['subscription_id']; ?>">
                                                <button type="submit" name="cancel_subscription" class="btn btn-outline-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to cancel this subscription?')">
                                                    <i class="fas fa-times me-1"></i>Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-subscriptions">
                            <div class="no-data-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <path d="M4 9h16"></path>
                                    <path d="M4 15h16"></path>
                                    <path d="M10 3L8 21"></path>
                                    <path d="M16 3l-2 18"></path>
                                </svg>
                            </div>
                            <h4>No Active Subscriptions</h4>
                            <p>You don't have any active subscriptions yet.</p>
                            <a href="subscriptions.php" class="btn btn-primary">Browse Subscriptions</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Locations Section -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">My Delivery Locations</h3>
                    <p class="card-subtitle">Manage your delivery addresses and preferences</p>
                </div>
                <div class="card-body">
                    <!-- Location Form -->
                    <div class="location-form-section">
                        <h4 class="section-title">Add New Location</h4>
                        
                        <form method="POST" class="location-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="address_type" class="form-label">Address Type</label>
                                    <select id="address_type" name="address_type" class="form-control">
                                        <option value="home">Home</option>
                                        <option value="work">Work</option>
                                        <option value="apartment">Apartment</option>
                                        <option value="complex">Complex</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="house_number" class="form-label">House Number</label>
                                    <input type="text" id="house_number" name="house_number" class="form-control" placeholder="e.g., 123">
                                </div>
                                <div class="form-group">
                                    <label for="apartment_unit" class="form-label">Apartment/Unit (Optional)</label>
                                    <input type="text" id="apartment_unit" name="apartment_unit" class="form-control" placeholder="e.g., Unit 4B">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="complex_name" class="form-label">Complex/Building Name (Optional)</label>
                                <input type="text" id="complex_name" name="complex_name" class="form-control" placeholder="e.g., Green Valley Complex">
                            </div>

                            <div class="form-group">
                                <label for="street_address" class="form-label">Street Address *</label>
                                <input type="text" id="street_address" name="street_address" class="form-control" required placeholder="e.g., Main Street">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" id="city" name="city" class="form-control" required placeholder="e.g., Johannesburg">
                                </div>
                                <div class="form-group">
                                    <label for="province" class="form-label">Province *</label>
                                    <select id="province" name="province" class="form-control" required>
                                        <option value="">Select Province</option>
                                        <option value="Gauteng">Gauteng</option>
                                        <option value="Western Cape">Western Cape</option>
                                        <option value="KwaZulu-Natal">KwaZulu-Natal</option>
                                        <option value="Eastern Cape">Eastern Cape</option>
                                        <option value="Free State">Free State</option>
                                        <option value="Limpopo">Limpopo</option>
                                        <option value="Mpumalanga">Mpumalanga</option>
                                        <option value="North West">North West</option>
                                        <option value="Northern Cape">Northern Cape</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">Postal Code *</label>
                                    <input type="text" id="postal_code" name="postal_code" class="form-control" required placeholder="e.g., 2000">
                                </div>
                            </div>

                            <!-- Google Maps Integration -->
                            <div class="form-group">
                                <label class="form-label">Pin Your Location on Map</label>
                                <div class="map-container">
                                    <div id="locationMap" style="height: 300px; border-radius: 8px; border: 1px solid var(--color-border);"></div>
                                    <div class="map-instructions">
                                        <small>Drag the marker to your exact location or click on the map to set your location</small>
                                    </div>
                                </div>
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
                            </div>

                            <div class="form-group">
                                <label for="delivery_instructions" class="form-label">Delivery Instructions (Optional)</label>
                                <textarea id="delivery_instructions" name="delivery_instructions" class="form-control" rows="3" placeholder="e.g., Leave at front door, Call when arriving, Security gate code, etc."></textarea>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is_primary" name="is_primary" value="1" class="form-check-input">
                                <label for="is_primary" class="form-check-label">Set as primary delivery address</label>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_location" class="btn btn-primary">Save Location</button>
                                <button type="button" id="getCurrentLocation" class="btn btn-outline">Use My Current Location</button>
                            </div>
                        </form>
                    </div>

                    <!-- Saved Locations -->
                    <div class="saved-locations-section">
                        <h4 class="section-title">Saved Locations</h4>
                        <?php if ($saved_locations->num_rows > 0): ?>
                            <div class="locations-grid">
                                <?php while($location = $saved_locations->fetch_assoc()): ?>
                                    <div class="location-card <?php echo $location['is_primary'] ? 'primary-location' : ''; ?>">
                                        <div class="location-header">
                                            <div class="location-type">
                                                <span class="type-badge"><?php echo ucfirst($location['address_type']); ?></span>
                                                <?php if ($location['is_primary']): ?>
                                                    <span class="primary-badge">Primary</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="location-actions">
                                                <button class="btn-icon edit-location" data-location='<?php echo json_encode($location); ?>'>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="btn-icon delete-location" data-id="<?php echo $location['location_id']; ?>">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="location-body">
                                            <p class="location-address">
                                                <?php 
                                                $address_parts = [];
                                                if (!empty($location['house_number'])) $address_parts[] = $location['house_number'];
                                                if (!empty($location['apartment_unit'])) $address_parts[] = $location['apartment_unit'];
                                                if (!empty($location['complex_name'])) $address_parts[] = $location['complex_name'];
                                                if (!empty($location['street_address'])) $address_parts[] = $location['street_address'];
                                                echo implode(', ', $address_parts);
                                                ?>
                                            </p>
                                            <p class="location-area">
                                                <?php echo $location['city'] . ', ' . $location['province'] . ' ' . $location['postal_code']; ?>
                                            </p>
                                            <?php if (!empty($location['delivery_instructions'])): ?>
                                                <div class="delivery-instructions">
                                                    <strong>Instructions:</strong> <?php echo htmlspecialchars($location['delivery_instructions']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <div class="no-data-icon">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <h4>No Saved Locations</h4>
                                <p>Add your first delivery location to get started.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="header-content">
                        <h3 class="card-title">Recent Orders</h3>
                        <a href="orders.php" class="btn btn-outline">
                            <span class="btn-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($orders_count > 0): ?>
                        <div class="transactions-table">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($order = $recent_orders->fetch_assoc()): 
                                            $status_class = getStatusClass($order['status']);
                                        ?>
                                            <tr class="transaction-row">
                                                <td class="transaction-date">#<?php echo $order['order_id']; ?></td>
                                                <td class="transaction-description"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                                <td class="transaction-points">R<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td class="transaction-type">
                                                    <span class="type-badge <?php echo $status_class; ?>">
                                                        <?php echo $order['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="transaction-action">
                                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-transactions">
                            <div class="no-data-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                            <h4>No Orders Yet</h4>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- EcoPoints History -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Recent EcoPoints Activity</h3>
                </div>
                <div class="card-body">
                    <?php if ($points_history->num_rows > 0): ?>
                        <div class="transactions-table">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Points</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($transaction = $points_history->fetch_assoc()): 
                                            $is_earned = $transaction['transaction_type'] == 'Earned';
                                        ?>
                                            <tr class="transaction-row">
                                                <td class="transaction-date">
                                                    <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                                </td>
                                                <td class="transaction-description">
                                                    <?php echo htmlspecialchars($transaction['reason']); ?>
                                                </td>
                                                <td class="transaction-points">
                                                    <span class="points-badge <?php echo $is_earned ? 'earned' : 'spent'; ?>">
                                                        <?php echo $is_earned ? '+' : '-'; ?>
                                                        <?php echo $transaction['points']; ?>
                                                    </span>
                                                </td>
                                                <td class="transaction-type">
                                                    <span class="type-badge <?php echo $is_earned ? 'earned' : 'spent'; ?>">
                                                        <?php echo $transaction['transaction_type']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-transactions">
                            <div class="no-data-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                                </svg>
                            </div>
                            <h4>No EcoPoints Activity</h4>
                            <p>Earn points by making purchases and completing eco-challenges!</p>
                            <a href="products.php" class="btn btn-primary">Start Earning</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
require_once __DIR__ . '/includes/footer.php'; 

// Helper function for status styling
function getStatusClass($status) {
    switch($status) {
        case 'Delivered': return 'delivered';
        case 'Shipped': return 'shipped';
        case 'Processing': return 'processing';
        case 'Paid': return 'paid';
        case 'Pending': return 'pending';
        case 'Cancelled': return 'cancelled';
        default: return 'unknown';
    }
}
?>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>
<script>
// Google Maps Integration
let map;
let marker;
let geocoder;

function initMap() {
    // Default to Johannesburg, South Africa
    const defaultLocation = { lat: -26.2041, lng: 28.0473 };
    
    map = new google.maps.Map(document.getElementById('locationMap'), {
        zoom: 12,
        center: defaultLocation,
        styles: [
            {
                featureType: 'all',
                elementType: 'geometry',
                stylers: [{ color: '#f5f5f5' }]
            },
            {
                featureType: 'poi',
                elementType: 'labels.text',
                stylers: [{ visibility: 'off' }]
            }
        ]
    });

    geocoder = new google.maps.Geocoder();

    // Add click listener to map
    map.addListener('click', function(event) {
        placeMarker(event.latLng);
        reverseGeocode(event.latLng);
    });

    // Try to get user's current location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(userLocation);
                placeMarker(userLocation);
                reverseGeocode(userLocation);
            },
            function() {
                // Use default location if geolocation fails
                placeMarker(defaultLocation);
            }
        );
    } else {
        placeMarker(defaultLocation);
    }
}

function placeMarker(location) {
    if (marker) {
        marker.setMap(null);
    }

    marker = new google.maps.Marker({
        position: location,
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP
    });

    // Update hidden fields
    document.getElementById('latitude').value = location.lat();
    document.getElementById('longitude').value = location.lng();

    // Add drag end listener
    marker.addListener('dragend', function() {
        reverseGeocode(marker.getPosition());
    });
}

function reverseGeocode(latLng) {
    geocoder.geocode({ location: latLng }, function(results, status) {
        if (status === 'OK' && results[0]) {
            const address = results[0];
            fillAddressFields(address);
        }
    });
}

function fillAddressFields(address) {
    const addressComponents = address.address_components;
    let streetNumber = '';
    let route = '';
    let city = '';
    let province = '';
    let postalCode = '';

    addressComponents.forEach(component => {
        const types = component.types;
        
        if (types.includes('street_number')) {
            streetNumber = component.long_name;
        } else if (types.includes('route')) {
            route = component.long_name;
        } else if (types.includes('locality') || types.includes('sublocality')) {
            city = component.long_name;
        } else if (types.includes('administrative_area_level_1')) {
            province = component.long_name;
        } else if (types.includes('postal_code')) {
            postalCode = component.long_name;
        }
    });

    // Fill form fields
    if (streetNumber) {
        document.getElementById('house_number').value = streetNumber;
    }
    if (route) {
        document.getElementById('street_address').value = route;
    }
    if (city) {
        document.getElementById('city').value = city;
    }
    if (province) {
        document.getElementById('province').value = province;
    }
    if (postalCode) {
        document.getElementById('postal_code').value = postalCode;
    }
}

// Get current location button
document.getElementById('getCurrentLocation').addEventListener('click', function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setCenter(userLocation);
                placeMarker(userLocation);
                reverseGeocode(userLocation);
            },
            function(error) {
                alert('Unable to get your current location. Please allow location access or use the map to select your location.');
            }
        );
    } else {
        alert('Geolocation is not supported by this browser.');
    }
});

// Initialize map when page loads
google.maps.event.addDomListener(window, 'load', initMap);

// Edit location functionality
document.querySelectorAll('.edit-location').forEach(button => {
    button.addEventListener('click', function() {
        const location = JSON.parse(this.getAttribute('data-location'));
        fillEditForm(location);
    });
});

function fillEditForm(location) {
    document.getElementById('address_type').value = location.address_type;
    document.getElementById('house_number').value = location.house_number || '';
    document.getElementById('apartment_unit').value = location.apartment_unit || '';
    document.getElementById('complex_name').value = location.complex_name || '';
    document.getElementById('street_address').value = location.street_address || '';
    document.getElementById('city').value = location.city || '';
    document.getElementById('province').value = location.province || '';
    document.getElementById('postal_code').value = location.postal_code || '';
    document.getElementById('delivery_instructions').value = location.delivery_instructions || '';
    document.getElementById('is_primary').checked = location.is_primary == 1;

    // Update map
    if (location.latitude && location.longitude) {
        const latLng = new google.maps.LatLng(parseFloat(location.latitude), parseFloat(location.longitude));
        map.setCenter(latLng);
        placeMarker(latLng);
    }

    // Scroll to form
    document.querySelector('.location-form-section').scrollIntoView({ behavior: 'smooth' });
}
</script>

<style>
/* NEW: Subscriptions Grid Styles */
.subscriptions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.subscription-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    background: var(--color-white);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.subscription-card:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.subscription-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--color-forest-light);
    transition: opacity 0.3s ease;
}

.subscription-card:hover::before {
    opacity: 1;
}

.subscription-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    gap: 1rem;
}

.subscription-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--color-border);
    flex-shrink: 0;
}

.subscription-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.subscription-info {
    flex: 1;
}

.subscription-name {
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.subscription-details {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.frequency-badge {
    padding: 0.375rem 0.75rem;
    background: var(--color-sand-light);
    color: var(--color-forest-dark);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid var(--color-border);
}

.co2-saved {
    color: var(--color-forest-medium);
    font-size: 0.875rem;
    font-weight: 500;
}

.subscription-body {
    border-top: 1px solid var(--color-border);
    padding-top: 1rem;
}

.delivery-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.next-delivery {
    color: var(--color-text);
    font-size: 0.875rem;
}

.delivery-countdown {
    padding: 0.5rem 1rem;
    background: var(--color-forest-light);
    color: var(--color-white);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}

.delivery-countdown.soon {
    background: #dc3545;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.subscription-actions {
    text-align: right;
}

.no-subscriptions {
    text-align: center;
    padding: 3rem 2rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-sand-light);
}

.no-subscriptions .no-data-icon {
    margin-bottom: 1.5rem;
    color: var(--color-text-light);
    opacity: 0.5;
}

.no-subscriptions h4 {
    color: var(--color-forest-dark);
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.no-subscriptions p {
    color: var(--color-text-light);
    margin-bottom: 2rem;
    font-size: 1rem;
}

/* Update stats grid for 4 items */
.stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

/* Profile Page Styles - Consistent with EcoPoints Design */
:root {
    --color-forest-dark: #2d4a2d;
    --color-forest-medium: #3a5c3a;
    --color-forest-light: #4a7c4a;
    --color-sand-light: #f8f6f2;
    --color-white: #ffffff;
    --color-border: #e8e6e1;
    --color-text: #333333;
    --color-text-light: #666666;
    --border-radius: 12px;
    --border-radius-sm: 8px;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
}

.page-header {
    margin-bottom: 2.5rem;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 1.5rem;
}

.page-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--color-text-light);
    margin-bottom: 0;
    font-size: 1.125rem;
}

/* Profile Sidebar - Consistent with EcoPoints Page */
.profile-sidebar {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.user-card {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
    text-align: center;
    background: linear-gradient(135deg, var(--color-sand-light) 0%, #ffffff 100%);
}

.user-avatar {
    margin-bottom: 1rem;
}

.avatar-container {
    width: 80px;
    height: 80px;
    border: 2px solid var(--color-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: var(--color-white);
    color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.user-name {
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
    font-weight: 600;
    font-size: 1.125rem;
}

.user-email {
    color: var(--color-text-light);
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.user-badges {
    margin-bottom: 1.5rem;
}

.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
    border: 1px solid;
    letter-spacing: 0.02em;
}

.role-badge.admin {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.role-badge.customer {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
}

.user-points {
    border-top: 1px solid var(--color-border);
    padding-top: 1.5rem;
}

.points-display {
    text-align: center;
}

.points-amount {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-forest-medium);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.points-label {
    font-size: 0.875rem;
    color: var(--color-text-light);
    font-weight: 500;
}

.sidebar-menu {
    padding: 1rem 0.5rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
    font-weight: 500;
    background: var(--color-white);
}

.sidebar-item:hover {
    background: var(--color-sand-light);
    border-color: var(--color-border);
    transform: translateX(4px);
    box-shadow: var(--shadow-sm);
}

.sidebar-item.active {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
    box-shadow: var(--shadow-sm);
}

.sidebar-icon {
    margin-right: 0.75rem;
    width: 16px;
    text-align: center;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
}

.stat-card:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    background: var(--color-forest-light);
    color: var(--color-white);
    border-color: var(--color-forest-light);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--color-text-light);
    font-size: 0.875rem;
    font-weight: 500;
}

/* Dashboard Cards */
.dashboard-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    margin-bottom: 2rem;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.card-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 1.25rem;
    letter-spacing: -0.01em;
}

.card-subtitle {
    margin: 0.25rem 0 0 0;
    color: var(--color-text-light);
    font-size: 0.875rem;
}

.card-body {
    padding: 2rem;
}

/* Location Management Styles */
.location-form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--color-forest-dark);
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-forest-medium);
    box-shadow: 0 0 0 3px rgba(58, 92, 58, 0.1);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-check-input {
    margin: 0;
}

.form-check-label {
    margin: 0;
    font-weight: 500;
    color: var(--color-forest-dark);
}

.form-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.map-container {
    margin-bottom: 1rem;
}

.map-instructions {
    margin-top: 0.5rem;
    color: var(--color-text-light);
    font-size: 0.875rem;
}

/* Saved Locations */
.saved-locations-section {
    margin-top: 2rem;
}

.locations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.location-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    background: var(--color-white);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.location-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--color-forest-light);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.location-card:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.location-card:hover::before {
    opacity: 1;
}

.location-card.primary-location {
    border-color: var(--color-forest-medium);
    background: var(--color-sand-light);
    box-shadow: var(--shadow-sm);
}

.location-card.primary-location::before {
    opacity: 1;
    background: var(--color-forest-medium);
}

.location-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.location-type {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.type-badge {
    padding: 0.375rem 0.75rem;
    background: var(--color-sand-light);
    color: var(--color-forest-dark);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid var(--color-border);
}

.primary-badge {
    padding: 0.375rem 0.75rem;
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid var(--color-forest-medium);
}

.location-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    padding: 0.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    background: transparent;
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: var(--color-sand-light);
    border-color: var(--color-forest-light);
    color: var(--color-forest-dark);
}

.location-address {
    font-weight: 500;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.location-area {
    color: var(--color-text-light);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.delivery-instructions {
    padding: 0.75rem;
    background: var(--color-sand-light);
    border-radius: var(--border-radius-sm);
    font-size: 0.875rem;
    color: var(--color-text);
    border-left: 3px solid var(--color-forest-light);
}

/* Transactions Table */
.transactions-table .table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.transaction-row {
    border-bottom: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

.transaction-row:hover {
    background: var(--color-sand-light);
}

.transaction-row:last-child {
    border-bottom: none;
}

.transaction-date {
    font-weight: 500;
    color: var(--color-forest-dark);
    white-space: nowrap;
}

.transaction-description {
    color: var(--color-text);
    max-width: 300px;
}

.points-badge,
.type-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid;
    letter-spacing: 0.02em;
}

.points-badge.earned,
.type-badge.earned {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
}

.points-badge.spent,
.type-badge.spent {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.type-badge.delivered {
    background: #e8f5e8;
    color: var(--color-forest-dark);
    border-color: #d4edda;
}

.type-badge.shipped {
    background: #cce7ff;
    color: #004085;
    border-color: #b3d7ff;
}

.type-badge.processing {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.type-badge.paid {
    background: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
}

.type-badge.pending {
    background: #e2e3e5;
    color: #383d41;
    border-color: #d6d8db;
}

/* No Data States */
.no-data,
.no-transactions {
    text-align: center;
    padding: 3rem 2rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-sand-light);
}

.no-data-icon {
    margin-bottom: 1.5rem;
    color: var(--color-text-light);
    opacity: 0.5;
}

.no-data h4,
.no-transactions h4 {
    color: var(--color-forest-dark);
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.no-data p,
.no-transactions p {
    color: var(--color-text-light);
    margin-bottom: 2rem;
    font-size: 1rem;
}

/* Alert Styles */
.alert {
    border-radius: var(--border-radius);
    border: 1px solid;
    margin-bottom: 2rem;
    padding: 1.25rem 1.5rem;
    font-weight: 500;
}

.alert-success {
    background-color: #e8f5e8;
    border-color: #d4edda;
    color: var(--color-forest-dark);
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border: 2px solid;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    letter-spacing: 0.02em;
    cursor: pointer;
}

.btn-primary {
    background: var(--color-forest-medium);
    color: var(--color-white);
    border-color: var(--color-forest-medium);
}

.btn-primary:hover {
    background: var(--color-forest-dark);
    border-color: var(--color-forest-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(58, 92, 58, 0.3);
}

.btn-outline {
    background: transparent;
    color: var(--color-forest-medium);
    border-color: var(--color-forest-medium);
}

.btn-outline:hover {
    background: var(--color-forest-medium);
    color: var(--color-white);
    transform: translateY(-2px);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
}

.btn-icon {
    margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .locations-grid {
        grid-template-columns: 1fr;
    }
    
    .subscriptions-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .location-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .location-actions {
        align-self: flex-end;
    }
    
    .transactions-table .table {
        font-size: 0.875rem;
    }
    
    .subscription-header {
        flex-direction: column;
        text-align: center;
    }
    
    .delivery-info {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.75rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .transactions-table .table thead {
        display: none;
    }
    
    .transactions-table .table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius-sm);
        padding: 1rem;
    }
    
    .transactions-table .table tbody td {
        display: block;
        text-align: right;
        padding: 0.5rem 0;
        border-bottom: none;
    }
    
    .transactions-table .table tbody td::before {
        content: attr(data-label);
        float: left;
        font-weight: 600;
        color: var(--color-forest-dark);
    }
}
</style>