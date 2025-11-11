<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Our Mission - DragonStone";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="mission-hero py-5">
    <div class="container">
        <div class="row align-items-center min-vh-80">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="mission-hero-title">Making Sustainable Living Accessible, Stylish, and Impactful</h1>
                <p class="mission-hero-subtitle">DragonStone is more than a store — it's a movement for conscious living.</p>
                <div class="mission-hero-image mt-5">
                    <img src="images/mission-hero.jpg" alt="Sustainable living with DragonStone products" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="mission-about py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title">About DragonStone</h2>
                    <div class="section-divider"></div>
                </div>
                <div class="about-content">
                    <p class="lead">DragonStone was founded by Aegon, Visenya, and Rhaenys with a shared vision: to make eco-friendly, sustainable home products accessible to urban households. They noticed that while many products claimed to be "green," most were either overpriced, ineffective, or part of a market saturated with greenwashing.</p>
                    <p>DragonStone bridges the gap by offering products that are functional, stylish, and genuinely sustainable. Our founders believe that everyone should have access to high-quality eco-friendly products that make a real difference for our planet.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<section class="mission-values py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">Our Core Values</h2>
            <p class="section-subtitle">Our mission is built on three pillars that guide every decision we make</p>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Transparency</h3>
                    <p>We provide full information about sourcing, production, and environmental impact. No hidden details, no greenwashing - just honest, open communication about our products and practices.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Education</h3>
                    <p>We aim to empower customers with knowledge about sustainable living. Through our blog, community forums, and product guides, we help you make informed environmental choices.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We foster a supportive network for sharing ideas, tips, and eco-friendly initiatives. Together, we're building a movement that extends far beyond individual purchases.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What We Offer Section -->
<section class="mission-offer py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="section-header mb-4">
                    <h2 class="section-title">What We Offer</h2>
                    <div class="section-divider"></div>
                </div>
                <p class="lead">DragonStone is committed to creating products that reduce environmental impact while enhancing everyday life. From reusable household goods and personal care items to eco-friendly wellness products, every item is carefully curated to meet our sustainability standards.</p>
                <p>Our product selection process involves rigorous testing for quality, sustainability, and real-world usability. We partner with ethical suppliers who share our commitment to environmental stewardship.</p>
            </div>
            <div class="col-lg-6">
                <div class="products-grid">
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-spray-can"></i>
                        </div>
                        <h4>Cleaning & Household</h4>
                        <ul>
                            <li>Compostable cleaning pods</li>
                            <li>Refillable glass bottles</li>
                            <li>Plant-based detergents</li>
                        </ul>
                    </div>
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h4>Kitchen Essentials</h4>
                        <ul>
                            <li>Bamboo kitchen utensils</li>
                            <li>Beeswax food wraps</li>
                            <li>Stainless steel containers</li>
                        </ul>
                    </div>
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h4>Home & Living</h4>
                        <ul>
                            <li>Organic cotton textiles</li>
                            <li>Soy wax candles</li>
                            <li>Recycled décor items</li>
                        </ul>
                    </div>
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4>Lifestyle Solutions</h4>
                        <ul>
                            <li>Solar-powered gadgets</li>
                            <li>Zero-waste kits</li>
                            <li>Eco-friendly personal care</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sustainability in Action Section -->
<section class="mission-sustainability py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title">Sustainability in Action</h2>
                    <div class="section-divider"></div>
                </div>
                <div class="sustainability-content text-center">
                    <p class="lead mb-4">Every decision at DragonStone prioritizes the planet. Our products are sourced ethically, packaged in compostable or recyclable materials, and designed for longevity.</p>
                    <div class="sustainability-features row g-4 mt-5">
                        <div class="col-md-4">
                            <div class="feature-item">
                                <div class="feature-number">01</div>
                                <h4>Ethical Sourcing</h4>
                                <p>We partner with suppliers who provide fair wages and safe working conditions</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-item">
                                <div class="feature-number">02</div>
                                <h4>Carbon Tracking</h4>
                                <p>We calculate the carbon footprint of each product and offer offset opportunities</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-item">
                                <div class="feature-number">03</div>
                                <h4>Circular Design</h4>
                                <p>Products are designed for repair, reuse, and eventual recycling</p>
                            </div>
                        </div>
                    </div>
                    <div class="ecopoints-highlight mt-5 p-4 rounded">
                        <h4>EcoPoints Program</h4>
                        <p>Through our EcoPoints program, customers can offset emissions and earn rewards for sustainable choices. Every purchase contributes to reforestation projects and environmental initiatives.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Join Movement Section -->
