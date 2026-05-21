<?php
/**
 * Core\Logger - Logging Service
 * Простий та потужний логер для security подій
 * ВЕРСІЯ: 2.0
 */

namespace Core;

class Logger {
    
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    
    private static $log_file = null;
    private static $levels = ['CRITICAL' => 0, 'ERROR' => 1, 'WARNING' => 2, 'INFO' => 3, 'DEBUG' => 4];
    
    /**
     * Ініціалізація логера
     */
    public static function init($log_file = null) {
        if ($log_file) {
            self::$log_file = $log_file;
        } else {
            self::$log_file = getenv('LOG_PATH') ?: dirname(__FILE__) . '/../logs/error.log';
        }
        
        // Переконатись, що директорія існує
        $log_dir = dirname(self::$log_file);
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0750, true);
        }
    }
    
    /**
     * Логувати критичну проблему
     */
    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Логувати помилку
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Логувати попередження
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Логувати інформацію
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Логувати debug інформацію
     */
    public static function debug($message, $context = []) {
        if (!getenv('DEBUG')) return; // Не логуємо в production
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Логувати security подію
     */
    public static function security($event, $level = self::WARNING, $context = []) {
        $context['event'] = $event;
        $context['user_id'] = $_SESSION['user_id'] ?? null;
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100);
        
        self::log($level, "[SECURITY] {$event}", $context);
    }
    
    /**
     * Основна функція логування
     */
    private static function log($level, $message, $context = []) {
        if (!self::$log_file) {
            self::init();
        }
        
        $min_level = getenv('LOG_LEVEL') ?: self::INFO;
        if (self::$levels[$level] > self::$levels[$min_level]) {
            return; // Не логуємо нижчі рівні
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $log_entry = "[{$timestamp}] [{$level}] {$message}{$context_str}\n";
        
        // Логуємо через error_log (system) або у файл
        error_log($log_entry, 3, self::$log_file);
    }
    
    /**
     * Отримати останні N логів
     */
    public static function getLogs($limit = 100) {
        if (!self::$log_file || !file_exists(self::$log_file)) {
            return [];
        }
        
        $lines = file(self::$log_file);
        return array_slice($lines, -$limit);
    }
    
    /**
     * Очистити старі логи (> N днів)
     */
    public static function cleanup($days = 30) {
        if (!self::$log_file || !file_exists(self::$log_file)) {
            return false;
        }
        
        $cutoff = time() - ($days * 86400);
        $lines = file(self::$log_file);
        $new_lines = [];
        
        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $line_time = strtotime($matches[1]);
                if ($line_time > $cutoff) {
                    $new_lines[] = $line;
                }
            }
        }
        
        return file_put_contents(self::$log_file, implode('', $new_lines), LOCK_EX);
    }
}
