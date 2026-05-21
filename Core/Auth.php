<?php
/**
 * Core\Auth - Authentication Service
 * Методи: Перевірка сесії, Логаут, Отримання даних користувача
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

namespace Core;

class Auth {
    
    /**
     * Перевіркa логіну користувача
     * @return bool
     */
    public static function loggedIn() {
        return !empty($_SESSION['user_id']) && 
               !empty($_SESSION['ppo_id']) &&
               !empty($_SESSION['csrf_token']);
    }
    
    /**
     * Отримати ID поточного користувача
     * @return int|null
     */
    public static function userId() {
        return self::loggedIn() ? (int)$_SESSION['user_id'] : null;
    }
    
    /**
     * Отримати ID ППО поточного користувача
     * @return int|null
     */
    public static function ppoId() {
        return self::loggedIn() ? (int)$_SESSION['ppo_id'] : null;
    }
    
    /**
     * Отримати роль поточного користувача
     * @return string|null
     */
    public static function role() {
        return self::loggedIn() ? $_SESSION['role'] ?? null : null;
    }
    
    /**
     * Отримати статус поточного користувача
     * @return string|null
     */
    public static function status() {
        return self::loggedIn() ? $_SESSION['status'] ?? null : null;
    }
    
    /**
     * Отримати дані поточного користувача
     * @return array|null
     */
    public static function user() {
        if (!self::loggedIn()) return null;
        
        return [
            'id' => (int)$_SESSION['user_id'],
            'ppo_id' => (int)$_SESSION['ppo_id'],
            'role' => $_SESSION['role'] ?? null,
            'status' => $_SESSION['status'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? 'User'
        ];
    }
    
    /**
     * Перевірка прав доступу
     * @param string $role Required role
     * @return bool
     */
    public static function hasRole($role) {
        if (!self::loggedIn()) return false;
        return ($_SESSION['role'] ?? null) === $role || ($_SESSION['status'] ?? null) === 'admin';
    }
    
    /**
     * Перевірка статусу
     * @param string|array $status
     * @return bool
     */
    public static function hasStatus($status) {
        if (!self::loggedIn()) return false;
        
        if (is_array($status)) {
            return in_array($_SESSION['status'] ?? null, $status);
        }
        
        return ($_SESSION['status'] ?? null) === $status;
    }
    
    /**
     * Перевірка чи є користувач лідером
     * @return bool
     */
    public static function isLeadership() {
        if (!self::loggedIn()) return false;
        
        $role = $_SESSION['role'] ?? null;
        $status = $_SESSION['status'] ?? null;
        
        return in_array($role, ['head', 'auditor', 'manager']) || $status === 'admin';
    }
    
    /**
     * Отримати CSRF токен
     * @return string
     */
    public static function getCsrfToken() {
        return $_SESSION['csrf_token'] ?? '';
    }
    
    /**
     * Перевірити CSRF токен
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken($token) {
        $session_token = $_SESSION['csrf_token'] ?? '';
        return hash_equals($session_token, $token);
    }
    
    /**
     * Логаут користувача
     * @return bool
     */
    public static function logout() {
        // 🔥 Log the logout event
        $user_id = $_SESSION['user_id'] ?? null;
        if ($user_id) {
            error_log("User {$user_id} logged out at " . date('Y-m-d H:i:s'));
        }
        
        // 🔥 Destroy session data
        $_SESSION = [];
        
        // 🔥 Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // 🔥 Destroy session on server
        session_destroy();
        
        return true;
    }
    
    /**
     * Перегенерувати ID сесії (для безпеки)
     * @return bool
     */
    public static function regenerateSessionId() {
        return session_regenerate_id(true);
    }
}
