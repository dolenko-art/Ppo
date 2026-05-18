<?php
namespace Models;
use Core\DB;

class News {
    
    // ==========================================
    // 📢 ОТРИМАННЯ НОВИН (З ПАГІНАЦІЄЮ)
    // ==========================================
    public static function getList($ppo_id, $limit = 5, $page = 1) {
        $limit = (int)$limit;
        $page = max(1, (int)$page); // Сторінка мінімум 1, захист від від'ємного OFFSET
        $offset = ($page - 1) * $limit;

        // 🔥 ВИПРАВЛЕНО: $limit та $offset винесено з тексту запиту у плейсхолдери
        return DB::fetchAll("
            SELECT * FROM news 
            WHERE ppo_id = ? 
            ORDER BY id DESC 
            LIMIT ? OFFSET ?
        ", [(int)$ppo_id, $limit, $offset]);
    }

    // ==========================================
    // 🔢 ПІДРАХУНОК ЗАГАЛЬНОЇ КІЛЬКОСТІ НОВИН
    // ==========================================
    public static function count($ppo_id) {
        // Завжди повертаємо ціле число
        return (int)DB::fetchColumn("
            SELECT COUNT(*) FROM news 
            WHERE ppo_id = ?
        ", [(int)$ppo_id]);
    }
}
