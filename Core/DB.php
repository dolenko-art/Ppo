<?php
/**
 * Core\DB - Database Abstraction Layer
 * Підтримує: Prepared Statements, Connection Pooling, Query Logging
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

namespace Core;

class DB {
    
    private static $pdo = null;
    private static $config = [];
    private static $query_count = 0;
    private static $query_log = [];
    
    /**
     * Підключення до БД
     * @return PDO
     */
    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        
        // 🔥 Читаємо конфіг з .env або config
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: 3306;
        $name = getenv('DB_NAME') ?: 'profspilka';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            
            self::$pdo = new \PDO(
                $dsn,
                $user,
                $pass,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_PERSISTENT => false,
                    \PDO::ATTR_EMULATE_PREPARES => false, // 🔥 Force real prepared statements
                    \PDO::ATTR_TIMEOUT => 30
                ]
            );
            
            // 🔥 Set charset and timezone
            self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            self::$pdo->exec("SET time_zone = '+00:00'");
            
            return self::$pdo;
            
        } catch (\PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new \Exception("Database connection failed. Contact support.");
        }
    }
    
    /**
     * Виконати запит із параметрами
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    private static function execute($sql, $params = []) {
        try {
            $pdo = self::connect();
            $stmt = $pdo->prepare($sql);
            
            // 🔥 Bind parameters safely
            foreach ($params as $key => $value) {
                $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
                $stmt->bindValue($key + 1, $value, $paramType);
            }
            
            $stmt->execute();
            
            // 🔥 Log query (only in debug mode)
            if (getenv('DEBUG')) {
                self::$query_log[] = [
                    'sql' => $sql,
                    'params' => count($params),
                    'rows' => $stmt->rowCount(),
                    'time' => microtime(true)
                ];
            }
            
            self::$query_count++;
            return $stmt;
            
        } catch (\PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new \Exception("Database error. Please try again.");
        }
    }
    
    /**
     * Отримати один рядок
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public static function fetch($sql, $params = []) {
        $stmt = self::execute($sql, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt = null; // Close statement
        return $result ?: null;
    }
    
    /**
     * Отримати всі рядки
     * @param string $sql
     * @param array $params
     * @return array
     */
    public static function fetchAll($sql, $params = []) {
        $stmt = self::execute($sql, $params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt = null;
        return $result ?: [];
    }
    
    /**
     * Отримати одне значення
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public static function fetchColumn($sql, $params = []) {
        $stmt = self::execute($sql, $params);
        $result = $stmt->fetchColumn(0);
        $stmt = null;
        return $result !== false ? $result : null;
    }
    
    /**
     * Виконати запит (INSERT/UPDATE/DELETE)
     * @param string $sql
     * @param array $params
     * @return int Rows affected
     */
    public static function query($sql, $params = []) {
        $stmt = self::execute($sql, $params);
        $rows = $stmt->rowCount();
        $stmt = null;
        return $rows;
    }
    
    /**
     * INSERT запит
     * @param string $table
     * @param array $data
     * @return int Last insert ID
     */
    public static function insert($table, $data = []) {
        $table = preg_replace('/[^a-z0-9_]/i', '', $table); // 🔥 Prevent injection
        
        if (empty($data)) return false;
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        // 🔥 Validate column names
        foreach ($columns as $col) {
            if (!preg_match('/^[a-z0-9_]+$/i', $col)) {
                throw new \Exception("Invalid column name: $col");
            }
        }
        
        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        self::query($sql, array_values($data));
        return self::connect()->lastInsertId();
    }
    
    /**
     * Отримати кількість запитів
     * @return int
     */
    public static function getQueryCount() {
        return self::$query_count;
    }
    
    /**
     * Отримати логи запитів
     * @return array
     */
    public static function getQueryLog() {
        return self::$query_log;
    }
    
    /**
     * Закрити з'єднання
     */
    public static function close() {
        self::$pdo = null;
    }
}