<section class="mission-join py-5 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <div class="section-header mb-5">
                    <h2 class="section-title text-white">Join the Movement</h2>
                    <div class="section-divider bg-white"></div>
                </div>
                <p class="lead mb-5">Our mission is not complete without our community. DragonStone encourages customers to share their sustainability journeys, participate in eco-challenges, and spread awareness. By shopping with us, you're contributing to a greener, more conscious world.</p>
                
                <div class="cta-buttons">
                    <div class="row g-3 justify-content-center">
                        <div class="col-lg-4 col-md-6">
                            <a href="products.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Shop Sustainable Products
                            </a>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <a href="eco-points.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-leaf me-2"></i>
                                Join EcoPoints Program
                            </a>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <a href="sustainability.php" class="btn btn-outline-light btn-lg w-100">
                                <i class="fas fa-book me-2"></i>
                                Learn About Sustainability
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="community-stats mt-5 pt-4 border-top">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stat">
                                <div class="stat-number">10,000+</div>
                                <div class="stat-label">Community Members</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat">
                                <div class="stat-number">50+</div>
                                <div class="stat-label">Eco Challenges</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat">
                                <div class="stat-number">200+</div>
                                <div class="stat-label">Sustainable Products</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat">
                                <div class="stat-number">15+</div>
                                <div class="stat-label">Partner Initiatives</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Mission Page Specific Styles */
:root {
    --color-sand-light: #f8f6f2;
    --color-sand-medium: #e8e0d4;
    --color-forest-dark: #2d4a2d;
    --color-forest-medium: #4a6b4a;
    --color-forest-light: #5a7a5a;
}

/* Hero Section */
.mission-hero {
    background: linear-gradient(135deg, var(--color-sand-light) 0%, var(--color-sand-medium) 100%);
}

.min-vh-80 {
    min-height: 80vh;
}

.mission-hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 1.5rem;
    line-height: 1.1;
}

.mission-hero-subtitle {
    font-size: 1.5rem;
    color: var(--color-forest-medium);
    margin-bottom: 2rem;
}

.mission-hero-image img {
    max-height: 400px;
    width: 100%;
    object-fit: cover;
}

/* Section Headers */
.section-header {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.25rem;
    color: var(--color-forest-medium);
    margin-bottom: 1.5rem;
}

.section-divider {
    width: 80px;
    height: 4px;
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    margin: 0 auto;
    border-radius: 2px;
}

.bg-light .section-divider {
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
}

/* Core Values */
.value-card {
    padding: 2.5rem 1.5rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(45, 74, 45, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(45, 74, 45, 0.15);
}

.value-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--color-forest-medium) 0%, var(--color-forest-light) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
    font-size: 2rem;
}

.value-card h3 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    font-weight: 600;
}

.value-card p {
    color: #666;
    line-height: 1.6;
}

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.product-category {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    border: 1px solid var(--color-sand-medium);
}

.category-icon {
    width: 50px;
    height: 50px;
    background: var(--color-sand-medium);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    color: var(--color-forest-medium);
    font-size: 1.25rem;
}

.product-category h4 {
    color: var(--color-forest-dark);
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.product-category ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.product-category li {
    padding: 0.25rem 0;
    color: #666;
    position: relative;
    padding-left: 1rem;
}

.product-category li:before {
    content: "•";
    color: var(--color-forest-light);
    position: absolute;
    left: 0;
}

/* Sustainability Features */
.feature-item {
    text-align: center;
    padding: 2rem 1rem;
}

.feature-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--color-sand-medium);
    margin-bottom: 1rem;
    line-height: 1;
}

.feature-item h4 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    font-weight: 600;
}

.feature-item p {
    color: #666;
    line-height: 1.6;
}

.ecopoints-highlight {
    background: linear-gradient(135deg, var(--color-sand-light) 0%, var(--color-sand-medium) 100%);
    border: 1px solid var(--color-sand-dark);
}

.ecopoints-highlight h4 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    font-weight: 600;
}

/* Join Movement Section */
.mission-join {
    background: linear-gradient(135deg, var(--color-forest-dark) 0%, var(--color-forest-medium) 100%);
}

.cta-buttons .btn {
    padding: 1rem 1.5rem;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.cta-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.community-stats .stat {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: rgba(255,255,255,0.8);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mission-hero-title {
        font-size: 2.5rem;
    }
    
    .mission-hero-subtitle {
        font-size: 1.25rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .value-card {
        padding: 2rem 1rem;
    }
    
    .cta-buttons .btn {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .mission-hero-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.75rem;
    }
    
    .community-stats .row {
        grid-template-columns: 1fr 1fr;
    }
}

/* Typography */
.lead {
    font-size: 1.25rem;
    font-weight: 400;
    line-height: 1.6;
    color: #555;
}

.bg-light .lead {
    color: #555;
}

.mission-join .lead {
    color: rgba(255,255,255,0.9);
}

/* Utility Classes */
.bg-dark {
    background: var(--color-forest-dark) !important;
}

.text-white {
    color: white !important;
}

.border-top {
    border-top: 1px solid rgba(255,255,255,0.2) !important;
}

.rounded-3 {
    border-radius: 1rem !important;
}

.shadow {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
}
</style>

<?php 
include 'includes/footer.php'; 
?>