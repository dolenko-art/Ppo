<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 1. ПЕРЕВІРКА ДОСТУПУ ТА ПАРАМЕТРІВ
if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['poll_id'])) {
    echo json_encode([]); 
    exit; 
}

$poll_id = (int)$_GET['poll_id'];
$ppo_id  = (int)$_SESSION['ppo_id'];

try {
    $poll = \Core\DB::fetch("SELECT is_secret FROM polls WHERE id = ? AND ppo_id = ?", [$poll_id, $ppo_id]);
    if (!$poll) { echo json_encode([]); exit; }

    // 🔥 ДОДАНО v.token ТА ЗМІНЕНО ЗЧЕПЛЕННЯ НА LEFT JOIN
    $results = \Core\DB::fetchAll("
        SELECT u.full_name as user_name, po.option_text, v.token 
        FROM votes v
        JOIN poll_options po ON v.option_id = po.id
        LEFT JOIN users u ON v.user_id = u.id
        WHERE v.poll_id = ?
    ", [$poll_id]);

    if ((int)$poll['is_secret'] === 2) {
        // РЕЖИМ E2E-V (Токени)
        foreach ($results as &$r) { 
            $r['user_name'] = $r['token'] ?: "Невідомий токен"; 
            $r['is_token'] = true; // Спеціальний маячок для JS
        }
    } elseif ((int)$poll['is_secret'] === 1) {
        // Конфіденційно (Просто статистика)
        foreach ($results as &$r) { 
            $r['user_name'] = "🔒 Анонімно"; 
            $r['is_token'] = false;
        }
    } else {
        // Відкрито
        foreach ($results as &$r) {
            $r['is_token'] = false;
            if (!empty($r['user_name'])) {
                $r['user_name'] = \Core\Security::decrypt($r['user_name']) ?: $r['user_name'];
            }
        }
    }
    unset($r);

    echo json_encode($results ?: [], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([]);
}
