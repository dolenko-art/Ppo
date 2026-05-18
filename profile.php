<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Підключаємо ядро (сесії, кодування, автозавантажувач та БД вже там)
require_once 'db.php';

if (!\Core\Auth::loggedIn()) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$ppo_id  = (int)($_SESSION['ppo_id'] ?? 0);

// ==========================================
// 1. ОТРИМАННЯ ДАНИХ ЮЗЕРА
// ==========================================
$user = \Core\DB::fetch("SELECT id, status, role, full_name, phone, dob, children_info, joined_at FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    header("Location: logout.php");
    exit;
}

$decrypted_name = \Core\Security::decrypt($user['full_name']);
$user['full_name'] = $decrypted_name !== false ? $decrypted_name : 'Невідомий користувач';
$user['avatar_initial'] = mb_substr(trim($user['full_name']), 0, 1, 'UTF-8');

$user['children_decrypted'] = '';
if (!empty($user['children_info'])) {
    $decrypted_children = \Core\Security::decrypt($user['children_info']);
    $user['children_decrypted'] = $decrypted_children !== false ? $decrypted_children : '';
}

// ==========================================
// 2. ЛОГІКА СТАТУСУ ТА СТАЖУ
// ==========================================
$st = (string)($user['status'] ?? '');
$user_status = $st; // 🔥 ФІКС: Обов'язково створюємо цю змінну для profile_view.php!
$role = trim((string)($user['role'] ?? ''));
$is_full_member = in_array($st, ['member', 'admin']);

if ($is_full_member && !empty($user['joined_at'])) {
    $join_date = new DateTime($user['joined_at']);
    $joined_date_str = $join_date->format('d.m.Y');
    $joined_days_str = (new DateTime())->diff($join_date)->format('%a') . ' дн.';
} else {
    $joined_date_str = '—';
    $joined_days_str = 'Очікується';
}
$age_str = !empty($user['dob']) ? (new DateTime())->diff(new DateTime($user['dob']))->y . ' р.' : 'Не вказано';

// ==========================================
// 3. ФОРМУЄМО БЕЙДЖ РОЛІ
// ==========================================
$roles_map = [
    'head'    => ['text' => 'Голова ППО', 'class' => 'badge-blue'],
    'auditor' => ['text' => 'Ревізор', 'class' => 'badge-orange'],
    'manager' => ['text' => 'Менеджер', 'class' => 'badge-green']
];

if (isset($roles_map[$role])) {
    $badge = $roles_map[$role];
} elseif ($st === 'admin') {
    $badge = ['text' => 'Адміністратор', 'class' => 'badge-blue'];
} elseif (in_array($st, ['candidate', 'pending'])) {
    $badge = ['text' => 'Кандидат на вступ', 'class' => 'badge-gray'];
} else {
    $badge = ['text' => 'Член ППО', 'class' => 'badge-green'];
}

