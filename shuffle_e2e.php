<?php
// ==========================================
// 🎲 E2E-MIXER: РОЗУМНИЙ БУФЕР ПЕРЕМІШУВАННЯ
// ==========================================

if (php_sapi_name() !== 'cli' && ($_GET['key'] ?? '') !== 'my_secret_cron_key_123') {
    die("Доступ заборонено.");
}

spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) require_once $path;
});

if (file_exists('db.php')) require_once 'db.php';

echo "Старт міксера E2E-голосів...\n";

try {
    // 🚀 МАГІЯ: Беремо з буфера голоси ТІЛЬКИ тих виборів, які вже ЗАВЕРШИЛИСЬ
    $sql = "
        SELECT b.* FROM e2e_buffer b
        JOIN polls p ON b.target_type = 'poll' AND b.target_id = p.id
        WHERE p.end_date <= NOW()
        
        UNION ALL
        
        SELECT b.* FROM e2e_buffer b
        JOIN nominations n ON b.target_type = 'nomination' AND b.target_id = n.id
        WHERE n.end_date <= NOW()
    ";
    
    $buffer_votes = \Core\DB::fetchAll($sql);

    if (empty($buffer_votes)) {
        die("Немає голосів із завершених виборів для перемішування.\n");
    }

    // 🎲 Перемішуємо всі зібрані голоси (тепер їх там буде багато, і час зітреться)
    shuffle($buffer_votes);

    $processed = 0;

    // Записуємо перемішані голоси у фінальні таблиці
    foreach ($buffer_votes as $vote) {
        if ($vote['target_type'] === 'poll') {
            \Core\DB::query(
                "INSERT INTO votes (user_id, token, poll_id, option_id) VALUES (NULL, ?, ?, ?)",
                [$vote['token'], $vote['target_id'], $vote['option_id']]
            );
        } elseif ($vote['target_type'] === 'nomination') {
            \Core\DB::query(
                "INSERT INTO nomination_votes (nominator_id, token, nomination_id, nominee_id, idea_text) VALUES (NULL, ?, ?, ?, ?)",
                [$vote['token'], $vote['target_id'], $vote['option_id'], $vote['idea_text']]
            );
        }
        
        // Видаляємо голос з буфера
        \Core\DB::query("DELETE FROM e2e_buffer WHERE id = ?", [$vote['id']]);
        $processed++;
    }

    echo "✅ Успішно перемішано та розсекречено {$processed} голосів.\n";

} catch (\Exception $e) {
    echo "❌ Помилка міксера: " . $e->getMessage() . "\n";
}
