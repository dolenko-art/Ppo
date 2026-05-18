<?php
// 1. ЗАГОЛОВОК НА САМОМУ ВЕРХУ (щоб і помилки віддавалися як JSON)
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 2. БЕЗПЕКА ДЛЯ AJAX: Замість Auth::check() віддаємо JSON-помилку
if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['poll_id'])) {
    echo json_encode(['error' => 'Unauthorized or missing parameters']);
    exit;
}

$poll_id = (int)$_GET['poll_id'];
$ppo_id  = (int)$_SESSION['ppo_id'];

try {
    // 3. БЕЗПЕКА (IDOR): Перевіряємо, чи належить опитування ЦІЙ профспілці!
    $poll = \Core\DB::fetch("SELECT is_secret FROM polls WHERE id = ? AND ppo_id = ?", [$poll_id, $ppo_id]);

    if (!$poll) {
        echo json_encode(['error' => 'Poll not found or access denied']);
        exit;
    }

    // 4. ОТРИМАННЯ ВАРІАНТІВ
    $options = \Core\DB::fetchAll("SELECT id, option_text FROM poll_options WHERE poll_id = ?", [$poll_id]);

    // 5. ОТРИМАННЯ ГОЛОСІВ
    $sql_votes = "SELECT v.option_id, u.full_name 
                  FROM votes v
                  JOIN users u ON v.user_id = u.id
                  WHERE v.poll_id = ?";
    $votes = \Core\DB::fetchAll($sql_votes, [$poll_id]);

    $total_votes = count($votes);
    $results = [];

    // 6. ОБРОБКА ДАНИХ ТА ДЕШИФРУВАННЯ
    foreach ($options as $opt) {
        $opt_votes = array_filter($votes, fn($v) => $v['option_id'] == $opt['id']);
        $voters = [];
        
        // Якщо опитування НЕ секретне — розшифровуємо імена
        if (!$poll['is_secret']) {
            foreach ($opt_votes as $v) {
                // БЕЗПЕКА: Додаємо fallback (?:), якщо дешифрація не вдалася
                $voters[] = \Core\Security::decrypt($v['full_name']) ?: $v['full_name'];
            }
            sort($voters); // Сортуємо імена за алфавітом для краси
        }
        
        $results[] = [
            'name'    => $opt['option_text'],
            'count'   => count($opt_votes),
            'percent' => $total_votes > 0 ? round((count($opt_votes) / $total_votes) * 100) : 0,
            // Якщо таємне, просто повертаємо масив з одним словом
            'voters'  => $poll['is_secret'] ? ['Приховано'] : $voters
        ];
    }

    // 7. СОРТУВАННЯ ВАРІАНТІВ (щоб лідери завжди були зверху - гарний UX)
    usort($results, fn($a, $b) => $b['count'] <=> $a['count']);

    // 8. ВІДДАЧА JSON
    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    // Якщо сталася помилка БД, не "світимо" її структуру хакерам
    echo json_encode(['error' => 'Database error occurred']);
}
