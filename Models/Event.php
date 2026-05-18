<?php
namespace Models;
use Core\DB;

class Event {
    
    // =====================================
    // 🛡️ ДОПОМІЖНИЙ МЕТОД: Точний час
    // =====================================
    private static function getKyivTime() {
        date_default_timezone_set('Europe/Kyiv');
        return date('Y-m-d H:i:s');
    }

    // ==========================================
    // 📅 ОТРИМАННЯ МАЙБУТНІХ ПОДІЙ (З ПАГІНАЦІЄЮ)
    // ==========================================
    public static function getUpcoming($ppo_id, $limit = 5, $page = 1) {
        $limit = (int)$limit;
        $page = max(1, (int)$page); 
        $offset = ($page - 1) * $limit;
        
        $now = self::getKyivTime();

        // 🔥 ВИПРАВЛЕНО: $limit та $offset винесено з тексту запиту у плейсхолдери ?
        return DB::fetchAll("
            SELECT * FROM events 
            WHERE ppo_id = ? AND event_date >= ? 
            ORDER BY event_date ASC 
            LIMIT ? OFFSET ?
        ", [(int)$ppo_id, $now, $limit, $offset]);
    }
}
