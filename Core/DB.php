<?php
namespace Core;

use PDO;
use PDOException;

class DB {
    private static $instance = null;

    // Отримання підключення (Singleton)
    public static function connect() {
        if (self::$instance === null) {
            $host = Config::get('DB_HOST');
            $db   = Config::get('DB_NAME');
            $user = Config::get('DB_USER');
            $pass = Config::get('DB_PASS');
            
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // 🛡️ ЗАХИСТ OWASP: Приховуємо реальну помилку (щоб не "світити" паролі/хости в браузер)
                // Записуємо деталі тільки у закритий лог-файл сервера
                error_log("КРИТИЧНА ПОМИЛКА БД: " . $e->getMessage());
                die("Системна помилка підключення до бази даних. Спробуйте пізніше.");
            }
        }
        return self::$instance;
    }

    // Виконання будь-якого запиту з параметрами (повертає об'єкт PDOStatement)
    public static function query($sql, $params = []) {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // ==========================================
    // МЕТОДИ ДЛЯ INSERT, UPDATE, DELETE
    // ==========================================
    // Виконання запиту без отримання даних (повертає кількість змінених рядків)
    public static function execute($sql, $params = []) {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Отримати один рядок (масив)
    public static function fetch($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }

    // Отримати всі рядки (масив масивів)
    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    // Отримати одне значення
    public static function fetchColumn($sql, $params = []) {
        return self::query($sql, $params)->fetchColumn();
    }

    // ==========================================
    // ПРОФЕСІЙНА РОБОТА З БД (Транзакції)
    // ==========================================
    public static function lastInsertId() {
        return self::connect()->lastInsertId();
    }

    public static function beginTransaction() {
        return self::connect()->beginTransaction();
    }

    public static function commit() {
        return self::connect()->commit();
    }

    public static function rollBack() {
        return self::connect()->rollBack();
    }
    // ==========================================

    // Швидка вставка даних
    public static function insert($table, $data) {
        $keys = array_keys($data);
        // Загортаємо назви полів у зворотні апострофи ` ` для безпеки
        $fields = implode(", ", array_map(function($key) { return "`$key`"; }, $keys));
        $placeholders = ":" . implode(", :", $keys);
        
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        self::query($sql, $data);
        return self::connect()->lastInsertId();
    }

    // 🛡️ БЕЗПЕЧНЕ ТА ШВИДКЕ ОНОВЛЕННЯ ДАНИХ (Виправлено баг колізії ключів)
    public static function update($table, $data, $where, $whereParams = []) {
        $set = "";
        $executeParams = [];
        
        // 1. Префікси для значень SET (захист від конфлікту імен :status vs :status)
        foreach ($data as $key => $val) {
            $set .= "`$key` = :set_$key, ";
            $executeParams["set_$key"] = $val;
        }
        $set = rtrim($set, ", ");
        
        $sql = "UPDATE `$table` SET $set WHERE $where";
        
        // 2. Додаємо параметри умови WHERE у загальний масив
        foreach ($whereParams as $key => $val) {
            $executeParams[$key] = $val;
        }
        
        // 3. Виконуємо чистий нативний execute (без ручних циклів)
        $stmt = self::connect()->prepare($sql);
        return $stmt->execute($executeParams);
    }
}
