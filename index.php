<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include simple header without database calls
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">Eco-Friendly Products That Don't Compromise on Style</h1>
                <p class="hero-subtitle">Discover genuinely sustainable home goods that are good for your home and the planet.</p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="feature-badge">Carbon Neutral</span>
                    <span class="feature-badge">Plastic-Free</span>
                    <span class="feature-badge">Ethically Sourced</span>
                    <span class="feature-badge">Water Positive</span>
                </div>
                <div class="hero-actions">
                    <a href="products.php" class="btn btn-primary btn-large">Shop Sustainable Products</a>
                    <a href="community.php" class="btn btn-secondary btn-large">Join Community</a>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- Floating organic shapes -->
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="feature-grid">
                    <a href="carbon-tracking.php" class="feature-card clickable-feature">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <h6>Carbon Tracking</h6>
                        <small>See your environmental impact</small>
                        <div class="feature-arrow">→</div>
                    </a>
                    <a href="eco-points.php" class="feature-card clickable-feature">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                            </svg>
                        </div>
                        <h6>EcoPoints</h6>
                        <small>Earn rewards for being green</small>
                        <div class="feature-arrow">→</div>
                    </a>
                    <a href="community.php" class="feature-card clickable-feature">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h6>Community</h6>
                        <small>Share tips and ideas</small>
                        <div class="feature-arrow">→</div>
                    </a>
                    <a href="subscriptions.php" class="feature-card clickable-feature">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <h6>Subscriptions</h6>
                        <small>Auto-delivery of essentials</small>
                        <div class="feature-arrow">→</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Categories -->
<section class="categories-section py-5">
    <div class="container">
        <h2 class="section-title">Shop By Category</h2>
        <div class="categories-grid">
            <?php
            $categories = [
                ['Cleaning & Household', 'Eco-friendly cleaning supplies', 'clean'],
                ['Kitchen & Dining', 'Sustainable kitchen essentials', 'kitchen'],
                ['Home Décor & Living', 'Eco-conscious home decor', 'decor'],
                ['Bathroom & Personal Care', 'Natural personal care products', 'bathroom'],
                ['Lifestyle & Wellness', 'Sustainable lifestyle items', 'wellness'],
                ['Kids & Pets', 'Eco-friendly for family and pets', 'family'],
                ['Outdoor & Garden', 'Sustainable outdoor living', 'garden']
            ];
            
            foreach ($categories as $category) {
                echo "
                <div class='category-card'>
                    <div class='category-icon category-{$category[2]}'>
                        <!-- Icon will be added via CSS -->
                    </div>
                    <h3>{$category[0]}</h3>
                    <p>{$category[1]}</p>
                    <a href='products.php' class='btn btn-primary'>Shop Now</a>
                </div>";
            }
            ?>
        </div>
    </div>
</section>

<!-- Eco Impact Stats -->
<section class="stats-section py-5">
    <div class="container">
        <div class="stats-grid">
            <a href="carbon-tracking.php" class="stat-item clickable-stat">
                <h3 class="stat-number">2,847</h3>
                <p class="stat-label">KG of CO₂ Saved</p>
                <div class="stat-hover">View Your Impact →</div>
            </a>
            <div class="stat-item">
                <h3 class="stat-number">1,234</h3>
                <p class="stat-label">Plastic Items Prevented</p>
            </div>
            <div class="stat-item">
                <h3 class="stat-number">856</h3>
                <p class="stat-label">Happy Customers</p>
            </div>
            <a href="eco-points.php" class="stat-item clickable-stat">
                <h3 class="stat-number">5,672</h3>
                <p class="stat-label">EcoPoints Earned</p>
                <div class="stat-hover">View Rewards →</div>
            </a>
        </div>
    </div>
</section>

