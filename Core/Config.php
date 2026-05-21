<?php
/**
 * Core\Config - Configuration Management
 * Читає налаштування з .env файлу
 * ВЕРСІЯ: 2.0
 */

namespace Core;

class Config {
    
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Завантажити конфіг з .env
     */
    public static function load() {
        if (self::$loaded) return;
        
        $env_file = __DIR__ . '/../.env';
        
        if (!file_exists($env_file)) {
            error_log("WARNING: .env file not found");
            self::$loaded = true;
            return;
        }
        
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) continue;
            
            if (strpos($line, '=') === false) continue;
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            if (in_array($value[0] ?? '', ['"', "'"])) {
                $value = substr($value, 1, -1);
            }
            
            self::$config[$key] = $value;
            putenv("{$key}={$value}");
        }
        
        self::$loaded = true;
    }
    
    /**
     * Отримати значення конфігу
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Встановити значення конфігу (runtime only)
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
        putenv("{$key}={$value}");
    }
    
    /**
     * Отримати весь конфіг
     * @return array
     */
    public static function all() {
        self::load();
        return self::$config;
    }
}
