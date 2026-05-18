<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// 1. ПЕРЕВІРКА ДОСТУПУ ТА ПАРАМЕТРІВ
if (empty($_SESSION['user_id']) || empty($_SESSION['ppo_id']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$ppo_id = (int)$_SESSION['ppo_id'];
$q = mb_strtolower(trim($_GET['q']), 'UTF-8');

// 🛡️ ЗАЩИТА: Мінімальна довжина запиту (захист від перебирання)
if (mb_strlen($q, 'UTF-8') < 2) {
    echo json_encode([]);
    exit;
}

// 🛡️ RATE LIMITING для пошуку
$rate_limiter = new \Core\RateLimiter($_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '');
if (!$rate_limiter->isAllowed(60, 3600)) { // Макс 60 запитів на годину
    http_response_code(429);
    echo json_encode(['success' => false, 'msg' => 'Забагато запитів']);
    exit;
}

try {
    // 2. ОТРИМАННЯ ДАНИХ (БЕЗПЕКА: тільки своя ППО)
    $users = \Core\DB::fetchAll(
        "SELECT id, full_name, status, joined_at FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')",
        [$ppo_id]
    );

    $results = [];
    $three_days_ago = strtotime('-3 days');

    // 3. ОБРОБКА, ДЕШИФРУВАННЯ ТА ПОШУК
    foreach ($users as $u) {
        // Правило 72 годин для нових членів
        if ($u['status'] !== 'admin' && (!$u['joined_at'] || strtotime($u['joined_at']) > $three_days_ago)) {
            continue;
        }

        // Дешифруємо ім'я з fallback
        $name = class_exists('\Core\Security') ? (\Core\Security::decrypt($u['full_name']) ?: $u['full_name']) : $u['full_name'];
        
        // 🛡️ ЗАХИСТ: Перевірити що ім'я валідне
        if (empty($name) || mb_strlen($name, 'UTF-8') > 255) {
            continue;
        }

        // Шукаємо збіг із введеними літерами
        if (mb_strpos(mb_strtolower($name, 'UTF-8'), $q, 0, 'UTF-8') !== false) {
            $results[] = [
                'id' => (int)$u['id'],
                // htmlspecialchars захищає від XSS при виводі у Frontend
                'full_name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
            ];
        }
    }

    // 4. ПОВЕРТАЄМО ТОП-10 РЕЗУЛЬТАТІВ
    echo json_encode(array_slice($results, 0, 10), JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    error_log("Search Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false]);
}
