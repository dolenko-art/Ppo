<?php
namespace Models;
use Core\DB;

class Union {
    
    // =====================================
    // 🛡️ ДОПОМІЖНИЙ МЕТОД: Точний час
    // =====================================
    private static function getKyivTime() {
        date_default_timezone_set('Europe/Kyiv');
        return date('Y-m-d H:i:s');
    }

    // =====================================
    // 👥 ЗАГАЛЬНЕ: Кворум ППО та Бейджі
    // =====================================
    public static function getMemberCount($ppo_id) {
        return (int)DB::fetchColumn("
            SELECT COUNT(*) FROM users 
            WHERE ppo_id = ? 
            AND status IN ('member','admin') 
            AND (status = 'admin' OR joined_at <= DATE_SUB(?, INTERVAL 3 DAY))
        ", [(int)$ppo_id, self::getKyivTime()]) ?: 1;
    }

    public static function getBadges($ppo_id, $user_id) {
        $p = (int)$ppo_id;
        $u = (int)$user_id;
        $now = self::getKyivTime();

        return [
            'polls'  => (int)DB::fetchColumn("SELECT COUNT(*) FROM polls p WHERE ppo_id=? AND end_date>=? AND is_active=1 AND NOT EXISTS(SELECT 1 FROM votes v WHERE v.poll_id=p.id AND v.user_id=?) AND NOT EXISTS(SELECT 1 FROM e2e_voters ev WHERE ev.target_id=p.id AND ev.target_type='poll' AND ev.user_id=?)", [$p, $now, $u, $u]),
            'noms'   => (int)DB::fetchColumn("SELECT COUNT(*) FROM nominations n WHERE ppo_id=? AND end_date>=? AND is_processed=0 AND NOT EXISTS(SELECT 1 FROM nomination_votes nv WHERE nv.nomination_id=n.id AND nv.nominator_id=?) AND NOT EXISTS(SELECT 1 FROM e2e_voters ev WHERE ev.target_id=n.id AND ev.target_type='nomination' AND ev.user_id=?)", [$p, $now, $u, $u]),
            'pets'   => (int)DB::fetchColumn("SELECT COUNT(*) FROM petitions p WHERE ppo_id=? AND status=0 AND NOT EXISTS(SELECT 1 FROM petition_signatures ps WHERE ps.petition_id=p.id AND ps.user_id=?)", [$p, $u]),
            'events' => (int)DB::fetchColumn("SELECT COUNT(*) FROM events e WHERE ppo_id=? AND event_date>=? AND NOT EXISTS(SELECT 1 FROM event_enrollments ee WHERE ee.event_id=e.id AND ee.user_id=?)", [$p, $now, $u])
        ];
    }

    // =====================================
    // 📰 НОВИНИ
    // =====================================
    public static function getNewsCount($ppo_id) { 
        return (int)DB::fetchColumn("SELECT COUNT(*) FROM news WHERE ppo_id = ?", [(int)$ppo_id]); 
    }
    
    public static function getNews($ppo_id, $limit, $page) {
        $limit = (int)$limit; 
        $offset = (max(1, (int)$page) - 1) * $limit;
        
        // Повна параметризація лімітів
        return DB::fetchAll("SELECT * FROM news WHERE ppo_id = ? ORDER BY id DESC LIMIT ? OFFSET ?", [
            (int)$ppo_id, 
            $limit, 
            $offset
        ]);
    }

    // =====================================
    // 📊 ГОЛОСУВАННЯ 
    // =====================================
    public static function getPollsCount($ppo_id, $user_id, $filter = 'action') {
        $params = [(int)$ppo_id];
        $where = "ppo_id = ?";
        $now = self::getKyivTime();

        if ($filter === 'action') {
            $where .= " AND end_date > ? AND is_active = 1 AND NOT EXISTS (SELECT 1 FROM votes v WHERE v.poll_id = polls.id AND v.user_id = ?) AND NOT EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = polls.id AND ev.target_type = 'poll' AND ev.user_id = ?)";
            array_push($params, $now, (int)$user_id, (int)$user_id);
        } elseif ($filter === 'ongoing') {
            $where .= " AND end_date > ? AND is_active = 1 AND (EXISTS (SELECT 1 FROM votes v WHERE v.poll_id = polls.id AND v.user_id = ?) OR EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = polls.id AND ev.target_type = 'poll' AND ev.user_id = ?))";
            array_push($params, $now, (int)$user_id, (int)$user_id);
        } else {
            $where .= " AND (end_date <= ? OR is_active = 0)";
            array_push($params, $now);
        }

        return (int)DB::fetchColumn("SELECT COUNT(*) FROM polls WHERE $where", $params);
    }

    public static function getPolls($ppo_id, $user_id, $limit, $page = 1, $filter = 'action') {
        $limit = (int)$limit; 
        $offset = (max(1, (int)$page) - 1) * $limit;
        $now = self::getKyivTime();
        
        $selectParams = [$now, (int)$user_id, (int)$user_id];
        $whereParams = [(int)$ppo_id];
        $where = "p.ppo_id = ?";

        if ($filter === 'action') {
            $where .= " AND p.end_date > ? AND p.is_active = 1 AND NOT EXISTS (SELECT 1 FROM votes v WHERE v.poll_id = p.id AND v.user_id = ?) AND NOT EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = p.id AND ev.target_type = 'poll' AND ev.user_id = ?)";
            array_push($whereParams, $now, (int)$user_id, (int)$user_id);
            $order = "ORDER BY p.end_date ASC"; 
        } elseif ($filter === 'ongoing') {
            $where .= " AND p.end_date > ? AND p.is_active = 1 AND (EXISTS (SELECT 1 FROM votes v WHERE v.poll_id = p.id AND v.user_id = ?) OR EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = p.id AND ev.target_type = 'poll' AND ev.user_id = ?))";
            array_push($whereParams, $now, (int)$user_id, (int)$user_id);
            $order = "ORDER BY p.end_date ASC";
        } else {
            $where .= " AND (p.end_date <= ? OR p.is_active = 0)";
            array_push($whereParams, $now);
            $order = "ORDER BY p.end_date DESC"; 
        }

        // Зливаємо параметри та додаємо ліміти в самий кінець
        $params = array_merge($selectParams, $whereParams);
        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll("
            SELECT p.*, 
                   IF(p.is_secret = 2 AND p.end_date > ?, 0, (SELECT COUNT(*) FROM votes v_all WHERE v_all.poll_id = p.id)) as current_votes,
                   ((SELECT COUNT(*) FROM votes v_my WHERE v_my.poll_id = p.id AND v_my.user_id = ?) + (SELECT COUNT(*) FROM e2e_voters ev WHERE ev.target_id = p.id AND ev.target_type = 'poll' AND ev.user_id = ?)) as has_voted
            FROM polls p 
            WHERE $where 
            $order 
            LIMIT ? OFFSET ?
        ", $params);
    }

    // =====================================
    // 🏆 ВИСУВАННЯ 
    // =====================================
    public static function getNomsCount($ppo_id, $user_id, $filter = 'action') {
        $params = [(int)$ppo_id];
        $where = "ppo_id = ?";
        $now = self::getKyivTime();

        if ($filter === 'action') {
            $where .= " AND end_date > ? AND is_processed = 0 AND NOT EXISTS (SELECT 1 FROM nomination_votes nv WHERE nv.nomination_id = nominations.id AND nv.nominator_id = ?) AND NOT EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = nominations.id AND ev.target_type = 'nomination' AND ev.user_id = ?)";
            array_push($params, $now, (int)$user_id, (int)$user_id);
        } elseif ($filter === 'ongoing') {
            $where .= " AND end_date > ? AND is_processed = 0 AND (EXISTS (SELECT 1 FROM nomination_votes nv WHERE nv.nomination_id = nominations.id AND nv.nominator_id = ?) OR EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = nominations.id AND ev.target_type = 'nomination' AND ev.user_id = ?))";
            array_push($params, $now, (int)$user_id, (int)$user_id);
        } else {
            $where .= " AND (end_date <= ? OR is_processed = 1)";
            array_push($params, $now);
        }

        return (int)DB::fetchColumn("SELECT COUNT(*) FROM nominations WHERE $where", $params);
    }

    public static function getNominations($ppo_id, $user_id, $limit, $page = 1, $filter = 'action') {
        $limit = (int)$limit; 
        $offset = (max(1, (int)$page) - 1) * $limit;
        $now = self::getKyivTime();

        $selectParams = [$now, (int)$user_id, (int)$user_id];
        $whereParams = [(int)$ppo_id];
        $where = "n.ppo_id = ?";

        if ($filter === 'action') {
            $where .= " AND n.end_date > ? AND n.is_processed = 0 AND NOT EXISTS (SELECT 1 FROM nomination_votes nv WHERE nv.nomination_id = n.id AND nv.nominator_id = ?) AND NOT EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = n.id AND ev.target_type = 'nomination' AND ev.user_id = ?)";
            array_push($whereParams, $now, (int)$user_id, (int)$user_id);
            $order = "ORDER BY n.end_date ASC";
        } elseif ($filter === 'ongoing') {
            $where .= " AND n.end_date > ? AND n.is_processed = 0 AND (EXISTS (SELECT 1 FROM nomination_votes nv WHERE nv.nomination_id = n.id AND nv.nominator_id = ?) OR EXISTS (SELECT 1 FROM e2e_voters ev WHERE ev.target_id = n.id AND ev.target_type = 'nomination' AND ev.user_id = ?))";
            array_push($whereParams, $now, (int)$user_id, (int)$user_id);
            $order = "ORDER BY n.end_date ASC";
        } else {
            $where .= " AND (n.end_date <= ? OR n.is_processed = 1)";
            array_push($whereParams, $now);
            $order = "ORDER BY n.end_date DESC";
        }

        // Зливаємо параметри та додаємо ліміти
        $params = array_merge($selectParams, $whereParams);
        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll("
            SELECT n.*, 
                   IF(n.is_secret = 2 AND n.end_date > ?, 0, (SELECT COUNT(*) FROM nomination_votes nv_all WHERE nv_all.nomination_id = n.id)) as current_votes,
                   ((SELECT COUNT(*) FROM nomination_votes nv_my WHERE nv_my.nomination_id = n.id AND nv_my.nominator_id = ?) + (SELECT COUNT(*) FROM e2e_voters ev WHERE ev.target_id = n.id AND ev.target_type = 'nomination' AND ev.user_id = ?)) as has_voted
            FROM nominations n 
            WHERE $where 
            $order 
            LIMIT ? OFFSET ?
        ", $params);
    }

    // =====================================
    // 📣 ПЕТИЦІЇ 
    // =====================================
    public static function getPetsCount($ppo_id, $user_id, $filter = 'action') {
        $params = [(int)$ppo_id];
        $where = "ppo_id = ?";

        if ($filter === 'action') {
            $where .= " AND status = 0 AND NOT EXISTS (SELECT 1 FROM petition_signatures ps WHERE ps.petition_id = petitions.id AND ps.user_id = ?)";
            array_push($params, (int)$user_id);
        } elseif ($filter === 'ongoing') {
            $where .= " AND status = 0 AND EXISTS (SELECT 1 FROM petition_signatures ps WHERE ps.petition_id = petitions.id AND ps.user_id = ?)";
            array_push($params, (int)$user_id);
        } else {
            $where .= " AND status != 0";
        }

        return (int)DB::fetchColumn("SELECT COUNT(*) FROM petitions WHERE $where", $params);
    }

    public static function getPetitions($ppo_id, $user_id, $limit, $page = 1, $filter = 'action') {
        $limit = (int)$limit; 
        $offset = (max(1, (int)$page) - 1) * $limit;
        
        $selectParams = [(int)$user_id]; 
        $whereParams = [(int)$ppo_id];
        $where = "p.ppo_id = ?";

        if ($filter === 'action') {
            $where .= " AND p.status = 0 AND NOT EXISTS (SELECT 1 FROM petition_signatures ps WHERE ps.petition_id = p.id AND ps.user_id = ?)";
            array_push($whereParams, (int)$user_id);
            $order = "ORDER BY p.id DESC";
        } elseif ($filter === 'ongoing') {
            $where .= " AND p.status = 0 AND EXISTS (SELECT 1 FROM petition_signatures ps WHERE ps.petition_id = p.id AND ps.user_id = ?)";
            array_push($whereParams, (int)$user_id);
            $order = "ORDER BY p.id DESC";
        } else {
            $where .= " AND p.status != 0";
            $order = "ORDER BY p.status ASC, p.id DESC"; 
        }

        // Зливаємо параметри та додаємо ліміти
        $params = array_merge($selectParams, $whereParams);
        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll("
            SELECT p.*, u.full_name as author_name, 
                   (SELECT COUNT(*) FROM petition_signatures ps_all WHERE ps_all.petition_id = p.id) as current_signs,
                   (SELECT COUNT(*) FROM petition_signatures ps_my WHERE ps_my.petition_id = p.id AND ps_my.user_id = ?) as has_voted
            FROM petitions p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE $where 
            $order 
            LIMIT ? OFFSET ?
        ", $params);
    }

    // =====================================
    // 📅 ПОДІЇ
    // =====================================
    public static function getEventsCount($ppo_id) { 
        return (int)DB::fetchColumn("SELECT COUNT(*) FROM events WHERE ppo_id = ?", [(int)$ppo_id]); 
    }
    
    public static function getEvents($ppo_id, $limit, $page) {
        $limit = (int)$limit; 
        $offset = (max(1, (int)$page) - 1) * $limit;
        
        return DB::fetchAll("
            SELECT e.*, (SELECT IFNULL(SUM(1+guests_count),0) FROM event_enrollments ee WHERE ee.event_id = e.id) as enrolled_count
            FROM events e 
            WHERE ppo_id = ? 
            ORDER BY e.event_date DESC 
            LIMIT ? OFFSET ?
        ", [(int)$ppo_id, $limit, $offset]);
    }
}
