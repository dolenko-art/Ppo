<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['petition_id'])) {
    echo json_encode([]); exit;
}

$petition_id = (int)$_GET['petition_id'];
$ppo_id      = (int)$_SESSION['ppo_id'];

// Отримуємо статус таємності
$petition = \Core\DB::fetch("SELECT is_secret_voting FROM petitions WHERE id = ? AND ppo_id = ?", [$petition_id, $ppo_id]);
if (!$petition) { echo json_encode([]); exit; }

$is_secret = (int)$petition['is_secret_voting'];

$sql = "SELECT u.full_name 
        FROM petition_signatures ps
        JOIN users u ON ps.user_id = u.id
        WHERE ps.petition_id = ?
        ORDER BY ps.id ASC";

$signatures = \Core\DB::fetchAll($sql, [$petition_id]);

$results = [];
foreach ($signatures as $row) {
    $name = \Core\Security::decrypt($row['full_name']) ?: $row['full_name'];
    
    $results[] = [
        'full_name' => $is_secret ? 'Приховано' : $name
    ];
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
