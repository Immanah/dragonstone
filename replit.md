# Dragonstone Grocery Store

## Overview

Dragonstone is an eco-friendly grocery store management system developed for ITECA. The project is a fully functional PHP-based web application featuring product management, user authentication, shopping cart, checkout, eco-points rewards system, community forums, and subscription services. The application promotes sustainable living by tracking carbon footprint savings and rewarding environmentally conscious shopping behavior.

## Recent Changes (November 4, 2025)

### GitHub Import Setup
- Imported codebase from GitHub and configured for Replit environment
- Created missing `includes/` directory with all required configuration files
- Set up PostgreSQL database with complete schema and sample data
- Fixed all SQL queries for PostgreSQL compatibility (boolean vs integer comparisons)
- Removed dangerous DROP TABLE statements from checkout process
- Configured production-safe error handling throughout the application
- Set up PHP development server on port 5000 with webview output
- Configured deployment for production (VM target)

## User Preferences

- Preferred communication style: Simple, everyday language
- Tech stack: PHP, CSS, HTML, JavaScript
- Database: PostgreSQL (Replit provides this instead of MySQL)

## System Architecture

### Current State

**Frontend Architecture**
- HTML/CSS/JavaScript with Bootstrap 5.3.0 framework
- Font Awesome 6.4.0 for icons
- Google Fonts (Inter) for typography
- Responsive design for desktop and mobile
- Dynamic product filtering and sorting
- Real-time cart management

**Backend Architecture**
- PHP 8.2.23 backend
- RESTful page structure (products.php, cart.php, checkout.php, etc.)
- Session-based authentication and cart management
- PDO-based PostgreSQL database wrapper with mysqli compatibility layer
- Transactional checkout process with order management
- EcoPoints reward system integration

**Data Storage**
- PostgreSQL database (heliumdb on helium:5432)
- 11 tables: categories, products, users, orders, order_items, eco_point_transactions, payments, reviews, forum_posts, subscriptions, reward_redemptions
- Sample data includes 4 categories, 10 eco-friendly products, 1 test user

**Authentication & Authorization**
- Session-based authentication (PHP sessions)
- Role-based access: customer, admin
- Login/logout/register functionality
- Protected routes using requireLogin() middleware
- Google OAuth placeholder (not yet configured)

### File Structure

```
/
├── includes/
│   ├── config.php          # App configuration, session, timezone, error handling
│   ├── database.php        # PostgreSQL connection with mysqli compatibility
│   ├── auth.php            # Authentication functions
│   ├── header.php          # HTML header, navigation, Bootstrap CSS
│   └── footer.php          # HTML footer, Bootstrap JS
├── index.php               # Homepage with hero section
├── products.php            # Product catalog with category filtering
├── product-detail.php      # Individual product details
├── cart.php                # Shopping cart management
├── checkout.php            # Order processing and payment
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # User logout
├── profile.php             # User profile and account settings
├── orders.php              # Order history
├── community.php           # Community forum and discussions
├── eco-points.php          # EcoPoints rewards dashboard
├── carbon-tracking.php     # Carbon footprint tracking
├── subscriptions.php       # Product subscription management
├── challenges.php          # Sustainability challenges
├── settings.php            # User settings
└── order-details.php       # Individual order details
```

### Design Considerations

**Problem**: PostgreSQL database instead of MySQL (Replit standard)
**Solution**: Created mysqli compatibility wrapper using PDO to minimize code changes
**Rationale**: Allows existing mysqli-style code to work with PostgreSQL with minimal refactoring

**Problem**: Boolean vs integer comparisons in SQL queries
**Solution**: Changed all `is_active = 1` to `is_active = TRUE`
**Rationale**: PostgreSQL strictly types boolean columns and won't auto-convert integers

**Problem**: Checkout process was dropping and recreating tables on every order
**Solution**: Removed all DROP TABLE and CREATE TABLE statements, kept only INSERT statements
**Rationale**: Prevents data loss and allows multiple concurrent orders

**Problem**: Debug error messages exposed in production
**Solution**: Centralized error handling in config.php with display_errors disabled
**Rationale**: Prevents exposure of sensitive database credentials and stack traces to users

## External Dependencies

### Frontend Libraries
- Bootstrap 5.3.0 (CSS framework) - via CDN
- Font Awesome 6.4.0 (icon library) - via CDN
- Google Fonts Inter (typography) - via CDN

### Backend
- PHP 8.2.23 (runtime)
- PostgreSQL (Neon-backed database via Replit)
- PDO PostgreSQL driver

### Sample Data
- 4 categories: Organic Produce, Eco-Friendly Home, Personal Care, Pantry Essentials
- 10 products with images, prices, CO2 savings data
- 1 test user (email: test@example.com, password: password - bcrypt hashed)

## Known Issues

- Product images use Unsplash URLs which may 404 (cosmetic, not critical)
- Email confirmation system uses mail() function (needs SMTP configuration for production)
- Google OAuth integration not yet configured (requires GOOGLE_CLIENT_ID)

## Deployment

- Configured for VM deployment (always-on server)
- Production-ready with error logging to php-errors.log
- Port 5000 bound to 0.0.0.0 for Replit webview
- Database credentials loaded from environment variables

## Test Credentials

- Email: test@example.com
- Password: password
- Role: customer
- EcoPoints Balance: 100
