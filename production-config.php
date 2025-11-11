<?php
// production-config.php - Deployment Instructions for DragonStone
// LOCATION: Place this file in your main dragonstone folder

/*
=== PRODUCTION DEPLOYMENT CHECKLIST ===

BEFORE DEPLOYING TO PRODUCTION SERVER:

1. UPDATE config.php:
   - Change: define('IS_PRODUCTION', true);
   - Change: define('SITE_URL', 'https://yourdomain.com/');
   - Update database credentials for production server
   - Set: ini_set('display_errors', 0);

2. UPDATE SMTP SETTINGS in config.php:
   - SMTP_HOST = 'your-production-smtp.com'
   - SMTP_USER = 'noreply@yourdomain.com'
   - SMTP_PASS = 'your-production-smtp-password'

3. UPDATE php.ini ON PRODUCTION SERVER:
   - SMTP = your-production-smtp.com
   - smtp_port = 587 (or 465 for SSL)

4. UPDATE sendmail.ini ON PRODUCTION SERVER:
   - smtp_server = your-production-smtp.com
   - auth_username = your-production-email@yourdomain.com
   - auth_password = your-production-smtp-password

=== PRODUCTION SMTP EXAMPLES ===

// For cPanel hosting:
SMTP_HOST = mail.yourdomain.com

// For AWS SES:
SMTP_HOST = email-smtp.us-east-1.amazonaws.com

// For SendGrid:
SMTP_HOST = smtp.sendgrid.net

// For Mailgun:
SMTP_HOST = smtp.mailgun.org

=== SECURITY CHECKLIST ===
✅ Remove this file from production server
✅ Set display_errors = 0
✅ Use production database credentials
✅ Update all absolute URLs to https://yourdomain.com/
✅ Test email functionality on production
*/

echo "<h1>Production Configuration Guide</h1>";
echo "<p>This file should be deleted when moving to production.</p>";
echo "<p>Follow the instructions above to configure your production server.</p>";
?>