<?php
/**
 * Контролер Реєстрації
 * Рівень безпеки: Enterprise (Захист від Botnets, CPU DoS, FK Violations)
 */

require_once 'db.php';

if (\Core\Auth::loggedIn()) {
    header("Location: index.php");
    exit;
}

// 🛡️ КОНФІГУРАЦІЯ БЕЗПЕКИ РЕЄСТРАЦІЇ
if (!defined('AUTH_TRUST_PROXIES')) define('AUTH_TRUST_PROXIES', false);
define('MAX_REGISTRATIONS_PER_IP', 3); 

$error = '';
$success = '';

// Отримуємо список ППО
$ppos_raw = \Core\DB::fetchAll("SELECT id, name FROM ppos ORDER BY name ASC");

// 🛡️ ФІКС MVC: Типізуємо дані в Контролері, щоб Шаблон залишався чистим
$ppos = [];
foreach ($ppos_raw as $p) {
    $ppos[] = [
        'id'   => (int)$p['id'],
        'name' => (string)$p['name']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Визначення IP для Rate Limiting
    $raw_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (AUTH_TRUST_PROXIES && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $raw_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    
    $packed_ip = inet_pton($raw_ip);
    if ($packed_ip !== false && strlen($packed_ip) === 16) { 
        $packed_ip = substr($packed_ip, 0, 8) . str_repeat("\0", 8);
    }
    $ip_address = $packed_ip !== false ? inet_ntop($packed_ip) : '0.0.0.0';
    
    $now = date('Y-m-d H:i:s');
    $token = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'Помилка безпеки. Оновіть сторінку і спробуйте ще раз.';
        error_log("Reg Alert: CSRF token mismatch from IP {$ip_address}.");
    } else {
        
        $spam_check_time = date('Y-m-d H:i:s', time() - 86400); 
        $reg_attempts = (int)\Core\DB::fetchColumn("
            SELECT COUNT(*) FROM login_attempts 
            WHERE status = 'register_spam' AND ip_address = ? AND attempted_at >= ?
        ", [$ip_address, $spam_check_time]);
        
        if ($reg_attempts >= MAX_REGISTRATIONS_PER_IP) {
            $error = 'Ви перевищили ліміт створення акаунтів з вашої мережі. Спробуйте завтра.';
            error_log("Reg Block: IP {$ip_address} blocked for spamming registrations.");
        } else {
            
            $ppo_id           = (int)($_POST['ppo_id'] ?? 0);
            $full_name        = trim((string)($_POST['full_name'] ?? ''));
            $phone            = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? ''); 
            $password         = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? ''; // 🛡️ Нове поле

            if (empty($full_name) || empty($phone) || empty($password) || empty($password_confirm) || empty($ppo_id)) {
                $error = 'Заповніть усі обов\'язкові поля';
            } elseif ($password !== $password_confirm) {
                // 🛡️ ФІКС UX: Перевірка співпадіння паролів
                $error = 'Паролі не співпадають';
            } elseif (mb_strlen($full_name, 'UTF-8') > 100) {
                $error = 'Ім\'я занадто довге';
            } elseif (strlen($phone) < 10 || strlen($phone) > 15) {
                $error = 'Некоректний формат номера телефону';
            } elseif (strlen($password) < 6) {
                $error = 'Пароль має містити мінімум 6 символів';
            } elseif (strlen($password) > 72) {
                $error = 'Пароль занадто довгий';
                error_log("Reg Alert: Oversized password payload from IP {$ip_address}");
            } else {
                
                $ppo_exists = false;
                foreach ($ppos as $p) {
                    if ($p['id'] === $ppo_id) {
                        $ppo_exists = true; break;
                    }
                }
                
                if (!$ppo_exists) {
                    $error = 'Оберіть коректний осередок ППО зі списку';
                } else {
                    
                    $exists = \Core\DB::fetch("SELECT id FROM users WHERE phone = ?", [$phone]);
                    if ($exists) {
                        \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'register_spam', ?)", [$ip_address, $phone, $now]);
                        $error = 'Цей номер уже зареєстрований';
                    } else {
                        
                        $encrypted_name = class_exists('\Core\Security') ? \Core\Security::encrypt($full_name) : $full_name;
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        $userId = \Core\DB::insert('users', [
                            'ppo_id'    => $ppo_id,
                            'full_name' => $encrypted_name,
                            'phone'     => $phone,
                            'password'  => $hash,
                            'status'    => 'pending' 
                        ]);

                        if ($userId) {
                            $success = 'Реєстрація успішна! Очікуйте підтвердження Головою ППО для доступу до кабінету.';
                            \Core\DB::query("INSERT INTO login_attempts (ip_address, phone, status, attempted_at) VALUES (?, ?, 'register_spam', ?)", [$ip_address, $phone, $now]);
                            error_log("Reg Success: New user ID {$userId} registered from IP {$ip_address}");
                        } else {
                            $error = 'Помилка реєстрації. Спробуйте пізніше.';
                        }
                    }
                }
            }
        }
    }
}

// ==========================================
// 🔥 ПІДКЛЮЧЕННЯ ЧЕРЕЗ LAYOUT
// ==========================================
$title = 'Реєстрація | Профспілка';
$header_title = 'Реєстрація';
$view_path = 'register_view.php';
$is_auth_page = true; 

require_once 'views/layout.php';
