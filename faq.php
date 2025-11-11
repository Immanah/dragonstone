<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$page_title = "FAQ - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="page-hero text-center mb-5">
        <h1 class="page-title">Frequently Asked Questions</h1>
        <p class="page-subtitle">Find answers to common questions about our products, shipping, and sustainability</p>
        
        <!-- Search Bar -->
        <div class="search-section mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="search-box">
                        <div class="search-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                        <input type="text" id="faqSearch" placeholder="Search for answers..." class="search-input">
                        <button class="search-btn">Search</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="faq-sidebar">
                <div class="sidebar-header">
                    <h5>Categories</h5>
                </div>
                <nav class="faq-nav">
                    <a href="#general" class="nav-item active" data-category="general">
                        <span class="nav-icon">📦</span>
                        General
                    </a>
                    <a href="#ordering" class="nav-item" data-category="ordering">
                        <span class="nav-icon">🛒</span>
                        Ordering
                    </a>
                    <a href="#shipping" class="nav-item" data-category="shipping">
                        <span class="nav-icon">🚚</span>
                        Shipping
                    </a>
                    <a href="#eco-points" class="nav-item" data-category="eco-points">
                        <span class="nav-icon">🌱</span>
                        EcoPoints
                    </a>
                    <a href="#sustainability" class="nav-item" data-category="sustainability">
                        <span class="nav-icon">♻️</span>
                        Sustainability
                    </a>
                    <a href="#returns" class="nav-item" data-category="returns">
                        <span class="nav-icon">↩️</span>
                        Returns
                    </a>
                </nav>
                
                <div class="sidebar-help">
                    <div class="help-card">
                        <h6>Still need help?</h6>
                        <p>Can't find what you're looking for?</p>
                        <a href="contact.php" class="btn btn-primary btn-sm">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Content -->
        <div class="col-md-9">
            <div class="faq-content">
                <!-- General Questions -->
                <section id="general" class="faq-section active">
                    <div class="section-header">
                        <h2 class="section-title">General Questions</h2>
                        <p class="section-subtitle">Basic information about DragonStone and our mission</p>
                    </div>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What is DragonStone?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>DragonStone is an eco-friendly e-commerce platform dedicated to providing sustainable products that help reduce environmental impact. We offer a wide range of environmentally conscious products while promoting sustainable living through our EcoPoints rewards system.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Where are you located?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We are based in South Africa and ship nationwide. Our headquarters are in Cape Town, but we work with sustainable suppliers from across the country to bring you the best eco-friendly products.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Are all your products eco-friendly?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Yes! Every product in our store is carefully vetted for its environmental impact. We prioritize products that are:</p>
                                <ul>
                                    <li>Made from sustainable or recycled materials</li>
                                    <li>Produced using eco-friendly manufacturing processes</li>
                                    <li>Biodegradable or easily recyclable</li>
                                    <li>From companies with strong environmental policies</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>How do I create an account?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Creating an account is easy! Click on the "Sign Up" button in the top navigation, fill in your details, and you'll be ready to start shopping. With an account, you can track orders, earn EcoPoints, and save your favorite products.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Ordering Questions -->
                <section id="ordering" class="faq-section">
                    <div class="section-header">
                        <h2 class="section-title">Ordering & Payment</h2>
                        <p class="section-subtitle">Questions about placing orders and payment methods</p>
                    </div>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What payment methods do you accept?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We accept the following payment methods:</p>
                                <ul>
                                    <li>Credit/Debit Cards (Visa, MasterCard, American Express)</li>
                                    <li>PayFast</li>
                                    <li>SnapScan</li>
                                    <li>EFT/Bank Transfer</li>
                                    <li>EcoPoints redemption</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Can I modify or cancel my order?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>You can modify or cancel your order within 1 hour of placing it. After this time, your order enters our processing system and cannot be changed. If you need to make changes after this period, please contact our support team immediately.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Do you offer bulk discounts?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Yes! We offer special pricing for bulk orders and businesses. For orders over R5,000, you'll automatically receive a 10% discount. For larger quantities or corporate orders, please contact our sales team for custom pricing.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Is my payment information secure?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Absolutely. We use industry-standard SSL encryption to protect your payment information. We never store your complete credit card details on our servers. All payments are processed through secure, PCI-compliant payment gateways.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Shipping Questions -->
                <section id="shipping" class="faq-section">
                    <div class="section-header">
                        <h2 class="section-title">Shipping & Delivery</h2>
                        <p class="section-subtitle">Information about shipping times and delivery options</p>
                    </div>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What are your shipping options?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We offer several shipping options:</p>
                                <ul>
                                    <li><strong>Standard Shipping:</strong> 3-5 business days - R75</li>
                                    <li><strong>Express Shipping:</strong> 1-2 business days - R120</li>
                                    <li><strong>Free Shipping:</strong> Orders over R500 qualify for free standard shipping</li>
                                    <li><strong>EcoPoints Free Shipping:</strong> Redeem 150 EcoPoints for free shipping</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Do you ship internationally?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Currently, we only ship within South Africa. We're working on expanding our international shipping options while maintaining our carbon-neutral commitment. Sign up for our newsletter to be notified when international shipping becomes available.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>How do you handle packaging?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We're committed to sustainable packaging:</p>
                                <ul>
                                    <li>All boxes are made from recycled cardboard</li>
                                    <li>We use biodegradable packing peanuts</li>
                                    <li>No plastic bubble wrap - we use paper-based alternatives</li>
                                    <li>All packaging is fully recyclable or compostable</li>
                                    <li>We minimize packaging size to reduce waste</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Can I track my order?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Yes! Once your order ships, you'll receive a tracking number via email. You can also track your order by logging into your account and visiting the "My Orders" section. Tracking updates are provided in real-time.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- EcoPoints Questions -->
                <section id="eco-points" class="faq-section">
                    <div class="section-header">
                        <h2 class="section-title">EcoPoints Rewards</h2>
                        <p class="section-subtitle">Learn about earning and redeeming EcoPoints</p>
                    </div>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What are EcoPoints?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>EcoPoints are our loyalty rewards that you earn for making sustainable choices. You can redeem EcoPoints for discounts, free products, and even environmental contributions like tree planting or carbon offsetting.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>How do I earn EcoPoints?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>You can earn EcoPoints through various actions:</p>
                                <ul>
                                    <li>+50 points for every order placed</li>
                                    <li>+10 points for each eco-friendly product purchased</li>
                                    <li>+15 points for writing product reviews</li>
                                    <li>+100 points for referring friends</li>
                                    <li>+25 points for completing eco-challenges</li>
                                    <li>+5 points for sharing on social media</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What can I redeem EcoPoints for?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>EcoPoints can be redeemed for various rewards:</p>
                                <ul>
                                    <li>R20 Voucher - 200 points</li>
                                    <li>Plant a Tree - 350 points</li>
                                    <li>R50 Voucher - 450 points</li>
                                    <li>Eco Tote Bag - 300 points</li>
                                    <li>Carbon Offset - 600 points</li>
                                    <li>Free Shipping - 150 points</li>
                                    <li>Eco Seed Pack - 100 points</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Do EcoPoints expire?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>EcoPoints are valid for 12 months from the date they are earned. We'll send you reminders before your points expire so you don't miss out on redeeming them.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Sustainability Questions -->
                <section id="sustainability" class="faq-section">
                    <div class="section-header">
                        <h2 class="section-title">Sustainability</h2>
                        <p class="section-subtitle">Our commitment to environmental responsibility</p>
                    </div>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>How is DragonStone carbon neutral?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We achieve carbon neutrality through several initiatives:</p>
                                <ul>
                                    <li>Carbon offsetting for all shipping emissions</li>
                                    <li>Using renewable energy in our operations</li>
                                    <li>Partnering with carbon-neutral suppliers</li>
                                    <li>Investing in reforestation projects</li>
                                    <li>Optimizing delivery routes to reduce fuel consumption</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Do you work with local communities?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Yes! We're committed to supporting local communities:</p>
                                <ul>
                                    <li>We source 60% of our products from local South African artisans and producers</li>
                                    <li>We partner with local environmental organizations</li>
                                    <li>Our tree planting initiatives support local reforestation projects</li>
                                    <li>We create employment opportunities in underserved communities</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What is your environmental impact?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We're proud of our positive environmental impact:</p>
                                <ul>
                                    <li>Planted over 10,000 trees through customer redemptions</li>
                                    <li>Offset 50+ tons of carbon emissions</li>
                                    <li>Diverted 15+ tons of plastic from landfills</li>
                                    <li>Supported 50+ local eco-friendly businesses</li>
                                    <li>100% of our packaging is recyclable or compostable</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Returns Questions -->
                <section id="returns" class="faq-section">
                    <div class="section-header">
                        <h2 class="section-title">Returns & Refunds</h2>
                        <p class="section-subtitle">Our return policy and refund process</p>
                    </div>
                    
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>What is your return policy?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>We offer a 30-day return policy for most items. To be eligible for a return:</p>
                                <ul>
                                    <li>Item must be unused and in original packaging</li>
                                    <li>Return request must be made within 30 days of delivery</li>
                                    <li>Proof of purchase is required</li>
                                    <li>Some items (personal care, custom orders) are final sale</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>How do I return an item?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Follow these steps to return an item:</p>
                                <ol>
                                    <li>Log into your account and go to "My Orders"</li>
                                    <li>Select the order containing the item you want to return</li>
                                    <li>Click "Return Item" and select your reason</li>
                                    <li>Print the return label and packing slip</li>
                                    <li>Package the item securely and attach the return label</li>
                                    <li>Drop off at any PostNet location</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>How long do refunds take?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Once we receive your return, processing typically takes:</p>
                                <ul>
                                    <li>3-5 business days to inspect the returned item</li>
                                    <li>5-7 business days for the refund to process</li>
                                    <li>Refunds are issued to your original payment method</li>
                                    <li>You'll receive email confirmation at each step</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <h4>Do you offer exchanges?</h4>
                                <span class="faq-toggle">+</span>
                            </div>
                            <div class="faq-answer">
                                <p>Yes! We're happy to exchange items for a different size or color if available. Simply indicate your preference when initiating the return process. If the exchange item costs more, you'll pay the difference. If it costs less, we'll refund the difference.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Still Need Help Section -->
                <div class="still-need-help">
                    <div class="help-section">
                        <h3>Still have questions?</h3>
                        <p>Can't find the answer you're looking for? Please reach out to our friendly support team.</p>
                        <div class="help-actions">
                            <a href="contact.php" class="btn btn-primary">Contact Support</a>
                            <a href="tel:+27123456789" class="btn btn-outline">
                                <span class="btn-icon">📞</span>
                                Call Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* FAQ Page Styles */
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

