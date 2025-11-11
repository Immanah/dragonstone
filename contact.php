<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Please enter your name';
    }
    
    if (empty($email)) {
        $errors[] = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Please enter a subject';
    }
    
    if (empty($message)) {
        $errors[] = 'Please enter your message';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Please provide more details in your message (minimum 10 characters)';
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        $conn = getDatabaseConnection();
        
        // Save to database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, category, message, priority, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())");
        $stmt->bind_param("ssssss", $name, $email, $subject, $category, $message, $priority);
        
        if ($stmt->execute()) {
            $success_message = "Thank you for your message! We'll get back to you within 24 hours.";
            
            // Clear form fields
            $name = $email = $subject = $message = '';
            $category = 'general';
            $priority = 'normal';
            
            // Send email notification (you would implement this based on your email setup)
            // sendContactEmail($name, $email, $subject, $category, $message, $priority);
        } else {
            $errors[] = "Sorry, there was an error sending your message. Please try again.";
        }
        
        $stmt->close();
        $conn->close();
    }
}

$page_title = "Contact Us - DragonStone";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Hero Section -->
    <div class="page-hero text-center mb-5">
        <h1 class="page-title">Contact Us</h1>
        <p class="page-subtitle">We're here to help! Get in touch with our friendly support team</p>
    </div>

    <div class="row">
        <!-- Contact Information -->
        <div class="col-lg-4 mb-4">
            <div class="contact-info-card">
                <div class="card-header">
                    <h3>Get in Touch</h3>
                    <p>Choose the best way to reach us</p>
                </div>
                
                <div class="contact-methods">
                    <div class="contact-method">
                        <div class="method-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div class="method-info">
                            <h4>Visit Us</h4>
                            <p>123 Eco Street<br>Green Point<br>Cape Town, 8005</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <div class="method-info">
                            <h4>Call Us</h4>
                            <p>+27 21 123 4567<br>Mon-Fri: 8AM-6PM<br>Sat: 9AM-2PM</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="method-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div class="method-info">
                            <h4>Email Us</h4>
                            <p>support@dragonstone.com<br>response within 24 hours<br>24/7 support for urgent issues</p>
                        </div>
                    </div>
                </div>
                
                <div class="emergency-contact">
                    <div class="emergency-alert">
                        <div class="alert-icon">🚨</div>
                        <div class="alert-content">
                            <h5>Urgent Order Issues</h5>
                            <p>For urgent order problems, call us directly for immediate assistance.</p>
                            <a href="tel:+27211234567" class="btn btn-sm btn-primary">Call Now</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Quick Links -->
            <div class="faq-quick-links mt-4">
                <h5>Quick Answers</h5>
                <div class="quick-links">
                    <a href="faq.php#ordering" class="quick-link">
                        <span class="link-icon">🛒</span>
                        Order Questions
                    </a>
                    <a href="faq.php#shipping" class="quick-link">
                        <span class="link-icon">🚚</span>
                        Shipping Info
                    </a>
                    <a href="faq.php#returns" class="quick-link">
                        <span class="link-icon">↩️</span>
                        Returns & Refunds
                    </a>
                    <a href="faq.php#eco-points" class="quick-link">
                        <span class="link-icon">🌱</span>
                        EcoPoints Help
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-8">
            <div class="contact-form-card">
                <div class="card-header">
                    <h3>Send us a Message</h3>
                    <p>Fill out the form below and we'll get back to you as soon as possible</p>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6>Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="contactForm" class="contact-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                                           required>
                                    <div class="form-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                           required>
                                    <div class="form-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="general" <?php echo ($category ?? 'general') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="ordering" <?php echo ($category ?? '') === 'ordering' ? 'selected' : ''; ?>>Order Questions</option>
                                        <option value="shipping" <?php echo ($category ?? '') === 'shipping' ? 'selected' : ''; ?>>Shipping & Delivery</option>
                                        <option value="returns" <?php echo ($category ?? '') === 'returns' ? 'selected' : ''; ?>>Returns & Refunds</option>
                                        <option value="eco-points" <?php echo ($category ?? '') === 'eco-points' ? 'selected' : ''; ?>>EcoPoints</option>
                                        <option value="technical" <?php echo ($category ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Support</option>
                                        <option value="partnership" <?php echo ($category ?? '') === 'partnership' ? 'selected' : ''; ?>>Partnership</option>
                                        <option value="other" <?php echo ($category ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low" <?php echo ($priority ?? 'normal') === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="normal" <?php echo ($priority ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="high" <?php echo ($priority ?? 'normal') === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="urgent" <?php echo ($priority ?? 'normal') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                    </select>
                                    <small class="form-text">Select "Urgent" for time-sensitive order issues</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?php echo htmlspecialchars($subject ?? ''); ?>" 
                                   placeholder="Brief description of your inquiry" required>
                            <div class="form-feedback"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Please provide detailed information about your inquiry..." 
                                      required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            <div class="form-feedback"></div>
                            <div class="char-count">
                                <span id="charCount">0</span> characters (minimum 10)
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" value="1" checked>
                                <label class="form-check-label" for="newsletter">
                                    Subscribe to our newsletter for eco-tips and special offers
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="btn-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"></line>
                                        <polygon points="22,2 15,22 11,13 2,9 22,2"></polygon>
                                    </svg>
                                </span>
                                Send Message
                            </button>
                            <button type="reset" class="btn btn-outline">Clear Form</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Response Times Info -->
            <div class="response-times mt-4">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-icon">⚡</div>
                        <div class="info-content">
                            <h6>Urgent Issues</h6>
                            <p>Response within 2 hours</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📧</div>
                        <div class="info-content">
                            <h6>Email Support</h6>
                            <p>Response within 24 hours</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">🛒</div>
                        <div class="info-content">
                            <h6>Order Issues</h6>
                            <p>Priority handling</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">🌍</div>
                        <div class="info-content">
                            <h6>24/7 Chat</h6>
                            <p>Available for members</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Contact Page Styles */
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

/* Contact Info Card */
.contact-info-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.contact-info-card .card-header {
    padding: 2rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.contact-info-card .card-header h3 {
    margin: 0 0 0.5rem 0;
    color: var(--color-forest-dark);
    font-weight: 700;
}

.contact-info-card .card-header p {
    margin: 0;
    color: var(--color-text-light);
}

.contact-methods {
    padding: 1.5rem;
}

.contact-method {
    display: flex;
    align-items: flex-start;
    padding: 1.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    background: var(--color-white);
}

.contact-method:hover {
    border-color: var(--color-forest-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.contact-method:last-child {
    margin-bottom: 0;
}

.method-icon {
    width: 48px;
    height: 48px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.25rem;
    background: var(--color-sand-light);
    color: var(--color-forest-medium);
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.contact-method:hover .method-icon {
    background: var(--color-forest-light);
    color: var(--color-white);
    border-color: var(--color-forest-light);
}

.method-info h4 {
    margin: 0 0 0.5rem 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 1.125rem;
}

.method-info p {
    margin: 0;
    color: var(--color-text);
    line-height: 1.5;
}

/* Emergency Contact */
.emergency-contact {
    padding: 0 1.5rem 1.5rem;
}

.emergency-alert {
    display: flex;
    align-items: flex-start;
    padding: 1.5rem;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: var(--border-radius-sm);
}

.alert-icon {
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.alert-content h5 {
    margin: 0 0 0.5rem 0;
    color: #856404;
    font-weight: 600;
}

.alert-content p {
    margin: 0 0 1rem 0;
    color: #856404;
    font-size: 0.875rem;
}

/* FAQ Quick Links */
.faq-quick-links {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    background: var(--color-white);
}

.faq-quick-links h5 {
    margin: 0 0 1rem 0;
    color: var(--color-forest-dark);
    font-weight: 600;
}

.quick-links {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-link {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.3s ease;
    background: var(--color-white);
}

.quick-link:hover {
    border-color: var(--color-forest-light);
    background: var(--color-sand-light);
    text-decoration: none;
    color: var(--color-text);
    transform: translateX(4px);
}

.link-icon {
    margin-right: 0.75rem;
    font-size: 1.125rem;
    width: 24px;
    text-align: center;
}

/* Contact Form Card */
.contact-form-card {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-white);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.contact-form-card .card-header {
    padding: 2rem;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-sand-light);
}

.contact-form-card .card-header h3 {
    margin: 0 0 0.5rem 0;
    color: var(--color-forest-dark);
    font-weight: 700;
}

.contact-form-card .card-header p {
    margin: 0;
    color: var(--color-text-light);
}

.contact-form-card .card-body {
    padding: 2rem;
}

/* Form Styles */
.contact-form {
    max-width: 100%;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--color-forest-dark);
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--color-white);
}

.form-control:focus {
    outline: none;
    border-color: var(--color-forest-medium);
    box-shadow: 0 0 0 3px rgba(58, 92, 58, 0.1);
}

.form-control:invalid:not(:focus) {
    border-color: #dc3545;
}

.form-feedback {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    color: #dc3545;
    min-height: 1.25rem;
}

.form-text {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin-top: 0.25rem;
}

.char-count {
    font-size: 0.875rem;
    color: var(--color-text-light);
    text-align: right;
    margin-top: 0.5rem;
}

.char-count.low {
    color: #dc3545;
}

.char-count.good {
    color: var(--color-forest-medium);
}

.form-check {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.form-check-input {
    margin-right: 0.75rem;
    margin-top: 0.25rem;
}

.form-check-label {
    color: var(--color-text);
    line-height: 1.4;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-border);
}

/* Response Times */
.response-times {
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    background: var(--color-white);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-sm);
    background: var(--color-sand-light);
}

.info-icon {
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.info-content h6 {
    margin: 0 0 0.25rem 0;
    color: var(--color-forest-dark);
    font-weight: 600;
    font-size: 0.875rem;
}

.info-content p {
    margin: 0;
    color: var(--color-text-light);
    font-size: 0.875rem;
}

/* Alert Styles */
.alert {
    border-radius: var(--border-radius);
    border: 1px solid;
    margin-bottom: 1.5rem;
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

.alert-danger ul {
    margin-bottom: 0;
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
    border: none;
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

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1rem;
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
    
    .contact-method {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .method-icon {
        margin-right: 0;
    }
    
    .emergency-alert {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .alert-icon {
        margin-right: 0;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .page-hero {
        padding: 2rem 1rem;
    }
    
    .contact-form-card .card-body {
        padding: 1.5rem;
    }
    
    .contact-methods {
        padding: 1rem;
    }
    
    .contact-method {
        padding: 1.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const charCount = document.getElementById('charCount');
    const messageInput = document.getElementById('message');
    
    // Character count for message
    messageInput.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count < 10) {
            charCount.parentElement.classList.add('low');
            charCount.parentElement.classList.remove('good');
        } else {
            charCount.parentElement.classList.remove('low');
            charCount.parentElement.classList.add('good');
        }
    });
    
    // Initialize character count
    charCount.textContent = messageInput.value.length;
    messageInput.dispatchEvent(new Event('input'));
    
    // Real-time validation
    const inputs = contactForm.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
    
    function validateField(field) {
        const feedback = field.parentElement.querySelector('.form-feedback');
        let isValid = true;
        let message = '';
        
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }
        
        if (field.id === 'message' && field.value.length < 10) {
            isValid = false;
            message = 'Message must be at least 10 characters long';
        }
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            message = 'This field is required';
        }
        
        if (!isValid) {
            field.classList.add('is-invalid');
            feedback.textContent = message;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            feedback.textContent = '';
        }
        
        return isValid;
    }
    
    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentElement.querySelector('.form-feedback');
        feedback.textContent = '';
    }
    
    // Form submission validation
    contactForm.addEventListener('submit', function(e) {
        let formIsValid = true;
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                formIsValid = false;
            }
        });
        
        if (!formIsValid) {
            e.preventDefault();
            
            // Scroll to first error
            const firstError = contactForm.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstError.focus();
            }
        }
    });
    
    // Priority level styling
    const prioritySelect = document.getElementById('priority');
    
    prioritySelect.addEventListener('change', function() {
        // Remove previous classes
        this.classList.remove('priority-low', 'priority-normal', 'priority-high', 'priority-urgent');
        
        // Add class based on selected value
        this.classList.add('priority-' + this.value);
    });
    
    // Initialize priority styling
    prioritySelect.dispatchEvent(new Event('change'));
});

// Add priority styling
const priorityStyles = document.createElement('style');
priorityStyles.textContent = `
    .priority-low {
        border-left: 4px solid #28a745;
    }
    
    .priority-normal {
        border-left: 4px solid #17a2b8;
    }
    
    .priority-high {
        border-left: 4px solid #ffc107;
    }
    
    .priority-urgent {
        border-left: 4px solid #dc3545;
        background-color: #fff5f5;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
    }
    
    .is-valid {
        border-color: #28a745 !important;
    }
`;
document.head.appendChild(priorityStyles);
</script>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>