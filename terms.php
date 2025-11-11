<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Dragonstone Eco Grocery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2a6b2a;
            --secondary-color: #4caf50;
            --accent-color: #8bc34a;
            --light-bg: #f9f9f9;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fdf8;
            color: #333;
            line-height: 1.6;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .hero-section {
            background: linear-gradient(rgba(42, 107, 42, 0.8), rgba(42, 107, 42, 0.9)), url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        
        .terms-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 40px;
            margin-bottom: 40px;
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        
        .terms-nav {
            position: sticky;
            top: 20px;
            background: #f5f9f5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .terms-nav .nav-link {
            color: var(--primary-color) !important;
            padding: 8px 0;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .terms-nav .nav-link:hover, .terms-nav .nav-link.active {
            color: var(--secondary-color) !important;
            border-left: 3px solid var(--accent-color);
            padding-left: 10px;
        }
        
        .highlight-box {
            background: #f0f7f0;
            border-left: 4px solid var(--accent-color);
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .eco-badge {
            background-color: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .footer a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .last-updated {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: inline-block;
        }
        
        .terms-list {
            padding-left: 20px;
        }
        
        .terms-list li {
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .terms-container {
                padding: 20px;
            }
            
            .hero-section {
                padding: 60px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="includes/dragosntore logo.jpg" alt="Dragonstone" style="width: 40px; height: 40px; border-radius: 8px; margin-right: 10px;">
                Dragonstone
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
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="terms.php">Terms</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Terms of Service</h1>
            <p class="lead mb-0">Understanding our agreement for a sustainable shopping experience</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <div class="last-updated">
            <i class="fas fa-calendar-alt me-2"></i>Last Updated: January 1, 2023
        </div>
        
        <div class="row">
            <!-- Terms Navigation -->
            <div class="col-lg-3">
                <div class="terms-nav">
                    <h5 class="mb-3">Quick Navigation</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#acceptance">1. Acceptance of Terms</a>
                        <a class="nav-link" href="#account">2. Account Registration</a>
                        <a class="nav-link" href="#products">3. Products & Pricing</a>
                        <a class="nav-link" href="#orders">4. Order Process</a>
                        <a class="nav-link" href="#shipping">5. Shipping & Delivery</a>
                        <a class="nav-link" href="#returns">6. Returns & Refunds</a>
                        <a class="nav-link" href="#sustainability">7. Sustainability Commitment</a>
                        <a class="nav-link" href="#intellectual">8. Intellectual Property</a>
                        <a class="nav-link" href="#liability">9. Limitation of Liability</a>
                        <a class="nav-link" href="#privacy">10. Privacy Policy</a>
                        <a class="nav-link" href="#changes">11. Changes to Terms</a>
                        <a class="nav-link" href="#contact">12. Contact Information</a>
                    </nav>
                </div>
            </div>
            
            <!-- Terms Content -->
            <div class="col-lg-9">
                <div class="terms-container">
                    <div class="intro-text mb-5">
                        <p>Welcome to Dragonstone Eco Grocery. These Terms of Service govern your use of our website and services. By accessing or using our website, you agree to be bound by these terms.</p>
                        
                        <div class="highlight-box">
                            <h5><i class="fas fa-leaf me-2"></i>Our Eco-Commitment</h5>
                            <p class="mb-0">At Dragonstone, we're not just a store - we're a movement toward sustainable living. Our terms reflect our commitment to ethical business practices and environmental responsibility.</p>
                        </div>
                    </div>
                    
                    <section id="acceptance">
                        <h3 class="section-title">1. Acceptance of Terms</h3>
                        <p>By accessing or using the Dragonstone website (the "Service"), you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>
                        
                        <p>The materials contained in this website are protected by applicable copyright and trademark law. These terms apply to all visitors, users, and others who access or use the Service.</p>
                    </section>
                    
                    <section id="account">
                        <h3 class="section-title">2. Account Registration</h3>
                        <p>To access certain features of the Service, you may be required to register for an account. When you register, you agree to:</p>
                        
                        <ul class="terms-list">
                            <li>Provide accurate, current, and complete information</li>
                            <li>Maintain and promptly update your account information</li>
                            <li>Maintain the security of your password and accept all risks of unauthorized access</li>
                            <li>Notify us immediately if you discover or suspect any security breaches related to the Service</li>
                            <li>Take responsibility for all activities that occur under your account</li>
                        </ul>
                        
                        <p>We reserve the right to disable any user account at our sole discretion, including if we believe you have violated these Terms of Service.</p>
                    </section>
                    
                    <section id="products">
                        <h3 class="section-title">3. Products & Pricing</h3>
                        <p>Dragonstone offers eco-friendly, sustainable, and organic products. We strive to ensure that all product information, including descriptions, images, and prices, is accurate. However, we do not warrant that product descriptions or other content is accurate, complete, reliable, current, or error-free.</p>
                        
                        <div class="highlight-box">
                            <h6><i class="fas fa-recycle me-2"></i>Pricing Transparency</h6>
                            <p class="mb-0">Our pricing includes the true cost of sustainable sourcing, fair labor practices, and carbon-neutral shipping. We're committed to transparency in our pricing model.</p>
                        </div>
                        
                        <p>All prices are shown in US Dollars and are subject to change without notice. We reserve the right to discontinue any product at any time.</p>
                    </section>
                    
                    <section id="orders">
                        <h3 class="section-title">4. Order Process</h3>
                        <p>When you place an order through our Service, you are making an offer to purchase the products in your cart. We will send you an email confirming receipt of your order.</p>
                        
                        <p>Acceptance of your order and the formation of the contract of sale between Dragonstone and you will not take place unless and until you have received your order confirmation email. We reserve the right to refuse or cancel any order for any reason at any time.</p>
                        
                        <p>Some situations that may result in your order being canceled include limitations on quantities available for purchase, inaccuracies or errors in product or pricing information, or problems identified by our credit and fraud avoidance department.</p>
                    </section>
                    
                    <section id="shipping">
                        <h3 class="section-title">5. Shipping & Delivery</h3>
                        <p>We are committed to carbon-neutral shipping. All our packages are shipped using environmentally responsible methods and materials. Please see our <a href="shipping.php">Shipping Information</a> page for detailed information about our shipping options, delivery times, and carbon offset program.</p>
                        
                        <p>Shipping times are estimates and not guarantees. We are not responsible for delays caused by weather, carrier issues, or other circumstances beyond our control.</p>
                        
                        <p>Risk of loss and title for items purchased from Dragonstone pass to you upon delivery of the items to the carrier.</p>
                    </section>
                    
                    <section id="returns">
                        <h3 class="section-title">6. Returns & Refunds</h3>
                        <p>We want you to be completely satisfied with your purchase. If you're not happy with a product, you may return it within 30 days of delivery for a full refund or exchange.</p>
                        
                        <ul class="terms-list">
                            <li>Items must be returned in their original condition and packaging</li>
                            <li>Return shipping costs are the responsibility of the customer, unless the return is due to our error</li>
                            <li>Perishable goods and personalized items cannot be returned</li>
                            <li>Refunds will be processed within 7-10 business days after we receive your return</li>
                        </ul>
                        
                        <p>For detailed instructions, please visit our <a href="returns.php">Returns & Refunds</a> page.</p>
                    </section>
                    
                    <section id="sustainability">
                        <h3 class="section-title">7. Sustainability Commitment</h3>
                        <p>As part of our mission to promote sustainable living, we are committed to:</p>
                        
                        <ul class="terms-list">
                            <li>Sourcing products from ethical and environmentally responsible suppliers</li>
                            <li>Using 100% recycled and biodegradable packaging materials</li>
                            <li>Offsetting 100% of carbon emissions from our operations and shipping</li>
                            <li>Donating 1% of all profits to environmental conservation organizations</li>
                            <li>Providing transparency about our supply chain and environmental impact</li>
                        </ul>
                        
                        <p>By purchasing from Dragonstone, you are supporting these sustainable practices and contributing to a healthier planet.</p>
                        
                        <div class="eco-badge">Carbon Neutral</div>
                        <div class="eco-badge">Plastic Free</div>
                        <div class="eco-badge">Ethically Sourced</div>
                    </section>
                    
                    <section id="intellectual">
                        <h3 class="section-title">8. Intellectual Property</h3>
                        <p>The Service and its original content, features, and functionality are owned by Dragonstone and are protected by international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>
                        
                        <p>Our trademarks and trade dress may not be used in connection with any product or service without the prior written consent of Dragonstone.</p>
                    </section>
                    
                    <section id="liability">
                        <h3 class="section-title">9. Limitation of Liability</h3>
                        <p>To the fullest extent permitted by applicable law, in no event shall Dragonstone, its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
                        
                        <ul class="terms-list">
                            <li>Your access to or use of or inability to access or use the Service</li>
                            <li>Any conduct or content of any third party on the Service</li>
                            <li>Any content obtained from the Service</li>
                            <li>Unauthorized access, use or alteration of your transmissions or content</li>
                        </ul>
                    </section>
                    
                    <section id="privacy">
                        <h3 class="section-title">10. Privacy Policy</h3>
                        <p>Your privacy is important to us. Please read our <a href="privacy.php">Privacy Policy</a> carefully as it describes how we collect, use, and protect your personal information.</p>
                        
                        <p>Our Privacy Policy is designed to help you understand how we collect and use the personal information you provide to us and to help you make informed decisions when using our Service.</p>
                    </section>
                    
                    <section id="changes">
                        <h3 class="section-title">11. Changes to Terms</h3>
                        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days' notice prior to any new terms taking effect.</p>
                        
                        <p>What constitutes a material change will be determined at our sole discretion. By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms.</p>
                    </section>
                    
                    <section id="contact">
                        <h3 class="section-title">12. Contact Information</h3>
                        <p>If you have any questions about these Terms of Service, please contact us:</p>
                        
                        <ul class="terms-list">
                            <li>By email: legal@dragonstone-eco.com</li>
                            <li>By phone: 1-800-DRAGONSTONE (1-800-372-4667)</li>
                            <li>By mail: Dragonstone Eco Grocery, 123 Sustainability Way, Green City, EC 12345</li>
                            <li>Through our website: <a href="contact.php">Contact Form</a></li>
                        </ul>
                    </section>
                    
                    <div class="mt-5 p-4 text-center" style="background: #f0f7f0; border-radius: 8px;">
                        <h5>Thank you for choosing Dragonstone</h5>
                        <p class="mb-0">Together, we're building a more sustainable future, one purchase at a time.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-white mb-3">
                        <img src="includes/dragosntore logo.jpg" alt="Dragonstone" style="width: 40px; height: 40px; border-radius: 8px; margin-right: 10px;">
                        Dragonstone
                    </h5>
                    <p class="text-light">Eco-friendly products for a sustainable future. Join us in making the world greener, one purchase at a time.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-white mb-3">Shop</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="products.php" class="text-light text-decoration-none">All Products</a></li>
                        <li class="mb-2"><a href="eco-points.php" class="text-light text-decoration-none">Eco-Points</a></li>
                        <li class="mb-2"><a href="wishlist.php" class="text-light text-decoration-none">My Wishlist</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-white mb-3">Help</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact Us</a></li>
                        <li class="mb-2"><a href="faq.php" class="text-light text-decoration-none">FAQ</a></li>
                        <li class="mb-2"><a href="shipping.php" class="text-light text-decoration-none">Shipping Info</a></li>
                        <li class="mb-2"><a href="returns.php" class="text-light text-decoration-none">Returns & Refunds</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-light text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="terms.php" class="text-light text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h6 class="text-white mb-3">Sustainability</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="about.php" class="text-light text-decoration-none">Our Mission</a></li>
                        <li class="mb-2"><a href="carbon-footprint.php" class="text-light text-decoration-none">Carbon Tracking</a></li>
                        <li class="mb-2"><a href="eco-points.php" class="text-light text-decoration-none">EcoPoints Program</a></li>
                        <li class="mb-2"><a href="community.php" class="text-light text-decoration-none">Community</a></li>
                        <li class="mb-2"><a href="subscriptions.php" class="text-light text-decoration-none">Subscriptions</a></li>
                        <li class="mb-2"><a href="blog.php" class="text-light text-decoration-none">Eco Blog</a></li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: var(--accent-color);">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light mb-0">
                        &copy; 2023 Dragonstone Eco Grocery. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-light">
                        <i class="fas fa-globe-americas me-1"></i> Proudly serving eco-conscious customers worldwide
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            
            anchorLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        // Update active nav link
                        document.querySelectorAll('.terms-nav .nav-link').forEach(navLink => {
                            navLink.classList.remove('active');
                        });
                        this.classList.add('active');
                        
                        // Scroll to target
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Update active nav link on scroll
            window.addEventListener('scroll', function() {
                const sections = document.querySelectorAll('section');
                const navLinks = document.querySelectorAll('.terms-nav .nav-link');
                
                let currentSection = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop - 150;
                    if (window.pageYOffset >= sectionTop) {
                        currentSection = section.getAttribute('id');
                    }
                });
                
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + currentSection) {
                        link.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>