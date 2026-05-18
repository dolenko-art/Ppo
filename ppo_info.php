<?php
require_once 'db.php';

if (!\Core\Auth::loggedIn()) { 
    header("Location: login.php"); 
    exit; 
}

$ppo_id = (int)$_SESSION['ppo_id'];
$user_id = (int)$_SESSION['user_id'];

// 1. Інформація про ППО
$ppo = \Core\DB::fetch("SELECT name, created_at, election_term_years FROM ppos WHERE id = ?", [$ppo_id]);
$ppo_name = $ppo['name'] ?? 'Профспілка';
$foundation_date = !empty($ppo['created_at']) ? date('d.m.Y', strtotime($ppo['created_at'])) : 'Невідомо';
$bank_connected = \Core\DB::fetchColumn("SELECT 1 FROM finance_config WHERE ppo_id = ? AND mono_token IS NOT NULL AND mono_token != ''", [$ppo_id]);

$election_period_years = max(1, (int)($ppo['election_term_years'] ?? 1));

// --- ВКЛАДКА 0: КЕРІВНИЦТВО ---
$raw_leaders = \Core\DB::fetchAll("SELECT id, full_name, role, role_assigned_at, joined_at FROM users WHERE ppo_id = ? AND role IN ('admin', 'head', 'auditor', 'manager')", [$ppo_id]) ?: [];

$role_names = ['head' => 'Голова ППО', 'auditor' => 'Ревізор', 'manager' => 'Менеджер'];
$role_icons = ['head' => 'fa-crown', 'auditor' => 'fa-scale-balanced', 'manager' => 'fa-briefcase'];

$leaders = [];
$role_counts = ['head' => 0, 'auditor' => 0, 'manager' => 0];

foreach ($raw_leaders as $l) {
    $r = $l['role'] === 'admin' ? 'head' : $l['role'];
    if (isset($role_counts[$r])) $role_counts[$r]++;
    
    $name = class_exists('\Core\Security') ? (\Core\Security::decrypt($l['full_name']) ?: $l['full_name']) : $l['full_name'];
    $assigned_time = !empty($l['role_assigned_at']) ? strtotime($l['role_assigned_at']) : strtotime($l['joined_at']); 
    
    $next_election_time = strtotime("+$election_period_years years", $assigned_time);
    $days_left = max(0, round(($next_election_time - time()) / 86400));
    
    $role_display_name = $role_names[$r] . ($role_counts[$r] > 1 ? ' #' . $role_counts[$r] : '');

    $leaders[] = [
        'role_name'     => $role_display_name,
        'icon'          => $role_icons[$r],
        'name'          => $name,
        'elected_date'  => date('d.m.Y', $assigned_time),
        'next_election' => date('d.m.Y', $next_election_time),
        'days_left'     => $days_left,
        'period'        => $election_period_years,
        'is_empty'      => false,
        'sort_weight'   => array_search($r, array_keys($role_names))
    ];
}

foreach (['head', 'auditor', 'manager'] as $r) {
    if ($role_counts[$r] === 0) {
        $leaders[] = [
            'role_name'   => $role_names[$r],
            'icon'        => $role_icons[$r],
            'name'        => 'Не обрано',
            'is_empty'    => true,
            'sort_weight' => array_search($r, array_keys($role_names))
        ];
    }
}

usort($leaders, function($a, $b) {
    return $a['sort_weight'] <=> $b['sort_weight'];
});

// --- ВКЛАДКА 1: СКЛАД ---
$limit = 15;
$p_m = max(1, (int)($_GET['p_m'] ?? 1));
$offset = ($p_m - 1) * $limit;
$total_members_count = \Core\DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')", [$ppo_id]) ?: 1;
$raw_members = \Core\DB::fetchAll("SELECT id, full_name, role, joined_at FROM users WHERE ppo_id = ? AND status IN ('member', 'admin') ORDER BY joined_at ASC LIMIT $limit OFFSET $offset", [$ppo_id]) ?: [];
$members_list = [];
foreach ($raw_members as $m) {
    $members_list[] = [
        'id'     => $m['id'],
        'name'   => class_exists('\Core\Security') ? (\Core\Security::decrypt($m['full_name']) ?: $m['full_name']) : $m['full_name'],
        'role'   => $m['role'], 
        'joined' => $m['joined_at']
    ];
}

// --- ВКЛАДКА 2: ТОП АКТИВІСТІВ ---
$top_activists_raw = \Core\DB::fetchAll("
    SELECT u.id, u.full_name, 
    ( 
        (SELECT COUNT(*) FROM votes v WHERE v.user_id = u.id) * 1 + 
        (SELECT COUNT(*) FROM nomination_votes nv WHERE nv.nominator_id = u.id) * 2 + 
        (SELECT COUNT(*) FROM petition_signatures ps WHERE ps.user_id = u.id) * 2 + 
        (SELECT COUNT(*) FROM event_enrollments ee WHERE ee.user_id = u.id) * 5 + 
        (SELECT COUNT(*) FROM petitions pt WHERE pt.user_id = u.id AND pt.status IN ('1', 'approved')) * 10 
    ) as activity_score
    FROM users u 
    WHERE u.ppo_id = ? AND u.status IN ('member', 'admin')
    HAVING activity_score > 0
    ORDER BY activity_score DESC 
    LIMIT 10
", [$ppo_id]) ?: [];

$top_activists = [];
foreach($top_activists_raw as $act) {
    $a_name = class_exists('\Core\Security') ? (\Core\Security::decrypt($act['full_name']) ?: $act['full_name']) : $act['full_name'];
    $top_activists[] = [
        'name' => $a_name,
        'score' => $act['activity_score']
    ];
}

// --- ВКЛАДКА 4: СТАТИСТИКА ---
$stats = [
    'polls'  => \Core\DB::fetchColumn("SELECT COUNT(*) FROM polls WHERE ppo_id = ?", [$ppo_id]),
    'noms'   => \Core\DB::fetchColumn("SELECT COUNT(*) FROM nominations WHERE ppo_id = ?", [$ppo_id]),
    'pets'   => \Core\DB::fetchColumn("SELECT COUNT(*) FROM petitions WHERE ppo_id = ?", [$ppo_id]),
    'events' => \Core\DB::fetchColumn("SELECT COUNT(*) FROM events WHERE ppo_id = ?", [$ppo_id])
];

$all_polls = \Core\DB::fetchAll("
    SELECT id, end_date, 
           (SELECT COUNT(*) FROM votes WHERE poll_id = polls.id) as current_votes 
    FROM polls WHERE ppo_id = ?
", [$ppo_id]) ?: [];

$total_percentages = 0;
$valid_polls_count = 0;

foreach ($all_polls as $p) {
    $eligible_voters = \Core\DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member', 'admin') AND joined_at <= ?", [$ppo_id, $p['end_date']]);
    if ($eligible_voters > 0) {
        $poll_rate = ($p['current_votes'] / $eligible_voters) * 100;
        $total_percentages += min(100, $poll_rate);
        $valid_polls_count++;
    }
}
$participation_rate = $valid_polls_count > 0 ? round($total_percentages / $valid_polls_count) : 0;

$title = 'Про організацію';
$header_title = 'Про організацію';
$view_path = 'ppo_info_view.php';
require_once 'views/layout.php';
