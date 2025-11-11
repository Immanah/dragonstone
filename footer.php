<footer class="footer mt-5 py-4" style="background: var(--primary-color);">
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
                    <li class="mb-2"><a href="mission.php" class="text-light text-decoration-none">Our Mission</a></li>
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
                    &copy; <?php echo date('Y'); ?> Dragonstone Eco Grocery. All rights reserved.
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

<!-- Custom JavaScript - FIXED VERSION -->
<script>
// Newsletter form submission - FIXED: Only targets specific newsletter forms
document.addEventListener('DOMContentLoaded', function() {
    // ONLY target forms with newsletter class or ID
    const newsletterForm = document.querySelector('form.newsletter-form, form#newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            if (email) {
                alert('Thank you for subscribing to our newsletter!');
                this.reset();
            }
        });
    }
    
    // Prevent checkout form from being hijacked
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Let the checkout form submit normally - no prevention
            console.log('Checkout form submitting normally...');
        });
    }
});

// Global error handler to prevent any JavaScript from breaking the page
window.addEventListener('error', function(e) {
    console.log('Global error handler caught:', e.message);
    return true; // Prevent error from breaking the page
});
</script>
</body>
</html>