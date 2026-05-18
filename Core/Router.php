<?php
namespace Core;

class Router {
    
    public static function handlePost() {
        $route = $_GET['route'] ?? '';

        // 🛡️ БРОНЯ 0: ПОВНЕ ВІДСІКАННЯ СЕСІЇ ДЛЯ АНОНІМНОГО ГОЛОСУ
        // Захист від криміналістичного аналізу файлів сесій (Forensics)
        if ($route === 'cast_e2e_vote') {
            if (session_status() === PHP_SESSION_ACTIVE) {
                // Закриваємо сесію без збереження, щоб не оновлювався timestamp файлу на диску Linux
                session_write_close();
            }
            // Знищуємо суперглобальні масиви з пам'яті поточного процесу
            $_SESSION = [];
            unset($_COOKIE[session_name()]);
        }

        // 1. ВИЗНАЧАЄМО ТИП ЗАПИТУ (Чи це тихий AJAX, чи звичайний сабміт форми)
        $isAjax = !empty($_POST['ajax']) || 
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        // 🛡️ БРОНЯ 1: ЄДИНА ПЕРЕВІРКА АВТОРИЗАЦІЇ
        if ($route !== 'cast_e2e_vote' && !\Core\Auth::loggedIn()) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'msg' => 'Сесія закінчилась. Оновіть сторінку.']);
                exit;
            }
            header("Location: login.php");
            exit;
        }

        // 🛡️ БРОНЯ 2: БЕЗПЕЧНА ПЕРЕВІРКА CSRF-ТОКЕНА
        if ($route !== 'cast_e2e_vote') {
            $clientToken = $_POST['csrf_token'] ?? '';
            $serverToken = $_SESSION['csrf_token'] ?? '';

            if (empty($clientToken) || !hash_equals($serverToken, $clientToken)) {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'msg' => 'Помилка безпеки: недійсний токен. Оновіть сторінку.']);
                    exit;
                }
                $_SESSION['toast_msg'] = "❌ Помилка безпеки сесії. Спробуйте ще раз.";
                header("Location: index.php");
                exit;
            }
        }

        // 🛡️ БРОНЯ 3: RATE LIMITING (АНТИ-СПАМ)
        $user_id = $_SESSION['user_id'] ?? 0;
        if ($user_id && $route !== 'cast_e2e_vote') {
            $last_action = $_SESSION['last_action_time'] ?? 0;
            $current_time = microtime(true);
            
            if ($current_time - $last_action < 1.0) {
                $msg = "⏳ Занадто часто! Будь ласка, зачекайте секунду.";
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'msg' => $msg]);
                    exit;
                }
                $_SESSION['toast_msg'] = $msg;
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
                exit;
            }
            $_SESSION['last_action_time'] = $current_time;
        }

        // ... Далі код вашого switch($route) залишається без змін ...
        ob_start();
        $result = null;

        switch ($route) {
            case 'apply': $result = \Controllers\ActionController::apply(); break;
            case 'vote': $result = \Controllers\ActionController::vote(); break;
            case 'nominate': $result = \Controllers\ActionController::nominate(); break;
            case 'create_petition': $result = \Controllers\ActionController::createPetition(); break;
            case 'sign_petition': $result = \Controllers\ActionController::signPetition(); break;
            case 'enroll': $result = \Controllers\ActionController::enroll(); break;
            case 'generate_e2e_token': $result = \Controllers\ActionController::generateE2eToken(); break;
            case 'cast_e2e_vote': $result = \Controllers\ActionController::castE2eVote(); break;
            case 'admin_action': $result = \Controllers\AdminController::handle(); break;
            case 'profile_action': $result = \Controllers\ProfileController::handle(); break;
            default:
                ob_end_clean();
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'msg' => 'Невідомий маршрут.']);
                    exit;
                }
                $_SESSION['toast_msg'] = "❌ Невідомий маршрут.";
                header("Location: index.php");
                exit;
        }

        $stray_output = ob_get_clean();
        if (is_array($result)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit;
        }
        echo trim($stray_output);
        exit;
    }
}
