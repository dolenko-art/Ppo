<?php
/**
 * Core\Response - JSON Response Helper
 * Стандартизирована відповідь API
 * ВЕРСІЯ: 2.0
 */

namespace Core;

class Response {
    
    /**
     * Успішна відповідь
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * Помилка
     */
    public static function error($message = 'Error', $code = 400, $data = null) {
        return self::json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * 401 Unauthorized
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 401);
    }
    
    /**
     * 403 Forbidden
     */
    public static function forbidden($message = 'Access Denied') {
        return self::error($message, 403);
    }
    
    /**
     * 404 Not Found
     */
    public static function notFound($message = 'Not Found') {
        return self::error($message, 404);
    }
    
    /**
     * 429 Too Many Requests
     */
    public static function rateLimited($message = 'Too Many Requests') {
        return self::error($message, 429);
    }
    
    /**
     * 500 Server Error
     */
    public static function serverError($message = 'Internal Server Error') {
        return self::error($message, 500);
    }
    
    /**
     * Validation Errors
     */
    public static function validationError($errors = []) {
        return self::json([
            'success' => false,
            'message' => 'Validation Failed',
            'errors' => $errors
        ], 422);
    }
    
    /**
     * JSON відповідь з HTTP кодом
     */
    private static function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