<!-- Community Section -->
<section class="community-section py-5">
    <div class="container">
        <div class="community-content">
            <div class="community-text">
                <h2 class="section-title">Join Our Sustainable Community</h2>
                <p class="community-description">Share tips, ask questions, and learn from fellow eco-conscious individuals in our growing community of like-minded environmental advocates.</p>
                <div class="community-actions">
                    <a href="community.php" class="btn btn-primary">Visit Forum</a>
                    <a href="challenges.php" class="btn btn-secondary">View Challenges</a>
                    <a href="carbon-tracking.php" class="btn btn-outline">Track Your Carbon</a>
                </div>
            </div>
            <div class="community-features">
                <div class="feature-list-card">
                    <h4>Why Choose DragonStone?</h4>
                    <ul class="feature-list">
                        <li class="feature-item">
                            <span class="check-icon"></span>
                            Every product vetted for sustainability
                        </li>
                        <li class="feature-item">
                            <span class="check-icon"></span>
                            <a href="carbon-tracking.php" class="feature-link">Carbon footprint tracking</a>
                        </li>
                        <li class="feature-item">
                            <span class="check-icon"></span>
                            <a href="eco-points.php" class="feature-link">Earn EcoPoints on every purchase</a>
                        </li>
                        <li class="feature-item">
                            <span class="check-icon"></span>
                            Plastic-free packaging
                        </li>
                        <li class="feature-item">
                            <span class="check-icon"></span>
                            Support local eco-initiatives
                        </li>
                        <li class="feature-item">
                            <span class="check-icon"></span>
                            30-day satisfaction guarantee
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Color Variables */
:root {
    /* Earth tone color palette */
    --color-sand-light: #f8f6f2;
    --color-sand-medium: #e8e0d4;
    --color-sand-dark: #d4c4a8;
    --color-sand-darker: #c2b299;
    
    /* Forest green palette */
    --color-forest-dark: #2d4a2d;
    --color-forest-medium: #4a6b4a;
    --color-forest-light: #5a7a5a;
    --color-forest-lighter: #6b8a6b;
    
    /* Neutral colors */
    --color-white: #ffffff;
    --color-text: #2d4a2d;
    --color-text-light: #5a7a5a;
    --color-border: #e8e0d4;
    
    /* Effects */
    --shadow-sm: 0 2px 8px rgba(45, 74, 45, 0.08);
    --shadow-md: 0 4px 16px rgba(45, 74, 45, 0.12);
    --shadow-lg: 0 8px 32px rgba(45, 74, 45, 0.15);
    --border-radius: 24px;
    --border-radius-lg: 40px;
}

/* Base Typography */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 16px;
    line-height: 1.6;
    color: var(--color-text);
    background: linear-gradient(135deg, var(--color-sand-light) 0%, var(--color-sand-medium) 100%);
}

/* Hero Section */
.hero-section {
    background: transparent;
    position: relative;
    overflow: hidden;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--color-forest-dark);
    margin-bottom: 1.5rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: var(--color-text-light);
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Feature Badges */
.feature-badge {
    background: var(--color-white);
    color: var(--color-forest-medium);
    padding: 0.75rem 1.25rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.9rem;
    border: 2px solid var(--color-border);
    backdrop-filter: blur(10px);
}

/* Buttons */
.btn {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    border-radius: 50px;
    padding: 1rem 2rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn-primary {
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    color: var(--color-white);
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    background: linear-gradient(135deg, var(--color-forest-dark) 0%, var(--color-forest-medium) 100%);
}

.btn-secondary {
    background: transparent;
    color: var(--color-forest-medium);
    border: 2px solid var(--color-forest-medium);
}

.btn-secondary:hover {
    background: var(--color-forest-medium);
    color: var(--color-white);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: var(--color-forest-medium);
    border: 2px solid var(--color-forest-medium);
}

.btn-outline:hover {
    background: var(--color-forest-medium);
    color: var(--color-white);
    transform: translateY(-2px);
}

.btn-large {
    font-size: 1.1rem;
    padding: 1.25rem 2.5rem;
}

/* Floating Shapes */
.floating-shapes {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    pointer-events: none;
    z-index: 0;
}

.shape {
    position: absolute;
    background: var(--color-forest-light);
    opacity: 0.08;
    border-radius: 60% 40% 30% 70%;
    animation: float 8s ease-in-out infinite;
}

.shape-1 {
    width: 120px;
    height: 120px;
    top: 10%;
    right: 10%;
    animation-delay: 0s;
}

.shape-2 {
    width: 80px;
    height: 80px;
    bottom: 20%;
    right: 20%;
    animation-delay: 2s;
}

.shape-3 {
    width: 100px;
    height: 100px;
    top: 40%;
    left: 10%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

/* Feature Grid */
.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.feature-card {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    text-align: center;
    border: 1px solid var(--color-border);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
    text-decoration: none;
    color: inherit;
    display: block;
}

.clickable-feature:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--color-forest-light);
}

