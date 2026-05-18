<?php
namespace Models;
use Core\DB;

class Nomination {
    
    // =====================================
    // 🛡️ ДОПОМІЖНИЙ МЕТОД: Точний час
    // =====================================
    private static function getKyivTime() {
        date_default_timezone_set('Europe/Kyiv');
        return date('Y-m-d H:i:s');
    }

    // ==========================================
    // 🎯 ОТРИМАННЯ АКТИВНИХ ВИСУВАНЬ (З ПАГІНАЦІЄЮ)
    // ==========================================
    public static function getList($ppo_id, $limit = 5, $page = 1) {
        $limit = (int)$limit;
        $page = max(1, (int)$page); 
        $offset = ($page - 1) * $limit;
        $now = self::getKyivTime();

        // 🔥 ВИПРАВЛЕНО: $limit та $offset передаються виключно через плейсхолдери ?
        // Додано перевірку end_date, щоб уникнути фантомних (прострочених) висувань
        return DB::fetchAll("
            SELECT * FROM nominations 
            WHERE ppo_id = ? AND is_processed = 0 AND end_date > ?
            ORDER BY id DESC 
            LIMIT ? OFFSET ?
        ", [(int)$ppo_id, $now, $limit, $offset]);
    }
}
