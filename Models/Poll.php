<?php
namespace Models;
use Core\DB;

class Poll {

    // =====================================
    // 🛡️ ДОПОМІЖНИЙ МЕТОД: Точний час
    // =====================================
    private static function getKyivTime() {
        date_default_timezone_set('Europe/Kyiv');
        return date('Y-m-d H:i:s');
    }

    // ==========================================
    // 🗳️ ОТРИМАННЯ АКТИВНИХ ГОЛОСУВАНЬ (З ПАГІНАЦІЄЮ)
    // ==========================================
    public static function getActive($ppo_id, $limit = 5, $page = 1) {
        $limit = (int)$limit;
        $page = max(1, (int)$page); 
        $offset = ($page - 1) * $limit;
        $now = self::getKyivTime();

        // 🔥 ВИПРАВЛЕНО: $limit та $offset передаються виключно через плейсхолдери ?
        // Додано перевірку end_date, щоб не виводити закриті, але ще не оброблені кроном опитування
        return DB::fetchAll("
            SELECT * FROM polls 
            WHERE ppo_id = ? AND is_active = 1 AND end_date > ?
            ORDER BY end_date DESC 
            LIMIT ? OFFSET ?
        ", [(int)$ppo_id, $now, $limit, $offset]);
    }

    // ==========================================
    // 🔴 ПІДРАХУНОК БЕЙДЖИКА (СКІЛЬКИ ГОЛОСУВАНЬ ПРОПУСТИВ)
    // ==========================================
    public static function getBadgeCount($ppo_id, $user_id) {
        $now = self::getKyivTime();

        // 🔥 ВИПРАВЛЕНО: Додано умову AND end_date > ? для захисту UX
        return (int)DB::fetchColumn("
            SELECT COUNT(*) FROM polls p 
            WHERE ppo_id = ? AND is_active = 1 AND end_date > ?
            AND NOT EXISTS (SELECT 1 FROM votes v WHERE v.poll_id = p.id AND v.user_id = ?)
            AND NOT EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = p.id AND ev.target_type = 'poll' AND ev.user_id = ?)
        ", [(int)$ppo_id, $now, (int)$user_id, (int)$user_id]);
    }
}
