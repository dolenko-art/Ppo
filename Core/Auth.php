<?php
namespace Core;

class Auth {
    
    /**
     * 🛡️ ПЕРЕВІРКА АВТОРИЗАЦІЇ + ЗАХИСТ ВІД КРАДІЖКИ СЕСІЇ
     */
    public static function loggedIn() {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        // Захист від Session Hijacking (Підміни куки)
        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Якщо в сесії вже є User-Agent, звіряємо його з поточним
        if (!empty($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $current_ua) {
                self::logout(true); // Примусовий тихий вихід через підозрілу активність
                return false;
            }
        } else {
            // Якщо це перший вхід/стара сесія — запам'ятовуємо браузер
            $_SESSION['user_agent'] = $current_ua;
        }

        return true;
    }

    /**
     * Глобальний захист сторінок (Рідірект неавторизованих)
     */
    public static function check() {
        if (!self::loggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header("Location: login.php");
            exit;
        }
    }

    /**
     * Отримання статичних даних користувача з сесії (ПІБ, ID)
     */
    public static function user($field = null) {
        if ($field) return $_SESSION[$field] ?? null;
        return $_SESSION;
    }

    /**
     * 🛡️ ДИНАМІЧНА ПЕРЕВІРКА СУПЕРАДМІНА (Захист від "Мертвої сесії")
     */
    public static function isAdmin() {
        $user_id = self::user('user_id');
        if (!$user_id) return false;

        // Робимо швидкий запит до БД для отримання актуального статусу
        $status = \Core\DB::fetchColumn("SELECT status FROM users WHERE id = ?", [$user_id]);
        
        // Якщо статус змінився (наприклад, адміна понизили), оновлюємо сесію
        if ($status !== ($_SESSION['status'] ?? '')) {
            $_SESSION['status'] = $status;
        }

        return $status === 'admin';
    }

    /**
     * 🛡️ ДИНАМІЧНА ПЕРЕВІРКА КЕРІВНИКА ППО (Захист від "Мертвої сесії")
     */
    public static function isPpoManager() {
        $user_id = self::user('user_id');
        if (!$user_id) return false;

        // Запитуємо актуальну посаду (роль) прямо з бази даних
        $role = \Core\DB::fetchColumn("SELECT role FROM users WHERE id = ?", [$user_id]);
        
        // Якщо роль змінилася (імпічмент, складання повноважень), оновлюємо сесію
        if ($role !== ($_SESSION['role'] ?? '')) {
            $_SESSION['role'] = $role;
        }

        return in_array($role, ['head', 'manager']);
    }

    /**
     * Безпечний вихід із системи + Логування
     */
    public static function logout($is_forced = false) {
        $user_id = $_SESSION['user_id'] ?? 0;
        $ppo_id = $_SESSION['ppo_id'] ?? 0;

        // 📝 Логуємо подію виходу (якщо сесія ще жива і підключено Guard)
        if ($user_id && class_exists('\Core\Guard')) {
            $action = $is_forced ? 'Примусове завершення сесії (Підозріла активність)' : 'Вихід із системи';
            \Core\Guard::logAction($user_id, $action, '', false, $ppo_id);
        }

        // Очищаємо масив сесії
        $_SESSION = [];
        
        // Знищуємо куку сесії (захист від Session Fixation)
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Знищуємо саму сесію
        session_destroy();
    }
}
