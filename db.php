<?php
/**
 * DB.PHP — Головний диспетчер системи (Bootstrap)
 * Тут ініціалізуються всі налаштування безпеки та підключення.
 */

// ==========================================
// 0. БАЗОВІ НАЛАШТУВАННЯ СИСТЕМИ (Security & Time)
// ==========================================
date_default_timezone_set('Europe/Kyiv'); // Критично для збігу часу в логах і БД
ini_set('display_errors', 0);             // Вимикаємо вивід помилок на екран (захист від витоку шляхів)
ini_set('log_errors', 1);                 // Вмикаємо логування у файл
error_reporting(E_ALL);                   // Логуємо всі помилки для аудиту

// ==========================================
// 1. АВТОЗАВАНТАЖУВАЧ КЛАСІВ (Має бути першим!)
// ==========================================
spl_autoload_register(function ($class) {
    // Конвертуємо простір імен (наприклад Core\DB) у шлях до файлу (Core/DB.php)
    $path = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// ==========================================
// 2. ЗАВАНТАЖЕННЯ КОНФІГУРАЦІЇ ТА КОНСТАНТ
// ==========================================
\Core\Config::load();

// 🛡️ КОНФІГУРАЦІЯ БЕЗПЕКИ АВТОРИЗАЦІЇ ТА RATE LIMITING
define('AUTH_TRUST_PROXIES', false);  // true - ТІЛЬКИ якщо сервер за Nginx Proxy/Cloudflare
define('AUTH_MAX_ATTEMPTS_PHONE', 5); // Суворий ліміт спроб для одного номера
define('AUTH_MAX_ATTEMPTS_IP', 20);   // М'якший ліміт для IP-адреси (захист NAT-мереж)
define('AUTH_LOCKOUT_MINUTES', 15);   // Час блокування при переборі
define('AUTH_LOG_RETENTION_DAYS', 7); // Скільки днів зберігати аудит-логи

// ==========================================
// 3. БЕЗПЕЧНІ СЕСІЇ (Maximum Security)
// ==========================================
if (session_status() === PHP_SESSION_NONE) {

    $lifetime = 60 * 60 * 24 * 30; 
    ini_set('session.gc_maxlifetime', $lifetime); 
    ini_set('session.cookie_lifetime', $lifetime); 

    ini_set('session.cookie_httponly', 1);        // JS не має доступу до кук сесії
    ini_set('session.use_only_cookies', 1);       // Забороняє передавати ID сесії в URL
    ini_set('session.use_strict_mode', 1);        // Захист від фіксації сесії (Session Fixation)
    ini_set('session.cookie_samesite', 'Strict'); // Захист від міжсайтових запитів (CSRF)

    // Автоматично вмикаємо Secure, якщо сайт працює через HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// ==========================================
// 4. ЗАХИСТ ВІД CSRF
// ==========================================
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (\Exception $e) {
        // Fallback на випадок, якщо random_bytes недоступний у старих системах
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32)); 
    }
}

// ==========================================
// 5. ІНІЦІАЛІЗАЦІЯ БАЗИ ДАНИХ
// ==========================================
try {
    // Отримуємо об'єкт підключення (Автозавантажувач сам знайде клас Core\DB)
    $pdo = \Core\DB::connect();
    
    // Примусово ставимо кодування для уникнення "кракозябр" у старих версіях PHP/MySQL
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
} catch (\Exception $e) {
    // Записуємо реальну помилку в лог сервера (щоб хакери не бачили структуру БД)
    error_log("Критична помилка БД: " . $e->getMessage());
    
    // Віддаємо статус 503, щоб пошукові роботи не індексували цю сторінку як робочу
    http_response_code(503);
    die("На сайті ведуться технічні роботи. Спробуйте пізніше.");
}

// ==========================================
// 6. ПІДТРИМКА СТАРИХ ФУНКЦІЙ (Backward Compatibility)
// ==========================================
// Залишаємо для сумісності з модулями, де ще використовуються старі назви функцій
if (!function_exists('encryptData')) {
    function encryptData($data) {
        return class_exists('\Core\Security') ? \Core\Security::encrypt($data) : $data;
    }
}

if (!function_exists('decryptData')) {
    function decryptData($data) {
        return class_exists('\Core\Security') ? \Core\Security::decrypt($data) : $data;
    }
}
