<?php
/**
 * Core\Middleware - Request Middleware Pipeline
 * Проміжний шар для обробки запитів
 * ВЕРСІЯ: 2.0
 */

namespace Core;

class Middleware {
    
    private static $middlewares = [];
    
    /**
     * Реєстрація middleware
     */
    public static function register($name, callable $handler) {
        self::$middlewares[$name] = $handler;
    }
    
    /**
     * Виконати middleware
     */
    public static function run($name) {
        if (isset(self::$middlewares[$name])) {
            call_user_func(self::$middlewares[$name]);
        }
    }
    
    /**
     * CORS Middleware
     */
    public static function cors($allowed_origins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Дозволити тільки авторизовані origin
        if (empty($allowed_origins)) {
            $allowed_origins = [getenv('APP_URL') ?: 'https://localhost'];
        }
        
        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
            header('Access-Control-Max-Age: 86400');
            
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit;
            }
        }
    }
    
    /**
     * Authentication Middleware
     */
    public static function auth() {
        if (!Auth::loggedIn()) {
            Response::unauthorized('Please login first');
        }
    }
    
    /**
     * Role-based Access Control
     */
    public static function role($required_role) {
        if (!Auth::hasRole($required_role)) {
            Logger::security('Unauthorized role access attempt', Logger::WARNING, [
                'required' => $required_role,
                'actual' => Auth::role()
            ]);
            Response::forbidden('Insufficient permissions');
        }
    }
    
    /**
     * Rate Limiting Middleware
     */
    public static function rateLimit($limit = 100, $window = 3600) {
        $limiter = new RateLimiter(Auth::userId(), $_SERVER['REMOTE_ADDR'] ?? '');
        if (!$limiter->isAllowed($limit, $window)) {
            Response::rateLimited('Too many requests. Please try again later.');
        }
    }
    
    /**
     * CSRF Protection Middleware
     */
    public static function csrf() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!Auth::verifyCsrfToken($token)) {
                Logger::security('CSRF token mismatch', Logger::ERROR);
                Response::error('CSRF token validation failed', 419);
            }
        }
    }
    
    /**
     * JSON Content-Type Middleware
     */
    public static function jsonOnly() {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && strpos($content_type, 'application/json') === false) {
            Response::error('Content-Type must be application/json', 415);
        }
    }
}
