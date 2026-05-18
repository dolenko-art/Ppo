<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['petition_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Доступ заборонено']);
    exit;
}

$petition_id = (int)$_GET['petition_id'];
$ppo_id      = (int)$_SESSION['ppo_id'];
$user_id     = (int)$_SESSION['user_id'];

try {
    // 🛡️ ПЕРЕВІРКА АВТОРИЗАЦІЇ: Дозвіл переглядати петицію
    $petition = \Core\DB::fetch(
        "SELECT id, ppo_id, user_id, is_secret_voting FROM petitions WHERE id = ? AND ppo_id = ?",
        [$petition_id, $ppo_id]
    );
    
    if (!$petition) {
        http_response_code(404);
        echo json_encode(['success' => false, 'msg' => 'Петиція не знайдена']);
        exit;
    }
    
    // 🛡️ ПЕРЕВІРКА: Тільки автор або лідерство можуть див. підписи
    $user = \Core\DB::fetch("SELECT role, status FROM users WHERE id = ?", [$user_id]);
    $is_leadership = in_array($user['role'] ?? '', ['head', 'auditor', 'manager']) || $user['status'] === 'admin';
    $is_author = (int)$petition['user_id'] === $user_id;
    
    if (!$is_author && !$is_leadership) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'Дозвіл заборонено']);
        exit;
    }

    $is_secret = (int)$petition['is_secret_voting'];
    
    $sql = "SELECT u.full_name 
            FROM petition_signatures ps
            JOIN users u ON ps.user_id = u.id
            WHERE ps.petition_id = ?
            ORDER BY ps.id ASC";
    
    $signatures = \Core\DB::fetchAll($sql, [$petition_id]);
    
    $results = [];
    foreach ($signatures as $row) {
        // 🛡️ БЕЗПЕКА: Правильне дешифрування з fallback
        $name = $is_secret ? 'Анонімно' : (class_exists('\Core\Security') 
            ? (\Core\Security::decrypt($row['full_name']) ?: 'Невідомо') 
            : $row['full_name']);
        
        $results[] = [
            'full_name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        ];
    }
    
    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    error_log("Petition Signers Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Помилка сервера']);
}