.page-hero {
    padding: 3rem 0;
    background: linear-gradient(135deg, var(--color-sand-light) 0%, #ffffff 100%);
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    font-size: 1.25rem;
    color: var(--color-text-light);
    max-width: 600px;
    margin: 0 auto;
}

/* Search Section */
.search-section {
    max-width: 600px;
    margin: 0 auto;
}

.search-box {
    position: relative;
    display: flex;
    background: var(--color-white);
    border: 2px solid var(--color-border);
    border-radius: 50px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.search-box:focus-within {
    border-color: var(--color-forest-medium);
    box-shadow: 0 4px 12px rgba(58, 92, 58, 0.15);
}

.search-icon {
    padding: 1rem 1.25rem;
    color: var(--color-text-light);
    display: flex;
    align-items: center;
}

.search-input {
    flex: 1;
    border: none;
    padding: 1rem 0;
    font-size: 1rem;
    background: transparent;
    outline: none;
    color: var(--color-text);
}

.search-input::placeholder {
    color: var(--color-text-light);
}

.search-btn {
    padding: 1rem 1.5rem;
    background: var(--color-forest-medium);
    color: var(--color-white);
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: var(--color-forest-dark);
}

/* FAQ Sidebar */
.faq-sidebar {
    position: sticky;
    top: 2rem;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.sidebar-header h5 {
    margin: 0;
    color: var(--color-forest-dark);
    font-weight: 600;
}

.faq-nav {
    padding: 1rem 0;
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: var(--color-text);
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-item:hover,
.nav-item.active {
    background: var(--color-sand-light);
    color: var(--color-forest-dark);
    border-left-color: var(--color-forest-medium);
    text-decoration: none;
}

.nav-icon {
    margin-right: 0.75rem;
    font-size: 1.125rem;
    width: 24px;
    text-align: center;
}

.sidebar-help {
    margin-top: 1.5rem;
}

.help-card {
    padding: 1.5rem;
    background: var(--color-sand-light);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    text-align: center;
}

.help-card h6 {
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.help-card p {
    color: var(--color-text-light);
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

/* FAQ Content */
.faq-content {
    min-height: 600px;
}

.faq-section {
    display: none;
}

.faq-section.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-border);
}

.section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-forest-dark);
    margin-bottom: 0.5rem;
}

.section-subtitle {
    color: var(--color-text-light);
    font-size: 1.125rem;
    margin: 0;
}

/* FAQ Accordion */
.faq-accordion {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.faq-item {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    border-color: var(--color-forest-light);
    box-shadow: var(--shadow-sm);
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    cursor: pointer;
    background: var(--color-white);
    transition: all 0.3s ease;
}

.faq-question:hover {
    background: var(--color-sand-light);
}

.faq-question h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-forest-dark);
    flex: 1;
}

.faq-toggle {
    font-size: 1.5rem;
    font-weight: 300;
    color: var(--color-forest-medium);
    transition: transform 0.3s ease;
    margin-left: 1rem;
}

.faq-answer {
    padding: 0 1.5rem;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    border-top: 1px solid transparent;
}

.faq-item.active .faq-answer {
    padding: 0 1.5rem 1.5rem 1.5rem;
    max-height: 1000px;
    border-top-color: var(--color-border);
}

.faq-item.active .faq-toggle {
    transform: rotate(45deg);
}

.faq-answer p {
    margin-bottom: 1rem;
    color: var(--color-text);
    line-height: 1.6;
}

.faq-answer p:last-child {
    margin-bottom: 0;
}

.faq-answer ul,
.faq-answer ol {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.faq-answer li {
    margin-bottom: 0.5rem;
    line-height: 1.5;
    color: var(--color-text);
}

.faq-answer strong {
    color: var(--color-forest-dark);
}

/* Still Need Help Section */
.still-need-help {
    margin-top: 3rem;
    padding: 3rem 2rem;
    background: linear-gradient(135deg, var(--color-sand-light) 0%, #ffffff 100%);
    border-radius: var(--border-radius);
    text-align: center;
    border: 1px solid var(--color-border);
}

.help-section h3 {
    color: var(--color-forest-dark);
    margin-bottom: 1rem;
    font-weight: 700;
}

.help-section p {
    color: var(--color-text-light);
    font-size: 1.125rem;
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.help-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
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
    text-decoration: none;
    color: var(--color-white);
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
    text-decoration: none;
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
    .page-title {
        font-size: 2rem;
    }
    
    .page-subtitle {
        font-size: 1.125rem;
    }
    
    .search-box {
        flex-direction: column;
        border-radius: var(--border-radius);
    }
    
    .search-input {
        padding: 1rem 1.25rem;
    }
    
    .search-btn {
        border-radius: 0 0 var(--border-radius-sm) var(--border-radius-sm);
    }
    
    .faq-sidebar {
        position: static;
        margin-bottom: 2rem;
    }
    
    .faq-question {
        padding: 1.25rem;
    }
    
    .faq-question h4 {
        font-size: 1rem;
    }
    
    .help-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .help-actions .btn {
        width: 200px;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .page-hero {
        padding: 2rem 1rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .nav-item {
        padding: 0.875rem 1.25rem;
    }
    
    .faq-question {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .faq-toggle {
        align-self: flex-end;
        margin-left: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Accordion functionality
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        
        question.addEventListener('click', () => {
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });
    
    // Category navigation
    const navItems = document.querySelectorAll('.nav-item');
    const faqSections = document.querySelectorAll('.faq-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            
            const category = item.getAttribute('data-category');
            
            // Update active nav item
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');
            
            // Show corresponding section
            faqSections.forEach(section => {
                section.classList.remove('active');
                if (section.id === category) {
                    section.classList.add('active');
                }
            });
            
            // Scroll to section
            document.getElementById(category).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('faqSearch');
    const searchBtn = document.querySelector('.search-btn');
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        if (searchTerm.length < 2) {
            alert('Please enter at least 2 characters to search');
            return;
        }
        
        let foundMatch = false;
        
        // Search through all FAQ items
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question h4').textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
            
            if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                foundMatch = true;
                
                // Show the section containing this item
                const section = item.closest('.faq-section');
                const category = section.id;
                
                // Activate corresponding nav and section
                navItems.forEach(nav => nav.classList.remove('active'));
                document.querySelector(`[data-category="${category}"]`).classList.add('active');
                
                faqSections.forEach(sec => sec.classList.remove('active'));
                section.classList.add('active');
                
                // Expand the matching FAQ item
                faqItems.forEach(faq => faq.classList.remove('active'));
                item.classList.add('active');
                
                // Scroll to the item
                item.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                
                // Highlight search term
                highlightText(item, searchTerm);
            }
        });
        
        if (!foundMatch) {
            alert('No results found for "' + searchTerm + '". Please try different keywords.');
        }
    }
    
    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    function highlightText(element, searchTerm) {
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        const nodes = [];
        let node;
        
        while (node = walker.nextNode()) {
            nodes.push(node);
        }
        
        nodes.forEach(node => {
            const text = node.textContent;
            const regex = new RegExp(searchTerm, 'gi');
            const newText = text.replace(regex, match => `<mark class="search-highlight">${match}</mark>`);
            
            if (newText !== text) {
                const span = document.createElement('span');
                span.innerHTML = newText;
                node.parentNode.replaceChild(span, node);
            }
        });
    }
    
    // Remove highlight when searching again
    searchInput.addEventListener('input', () => {
        const highlights = document.querySelectorAll('.search-highlight');
        highlights.forEach(highlight => {
            const parent = highlight.parentNode;
            parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
            parent.normalize();
        });
    });
});

// Add highlight styles
const style = document.createElement('style');
style.textContent = `
    .search-highlight {
        background-color: #fff3cd;
        padding: 0.1rem 0.2rem;
        border-radius: 2px;
        font-weight: 600;
    }
`;
document.head.appendChild(style);
</script>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>