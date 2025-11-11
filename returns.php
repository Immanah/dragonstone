<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Refunds - Dragonstone Eco Grocery</title>
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
            background: linear-gradient(rgba(42, 107, 42, 0.8), rgba(42, 107, 42, 0.9)), url('https://images.unsplash.com/photo-1607082349566-187342175e2f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        
        .returns-container {
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
        
        .policy-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .policy-card:hover {
            transform: translateY(-5px);
        }
        
        .policy-card .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 20px;
        }
        
        .process-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fdf9;
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }
        
        .step-number {
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
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
        
        .policy-highlight {
            background: #f0f7f0;
            border-left: 4px solid var(--accent-color);
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
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
        
        .product-category {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .category-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 15px;
            width: 40px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .returns-container {
                padding: 20px;
            }
            
            .hero-section {
                padding: 60px 0;
            }
            
            .process-step {
                flex-direction: column;
            }
            
            .step-number {
                margin-bottom: 15px;
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
                        <a class="nav-link active" href="returns.php">Returns</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Returns & Refunds</h1>
            <p class="lead mb-0">Hassle-free returns with our eco-friendly process</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Policy Overview -->
        <div class="row mb-5">
            <div class="col-lg-10 mx-auto text-center">
                <h2 class="mb-4">Our Sustainable Returns Policy</h2>
                <p class="lead">We want you to be completely satisfied with your eco-friendly purchases. If you're not happy with a product, we offer a simple 30-day return policy with a focus on sustainability.</p>
                <div class="eco-badge">30-Day Returns</div>
                <div class="eco-badge">Eco-Friendly Process</div>
                <div class="eco-badge">Full Refunds</div>
            </div>
        </div>

        <!-- Return Process -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="returns-container">
                    <h3 class="section-title">How to Return an Item</h3>
                    <p>Returning an item is simple with our eco-friendly process. Follow these steps:</p>
                    
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div>
                            <h5>Initiate Your Return</h5>
                            <p>Log into your account and go to "Order History." Select the item you wish to return and provide a reason for the return. You'll receive a return authorization email with instructions.</p>
                            <button class="btn btn-success btn-sm">Start Return Process</button>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div>
                            <h5>Package Your Item</h5>
                            <p>Please use the original packaging if possible. If not, use any recyclable packaging materials. Include all original tags and documentation. We encourage reusing packaging to minimize waste.</p>
                            <div class="policy-highlight">
                                <i class="fas fa-recycle me-2"></i>
                                <strong>Eco-Tip:</strong> Reuse the packaging your order came in or use other recyclable materials you have at home.
                            </div>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div>
                            <h5>Ship Your Return</h5>
                            <p>Print the prepaid shipping label included in your return authorization email. Attach it to your package and drop it off at any designated shipping location. We've partnered with carbon-neutral shipping providers.</p>
                            <p><small><i class="fas fa-info-circle me-1"></i>Return shipping is free for defective items or our errors. For other returns, a shipping fee may apply.</small></p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div>
                            <h5>Receive Your Refund</h5>
                            <p>Once we receive and inspect your return, we'll process your refund within 3-5 business days. You'll receive an email confirmation when your refund is issued. Refunds are issued to your original payment method.</p>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <h6>Return Received</h6>
                                    <p class="mb-0">We process returns within 1-2 business days of receipt</p>
                                </div>
                                <div class="timeline-item">
                                    <h6>Quality Check</h6>
                                    <p class="mb-0">Items are inspected to ensure they meet return criteria</p>
                                </div>
                                <div class="timeline-item">
                                    <h6>Refund Issued</h6>
                                    <p class="mb-0">Refund processed to your original payment method</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Policies by Category -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="returns-container">
                    <h3 class="section-title">Return Policies by Product Category</h3>
                    <p>Different products have specific return considerations. Here's what you need to know:</p>
                    
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-apple-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Organic Foods & Perishables</h5>
                            <p class="mb-0">Due to health and safety regulations, we cannot accept returns of perishable food items unless they arrive damaged or spoiled. Please contact us within 24 hours of delivery for perishable issues.</p>
                        </div>
                    </div>
                    
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-pump-soap"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Eco-Friendly Cleaning Products</h5>
                            <p class="mb-0">Unopened cleaning products can be returned within 30 days. For safety reasons, we cannot accept returns of opened cleaning products.</p>
                        </div>
                    </div>
                    
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Sustainable Clothing & Textiles</h5>
                            <p class="mb-0">Clothing and textiles must be returned in original condition with tags attached. We accept returns within 30 days of delivery.</p>
                        </div>
                    </div>
                    
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Zero-Waste Kits & Subscriptions</h5>
                            <p class="mb-0">Subscription items can be returned within 30 days of receipt. Please note that personalized or customized subscription boxes may have different return policies.</p>
                        </div>
                    </div>
                    
                    <div class="product-category">
                        <div class="category-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Garden & Home Goods</h5>
                            <p class="mb-0">Garden products and home goods can be returned within 30 days if unused and in original packaging. Live plants have a 14-day return window.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refund Information -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card policy-card h-100">
                    <div class="card-header text-center">
                        <i class="fas fa-money-bill-wave me-2"></i>Refund Information
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">When Will I Get My Refund?</h5>
                        <p class="card-text">Refund processing times vary depending on your payment method:</p>
                        <ul>
                            <li><strong>Credit/Debit Cards:</strong> 3-5 business days after we process your return</li>
                            <li><strong>PayPal:</strong> 2-3 business days after we process your return</li>
                            <li><strong>Store Credit:</strong> Immediately available after return processing</li>
                        </ul>
                        <p class="small text-muted">The time it takes for the refund to appear in your account depends on your financial institution's processing time.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card policy-card h-100">
                    <div class="card-header text-center">
                        <i class="fas fa-exchange-alt me-2"></i>Exchange Policy
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Need a Different Size or Color?</h5>
                        <p class="card-text">We're happy to help with exchanges! Here's how it works:</p>
                        <ul>
                            <li>Initiate a return for the item you wish to exchange</li>
                            <li>Once we receive your return, we'll issue a store credit</li>
                            <li>Use your store credit to purchase the replacement item</li>
                            <li>Exchanges are subject to product availability</li>
                        </ul>
                        <p class="small text-muted">For faster service, you can place a new order for the desired item and return the original separately.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eco-Friendly Commitment -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="returns-container">
                    <h3 class="section-title">Our Eco-Friendly Returns Commitment</h3>
                    <p>At Dragonstone, we're committed to minimizing the environmental impact of returns. Here's how we're making returns more sustainable:</p>
                    
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="category-icon mb-3" style="font-size: 2.5rem;">
                                <i class="fas fa-recycle"></i>
                            </div>
                            <h5>Product Resale & Donation</h5>
                            <p>Returned items in good condition are either resold at a discount or donated to environmental organizations and community groups.</p>
                        </div>
                        
                        <div class="col-md-4 text-center mb-4">
                            <div class="category-icon mb-3" style="font-size: 2.5rem;">
                                <i class="fas fa-tree"></i>
                            </div>
                            <h5>Carbon-Neutral Shipping</h5>
                            <p>All return shipments are carbon-neutral. We partner with shipping carriers that offset their emissions through verified environmental projects.</p>
                        </div>
                        
                        <div class="col-md-4 text-center mb-4">
                            <div class="category-icon mb-3" style="font-size: 2.5rem;">
                                <i class="fas fa-box"></i>
                            </div>
                            <h5>Packaging Reuse</h5>
                            <p>We encourage reusing original packaging for returns. Returned packaging is either reused in our warehouse or properly recycled.</p>
                        </div>
                    </div>
                    
                    <div class="policy-highlight text-center">
                        <h5><i class="fas fa-leaf me-2"></i>Did You Know?</h5>
                        <p class="mb-0">For every return processed through our eco-friendly system, we plant a tree through our partnership with One Tree Planted. Since 2020, we've planted over 10,000 trees from returns!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="returns-container">
                    <h3 class="section-title">Frequently Asked Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq1">
                            <span>What if I received a damaged or defective item?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="faq1" class="collapse faq-answer">
                            <p>We're sorry to hear that! Please contact our customer service team within 7 days of delivery. We'll arrange for a replacement or refund and cover all shipping costs. You may be asked to provide photos of the damaged item.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq2">
                            <span>Do I need to include the original packaging?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="faq2" class="collapse faq-answer">
                            <p>We encourage using the original packaging if possible to minimize waste, but it's not required. Please use any recyclable packaging materials you have available. The most important thing is that the item is well-protected during shipping.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq3">
                            <span>Can I return a gift I received?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="faq3" class="collapse faq-answer">
                            <p>Yes! Gift returns are accepted within 30 days of purchase. You'll need the order number or gift receipt. The refund will be issued as store credit that can be used for any future purchase on our website.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq4">
                            <span>What happens to returned products?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="faq4" class="collapse faq-answer">
                            <p>We're committed to sustainable practices with all returned items:</p>
                            <ul>
                                <li><strong>Like-new items:</strong> Resold at a discount in our "Eco-Revived" section</li>
                                <li><strong>Opened but usable items:</strong> Donated to environmental organizations</li>
                                <li><strong>Damaged items:</strong> Recycled or repurposed whenever possible</li>
                                <li><strong>Perishable items:</strong> Composted or donated to local farms</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question" data-bs-toggle="collapse" data-bs-target="#faq5">
                            <span>How long does the return process take?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="faq5" class="collapse faq-answer">
                            <p>The complete return process typically takes 10-14 days from when you ship your return:</p>
                            <ul>
                                <li>Shipping time: 3-7 business days</li>
                                <li>Processing at our facility: 1-2 business days</li>
                                <li>Refund issuance: 3-5 business days</li>
                            </ul>
                            <p>You'll receive email updates at each stage of the process.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <div class="returns-container">
                    <div class="card-body py-5">
                        <h3>Need Help With a Return?</h3>
                        <p class="mb-4">Our customer service team is here to assist you with any return questions or issues.</p>
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                            <a href="contact.php" class="btn btn-success btn-lg">
                                <i class="fas fa-headset me-2"></i>Contact Support
                            </a>
                            <button class="btn btn-outline-success btn-lg">
                                <i class="fas fa-phone me-2"></i>1-800-DRAGONSTONE
                            </button>
                        </div>
                        <p class="mt-3 small text-muted">Monday-Friday 9am-6pm EST | Saturday 10am-4pm EST</p>
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
            
            // Start return process button
            const startReturnBtn = document.querySelector('.process-step .btn');
            if (startReturnBtn) {
                startReturnBtn.addEventListener('click', function() {
                    alert('Redirecting to return portal... In a live implementation, this would direct customers to their account page to start the return process.');
                });
            }
        });
    </script>
</body>
</html>