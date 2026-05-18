<?php
/**
 * Контролер авторизації користувачів
 * Рівень безпеки: Enterprise (OWASP Compliant)
 * Захист: CSRF, Rate Limiting, Session Fixation, IPv6-subnetting, CPU DoS, Timing Attacks
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

require_once 'db.php';

if (!defined('AUTH_TRUST_PROXIES')) define('AUTH_TRUST_PROXIES', false);
if (!defined('AUTH_MAX_ATTEMPTS_PHONE')) define('AUTH_MAX_ATTEMPTS_PHONE', 5);
if (!defined('AUTH_MAX_ATTEMPTS_IP')) define('AUTH_MAX_ATTEMPTS_IP', 20);
if (!defined('AUTH_LOCKOUT_MINUTES')) define('AUTH_LOCKOUT_MINUTES', 15);
if (!defined('AUTH_LOG_RETENTION_DAYS')) define('AUTH_LOG_RETENTION_DAYS', 7);

$error = '';

if (\Core\Auth::loggedIn()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    /**
     * 1. ВИЗНАЧЕННЯ ТА НОРМАЛІЗАЦІЯ IP
     */
    $raw_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (AUTH_TRUST_PROXIES && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $raw_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    
    $packed_ip = inet_pton($raw_ip);
    if ($packed_ip !== false) {
        if (strlen($packed_ip) === 16) { 
            $packed_ip = substr($packed_ip, 0, 8) . str_repeat("\0", 8);
        }
        $ip_address = inet_ntop($packed_ip);
    } else {
        $ip_address = '0.0.0.0';
    }
    
    $now = date('Y-m-d H:i:s');
    $lockout_threshold = date('Y-m-d H:i:s', time() - (AUTH_LOCKOUT_MINUTES * 60));
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    /**
     * 2. ЗАХИСТ ВІД CSRF
     */
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        error_log("Security Alert: CSRF token mismatch from IP {$ip_address}.");
        $error = "Помилка безпеки: сесія застаріла. Оновіть сторінку та спробуйте ще раз.";
    } else {
        
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        /**
         * 3. ПОПЕРЕДНЯ ФІЛЬТРАЦІЯ
         */
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'failed', ?)", [$ip_address, substr($phone, 0, 15), $now]);
            $error = "Невірний номер або пароль";
            error_log("Auth Failed: Invalid phone length from IP {$ip_address}");
        } 
        elseif (strlen($password) > 72) {
            \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'failed', ?)", [$ip_address, $phone, $now]);
            $error = "Невірний номер або пароль";
            error_log("Auth Failed: Password payload too large from IP {$ip_address}");
        } 
        else {
            
            /**
             * 4. ПЕРЕВІРКА RATE LIMITS
             */
            $attempts_phone = (int)\Core\DB::fetchColumn(
                "SELECT COUNT(*) FROM login_attempts WHERE status = 'failed' AND phone = ? AND attempted_at >= ?",
                [$phone, $lockout_threshold]
            );

            $attempts_ip = (int)\Core\DB::fetchColumn(
                "SELECT COUNT(*) FROM login_attempts WHERE status = 'failed' AND ip_address = ? AND attempted_at >= ?",
                [$ip_address, $lockout_threshold]
            );

            if ($attempts_phone >= AUTH_MAX_ATTEMPTS_PHONE) {
                $error = "Забагато невдалих спроб для цього номера. Доступ заблоковано на " . AUTH_LOCKOUT_MINUTES . " хвилин.";
                error_log("Rate Limit: Phone {$phone} blocked at {$now}.");
            } elseif ($attempts_ip >= AUTH_MAX_ATTEMPTS_IP) {
                $error = "Забагато запитів з вашої мережі. Зачекайте кілька хвилин.";
                error_log("Rate Limit: IP/Subnet {$ip_address} blocked at {$now}.");
            } else {
                
                /**
                 * 5. АВТЕНТИФІКАЦІЯ ЮЗЕРА З ТАЙМІНГ-ЗАХИСТОМ
                 */
                $user = \Core\DB::fetch(
                    "SELECT id, ppo_id, status, role, full_name, password FROM users WHERE phone = ?",
                    [$phone]
                );

                $dummy_hash = '$2y$10$usesomesillystringfore2uDLvq1DpXxyCjJ8zOMwPqP9h.Yeq';
                $is_password_valid = false;
                
                if ($user) {
                    $is_password_valid = password_verify($password, $user['password']);
                } else {
                    password_verify($password, $dummy_hash);
                }

                if ($is_password_valid) {
                    
                    /**
                     * 6. ОБРОБКА СПЕЦИФІЧНИХ СТАТУСІВ
                     */
                    if (in_array($user['status'], ['pending', 'candidate'])) {
                        $error = "⏳ Ваша реєстрація ще перевіряється керівництвом ППО.";
                        \Core\DB::query("DELETE FROM login_attempts WHERE phone = ? AND status = 'failed'", [$phone]);
                        \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'restricted', ?)", [$ip_address, $phone, $now]);
                    } elseif ($user['status'] === 'banned') {
                        $error = "🚫 Ваш профіль заблоковано.";
                        \Core\DB::query("DELETE FROM login_attempts WHERE phone = ? AND status = 'failed'", [$phone]);
                        \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'restricted', ?)", [$ip_address, $phone, $now]);
                    } else {
                        
                        /**
                         * 7. УСПІШНА АВТОРИЗАЦІЯ
                         */
                        session_regenerate_id(true);
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['ppo_id']  = (int)$user['ppo_id'];
                        $_SESSION['status']  = $user['status'];
                        $_SESSION['role']    = $user['role'];
                        
                        $decrypted_name = class_exists('\Core\Security') ? \Core\Security::decrypt($user['full_name']) : $user['full_name'];
                        $_SESSION['full_name'] = $decrypted_name !== false ? $decrypted_name : 'Користувач';
                        
                        \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'success', ?)", [$ip_address, $phone, $now]);
                        error_log("Auth Success: User ID {$user['id']} logged in from IP {$ip_address} at {$now}.");
                        
                        header("Location: index.php");
                        exit;
                    }
                    
                } else {
                    /**
                     * 8. ПОМИЛКА АВТОРИЗАЦІЇ
                     */
                    \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'failed', ?)", [$ip_address, $phone, $now]);
                    $error = "Невірний номер або пароль";
                    error_log("Auth Failed: Phone {$phone} from IP {$ip_address} at {$now}");
                }
            }
        }
    }
}

/**
 * 9. GARBAGE COLLECTOR
 */
if (rand(1, 100) <= 2) {
    $cleanup_date = date('Y-m-d H:i:s', time() - (AUTH_LOG_RETENTION_DAYS * 86400));
    \Core\DB::query("DELETE FROM login_attempts WHERE attempted_at < ?", [$cleanup_date]);
}

$title = 'Вхід | Профспілка';
$header_title = 'Авторизація';
$view_path = 'login_view.php';
$is_auth_page = true;

require_once 'views/layout.php';
