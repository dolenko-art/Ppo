<?php
namespace Core;

class Config {
    
    /**
     * Завантаження та парсинг .env файлу
     */
    public static function load() {
        $path = __DIR__ . '/../../.env'; // Шлях до .env
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ігноруємо коментарі та порожні рядки
            if (empty($line) || strpos($line, '#') === 0) {
                continue; 
            }
            
            // Надійний парсинг через регулярний вираз (вимога аудитора №2)
            if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                $name = trim($matches[1]);
                $value = trim($matches[2]);

                // Видаляємо одинарні або подвійні лапки по краях значення
                if (preg_match('/^([\'"])(.*)\1$/', $value, $quoteMatches)) {
                    $value = $quoteMatches[2];
                }

                // Зберігаємо строго в межах поточного запиту (вимога аудитора №1)
                // Видалено небезпечний putenv() для Thread Safety
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Отримання значення змінної оточення
     */
    public static function get($key, $default = null) {
        // Шукаємо у масивах поточного запиту або системному оточенні
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value !== false && $value !== null ? $value : $default;
    }
}
