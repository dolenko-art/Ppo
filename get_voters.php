<?php
// 1. ЗАГОЛОВОК НА САМОМУ ВЕРХУ
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 2. ПЕРЕВІРКА ДОСТУПУ ТА ПАРАМЕТРІВ
if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['nomination_id'])) {
    echo json_encode(['error' => 'Unauthorized or missing parameters']);
    exit;
}

$nom_id = (int)$_GET['nomination_id'];
$ppo_id = (int)$_SESSION['ppo_id'];

try {
    // 3. БЕЗПЕКА: Перевірка приналежності до ППО та статус таємності
    $nomination = \Core\DB::fetch("SELECT is_secret FROM nominations WHERE id = ? AND ppo_id = ?", [$nom_id, $ppo_id]);

    if (!$nomination) {
        echo json_encode(['error' => 'Nomination not found or access denied']);
        exit;
    }

    $is_secret = (int)$nomination['is_secret'];

    // 4. ОТРИМАННЯ ДАНИХ
    $sql = "SELECT u1.full_name as voter, u2.full_name as nominee 
            FROM nomination_votes nv 
            JOIN users u1 ON nv.nominator_id = u1.id 
            JOIN users u2 ON nv.nominee_id = u2.id 
            WHERE nv.nomination_id = ?";

    $votes = \Core\DB::fetchAll($sql, [$nom_id]);

    // 5. ДЕШИФРУВАННЯ ТА МАСКУВАННЯ
    $results = [];
    foreach ($votes as $row) {
        // Розшифровуємо кандидата (його ми показуємо завжди)
        $nominee = \Core\Security::decrypt($row['nominee']) ?: $row['nominee'];
        
        // БЕЗПЕКА: Якщо таємне - ховаємо ім'я того, хто висував
        if ($is_secret) {
            $voter = 'Приховано';
        } else {
            $voter = \Core\Security::decrypt($row['voter']) ?: $row['voter'];
        }

        $results[] = [
            'voter'   => $voter,
            'nominee' => $nominee
        ];
    }

    // 6. СОРТУВАННЯ
    // Сортуємо масив за ім'ям того, хто голосував, тільки якщо це не таємно
    if (!$is_secret) {
        usort($results, function($a, $b) {
            return mb_strcasecmp($a['voter'], $b['voter']);
        });
    }

    // 7. ВІДДАЧА РЕЗУЛЬТАТУ
    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    // Якщо сталася помилка БД, віддаємо акуратну помилку в форматі JSON
    echo json_encode(['error' => 'Database error occurred']);
}
