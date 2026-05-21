<?php
/**
 * Core\Validator - Input Validation Service
 * Віртуальний валідатор для всіх input даних
 * ВЕРСІЯ: 2.0
 */

namespace Core;

class Validator {
    
    private static $errors = [];
    
    /**
     * Валідувати email
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Валідувати URL
     */
    public static function url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Валідувати IP
     */
    public static function ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Валідувати telefon (10-15 digits)
     */
    public static function phone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
    
    /**
     * Валідувати пароль (min 6 chars, max 72 chars)
     */
    public static function password($password) {
        return strlen($password) >= 6 && strlen($password) <= 72;
    }
    
    /**
     * Валідувати Unicode text
     */
    public static function text($text, $min = 1, $max = 1000) {
        $len = mb_strlen($text, 'UTF-8');
        return $len >= $min && $len <= $max;
    }
    
    /**
     * Валідувати integer
     */
    public static function integer($value, $min = PHP_INT_MIN, $max = PHP_INT_MAX) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value !== false && $value >= $min && $value <= $max;
    }
    
    /**
     * Валідувати date (YYYY-MM-DD)
     */
    public static function date($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Валідувати datetime (YYYY-MM-DD HH:MM:SS)
     */
    public static function datetime($datetime) {
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }
    
    /**
     * Валідувати array значення в списку
     */
    public static function inArray($value, $allowed = []) {
        return in_array($value, $allowed, true);
    }
    
    /**
     * Валідувати регулярним виразом
     */
    public static function regex($value, $pattern) {
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Валідувати, що значення унікальне в БД
     */
    public static function unique($table, $column, $value, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $count = (int)DB::fetchColumn($sql, $params);
        return $count === 0;
    }
    
    /**
     * Валідувати, що значення існує в БД
     */
    public static function exists($table, $column, $value) {
        $count = (int)DB::fetchColumn("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$value]);
        return $count > 0;
    }
    
    /**
     * Batch validation
     */
    public static function validate($data, $rules) {
        self::$errors = [];
        
        foreach ($rules as $field => $rule_list) {
            $value = $data[$field] ?? null;
            
            foreach (explode('|', $rule_list) as $rule) {
                if (strpos($rule, ':') !== false) {
                    list($method, $params) = explode(':', $rule, 2);
                    $params = explode(',', $params);
                } else {
                    $method = $rule;
                    $params = [];
                }
                
                // Дозвіл на empty значення для optional полів
                if ($method === 'nullable' && empty($value)) {
                    continue;
                }
                
                // Обов'язковість
                if ($method === 'required' && empty($value)) {
                    self::$errors[$field] = "Field $field is required";
                    break;
                }
                
                if (empty($value) && $method !== 'nullable' && $method !== 'required') {
                    continue; // Skip validation if empty
                }
                
                // Вызов валідатора
                if (method_exists(self::class, $method)) {
                    if (empty($params)) {
                        $valid = self::$method($value);
                    } else {
                        $valid = self::$method($value, ...$params);
                    }
                    
                    if (!$valid) {
                        self::$errors[$field] = "Field $field failed validation: $method";
                        break;
                    }
                }
            }
        }
        
        return empty(self::$errors);
    }
    
    /**
     * Отримати помилки валідації
     */
    public static function errors() {
        return self::$errors;
    }
    
    /**
     * Отримати першу помилку
     */
    public static function firstError() {
        return reset(self::$errors) ?: null;
    }
}
