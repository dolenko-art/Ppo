<?php
declare(strict_types=1);

namespace Controllers;

// 🛡️ Безпечне підключення сервісу (якщо файл існує)
$voting_service_path = __DIR__ . '/../Services/VotingService.php';
if (file_exists($voting_service_path)) {
    require_once $voting_service_path;
}

class HomeController {

    public static function index() {
        if (file_exists('db.php')) {
            require_once 'db.php';
        }

        if (!\Core\Auth::loggedIn()) { header("Location: login.php"); exit; }

        $ppo_id  = (int)($_SESSION['ppo_id'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        // ==========================================
        // ⚙️ АВТОМАТИЗАЦІЯ ППО (Захищено від падінь)
        // ==========================================
        try {
            if (class_exists('\Services\VotingService')) {
                \Services\VotingService::checkExpiredNominations();
                \Services\VotingService::processSystemPolls($ppo_id); 
            }
        } catch (\Throwable $e) {
            error_log("Automation Error in HomeController: " . $e->getMessage());
        }

        // Дані користувача
        $user = \Core\DB::fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user) { \Core\Auth::logout(); header("Location: login.php"); exit; }

        $user_status = (string)($user['status'] ?? 'pending');
        $user_role   = (string)($user['role'] ?? '');

        // 👑 Права доступу
        $is_admin    = ($user_status === 'admin' || in_array($user_role, ['admin', 'head', 'manager'], true));
        $is_auditor_role = ($user_role === 'auditor');
        $joined_at   = $user['joined_at'] ?? date('Y-m-d H:i:s');
        $is_newbee   = ($user_status === 'member' && !$is_admin && (strtotime($joined_at) > strtotime('-3 days')));
        $can_act     = ($user_status === 'member' || $is_admin) && !$is_newbee;

        $has_admission_poll = \Core\DB::fetchColumn("SELECT 1 FROM polls WHERE action_type='admission' AND target_user_id=? AND is_processed=0", [$user_id]);

        $getThreshold = function($total, $type) {
            $total = (int)$total;
            switch((string)$type) {
                case '2/3': return ceil($total * (2/3));
                case '70':  return ceil($total * 0.70);
                case '75':  return ceil($total * 0.75);
                case '100': return $total;
                default:    return floor($total / 2) + 1;
            }
        };

        // Загальні дані ППО
        $ppo_name = \Core\DB::fetchColumn("SELECT name FROM ppos WHERE id = ?", [$ppo_id]) ?: 'Профспілка';
        $total_members = (int)(\Models\Union::getMemberCount($ppo_id) ?? 0);
        $badges = \Models\Union::getBadges($ppo_id, $user_id) ?: [];
        $fin    = \Models\Finance::getStats($ppo_id) ?: ['total'=>0, 'cash_balance'=>0, 'accounts'=>[]];
        $unread_notifs_count = (int)\Core\DB::fetchColumn("SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0", [$user_id]);

        // 👥 ФІКС 1: Захист від foreach(false)
        $raw_members = \Core\DB::fetchAll("SELECT id, full_name, status, joined_at FROM users WHERE ppo_id=? AND status IN ('member', 'admin') AND id != ?", [$ppo_id, $user_id]);
        $active_members_list = [];
        
        if (!empty($raw_members) && is_array($raw_members)) {
            $three_days_ago = strtotime('-3 days');
            $has_security = class_exists('\Core\Security');
            foreach ($raw_members as $u) {
                if ($u['status'] !== 'admin' && (empty($u['joined_at']) || strtotime($u['joined_at']) > $three_days_ago)) continue;
                $decrypted_name = $has_security ? \Core\Security::decrypt($u['full_name']) : '';
                $active_members_list[] = [
                    'id' => (int)$u['id'],
                    'full_name' => $decrypted_name ?: $u['full_name']
                ];
            }
        }

        // ПАГІНАЦІЯ
        $limit = 10;
        $p_news = max(1, (int)($_GET['p_n'] ?? 1));
        $p_poll = max(1, (int)($_GET['p_p'] ?? 1));
        $p_nom  = max(1, (int)($_GET['p_v'] ?? 1));
        $p_pet  = max(1, (int)($_GET['p_pet'] ?? 1));
        $p_evt  = max(1, (int)($_GET['p_e'] ?? 1));

        $sub_poll = $_GET['sub_poll'] ?? 'action'; 
        $sub_nom  = $_GET['sub_nom']  ?? 'action';
        $sub_pet  = $_GET['sub_pet']  ?? 'action';

        // 🔥 ФІКС 2: Захищаємо масиви від null (що ламає htmlspecialchars у View)
        $news  = \Models\Union::getNews($ppo_id, $limit, $p_news) ?: []; 
        $news_count = (int)(\Models\Union::getNewsCount($ppo_id) ?? 0);
        if (!empty($news)) {
            foreach ($news as &$n) {
                $n['content'] = $n['content'] ?? '';
                $n['title'] = $n['title'] ?? 'Новина';
            } unset($n);
        }

        // 🔥 ФІКС 3: ПІДГОТОВКА ДАНИХ ГОЛОСУВАНЬ (Замість N+1 в шаблоні)
        $polls = \Models\Union::getPolls($ppo_id, $user_id, $limit, $p_poll, $sub_poll) ?: [];
        $polls_count = (int)(\Models\Union::getPollsCount($ppo_id, $user_id, $sub_poll) ?? 0);

        if (!empty($polls)) {
            $poll_ids = array_column($polls, 'id');
            $ids_placeholder = implode(',', array_fill(0, count($poll_ids), '?'));
            
            $all_options = \Core\DB::fetchAll("SELECT * FROM poll_options WHERE poll_id IN ($ids_placeholder)", $poll_ids) ?: [];
            $my_votes = \Core\DB::fetchAll("SELECT poll_id FROM votes WHERE user_id = ? AND poll_id IN ($ids_placeholder)", array_merge([$user_id], $poll_ids)) ?: [];
            $my_voted_ids = array_column($my_votes, 'poll_id');
            
            $my_e2e_votes = \Core\DB::fetchAll("SELECT target_id FROM e2e_voters WHERE target_type='poll' AND user_id = ? AND target_id IN ($ids_placeholder)", array_merge([$user_id], $poll_ids)) ?: [];
            $my_e2e_ids = array_column($my_e2e_votes, 'target_id');

            foreach ($polls as &$p) {
                $p['options'] = array_filter($all_options, fn($o) => $o['poll_id'] == $p['id']);
                $p['has_voted'] = in_array($p['id'], $my_voted_ids) || in_array($p['id'], $my_e2e_ids);
                $p['description'] = $p['description'] ?? '';
                $p['title'] = $p['title'] ?? 'Голосування';
            } unset($p);
        }

        $noms  = \Models\Union::getNominations($ppo_id, $user_id, $limit, $p_nom, $sub_nom) ?: []; 
        $noms_count = (int)(\Models\Union::getNomsCount($ppo_id, $user_id, $sub_nom) ?? 0);
        if (!empty($noms)) {
            foreach ($noms as &$nm) {
                $nm['description'] = $nm['description'] ?? '';
                $nm['title'] = $nm['title'] ?? 'Висування';
            } unset($nm);
        }

        $pets  = \Models\Union::getPetitions($ppo_id, $user_id, $limit, $p_pet, $sub_pet) ?: [];   
        $pets_count = (int)(\Models\Union::getPetsCount($ppo_id, $user_id, $sub_pet) ?? 0);
        if (!empty($pets)) {
            foreach ($pets as &$pt) {
                $pt['description'] = $pt['description'] ?? '';
            } unset($pt);
        }

        $events = \Models\Union::getEvents($ppo_id, $limit, $p_evt) ?: [];      
        $events_count = (int)(\Models\Union::getEventsCount($ppo_id) ?? 0);
        if (!empty($events)) {
            foreach ($events as &$ev) {
                $ev['description'] = $ev['description'] ?? '';
            } unset($ev);
        }

        // ==========================================
        // 🔥 ПІДКЛЮЧЕННЯ ЧЕРЕЗ LAYOUT
        // ==========================================
        $title = 'ACTION: Прозора Спілка';
        $header_title = 'Профспілка';
        $view_path = 'index_view.php';
        
        require_once __DIR__ . '/../views/layout.php';
    }
}
