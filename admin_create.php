<?php
require_once 'db.php';

// 1. Перевірка авторизації
if (!\Core\Auth::loggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$ppo_id = (int)$_SESSION['ppo_id'];

// 2. Отримуємо права користувача та поточну вкладку
$active_tab = $_GET['tab'] ?? 'menu'; // 🔥 Вкладка за замовчуванням тепер МЕНЮ

$current_user = \Core\DB::fetch("SELECT status, role FROM users WHERE id = ?", [$user_id]);
$user_status = $current_user['status'] ?? 'member';
$user_role = $current_user['role'] ?? null;

// 🔒 Захист доступу: тільки для Адмінів, Голів та Менеджерів
if ($user_status !== 'admin' && !in_array($user_role, ['head', 'manager'])) {
    $_SESSION['toast_msg'] = "❌ У вас немає доступу до Пульта управління.";
    header("Location: index.php");
    exit;
}

$limit = 10;

// ==========================================
// 📊 СЛАЙД 1: ЗАЯВКИ НА РЕЄСТРАЦІЮ (ПРЕМОДЕРАЦІЯ)
// ==========================================
$p_reg = max(1, (int)($_GET['p_reg'] ?? 1));
$reg_count = \Core\DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status = 'pending'", [$ppo_id]);

$reg_paginated = \Core\DB::fetchAll("
    SELECT id, full_name, joined_at 
    FROM users 
    WHERE ppo_id = ? AND status = 'pending' 
    ORDER BY id ASC 
    LIMIT $limit OFFSET " . (($p_reg - 1) * $limit) . "
", [$ppo_id]);

foreach ($reg_paginated as &$u) {
    $u['full_name'] = class_exists('\Core\Security') ? (\Core\Security::decrypt($u['full_name']) ?: $u['full_name']) : $u['full_name'];
}
unset($u); 

// ==========================================
// 📅 СЛАЙД 5: ПОДІЇ ТА ЇХ УЧАСНИКИ
// ==========================================
$sub_events = $_GET['sub_events'] ?? 'list';
$p_evt = max(1, (int)($_GET['p_evt'] ?? 1));
$events_count = \Core\DB::fetchColumn("SELECT COUNT(*) FROM events WHERE ppo_id=?", [$ppo_id]);
$events_paginated = \Core\DB::fetchAll("
    SELECT * FROM events 
    WHERE ppo_id=? 
    ORDER BY event_date DESC 
    LIMIT $limit OFFSET " . (($p_evt - 1) * $limit) . "
", [$ppo_id]);

foreach ($events_paginated as &$ev) {
    $ev['participants'] = [];
    try {
        $subs = \Core\DB::fetchAll("
            SELECT u.full_name, s.guests_count, s.guests_info 
            FROM event_enrollments s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.event_id = ?
        ", [$ev['id']]);
        
        foreach($subs as &$s) {
            $s['full_name'] = class_exists('\Core\Security') ? (\Core\Security::decrypt($s['full_name']) ?: $s['full_name']) : $s['full_name'];
            $s['guests_count'] = (int)($s['guests_count'] ?? 0);
            
            $g_info = $s['guests_info'];
            $s['guests_text'] = '';
            
            if (!empty($g_info)) {
                $decoded = json_decode($g_info, true);
                if (is_array($decoded)) {
                    $g_arr = [];
                    foreach($decoded as $g) {
                        $name = $g['name'] ?? 'Гість';
                        $age = !empty($g['age']) ? "({$g['age']} р.)" : "";
                        $g_arr[] = trim("$name $age");
                    }
                    $s['guests_text'] = implode(', ', $g_arr);
                } else {
                    $s['guests_text'] = htmlspecialchars($g_info);
                }
            }
        }
        unset($s);
        
        usort($subs, function($a, $b) { 
            return strcmp(mb_strtolower($a['full_name'], 'UTF-8'), mb_strtolower($b['full_name'], 'UTF-8')); 
        });
        
        $ev['participants'] = $subs;
    } catch (\Throwable $e) {}
}
unset($ev);

// ==========================================
// 📣 СЛАЙД 6: ПЕТИЦІЇ НА РОЗГЛЯДІ
// ==========================================
$admin_pets = \Core\DB::fetchAll("
    SELECT id, title, status 
    FROM petitions 
    WHERE ppo_id = ? AND status = 1 
    ORDER BY created_at DESC
", [$ppo_id]);

// ==========================================
// 💰 СЛАЙД 7: ФІНАНСИ
// ==========================================
$sub_fin = $_GET['sub_fin'] ?? 'cash';
$has_mono_token = false;
$mono_accs = [];

try {
    $fin_config = \Core\DB::fetch("SELECT * FROM finance_config WHERE ppo_id = ?", [$ppo_id]);
    $has_mono_token = !empty($fin_config['mono_token']);

    if ($has_mono_token) {
        $mono_accs = \Core\DB::fetchAll("SELECT id, name FROM bank_accounts WHERE ppo_id = ? AND bank_type = 'mono'", [$ppo_id]);
    }
} catch (\Throwable $e) {
    $has_mono_token = false;
    $mono_accs = [];
}

$title = 'Пульт управління';
$header_title = 'Пульт управління';
$view_path = 'admin_create_view.php';
// Додаємо прапорець, щоб шапка і загальне меню знали, що ми в адмінці
$is_admin_page = true; 
require_once 'views/layout.php';

