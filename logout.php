<?php
/**
 * logout.php - Вихід користувача
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

require_once 'db.php';

if (\Core\Auth::loggedIn()) {
    $user_id = \Core\Auth::userId();
    error_log("User {$user_id} logged out at " . date('Y-m-d H:i:s') . " from IP " . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    
    \Core\Auth::logout();
}

// 🔥 Redirect to login with message
$_SESSION['toast_msg'] = '✅ Ви вийшли з системи. До побачення!';
header("Location: login.php");
exit;
