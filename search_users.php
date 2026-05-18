<?php
// 1. ЗАГОЛОВОК НА САМОМУ ВЕРХУ
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 2. ПЕРЕВІРКА ДОСТУПУ ТА ПАРАМЕТРІВ
if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$ppo_id = (int)$_SESSION['ppo_id'];
$q = mb_strtolower(trim($_GET['q']), 'UTF-8');

try {
    // 3. ОТРИМАННЯ ДАНИХ (БЕЗПЕКА: тільки своя ППО)
    $users = \Core\DB::fetchAll("SELECT id, full_name, status, joined_at FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')", [$ppo_id]);

    $results = [];
    $three_days_ago = strtotime('-3 days');

    // 4. ОБРОБКА, ДЕШИФРУВАННЯ ТА ПОШУК
    foreach ($users as $u) {
        // --- ПРАВИЛО 72 ГОДИН ДЛЯ КАНДИДАТА ---
        // Якщо це не адмін і в ППО менше 3 днів - пропускаємо його (не показуємо в пошуку)
        if ($u['status'] !== 'admin' && (!$u['joined_at'] || strtotime($u['joined_at']) > $three_days_ago)) {
            continue;
        }

        // Дешифруємо ім'я з fallback
        $name = \Core\Security::decrypt($u['full_name']) ?: $u['full_name'];

        // Шукаємо збіг із введеними літерами
        if (mb_strpos(mb_strtolower($name, 'UTF-8'), $q, 0, 'UTF-8') !== false) {
            $results[] = [
                'id' => $u['id'],
                // htmlspecialchars додатково захищає від XSS при виводі у Frontend
                'full_name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8') 
            ];
        }
    }

    // 5. ПОВЕРТАЄМО ТОП-10 РЕЗУЛЬТАТІВ З КИРИЛИЦЕЮ
    echo json_encode(array_slice($results, 0, 10), JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    // Віддаємо пустий масив, щоб не ламати скрипти на клієнті
    echo json_encode([]);
}
