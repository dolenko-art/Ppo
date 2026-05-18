<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 1. ПЕРЕВІРКА ДОСТУПУ ТА ПАРАМЕТРІВ
if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['nomination_id'])) {
    echo json_encode([]); exit;
}

$nom_id = (int)$_GET['nomination_id'];
$ppo_id = (int)$_SESSION['ppo_id'];

// 2. БЕЗПЕКА: Перевірка приналежності до ППО та отримання статусу таємності
$nomination = \Core\DB::fetch("SELECT is_secret FROM nominations WHERE id = ? AND ppo_id = ?", [$nom_id, $ppo_id]);

if (!$nomination) {
    echo json_encode([]); exit;
}

$is_secret = (int)$nomination['is_secret'];

// 3. ОТРИМАННЯ ДАНИХ
$sql = "SELECT 
            nv.nominee_id,
            u_nominee.full_name AS candidate_encrypted,
            u_voter.full_name AS voter_encrypted
        FROM nomination_votes nv
        JOIN users u_nominee ON nv.nominee_id = u_nominee.id
        JOIN users u_voter ON nv.nominator_id = u_voter.id
        WHERE nv.nomination_id = ?";

$votes = \Core\DB::fetchAll($sql, [$nom_id]);

$total_votes = count($votes);
$candidates = [];

// 4. ОБРОБКА ТА ДЕШИФРУВАННЯ
foreach ($votes as $row) {
    $id = $row['nominee_id'];
    
    // Ініціалізуємо кандидата, якщо його ще немає в масиві
    if (!isset($candidates[$id])) {
        // Дешифруємо з підстраховкою
        $decrypted_candidate = \Core\Security::decrypt($row['candidate_encrypted']) ?: $row['candidate_encrypted'];
        
        $candidates[$id] = [
            'name'   => $decrypted_candidate,
            'count'  => 0,
            'voters' => []
        ];
    }
    
    $candidates[$id]['count']++;
    
    // БЕЗПЕКА: Якщо висування таємне, не дешифруємо і не віддаємо імена виборців
    if ($is_secret) {
        $candidates[$id]['voters'][] = 'Приховано';
    } else {
        $decrypted_voter = \Core\Security::decrypt($row['voter_encrypted']) ?: $row['voter_encrypted'];
        $candidates[$id]['voters'][] = $decrypted_voter;
    }
}

// 5. ФОРМУВАННЯ ФІНАЛЬНОГО МАСИВУ ТА ВІДСОТКІВ
$final_results = [];
foreach ($candidates as $c) {
    $c['percent'] = $total_votes > 0 ? round(($c['count'] / $total_votes) * 100) : 0;
    
    // Сортуємо імена виборців за алфавітом (тільки якщо це відкрите голосування)
    if (!$is_secret) {
        sort($c['voters']);
    }
    
    $final_results[] = $c;
}

// 6. СОРТУВАННЯ ЗА КІЛЬКІСТЮ ГОЛОСІВ (Лідер зверху)
usort($final_results, fn($a, $b) => $b['count'] <=> $a['count']);

// 7. ВИДАЧА JSON
echo json_encode($final_results, JSON_UNESCAPED_UNICODE);
