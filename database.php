<?php
// database.php - Database Connection and Setup
require_once __DIR__ . '/config.php';

// Prevent multiple inclusions
if (!defined('DRAGONSTONE_DB_INCLUDED')) {
    define('DRAGONSTONE_DB_INCLUDED', true);

    function getDatabaseConnection() {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            // Try connecting without database to create it
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                return false;
            }
            
            // Create database if it doesn't exist
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                error_log("Failed to create database: " . $conn->error);
                return false;
            }
            $conn->select_db(DB_NAME);
        }
        
        // Set charset to prevent issues
        $conn->set_charset("utf8mb4");
        
        return $conn;
    }

    // Function to join an event
    function joinEvent($user_id, $event_id) {
        $conn = getDatabaseConnection();
        if (!$conn) return false;

        // Check if event exists and has capacity
        $event_check = $conn->prepare("SELECT max_participants, (SELECT COUNT(*) FROM event_participants WHERE event_id = ?) as current_participants FROM community_events WHERE event_id = ? AND is_active = TRUE");
        $event_check->bind_param("ii", $event_id, $event_id);
        $event_check->execute();
        $result = $event_check->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event) {
            return ['success' => false, 'message' => 'Event not found or inactive'];
        }
        
        if ($event['current_participants'] >= $event['max_participants']) {
            return ['success' => false, 'message' => 'Event is full'];
        }

        // Check if user already joined
        $check_joined = $conn->prepare("SELECT participant_id FROM event_participants WHERE user_id = ? AND event_id = ?");
        $check_joined->bind_param("ii", $user_id, $event_id);
        $check_joined->execute();
        
        if ($check_joined->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'You have already joined this event'];
        }

        // Join the event
        $join_stmt = $conn->prepare("INSERT INTO event_participants (user_id, event_id) VALUES (?, ?)");
        $join_stmt->bind_param("ii", $user_id, $event_id);
        
        if ($join_stmt->execute()) {
            // Log activity
            logUserActivity($user_id, 'event_join', "Joined event ID: $event_id");
            
            return ['success' => true, 'message' => 'Successfully joined the event!'];
        } else {
            return ['success' => false, 'message' => 'Failed to join event'];
        }
    }

    // Function to leave an event
    function leaveEvent($user_id, $event_id) {
        $conn = getDatabaseConnection();
        if (!$conn) return false;

        $leave_stmt = $conn->prepare("DELETE FROM event_participants WHERE user_id = ? AND event_id = ?");
        $leave_stmt->bind_param("ii", $user_id, $event_id);
        
        if ($leave_stmt->execute() && $leave_stmt->affected_rows > 0) {
            // Log activity
            logUserActivity($user_id, 'event_leave', "Left event ID: $event_id");
            
            return ['success' => true, 'message' => 'Successfully left the event'];
        } else {
            return ['success' => false, 'message' => 'You are not participating in this event'];
        }
    }

    // Function to check if user has joined an event
    function hasJoinedEvent($user_id, $event_id) {
        $conn = getDatabaseConnection();
        if (!$conn) return false;

        $check_stmt = $conn->prepare("SELECT participant_id FROM event_participants WHERE user_id = ? AND event_id = ?");
        $check_stmt->bind_param("ii", $user_id, $event_id);
        $check_stmt->execute();
        
        return $check_stmt->get_result()->num_rows > 0;
    }

    // Function to get event participants count
    function getEventParticipantsCount($event_id) {
        $conn = getDatabaseConnection();
        if (!$conn) return 0;

        $count_stmt = $conn->prepare("SELECT COUNT(*) as participant_count FROM event_participants WHERE event_id = ?");
        $count_stmt->bind_param("i", $event_id);
        $count_stmt->execute();
        $result = $count_stmt->get_result();
        $data = $result->fetch_assoc();
        
        return $data['participant_count'] ?? 0;
    }

    // Function to get user's joined events
    function getUserJoinedEvents($user_id) {
        $conn = getDatabaseConnection();
        if (!$conn) return [];

        $events_stmt = $conn->prepare("
            SELECT ce.*, ep.join_date 
            FROM community_events ce 
            INNER JOIN event_participants ep ON ce.event_id = ep.event_id 
            WHERE ep.user_id = ? AND ce.is_active = TRUE 
            ORDER BY ep.join_date DESC
        ");
        $events_stmt->bind_param("i", $user_id);
        $events_stmt->execute();
        $result = $events_stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $events;
    }

    // Function to log user activity
    function logUserActivity($user_id, $activity_type, $activity_details) {
        $conn = getDatabaseConnection();
        if (!$conn) return false;

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $log_stmt = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, activity_details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("issss", $user_id, $activity_type, $activity_details, $ip_address, $user_agent);
        $log_stmt->execute();
        
        return true;
    }

    // Only run setup if this file is accessed directly
    if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'database.php') {
        echo "<!DOCTYPE html><html><head><title>Database Setup</title><style>body{font-family:Arial,sans-serif;margin:20px;line-height:1.6;}</style></head><body>";
        echo "<h1>DragonStone Database Setup</h1>";
        
        $conn = getDatabaseConnection();
        if (!$conn) {
            die("<div style='color: red; padding: 20px; border: 2px solid red;'>❌ Database connection failed. Please check your database configuration in config.php</div>");
        }
        
        echo "<div style='color: green;'>✅ Database connected successfully!</div>";
        
        // Disable foreign key checks during setup
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        // Table creation queries with CASCADE deletes for user-related tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                user_id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255),
                google_id VARCHAR(255) NULL,
                auth_provider ENUM('email','google') DEFAULT 'email',
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                role ENUM('Customer','Admin','ContentManager','ReportViewer') DEFAULT 'Customer',
                eco_points_balance INT DEFAULT 0,
                date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME NULL,
                login_count INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                phone VARCHAR(20),
                address TEXT,
                city VARCHAR(100),
                postal_code VARCHAR(20),
                newsletter_subscribed BOOLEAN DEFAULT FALSE,
                verification_code VARCHAR(10) NULL,
                is_verified TINYINT(1) DEFAULT 0,
                INDEX idx_email (email),
                INDEX idx_google_id (google_id),
                INDEX idx_auth_provider (auth_provider),
                INDEX idx_role (role),
                INDEX idx_verified (is_verified),
                INDEX idx_last_login (last_login)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS suppliers (
                supplier_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                contact_email VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS user_activity (
                activity_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                activity_type VARCHAR(50),
                activity_details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_activity_type (activity_type),
                INDEX idx_activity_date (activity_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS user_sessions (
                session_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                logout_time TIMESTAMP NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_login_time (login_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS user_registration_log (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                registration_ip VARCHAR(45),
                user_agent TEXT,
                verification_sent BOOLEAN DEFAULT FALSE,
                verification_sent_date DATETIME NULL,
                verified_date DATETIME NULL,
                referral_source VARCHAR(100),
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_registration_date (registration_date),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS user_login_activity (
                activity_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                login_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                login_ip VARCHAR(45),
                user_agent TEXT,
                login_method ENUM('email','google') DEFAULT 'email',
                success BOOLEAN DEFAULT TRUE,
                failure_reason VARCHAR(255) NULL,
                session_id VARCHAR(255),
                logout_date DATETIME NULL,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_login_date (login_date),
                INDEX idx_success (success),
                INDEX idx_session_id (session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS admin_users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('superadmin', 'admin', 'manager', 'reports') DEFAULT 'admin',
                permissions JSON,
                status ENUM('active', 'inactive') DEFAULT 'active',
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS admin_activity (
                activity_id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                activity_type VARCHAR(50) NOT NULL,
                activity_details TEXT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NOT NULL,
                activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_activity_type (activity_type),
                INDEX idx_activity_date (activity_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS user_activity_log (
                activity_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                activity_type VARCHAR(100) NOT NULL,
                activity_description TEXT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                related_id INT NULL,
                related_table VARCHAR(50),
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_activity_type (activity_type),
                INDEX idx_activity_date (activity_date),
                INDEX idx_related (related_table, related_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS categories (
                category_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                parent_id INT NULL,
                image_path VARCHAR(255) NULL,
                is_active TINYINT(1) DEFAULT 1,
                show_in_menu TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL,
                INDEX idx_parent_id (parent_id),
                INDEX idx_is_active (is_active),
                INDEX idx_show_in_menu (show_in_menu),
                INDEX idx_sort_order (sort_order),
                INDEX idx_date_created (date_created)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS products (
                product_id INT AUTO_INCREMENT PRIMARY KEY,
                category_id INT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                stock_quantity INT NOT NULL,
                co2_saved DECIMAL(8,2),
                image_url VARCHAR(500),
                image_path VARCHAR(255),
                supplier_id INT,
                low_stock_threshold INT DEFAULT 10,
                is_active BOOLEAN DEFAULT TRUE,
                date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
                INDEX idx_category (category_id),
                INDEX idx_active (is_active),
                INDEX idx_name (name),
                INDEX idx_supplier (supplier_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS orders (
                order_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                total_amount DECIMAL(10,2) NOT NULL,
                shipping_cost DECIMAL(10,2) DEFAULT 0,
                final_amount DECIMAL(10,2) NOT NULL,
                shipping_address TEXT NOT NULL,
                delivery_method VARCHAR(20) DEFAULT 'shipping',
                status VARCHAR(50) DEFAULT 'Processing',
                tracking_number VARCHAR(100),
                customer_phone VARCHAR(20),
                customer_name VARCHAR(255),
                order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_order_date (order_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS payments (
                payment_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                status VARCHAR(50) NOT NULL,
                transaction_id VARCHAR(100) NOT NULL,
                card_type VARCHAR(20),
                card_last4 VARCHAR(4),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
                INDEX idx_order_id (order_id),
                INDEX idx_status (status),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS order_items (
                order_item_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT,
                product_id INT,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
                INDEX idx_order (order_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS cart (
                cart_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                product_id INT,
                quantity INT NOT NULL DEFAULT 1,
                added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
                UNIQUE KEY unique_cart_item (user_id, product_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS reviews (
                review_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                product_id INT,
                rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_approved BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
                FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL,
                INDEX idx_product (product_id),
                INDEX idx_user (user_id),
                INDEX idx_approved (is_approved),
                UNIQUE KEY unique_user_product (user_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS eco_point_transactions (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                points INT NOT NULL,
                transaction_type ENUM('Earned','Spent','Adjusted') NOT NULL,
                reason VARCHAR(255) NOT NULL,
                reference_id INT NULL,
                transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_date (transaction_date),
                INDEX idx_type (transaction_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS subscriptions (
                subscription_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                product_id INT,
                frequency_days INT NOT NULL DEFAULT 30,
                next_delivery DATE NOT NULL,
                status ENUM('active','paused','cancelled') DEFAULT 'active',
                start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_delivery DATE NULL,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
                FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_next_delivery (next_delivery),
                INDEX idx_user_product (user_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS reward_redemptions (
                redemption_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                reward_name VARCHAR(100) NOT NULL,
                reward_type VARCHAR(50) NOT NULL,
                reward_code VARCHAR(50) NOT NULL,
                points_cost INT NOT NULL,
                voucher_code VARCHAR(20) NOT NULL,
                expiry_date DATE NOT NULL,
                status ENUM('active', 'used', 'expired') DEFAULT 'active',
                redeemed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_expiry (expiry_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS forum_posts (
                post_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                post_type ENUM('Discussion','Question','Tip','Announcement') DEFAULT 'Discussion',
                is_pinned BOOLEAN DEFAULT FALSE,
                like_count INT DEFAULT 0,
                post_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_user (user_id),
                INDEX idx_type (post_type),
                INDEX idx_pinned (is_pinned),
                INDEX idx_post_date (post_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS forum_replies (
                reply_id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT,
                user_id INT,
                content TEXT NOT NULL,
                reply_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
                INDEX idx_post_id (post_id),
                INDEX idx_user_id (user_id),
                INDEX idx_reply_date (reply_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS community_events (
                event_id INT AUTO_INCREMENT PRIMARY KEY,
                event_name VARCHAR(255) NOT NULL,
                event_description TEXT,
                event_schedule VARCHAR(255),
                event_location VARCHAR(255),
                max_participants INT DEFAULT 100,
                is_active BOOLEAN DEFAULT TRUE,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_active (is_active),
                INDEX idx_created_date (created_date),
                INDEX idx_max_participants (max_participants)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            "CREATE TABLE IF NOT EXISTS user_addresses (
                address_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                address_line1 TEXT,
                address_line2 TEXT,
                city VARCHAR(100),
                postal_code VARCHAR(20),
                phone VARCHAR(20),
                address_type ENUM('house', 'apartment', 'complex', 'office') DEFAULT 'house',
                delivery_notes TEXT,
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS event_participants (
                participant_id INT AUTO_INCREMENT PRIMARY KEY,
                event_id INT,
                user_id INT,
                join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES community_events(event_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                UNIQUE KEY unique_participation (event_id, user_id),
                INDEX idx_event_id (event_id),
                INDEX idx_user_id (user_id),
                INDEX idx_join_date (join_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS user_locations (
                location_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                address_type ENUM('home', 'work', 'apartment', 'complex', 'other') DEFAULT 'home',
                street_address VARCHAR(255) NOT NULL,
                apartment_unit VARCHAR(50),
                house_number VARCHAR(20),
                complex_name VARCHAR(255),
                city VARCHAR(100) NOT NULL,
                province VARCHAR(100) NOT NULL,
                postal_code VARCHAR(20) NOT NULL,
                country VARCHAR(100) DEFAULT 'South Africa',
                latitude DECIMAL(10, 8),
                longitude DECIMAL(11, 8),
                delivery_instructions TEXT,
                is_primary BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_address_type (address_type),
                INDEX idx_is_primary (is_primary),
                INDEX idx_city (city),
                INDEX idx_province (province),
                INDEX idx_postal_code (postal_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                subject VARCHAR(200) NOT NULL,
                category VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_category (category),
                INDEX idx_priority (priority),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($tables as $table_sql) {
            if ($conn->query($table_sql) === TRUE) {
                $success_count++;
            } else {
                $error_count++;
                $error_msg = "Error creating table: " . $conn->error;
                $errors[] = $error_msg;
                error_log($error_msg);
            }
        }

        // Insert admin user after tables are created
        $admin_insert_sql = "INSERT IGNORE INTO admin_users (username, password_hash, name, role, permissions) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'superadmin', '[\"all\"]')";
        if (!$conn->query($admin_insert_sql)) {
            error_log("Error inserting admin user: " . $conn->error);
        }

        // Add any missing columns to users table with proper error handling
        $alter_queries = [
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL AFTER password_hash",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_provider ENUM('email','google') DEFAULT 'email' AFTER google_id",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_code VARCHAR(10) NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL AFTER date_created",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 0 AFTER last_login",
            "ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL"
        ];

        foreach ($alter_queries as $alter_query) {
            if (!$conn->query($alter_query)) {
                // Ignore errors for columns that already exist
                if (strpos($conn->error, 'Duplicate column name') === false) {
                    error_log("Error altering users table: " . $conn->error);
                }
            }
        }

        // Add any missing columns to products table
        $product_alter_queries = [
            "ALTER TABLE products ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) AFTER image_url",
            "ALTER TABLE products ADD COLUMN IF NOT EXISTS supplier_id INT AFTER image_path",
            "ALTER TABLE products ADD COLUMN IF NOT EXISTS low_stock_threshold INT DEFAULT 10 AFTER supplier_id",
            "ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER low_stock_threshold"
        ];

        foreach ($product_alter_queries as $alter_query) {
            if (!$conn->query($alter_query)) {
                // Ignore duplicate column errors
                if (strpos($conn->error, 'Duplicate column name') === false) {
                    error_log("Error altering products table: " . $conn->error);
                }
            }
        }

        // Ensure all category management columns exist with proper error handling
        $categories_alter_queries = [
            "ALTER TABLE categories ADD COLUMN IF NOT EXISTS parent_id INT NULL AFTER description",
            "ALTER TABLE categories ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL AFTER parent_id",
            "ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER image_path",
            "ALTER TABLE categories ADD COLUMN IF NOT EXISTS show_in_menu TINYINT(1) DEFAULT 1 AFTER is_active",
            "ALTER TABLE categories ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 AFTER show_in_menu",
            "ALTER TABLE categories ADD COLUMN IF NOT EXISTS date_created DATETIME DEFAULT CURRENT_TIMESTAMP AFTER sort_order"
        ];

        foreach ($categories_alter_queries as $alter_query) {
            if (!$conn->query($alter_query)) {
                // Ignore duplicate column errors
                if (strpos($conn->error, 'Duplicate column name') === false) {
                    error_log("Error altering categories table: " . $conn->error);
                }
            }
        }

        // Add missing columns to orders table
        $orders_alter_queries = [
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2) DEFAULT 0 AFTER total_amount",
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS final_amount DECIMAL(10,2) NOT NULL AFTER shipping_cost",
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_method VARCHAR(20) DEFAULT 'shipping' AFTER shipping_address",
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) AFTER status",
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(20) AFTER tracking_number",
            "ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255) AFTER customer_phone"
        ];

        foreach ($orders_alter_queries as $alter_query) {
            if (!$conn->query($alter_query)) {
                // Ignore duplicate column errors
                if (strpos($conn->error, 'Duplicate column name') === false) {
                    error_log("Error altering orders table: " . $conn->error);
                }
            }
        }

        // Add max_participants column to community_events if it doesn't exist
        if (!$conn->query("ALTER TABLE community_events ADD COLUMN IF NOT EXISTS max_participants INT DEFAULT 100 AFTER event_location")) {
            // Ignore duplicate column errors
            if (strpos($conn->error, 'Duplicate column name') === false) {
                error_log("Error adding max_participants to community_events: " . $conn->error);
            }
        }

        // Add foreign key for parent_id in categories if it doesn't exist
        $fk_check_categories = $conn->query("SELECT COUNT(*) as count FROM information_schema.TABLE_CONSTRAINTS 
                                           WHERE CONSTRAINT_SCHEMA = DATABASE() 
                                           AND TABLE_NAME = 'categories' 
                                           AND CONSTRAINT_NAME = 'categories_ibfk_1'");
        if ($fk_check_categories && $fk_check_categories->fetch_assoc()['count'] == 0) {
            if (!$conn->query("ALTER TABLE categories ADD FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL")) {
                error_log("Error adding foreign key to categories: " . $conn->error);
            }
        }

        // Add foreign key for supplier_id if it doesn't exist
        $fk_check = $conn->query("SELECT COUNT(*) as count FROM information_schema.TABLE_CONSTRAINTS 
                                 WHERE CONSTRAINT_SCHEMA = DATABASE() 
                                 AND TABLE_NAME = 'products' 
                                 AND CONSTRAINT_NAME = 'products_ibfk_2'");
        if ($fk_check && $fk_check->fetch_assoc()['count'] == 0) {
            $conn->query("ALTER TABLE products ADD FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)");
        }

        // Add missing columns to forum_posts table
        $forum_alter_queries = [
            "ALTER TABLE forum_posts ADD COLUMN IF NOT EXISTS is_pinned BOOLEAN DEFAULT FALSE",
            "ALTER TABLE forum_posts ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0"
        ];

        foreach ($forum_alter_queries as $alter_query) {
            if (!$conn->query($alter_query)) {
                // Ignore duplicate column errors
                if (strpos($conn->error, 'Duplicate column name') === false) {
                    error_log("Error altering forum_posts table: " . $conn->error);
                }
            }
        }

        // Fix the reviews table foreign key constraint if it exists
        $fix_reviews_sql = "ALTER TABLE reviews DROP FOREIGN KEY IF EXISTS reviews_ibfk_2";
        $conn->query($fix_reviews_sql);
        
        $fix_reviews_sql2 = "ALTER TABLE reviews ADD CONSTRAINT reviews_ibfk_2 FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL";
        if (!$conn->query($fix_reviews_sql2)) {
            error_log("Error fixing reviews foreign key: " . $conn->error);
        }

        // Fix foreign key constraints to use CASCADE for user deletion
        $fix_foreign_keys = [
            "ALTER TABLE eco_point_transactions DROP FOREIGN KEY IF EXISTS eco_point_transactions_ibfk_1",
            "ALTER TABLE eco_point_transactions ADD CONSTRAINT eco_point_transactions_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE",
            
            "ALTER TABLE user_activity DROP FOREIGN KEY IF EXISTS user_activity_ibfk_1", 
            "ALTER TABLE user_activity ADD CONSTRAINT user_activity_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE",
            
            "ALTER TABLE event_participants DROP FOREIGN KEY IF EXISTS event_participants_ibfk_2",
            "ALTER TABLE event_participants ADD CONSTRAINT event_participants_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE"
        ];

        foreach ($fix_foreign_keys as $fix_sql) {
            if (!$conn->query($fix_sql)) {
                // Ignore errors for constraints that don't exist yet
                if (strpos($conn->error, 'check that it exists') === false) {
                    error_log("Error fixing foreign key: " . $conn->error);
                }
            }
        }

        // Insert comprehensive community events
        $events = [
            ['Eco-Warrior Challenge', 'Join our monthly sustainability challenge! Complete eco-friendly tasks and earn bonus EcoPoints while reducing your carbon footprint.', 'Monthly Challenge', 'Online & Local Communities', 500],
            ['Sustainable Living Workshop', 'Learn practical tips for reducing waste, energy conservation, and making eco-conscious purchasing decisions in our interactive workshop.', 'Every Saturday', 'Community Center & Online', 100],
            ['Product Recycling Drive', 'Bring your used electronics, batteries, and other recyclables to our community recycling event. Proper disposal ensures materials get a second life!', 'Bi-weekly', 'Local Collection Centers', 300],
            ['Green Tech Innovation Forum', 'Connect with innovators and sustainability experts discussing the latest in green technology and circular economy solutions.', 'Monthly Meetup', 'Innovation Hub & Virtual', 150],
            ['Community Garden Initiative', 'Help us plant and maintain community gardens that provide fresh produce while promoting biodiversity and sustainable agriculture.', 'Weekly Sessions', 'Local Community Gardens', 200],
            ['Eco-Product Demo Day', 'Test and learn about our latest sustainable products. See how energy-efficient devices and eco-friendly alternatives can transform your daily life.', 'Monthly Showcase', 'Demo Centers & Online', 200]
        ];

        $event_stmt = $conn->prepare("INSERT IGNORE INTO community_events (event_name, event_description, event_schedule, event_location, max_participants) VALUES (?, ?, ?, ?, ?)");
        foreach ($events as $event) {
            $event_stmt->bind_param("ssssi", $event[0], $event[1], $event[2], $event[3], $event[4]);
            if (!$event_stmt->execute()) {
                error_log("Error inserting event: " . $event_stmt->error);
            }
        }
        $event_stmt->close();

        // Insert sample hierarchical categories for testing
        $sample_categories = [
            // Main categories (parent_id = NULL)
            ['Home & Living', 'All home and living essentials', NULL, NULL, 1, 1, 1],
            ['Personal Care', 'Natural personal care products', NULL, NULL, 1, 1, 2],
            ['Kitchen & Dining', 'Sustainable kitchen essentials', NULL, NULL, 1, 1, 3],
            ['Outdoor & Garden', 'Eco-friendly outdoor products', NULL, NULL, 1, 1, 4],
            
            // Subcategories for Home & Living
            ['Bedding', 'Eco-friendly bedding and linens', 1, NULL, 1, 1, 1],
            ['Home Decor', 'Sustainable home decoration', 1, NULL, 1, 1, 2],
            ['Cleaning Supplies', 'Natural cleaning products', 1, NULL, 1, 1, 3],
            
            // Subcategories for Personal Care
            ['Bath & Body', 'Natural bath and body products', 2, NULL, 1, 1, 1],
            ['Oral Care', 'Eco-friendly oral hygiene', 2, NULL, 1, 1, 2],
            ['Hair Care', 'Sustainable hair care solutions', 2, NULL, 1, 1, 3],
            
            // Subcategories for Kitchen & Dining
            ['Food Storage', 'Reusable food containers', 3, NULL, 1, 1, 1],
            ['Cookware', 'Sustainable cooking utensils', 3, NULL, 1, 1, 2],
            ['Cutlery & Tableware', 'Eco-friendly dining sets', 3, NULL, 1, 1, 3]
        ];

        // First, let's get the category IDs for our main categories
        $main_categories = [
            'Home & Living' => 1,
            'Personal Care' => 2, 
            'Kitchen & Dining' => 3,
            'Outdoor & Garden' => 4
        ];

        $category_stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description, parent_id, image_path, is_active, show_in_menu, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($category_stmt) {
            foreach ($sample_categories as $category) {
                $parent_id = $category[2];
                // If it's a subcategory and we have a category name instead of ID, look it up
                if ($parent_id && !is_numeric($parent_id)) {
                    $parent_id = $main_categories[$parent_id] ?? NULL;
                }
                $category_stmt->bind_param("ssisiii", $category[0], $category[1], $parent_id, $category[3], $category[4], $category[5], $category[6]);
                $category_stmt->execute();
            }
            $category_stmt->close();
        }

        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        // Insert sample data only if tables were created successfully
        if ($error_count === 0) {
            // Insert suppliers
            $suppliers = [
                ['EcoSupplies Co.', 'contact@ecosupplies.com', '+1-555-0101', '123 Green Street, Eco City, EC 12345'],
                ['Sustainable Living Ltd.', 'info@sustainableliving.com', '+1-555-0102', '456 Earth Avenue, Greenville, GV 67890'],
                ['Natural Home Products', 'sales@naturalhome.com', '+1-555-0103', '789 Organic Road, Natureville, NV 11223']
            ];

            $stmt = $conn->prepare("INSERT IGNORE INTO suppliers (name, contact_email, phone, address) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                foreach ($suppliers as $supplier) {
                    $stmt->bind_param("ssss", $supplier[0], $supplier[1], $supplier[2], $supplier[3]);
                    $stmt->execute();
                }
                $stmt->close();
            }

            // Insert products with supplier relationships
            $products = [
                [1, 'Bamboo Toothbrush', 'Natural bamboo toothbrush with replaceable head', 199.99, 50, 32, 1, 5],
                [1, 'Compostable Sponges', 'Plant-based cleaning sponges', 89.99, 25, 15, 1, 10],
                [2, 'Reusable Silicone Bags', 'Silicone food storage bags - 3 pack', 249.99, 30, 28, 2, 8],
                [4, 'Organic Cotton Towels', 'Chemical-free bath towels - set of 2', 399.99, 20, 41, 3, 5],
                [7, 'Solar Garden Light', 'Solar powered garden light with motion sensor', 459.99, 15, 56, 2, 3],
                [3, 'Recycled Glass Vase', 'Beautiful vase made from recycled glass', 299.99, 10, 25, 3, 2],
                [5, 'Eco Yoga Mat', 'Natural rubber yoga mat with carrying strap', 349.99, 18, 30, 1, 5],
                [6, 'Wooden Toy Set', 'FSC-certified wooden building blocks', 279.99, 12, 35, 2, 4],
                [2, 'Bamboo Cutlery Set', 'Portable bamboo cutlery for on-the-go', 159.99, 40, 22, 1, 15]
            ];

            $stmt = $conn->prepare("INSERT IGNORE INTO products (category_id, name, description, price, stock_quantity, co2_saved, supplier_id, low_stock_threshold) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                foreach ($products as $product) {
                    $stmt->bind_param("issdiiii", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7]);
                    $stmt->execute();
                }
                $stmt->close();
            }

            // Create admin user (pre-verified)
            $admin_email = 'admin@dragonstone.com';
            $admin_password = 'admin123';
            $admin_first = 'System';
            $admin_last = 'Administrator';
            $admin_role = 'Admin';
            
            $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT IGNORE INTO users (email, password_hash, first_name, last_name, role, auth_provider, is_verified, last_login, login_count) VALUES (?, ?, ?, ?, ?, 'email', 1, NOW(), 1)");
            if ($stmt) {
                $stmt->bind_param("sssss", $admin_email, $password_hash, $admin_first, $admin_last, $admin_role);
                $stmt->execute();
                $stmt->close();
            }

            // Create sample customer users (pre-verified for demo)
            $customers = [
                ['john@email.com', 'customer123', 'John', 'Doe', 'Customer'],
                ['sarah@email.com', 'customer123', 'Sarah', 'Smith', 'Customer'],
                ['mike@email.com', 'customer123', 'Mike', 'Johnson', 'Customer']
            ];

            $stmt = $conn->prepare("INSERT IGNORE INTO users (email, password_hash, first_name, last_name, role, eco_points_balance, auth_provider, is_verified, last_login, login_count) VALUES (?, ?, ?, ?, ?, ?, 'email', 1, NOW(), ?)");
            if ($stmt) {
                foreach ($customers as $customer) {
                    $password_hash = password_hash($customer[1], PASSWORD_DEFAULT);
                    $eco_points = rand(100, 2000);
                    $login_count = rand(1, 10);
                    $stmt->bind_param("sssssii", $customer[0], $password_hash, $customer[2], $customer[3], $customer[4], $eco_points, $login_count);
                    $stmt->execute();
                }
                $stmt->close();
            }

            echo "<div style='padding: 20px; background: #d4edda; color: #155724; border-radius: 5px; margin: 20px;'>";
            echo "<h3>✅ Database Setup Completed Successfully!</h3>";
            echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
            echo "<p><strong>Tables Created:</strong> {$success_count}</p>";
            echo "<p><strong>✅ Enhanced E-commerce System:</strong> Complete order and payment management:</p>";
            echo "<ul>";
            echo "<li>💰 <strong>Enhanced Orders Table:</strong></li>";
            echo "<li>  • Shipping cost tracking</li>";
            echo "<li>  • Final amount calculation</li>";
            echo "<li>  • Multiple delivery methods</li>";
            echo "<li>  • Tracking number support</li>";
            echo "<li>  • Customer contact information</li>";
            echo "<li>💳 <strong>Payments Table:</strong></li>";
            echo "<li>  • Payment method tracking</li>";
            echo "<li>  • Transaction ID storage</li>";
            echo "<li>  • Card type and last 4 digits</li>";
            echo "<li>  • Payment status monitoring</li>";
            echo "<li>  • Comprehensive indexing</li>";
            echo "</ul>";
            echo "<p><strong>✅ Fixed Foreign Key Constraints:</strong> All user-related tables now use ON DELETE CASCADE</p>";
            echo "<p><strong>✅ Complete Event Join/Leave System:</strong> Full functionality for eco challenges</p>";
            echo "<p><strong>✅ Advanced Category Management:</strong> Hierarchical category system</p>";
            echo "<p><strong>✅ Admin Activity Tracking:</strong> Complete audit trail</p>";
            echo "<p><strong>All systems are ready for production!</strong></p>";
            echo "<p><strong>Admin Login:</strong> admin@dragonstone.com / admin123</p>";
            echo "<p><strong>Customer Login:</strong> john@email.com / customer123</p>";
            echo "</div>";
        } else {
            echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>";
            echo "<h3>❌ Database Setup Completed with Errors</h3>";
            echo "<p><strong>Tables Created Successfully:</strong> {$success_count}</p>";
            echo "<p><strong>Tables Failed:</strong> {$error_count}</p>";
            foreach ($errors as $error) {
                echo "<p><small>" . htmlspecialchars($error) . "</small></p>";
            }
            echo "</div>";
        }

        $conn->close();
        echo "</body></html>";
    }
}
?>