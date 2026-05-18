<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['nomination_id'])) {
    echo json_encode([]); exit;
}

$nom_id = (int)$_GET['nomination_id'];
$ppo_id = (int)$_SESSION['ppo_id'];

$nomination = \Core\DB::fetch("SELECT is_secret, target_role FROM nominations WHERE id = ? AND ppo_id = ?", [$nom_id, $ppo_id]);
if (!$nomination) { echo json_encode([]); exit; }

$is_secret = (int)$nomination['is_secret'];
$is_regular = ($nomination['target_role'] === 'regular');

// 🔥 ДОДАНО nv.token ТА ЗМІНЕНО ЗЧЕПЛЕННЯ НА LEFT JOIN
if ($is_regular) {
    $sql = "SELECT u1.full_name as nominator_enc, nv.idea_text as idea_val, nv.token 
            FROM nomination_votes nv
            LEFT JOIN users u1 ON nv.nominator_id = u1.id
            WHERE nv.nomination_id = ? AND nv.idea_text IS NOT NULL
            ORDER BY nv.id DESC";
} else {
    $sql = "SELECT u1.full_name as nominator_enc, u2.full_name as nominee_enc, nv.token 
            FROM nomination_votes nv
            LEFT JOIN users u1 ON nv.nominator_id = u1.id
            LEFT JOIN users u2 ON nv.nominee_id = u2.id
            WHERE nv.nomination_id = ?
            ORDER BY nv.id DESC";
}

$votes = \Core\DB::fetchAll($sql, [$nom_id]);
$results = [];

foreach ($votes as $row) {
    $is_token = false;
    
    if ($is_secret === 2) {
        $nominator = $row['token'] ?: "Невідомий токен";
        $is_token = true;
    } elseif ($is_secret === 1) {
        $nominator = "🔒 Приховано";
    } else {
        $nominator = !empty($row['nominator_enc']) ? (\Core\Security::decrypt($row['nominator_enc']) ?: $row['nominator_enc']) : 'Система';
    }
    
    if ($is_regular) {
        $variant = $row['idea_val'];
    } else {
        $variant = !empty($row['nominee_enc']) ? (\Core\Security::decrypt($row['nominee_enc']) ?: $row['nominee_enc']) : 'Видалений користувач';
    }
    
    $results[] = [
        'nominee'   => $variant,
        'nominator' => $nominator,
        'is_token'  => $is_token
    ];
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
