<?php
/**
 * DB.PHP — Головний диспетчер системи (Bootstrap)
 * Тут ініціалізуються всі налаштування безпеки та підключення.
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

// ==========================================
// 0. БАЗОВІ НАЛАШТУВАННЯ СИСТЕМИ (Security & Time)
// ==========================================
date_default_timezone_set('Europe/Kyiv');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/logs/error.log');
error_reporting(E_ALL);

// 🛡️ БЕЗПЕКА: Обов'язковий HTTPS в production
if (PHP_SAPI !== 'cli' && !in_array($_SERVER['REQUEST_METHOD'] ?? '', ['OPTIONS'])) {
    if (getenv('ENVIRONMENT') === 'production') {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
            exit;
        }
    }
}

// 🛡️ БЕЗПЕКА: Content Security Policy
if (PHP_SAPI !== 'cli') {
    define('CSP_NONCE', bin2hex(random_bytes(16)));
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . CSP_NONCE . "' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; style-src 'self' 'nonce-" . CSP_NONCE . "' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self' https://api.qrserver.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ==========================================
// 1. АВТОЗАВАНТАЖУВАЧ КЛАСІВ
// ==========================================
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// ==========================================
// 2. ЗАВАНТАЖЕННЯ КОНФІГУРАЦІЇ
// ==========================================
if (class_exists('\\Core\\Config')) {
    \Core\Config::load();
}

// 🛡️ КОНФІГУРАЦІЯ БЕЗПЕКИ
define('AUTH_TRUST_PROXIES', getenv('AUTH_TRUST_PROXIES') === 'true');
define('AUTH_MAX_ATTEMPTS_PHONE', 5);
define('AUTH_MAX_ATTEMPTS_IP', 20);
define('AUTH_LOCKOUT_MINUTES', 15);
define('AUTH_LOG_RETENTION_DAYS', 7);
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_DEFAULT', 100);
define('RATE_LIMIT_WINDOW', 3600);

// ==========================================
// 3. БЕЗПЕЧНІ СЕСІЇ (Maximum Security)
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 60 * 60 * 24 * 30;
    ini_set('session.gc_maxlifetime', $lifetime);
    ini_set('session.cookie_lifetime', $lifetime);
    
    // 🛡️ МАКСИМАЛЬНА БЕЗПЕКА
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', 1); // ЗАВЖДИ у production
    
    session_start();
}

// ==========================================
// 4. ЗАХИСТ ВІД CSRF
// ==========================================
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (\Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// ==========================================
// 5. ІНІЦІАЛІЗАЦІЯ БАЗИ ДАНИХ
// ==========================================
try {
    $pdo = \Core\DB::connect();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Exception $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(503);
    die("На сайті ведуться технічні роботи. Спробуйте пізніше.");
}

// ==========================================
// 6. RATE LIMITING (Глобальне)
// ==========================================
if (RATE_LIMIT_ENABLED && php_sapi_name() !== 'cli') {
    $rate_limiter = new \Core\RateLimiter($_SESSION['user_id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? '');
    if (!$rate_limiter->isAllowed(RATE_LIMIT_DEFAULT, RATE_LIMIT_WINDOW)) {
        http_response_code(429);
        die(json_encode(['success' => false, 'msg' => 'Забагато запитів. Спробуйте пізніше.']));
    }
}