.feature-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--color-sand-medium) 0%, var(--color-sand-dark) 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: var(--color-forest-medium);
}

.feature-card h6 {
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.feature-card small {
    color: var(--color-text-light);
    font-size: 0.875rem;
}

.feature-arrow {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: var(--color-forest-light);
    font-weight: 600;
    opacity: 0;
    transition: all 0.3s ease;
}

.clickable-feature:hover .feature-arrow {
    opacity: 1;
    transform: translateX(4px);
}

/* Sections */
.categories-section {
    background: var(--color-white);
}

.section-title {
    font-size: 2.625rem;
    font-weight: 700;
    text-align: center;
    color: var(--color-forest-dark);
    margin-bottom: 3rem;
}

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.category-card {
    background: var(--color-white);
    padding: 2.5rem 2rem;
    border-radius: var(--border-radius);
    text-align: center;
    border: 1px solid var(--color-border);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
}

.category-card:hover::before {
    transform: scaleX(1);
}

.category-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--color-sand-medium) 0%, var(--color-sand-dark) 100%);
    border-radius: 24px;
    margin: 0 auto 1.5rem;
    position: relative;
}

/* Category-specific icons using CSS */
.category-clean::before { content: '🧹'; font-size: 2rem; }
.category-kitchen::before { content: '🍳'; font-size: 2rem; }
.category-decor::before { content: '🏠'; font-size: 2rem; }
.category-bathroom::before { content: '🚿'; font-size: 2rem; }
.category-wellness::before { content: '🌿'; font-size: 2rem; }
.category-family::before { content: '👨‍👩‍👧‍👦'; font-size: 2rem; }
.category-garden::before { content: '🌳'; font-size: 2rem; }

.category-card h3 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    font-weight: 600;
}

.category-card p {
    color: var(--color-text-light);
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

/* Stats Section */
.stats-section {
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    color: var(--color-white);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.stat-item {
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    padding: 2rem 1rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    color: inherit;
}

.clickable-stat {
    cursor: pointer;
}

.clickable-stat:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-4px);
}

.stat-number {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-label {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.stat-hover {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255, 255, 255, 0.95);
    color: var(--color-forest-dark);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    opacity: 0;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.clickable-stat:hover .stat-hover {
    opacity: 1;
}

.clickable-stat:hover .stat-number,
.clickable-stat:hover .stat-label {
    opacity: 0;
}

/* Community Section */
.community-section {
    background: var(--color-sand-light);
}

.community-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.community-text {
    padding-right: 2rem;
}

.community-description {
    font-size: 1.125rem;
    color: var(--color-text-light);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.community-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.feature-list-card {
    background: var(--color-white);
    padding: 2.5rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--color-border);
    backdrop-filter: blur(10px);
}

.feature-list-card h4 {
    color: var(--color-forest-dark);
    margin-bottom: 1.5rem;
    font-weight: 600;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    color: var(--color-text);
}

.feature-link {
    color: var(--color-forest-medium);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.feature-link:hover {
    color: var(--color-forest-dark);
    text-decoration: underline;
}

.check-icon {
    width: 20px;
    height: 20px;
    background: var(--color-forest-medium);
    border-radius: 50%;
    margin-right: 1rem;
    margin-top: 0.25rem;
    flex-shrink: 0;
    position: relative;
}

.check-icon::after {
    content: '✓';
    color: var(--color-white);
    font-size: 0.75rem;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .community-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .community-text {
        padding-right: 0;
    }
    
    .community-actions {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-number {
        font-size: 2.5rem;
    }
    
    .stat-hover {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php 
// Include footer
include 'includes/footer.php'; 
?>