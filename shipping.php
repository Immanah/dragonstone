<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Information - Dragonstone Eco Grocery</title>
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
            background: linear-gradient(rgba(42, 107, 42, 0.8), rgba(42, 107, 42, 0.9)), url('https://images.unsplash.com/photo-1605000797499-95a51c5269ae?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        
        .shipping-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .shipping-card:hover {
            transform: translateY(-5px);
        }
        
        .shipping-card .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 20px;
        }
        
        .eco-badge {
            background-color: var(--accent-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .shipping-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--accent-color);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .faq-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .faq-question {
            background-color: #f5f9f5;
            padding: 15px 20px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .faq-question:hover {
            background-color: #e8f5e8;
        }
        
        .faq-answer {
            padding: 20px;
            background-color: white;
            border-top: 1px solid #e0e0e0;
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
        
        .shipping-method {
            border-left: 4px solid var(--accent-color);
            padding-left: 15px;
            margin-bottom: 25px;
        }
        
        .carbon-neutral-badge {
            background-color: #2a6b2a;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 15px;
            font-size: 0.9rem;
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
                        <a class="nav-link active" href="shipping.php">Shipping</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Shipping Information</h1>
            <p class="lead mb-0">Eco-friendly shipping options with carbon-neutral delivery</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Shipping Overview -->
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="mb-4">Our Commitment to Sustainable Shipping</h2>
                <p class="lead">At Dragonstone, we're committed to reducing our environmental impact through eco-conscious shipping practices. All our packages are shipped using carbon-neutral methods with minimal packaging waste.</p>
                <div class="carbon-neutral-badge">
                    <i class="fas fa-leaf me-2"></i>All shipments are carbon neutral
                </div>
            </div>
        </div>

        <!-- Shipping Methods -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="text-center mb-4">Shipping Methods & Delivery Times</h3>
            </div>
            
            <div class="col-md-4">
                <div class="card shipping-card h-100">
                    <div class="card-header text-center">
                        <i class="fas fa-bicycle fa-lg me-2"></i>Eco Standard
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">5-7 Business Days</h5>
                        <p class="card-text">Our most sustainable option with minimal carbon footprint. Packages are delivered via ground transportation using eco-friendly vehicles.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Carbon neutral delivery</li>
                            <li><i class="fas fa-check text-success me-2"></i>Recyclable packaging</li>
                            <li><i class="fas fa-check text-success me-2"></i>Free for orders over $50</li>
                        </ul>
                        <p class="fw-bold">$4.99 or FREE over $50</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shipping-card h-100">
                    <div class="card-header text-center">
                        <i class="fas fa-truck fa-lg me-2"></i>Eco Express
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">2-3 Business Days</h5>
                        <p class="card-text">Faster delivery while maintaining our environmental commitments. Uses optimized routes to reduce emissions.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Carbon offset delivery</li>
                            <li><i class="fas fa-check text-success me-2"></i>Recyclable packaging</li>
                            <li><i class="fas fa-check text-success me-2"></i>Tracking included</li>
                        </ul>
                        <p class="fw-bold">$9.99 or $5.99 over $100</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shipping-card h-100">
                    <div class="card-header text-center">
                        <i class="fas fa-shipping-fast fa-lg me-2"></i>Next Day Green
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">1 Business Day</h5>
                        <p class="card-text">For when you need it fast. We offset 200% of carbon emissions for next-day deliveries.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>200% carbon offset</li>
                            <li><i class="fas fa-check text-success me-2"></i>Recyclable packaging</li>
                            <li><i class="fas fa-check text-success me-2"></i>Priority handling</li>
                        </ul>
                        <p class="fw-bold">$19.99</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Process -->
        <div class="row mb-5">
            <div class="col-lg-10 mx-auto">
                <div class="card shipping-card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Our Shipping Process</h4>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <h5>Order Processing</h5>
                                <p>We process orders within 24 hours. During peak seasons, please allow up to 48 hours.</p>
                            </div>
                            <div class="timeline-item">
                                <h5>Eco-Packaging</h5>
                                <p>Your items are carefully packaged using 100% recycled and biodegradable materials.</p>
                            </div>
                            <div class="timeline-item">
                                <h5>Carbon-Neutral Shipping</h5>
                                <p>We partner with shipping carriers that offset their carbon emissions through verified environmental projects.</p>
                            </div>
                            <div class="timeline-item">
                                <h5>Delivery</h5>
                                <p>Your sustainable products arrive at your doorstep with minimal environmental impact.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- International Shipping -->
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <div class="card shipping-card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">International Shipping</h4>
                    </div>
                    <div class="card-body">
                        <p>We ship to over 50 countries worldwide. International shipping times vary by destination:</p>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="shipping-method">
                                    <h6>Canada & Mexico</h6>
                                    <p>7-14 business days • $14.99</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="shipping-method">
                                    <h6>Europe</h6>
                                    <p>10-18 business days • $19.99</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="shipping-method">
                                    <h6>Asia & Australia</h6>
                                    <p>14-21 business days • $24.99</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="shipping-method">
                                    <h6>South America</h6>
                                    <p>12-20 business days • $22.99</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> International customers are responsible for any customs duties, taxes, or import fees that may apply.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="row mb-5">
            <div class="col-lg-10 mx-auto">
                <h3 class="text-center mb-4">Frequently Asked Questions</h3>
                
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq1">
                        <span>How do you achieve carbon-neutral shipping?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div id="faq1" class="collapse faq-answer">
                        We calculate the carbon emissions for each shipment and purchase verified carbon offsets through environmental projects like reforestation and renewable energy initiatives. This makes all our shipments carbon neutral.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq2">
                        <span>What is your packaging made from?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div id="faq2" class="collapse faq-answer">
                        Our packaging is 100% recycled and biodegradable. We use cardboard boxes from post-consumer waste, paper padding instead of plastic bubbles, and compostable mailers for smaller items.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq3">
                        <span>Do you offer free shipping?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div id="faq3" class="collapse faq-answer">
                        Yes! We offer free Eco Standard shipping on all orders over $50. For international orders, free shipping is available on orders over $100.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq4">
                        <span>Can I track my order?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div id="faq4" class="collapse faq-answer">
                        Yes, all orders come with tracking information. You'll receive a tracking number via email once your order ships. For Eco Standard shipping, tracking may be more limited than with our expedited options.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq5">
                        <span>What if I'm not home when my package arrives?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div id="faq5" class="collapse faq-answer">
                        Delivery procedures vary by carrier. Most will attempt delivery and then leave a notice with instructions for pickup. You can also provide special delivery instructions during checkout.
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <div class="card shipping-card">
                    <div class="card-body py-5">
                        <h3>Need Help With Shipping?</h3>
                        <p class="mb-4">Our customer service team is here to assist you with any shipping questions or concerns.</p>
                        <a href="contact.php" class="btn btn-success btn-lg">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </a>
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
        // FAQ toggle animation
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('fa-chevron-down')) {
                        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                    } else {
                        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                    }
                });
            });
        });
    </script>
</body>
</html>