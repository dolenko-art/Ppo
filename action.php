<?php
// ==========================================
// 🚀 1. БАЗОВА ІНІЦІАЛІЗАЦІЯ
// ==========================================
$isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) require_once $path;
});

if (file_exists('db.php')) require_once 'db.php';

$route = $_GET['route'] ?? '';

// ==========================================
// 🛡️ 2. ГЛОБАЛЬНА БЕЗПЕКА (AUTH, CSRF & ANTI-FLOOD)
// ==========================================
$public_routes = ['cast_e2e_vote']; // Анонімні роути

if (!in_array($route, $public_routes)) {
    if (!\Core\Auth::loggedIn()) {
        if ($isAjax) { echo json_encode(['success' => false, 'msg' => 'Сесія закінчилась. Оновіть сторінку.']); exit; }
        header("Location: login.php"); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $clientToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $clientToken)) {
            if ($isAjax) { echo json_encode(['success' => false, 'msg' => 'Помилка безпеки: недійсний токен. Оновіть сторінку.']); exit; }
            $_SESSION['toast_msg'] = "❌ Помилка безпеки сесії. Спробуйте ще раз.";
            header("Location: index.php"); exit;
        }

        // 🛡️ Жорсткий Anti-Flood для POST запитів (1 дія на секунду)
        $user_id = $_SESSION['user_id'] ?? 0;
        $mem_key = "last_post_time_{$user_id}";
        $current_time = microtime(true);
        if ($current_time - ($_SESSION[$mem_key] ?? 0) < 1.0) {
            if ($isAjax) { echo json_encode(['success' => false, 'msg' => '⏳ Занадто часто! Зачекайте секунду.']); exit; }
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php')); exit;
        }
        $_SESSION[$mem_key] = $current_time;
    }
}

// ==========================================
// 🚀 3. МАРШРУТИЗАЦІЯ З ПЕРЕВІРКОЮ HTTP-МЕТОДІВ
// ==========================================
$result = null;
$req_method = $_SERVER['REQUEST_METHOD'];

// Словник: 'Назва маршруту' => 'Дозволений метод'
$allowed_routes = [
    // GET-маршрути (Тільки читання)
    'search_users'             => 'GET',
    'search_candidate'         => 'GET',

    // POST-маршрути (Зміна стану - захищені CSRF)
    'apply'                    => 'POST',
    'vote'                     => 'POST',
    'nominate'                 => 'POST',
    'nominate_idea'            => 'POST',
    'create_petition'          => 'POST',
    'sign_petition'            => 'POST',
    'leave_ppo'                => 'POST',
    'enroll'                   => 'POST',
    'create_exclusion'         => 'POST',
    'submit_exclusion_comment' => 'POST',
    'resolve_election'         => 'POST',
    
    // 🔥 ДОДАНО ДЛЯ РОБОТИ ПРОФІЛЮ
    'sign_protocol'            => 'POST',
    'resign_role'              => 'POST',
    
    'generate_e2e_token'       => 'POST',
    'cast_e2e_vote'            => 'POST',
    
    'admin_action'             => 'POST',
    'profile_action'           => 'POST',
    'auditor_action'           => 'POST'
];

try {
    if (!isset($allowed_routes[$route])) {
        throw new \Exception('Невідома дія: ' . htmlspecialchars($route));
    }

    if ($req_method !== $allowed_routes[$route]) {
        throw new \Exception('Метод не дозволено (Method Not Allowed).');
    }

    switch ($route) {
        case 'apply':                    $result = \Controllers\ActionController::apply(); break;
        case 'vote':                     $result = \Controllers\ActionController::vote(); break;
        case 'nominate':                 $result = \Controllers\ActionController::nominate(); break;
        case 'nominate_idea':            $result = \Controllers\ActionController::nominateIdea(); break;
        case 'create_petition':          $result = \Controllers\ActionController::createPetition(); break;
        case 'sign_petition':            $result = \Controllers\ActionController::signPetition(); break;
        case 'leave_ppo':                $result = \Controllers\ActionController::leavePpo(); break;
        case 'enroll':                   $result = \Controllers\ActionController::enroll(); break;
        case 'create_exclusion':         $result = \Controllers\ActionController::createExclusion(); break;
        case 'submit_exclusion_comment': $result = \Controllers\ActionController::submitExclusionComment(); break;
        case 'resolve_election':         $result = \Controllers\ActionController::resolveElection(); break;
        
        // 🔥 ДОДАНО ДЛЯ РОБОТИ ПРОФІЛЮ
        case 'sign_protocol':            $result = \Controllers\ActionController::signProtocol(); break;
        case 'resign_role':              $result = \Controllers\ActionController::resignRole(); break;
        
        case 'generate_e2e_token':       $result = \Controllers\E2EController::generateToken(); break;
        case 'cast_e2e_vote':            $result = \Controllers\E2EController::castVote(); break;
        
        case 'search_users':             $result = \Controllers\SearchController::searchUsers(); break;
        case 'search_candidate':         $result = \Controllers\SearchController::searchCandidate(); break;

        case 'admin_action':             $result = \Controllers\AdminController::handle(); break;
        case 'profile_action':           $result = \Controllers\ProfileController::handle(); break;
        case 'auditor_action':           $result = \Controllers\AuditorController::handle(); break;
    }
} catch (\Exception $e) {
    error_log("Router Error [Route: {$route}, Method: {$req_method}]: " . $e->getMessage());
    $result = ['success' => false, 'msg' => 'Помилка виконання дії або неправильний метод запиту.'];
}

// ==========================================
// 🏁 4. ФІНАЛІЗАЦІЯ ВІДПОВІДІ
// ==========================================
if (is_array($result)) {
    echo json_encode($result);
    exit;
}

if ($isAjax) echo json_encode(['success' => false, 'msg' => 'Порожня відповідь сервера']);
exit;
