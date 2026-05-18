<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$poll_id = (int)$_GET['id'];
$ppo_id = (int)$_SESSION['ppo_id'];
$user_id = (int)$_SESSION['user_id'];

try {
    // 🛡️ ПЕРЕВІРКА: Розпорядження належить цій ППО
    $poll = \Core\DB::fetch(
        "SELECT id, is_secret FROM polls WHERE id = ? AND ppo_id = ?",
        [$poll_id, $ppo_id]
    );
    
    if (!$poll) {
        http_response_code(404);
        echo json_encode(['success' => false]);
        exit;
    }
    
    $is_secret = (int)$poll['is_secret'];
    
    $options = \Core\DB::fetchAll(
        "SELECT id, option_text FROM poll_options WHERE poll_id = ? ORDER BY id ASC",
        [$poll_id]
    ) ?: [];
    
    $results = [];
    
    foreach ($options as $opt) {
        $vote_count = (int)\Core\DB::fetchColumn(
            "SELECT COUNT(*) FROM votes WHERE poll_id = ? AND option_id = ?",
            [$poll_id, $opt['id']]
        );
        
        $results[] = [
            'id' => (int)$opt['id'],
            'text' => htmlspecialchars($opt['option_text'], ENT_QUOTES, 'UTF-8'),
            'votes' => $vote_count
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    error_log("Poll Results Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Server error']);
}
