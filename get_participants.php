<?php
// 1. КАЖЕМО БРАУЗЕРУ, ЩО ЦЕ JSON (дуже важливо для AJAX)
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!\Core\Auth::loggedIn()) { 
    echo json_encode([]); 
    exit; 
}

$event_id = (int)($_GET['event_id'] ?? 0);
$ppo_id = (int)($_SESSION['ppo_id'] ?? 0); // Беремо ID профспілки поточного юзера

try {
    // 2. БЕЗПЕКА: Додаємо JOIN таблиці events, щоб переконатися, що подія належить САМЕ ЦІЙ профспілці
    $participants = \Core\DB::fetchAll("
        SELECT u.full_name as user_name, ee.guests_count, ee.guests_info 
        FROM event_enrollments ee 
        JOIN users u ON ee.user_id = u.id 
        JOIN events ev ON ee.event_id = ev.id 
        WHERE ee.event_id = ? AND ev.ppo_id = ?
    ", [$event_id, $ppo_id]);

    // Розшифровуємо імена
    foreach ($participants as &$p) {
        if (class_exists('\Core\Security')) {
            $p['user_name'] = \Core\Security::decrypt($p['user_name']) ?: $p['user_name'];
        }
    }
    unset($p); // Очищаємо посилання

    // JSON_UNESCAPED_UNICODE гарантує, що українські літери не перетворяться на \u0430\u0431...
    echo json_encode($participants, JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    // Якщо помилка бази, віддаємо пустий масив, щоб не ламався JS
    echo json_encode([]);
}