// ==========================================
// 4. ПРОТОКОЛИ ТА СПОВІЩЕННЯ (ОПТИМІЗОВАНО)
// ==========================================
$missing_protocols = \Core\DB::fetchAll("
    SELECT p.poll_id, pl.title, p.created_at 
    FROM protocols p 
    LEFT JOIN polls pl ON p.poll_id = pl.id 
    WHERE p.ppo_id = ? AND ((p.head_user_id = ? AND p.head_signed_at IS NULL) OR (p.secretary_user_id = ? AND p.manager_signed_at IS NULL))
", [$ppo_id, $user_id, $user_id]) ?: [];

$pending_signatures = count($missing_protocols);

$my_notifs = \Core\DB::fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50", [$user_id]) ?: [];

foreach ($missing_protocols as $mp) {
    $expected_msg = "[SIGN_PROTOCOL:{$mp['poll_id']}]";
    $found = false;
    foreach ($my_notifs as $ntf) { 
        if (mb_strpos($ntf['message'] ?? '', $expected_msg) !== false && ($ntf['is_read'] ?? 1) == 0) { 
            $found = true; break; 
        } 
    }
    if (!$found) {
        $my_notifs[] = [
            'id' => 'recovered_'.$mp['poll_id'], 
            'created_at' => $mp['created_at'] ?? date('Y-m-d H:i:s'), 
            'title' => '🚨 Забутий підпис протоколу!', 
            'message' => "Система виявила документ, який очікує вашого КЕП: «" . ($mp['title'] ?? 'Рішення зборів') . "». {$expected_msg}", 
            'is_read' => 0
        ];
    }
}

$action_notifs = []; 
$info_notifs = [];
$active_exclusion_poll = \Core\DB::fetch("SELECT id, target_comment FROM polls WHERE target_user_id = ? AND action_type = 'exclusion' AND is_active = 1 ORDER BY id DESC LIMIT 1", [$user_id]);

foreach ($my_notifs as &$ntf) {
    $msg = (string)($ntf['message'] ?? '');
    $ntf['formatted_date'] = date('d.m.y H:i', strtotime(!empty($ntf['created_at']) ? $ntf['created_at'] : 'now'));
    $ntf['clean_message'] = trim(preg_replace('/\[[\w\-]+\s*:[^\]]*\]/', '', $msg));
    
    $has_action = preg_match('/\[(ACCEPT_ROLE|SIGN_PROTOCOL|EXCLUSION_EXPLANATION)[\w\-:\s]*\]/', $msg) || mb_strpos($msg, 'ініціював ваше виключення') !== false;
    
    if ($has_action) {
        $ntf['has_sign']     = preg_match('/\[SIGN_PROTOCOL:\s*(\d+)\s*\]/i', $msg, $sign_matches);
        $ntf['has_election'] = preg_match('/\[ACCEPT_ROLE:\s*(\d+)\s*:\s*([a-zA-Z_]+)\s*\]/i', $msg, $matches);
        $has_explanation     = preg_match('/\[EXCLUSION_EXPLANATION:\s*(\d+)\s*\]/i', $msg, $exp_matches);
        
        $ntf['poll_id'] = $ntf['has_sign'] ? (int)($sign_matches[1] ?? 0) : ($ntf['has_election'] ? (int)($matches[1] ?? 0) : 0);
        $ntf['target_role'] = $ntf['has_election'] ? (string)($matches[2] ?? '') : '';
        $ntf['p_id'] = 0;
        $ntf['show_defense'] = false;
        
        if (mb_strpos($msg, 'ініціював ваше виключення') !== false && $active_exclusion_poll) {
            $ntf['p_id'] = (int)$active_exclusion_poll['id'];
            if (empty((string)$active_exclusion_poll['target_comment'])) $ntf['show_defense'] = true;
        } elseif ($has_explanation) {
            $ntf['p_id'] = (int)($exp_matches[1] ?? 0);
            $ntf['show_defense'] = true; 
        }
        
        $action_notifs[] = $ntf; 
    } else { 
        $info_notifs[] = $ntf; 
    }
}
unset($ntf);

// 🔥 ФІКС PHP 7.x: Використовуємо класичні функції замість стрілочних fn()
usort($action_notifs, function($a, $b) {
    return strtotime($b['created_at'] ?? 0) <=> strtotime($a['created_at'] ?? 0);
});
usort($info_notifs, function($a, $b) {
    return strtotime($b['created_at'] ?? 0) <=> strtotime($a['created_at'] ?? 0);
});
$info_notifs = array_slice($info_notifs, 0, 50);

// ==========================================
// 5. ДОВІДНИК ППО
// ==========================================
$limit = 15;
$p_dir = max(1, (int)($_GET['p_dir'] ?? 1));
$offset = ($p_dir - 1) * $limit;

$all_members_raw = \Core\DB::fetchAll("
    SELECT full_name, status, joined_at 
    FROM users 
    WHERE ppo_id = ? AND status IN ('member', 'admin') 
    ORDER BY id ASC 
    LIMIT $limit OFFSET $offset
", [$ppo_id]) ?: [];

$ppo_members = [];
foreach ($all_members_raw as $m) {
    $dec_name = \Core\Security::decrypt($m['full_name']);
    $ppo_members[] = [
        'full_name' => $dec_name !== false ? $dec_name : 'Невідомий користувач',
        'status'    => $m['status'],
        'joined_at' => $m['joined_at']
    ];
}

$active_tab = $_GET['tab'] ?? 'info';

\Core\DB::query("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0 AND message NOT LIKE '%[%]%' AND message NOT LIKE '%ініціював ваше виключення%'", [$user_id]);

// 🔥 ПІДКЛЮЧЕННЯ ЧЕРЕЗ LAYOUT
$title = 'Мій Профіль';
$header_title = 'Мій Профіль'; 
$view_path = 'profile_view.php';
require_once 'views/layout.php';
