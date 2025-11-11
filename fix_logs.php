<?php
// fix_logs.php - Create missing log directory
$log_dir = "C:\\xampp2\\php\\logs\\";
$log_file = $log_dir . "mail.log";

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
    echo "<p>✅ Created directory: $log_dir</p>";
}

if (!file_exists($log_file)) {
    file_put_contents($log_file, "DragonStone Mail Log - Created: " . date('Y-m-d H:i:s') . "\n");
    echo "<p>✅ Created file: $log_file</p>";
}

echo "<p>Log system is now ready!</p>";
echo "<p><a href='test_email.php'>Test Email Again</a></p>";
?>