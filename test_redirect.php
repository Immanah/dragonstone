<?php
// test_redirect.php
session_start();
$_SESSION['test_redirect'] = 'working';
header('Location: debug_register.php?redirect=success');
exit();
?>