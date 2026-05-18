<?php
// 🔥 ПРИМУСОВЕ КОДУВАННЯ UTF-8 ДЛЯ ВСЬОГО ФАЙЛУ
header('Content-Type: text/html; charset=utf-8');

require_once 'db.php';

if (!\Core\Auth::loggedIn()) { header("Location: login.php"); exit; }

$ppo_id = (int)$_SESSION['ppo_id'];
$user_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

$safe_nonce = defined('CSP_NONCE') ? CSP_NONCE : '';

// =========================================
// 🔥 ГЕНЕРАЦІЯ ДОДАТКІВ (РЕЄСТР ТОКЕНІВ ТА ЯВКИ E2E-V)
// =========================================
if ($action === 'registry') {
    $poll_id = (int)($_GET['id'] ?? 0);
    
    $poll = \Core\DB::fetch("SELECT * FROM polls WHERE id = ? AND ppo_id = ?", [$poll_id, $ppo_id]);
    if (!$poll || (int)$poll['is_secret'] !== 2) {
        header("Location: archive.php"); exit;
    }

    $protocol = \Core\DB::fetch("SELECT * FROM protocols WHERE poll_id = ? AND ppo_id = ?", [$poll_id, $ppo_id]);
    $protocol_num = $protocol['protocol_number'] ?? 'Б/Н';
    $transaction_hash = $protocol['document_hash'] ?? hash('sha256', $poll_id . time());

    $h_parts = explode('|||', $protocol['head_name_snapshot'] ?? '');
    $s_parts = explode('|||', $protocol['secretary_name_snapshot'] ?? '');
    
    $head_name = trim($h_parts[0]) ?: 'Головуючий не визначений';
    $secretary_name = trim($s_parts[0]) ?: 'Секретар не визначений'; 
    
    $head_signed_at = $protocol['head_signed_at'] ?? null;
    $sec_signed_at  = $protocol['manager_signed_at'] ?? null;

    $registry = \Core\DB::fetchAll("
        SELECT v.token, po.option_text 
        FROM votes v
        JOIN poll_options po ON v.option_id = po.id
        WHERE v.poll_id = ?
        ORDER BY v.id ASC
    ", [$poll_id]) ?: [];

    $attendance = \Core\DB::fetchAll("
        SELECT u.full_name, ds.signature_hash, ds.signed_at
        FROM digital_signatures ds
        JOIN users u ON ds.user_id = u.id
        WHERE ds.action_context = 'e2e_request_poll' AND ds.target_id = ?
        ORDER BY ds.signed_at ASC
    ", [$poll_id]) ?: [];

    // Друкована сторінка
    ?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <title>Додатки до Протоколу №<?= $protocol_num ?></title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;700;800&display=swap" rel="stylesheet" nonce="<?= $safe_nonce ?>">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" nonce="<?= $safe_nonce ?>">
        <style nonce="<?= $safe_nonce ?>">
            @page { size: A4; margin: 0; }
            body { background: #e5e7eb; display: flex; flex-direction: column; align-items: center; font-family: 'Times New Roman', Times, serif; margin:0; padding:20px 10px; gap: 20px; }
            .a4 { background: #fff; width: 210mm; min-height: 297mm; padding: 20mm; box-sizing: border-box; box-shadow: 0 15px 35px rgba(0,0,0,0.15); position: relative; display: flex; flex-direction: column; overflow: hidden; }
            .h-right { text-align: right; font-weight: bold; margin-bottom: 30px; font-size: 15px; }
            .title { text-align: center; text-transform: uppercase; font-weight: bold; font-size: 18px; margin-bottom: 20px; }
            .desc { text-align: justify; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 30px; table-layout: fixed; }
            th, td { border: 1px solid #000; padding: 10px; text-align: left; font-size: 13px; word-wrap: break-word; }
            th { background: #f9f9f9; font-weight: bold; text-align: center; }
            .token { font-family: 'DM Mono', monospace; font-size: 12px; font-weight: bold; letter-spacing: 0.5px; text-align: center; word-break: break-all; }
            .signatures-row { margin-top: auto; padding-top: 40px; display: flex; justify-content: space-between; gap: 40px; page-break-inside: avoid; }
            .sig-col { flex: 1; display: flex; flex-direction: column; align-items: center; }
            .sig-label { font-weight: bold; font-size: 14px; margin-bottom: 15px; font-family: 'Times New Roman', Times, serif; }
            .kep-stamp { width: 100%; max-width: 260px; border: 2px solid #2563eb; border-radius: 10px; padding: 12px; background: rgba(37, 99, 235, 0.02); font-family: 'DM Mono', monospace; font-size: 10px; color: #1e3a8a; position: relative; text-align: left; transform: rotate(-1.5deg); }
            .kep-stamp i.fa-lock { position: absolute; right: 10px; top: 10px; opacity: 0.1; font-size: 20px; }
            .kep-header { font-weight: bold; border-bottom: 1px dashed rgba(37, 99, 235, 0.3); padding-bottom: 4px; margin-bottom: 6px; text-transform: uppercase; }
            .kep-row b { color: #000; }
            .kep-waiting { width: 100%; max-width: 260px; height: 110px; border: 2px dashed #ccc; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #999; font-size: 12px; font-family: 'Manrope', sans-serif; }
            .footer-sys { margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px; font-size: 10px; color: #777; text-align: center; font-family: 'Manrope', sans-serif; }
            @media print { body { background: #fff; padding: 0; display: block; } .a4 { box-shadow: none; padding: 20mm; margin: 0; page-break-after: always; } }
        </style>
    </head>
    <body>
        <div class="a4">
            <div class="h-right">ДОДАТОК 1<br>до Протоколу № <?= $protocol_num ?><br>від <?= date('d.m.Y', strtotime($poll['end_date'])) ?></div>
            <div class="title">Відкритий реєстр<br>криптографічних бюлетенів (Токенів)</div>
            <div class="desc">Цей реєстр згенеровано системою автоматично. Він є публічною і незмінною частиною Протоколу № <?= $protocol_num ?>. Кожен рядок відповідає одному унікальному анонімному бюлетеню.</div>
            <table>
                <thead><tr><th style="width: 40px;">№</th><th>Трек-номер бюлетеня (Токен)</th><th style="width: 150px;">Обраний варіант</th></tr></thead>
                <tbody>
                    <?php $i = 1; foreach ($registry as $row): ?>
                        <tr><td style="text-align: center;"><?= $i++ ?></td><td class="token"><?= htmlspecialchars($row['token']) ?></td><td style="text-align: center;"><b><?= htmlspecialchars($row['option_text']) ?></b></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($registry)): ?><tr><td colspan="3" style="text-align:center;">Урна порожня. Бюлетенів не виявлено.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div class="signatures-row">
                <div class="sig-col">
                    <div class="sig-label">Головуючий зборів</div>
                    <?php if (!empty($head_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= htmlspecialchars($head_name) ?></b></div><div class="kep-row">Дата: <b><?= date('d.m.Y H:i', strtotime($head_signed_at)) ?></b></div><div class="kep-row" style="margin-top:4px; opacity:0.6; font-size:8px;">ID: <?= hash('crc32', $head_signed_at) ?></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
                <div class="sig-col">
                    <div class="sig-label">Секретар зборів</div>
                    <?php if (!empty($sec_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= htmlspecialchars($secretary_name) ?></b></div><div class="kep-row">Дата: <b><?= date('d.m.Y H:i', strtotime($sec_signed_at)) ?></b></div><div class="kep-row" style="margin-top:4px; opacity:0.6; font-size:8px;">ID: <?= hash('crc32', $sec_signed_at) ?></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
            </div>
            <div class="footer-sys">Документ сформовано підсистемою E2E-V (Абсолютно таємне голосування).<br>Цифровий ідентифікатор процедури: <b><?= $transaction_hash ?></b></div>
        </div>

        <div class="a4">
            <div class="h-right">ДОДАТОК 2<br>до Протоколу № <?= $protocol_num ?><br>від <?= date('d.m.Y', strtotime($poll['end_date'])) ?></div>
            <div class="title">Реєстр видачі бюлетенів<br>(Таблиця явки та ідентифікації)</div>
            <div class="desc">У цьому реєстрі зафіксовано перелік членів профспілки, які підтвердили свою особу за допомогою Кваліфікованого Електронного Підпису (КЕП) та отримали сліпий криптографічний бюлетень.</div>
            <table>
                <thead><tr><th style="width: 30px;">№</th><th style="width: 150px;">ПІБ Учасника</th><th>Криптографічний хеш підпису (КЕП)</th><th style="width: 110px;">Дата видачі</th></tr></thead>
                <tbody>
                    <?php $i = 1; foreach ($attendance as $row): $dec_name = class_exists('\Core\Security') ? (\Core\Security::decrypt($row['full_name']) ?: $row['full_name']) : $row['full_name']; ?>
                        <tr><td style="text-align: center;"><?= $i++ ?></td><td><b><?= htmlspecialchars($dec_name) ?></b></td><td class="token" style="font-size: 10px; color: #1e3a8a;"><?= htmlspecialchars($row['signature_hash']) ?></td><td style="text-align: center; font-size: 11px;"><?= date('d.m.Y H:i:s', strtotime($row['signed_at'])) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance)): ?><tr><td colspan="4" style="text-align:center;">Немає записів про явку.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div class="signatures-row">
                <div class="sig-col">
                    <div class="sig-label">Головуючий зборів</div>
                    <?php if (!empty($head_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= htmlspecialchars($head_name) ?></b></div><div class="kep-row">Дата: <b><?= date('d.m.Y H:i', strtotime($head_signed_at)) ?></b></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
                <div class="sig-col">
                    <div class="sig-label">Секретар зборів</div>
                    <?php if (!empty($sec_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= htmlspecialchars($secretary_name) ?></b></div><div class="kep-row">Дата: <b><?= date('d.m.Y H:i', strtotime($sec_signed_at)) ?></b></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
            </div>
            <div class="footer-sys">Документ сформовано підсистемою E2E-V (Абсолютно таємне голосування).<br>Цифровий ідентифікатор процедури: <b><?= $transaction_hash ?></b></div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================================
// ГЕНЕРАЦІЯ ОФІЦІЙНОГО ПРОТОКОЛУ (ПЕЧАТКА)
// =========================================
if ($action === 'protocol') {
    $poll_id = (int)($_GET['id'] ?? 0);
    
    $poll = \Core\DB::fetch("SELECT * FROM polls WHERE id = ? AND ppo_id = ?", [$poll_id, $ppo_id]);
    if (!$poll) {
        $_SESSION['toast_msg'] = "❌ Протокол не знайдено.";
        header("Location: archive.php"); exit;
    }

    $protocol = \Core\DB::fetch("SELECT * FROM protocols WHERE poll_id = ? AND ppo_id = ?", [$poll_id, $ppo_id]);
    if (!$protocol) {
        $_SESSION['toast_msg'] = "❌ Дані протоколу ще формуються. Спробуйте оновити сторінку за хвилину.";
        header("Location: archive.php"); exit;
    }

    $ppo = \Core\DB::fetch("SELECT name FROM ppos WHERE id = ?", [$ppo_id]);
    $back_link = (isset($_GET['from']) && $_GET['from'] === 'notifs') ? 'profile.php?tab=notifs' : 'archive.php';
    $protocol_num = $protocol['protocol_number'] ?? 'Б/Н';
    $transaction_hash = $protocol['document_hash'] ?: hash('sha256', $poll_id . ($protocol['created_at'] ?? time()));
    
    $t_members = (int)($protocol['total_members_snapshot'] ?? 0);
    if ($t_members === 0) {
        $t_members = (int)\Core\DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppo_id]) ?: 1;
    }
    
    $t_voted = (int)($protocol['total_voted_snapshot'] ?? 0);
    if ($t_voted === 0) {
        $t_voted = (int)\Core\DB::fetchColumn("SELECT COUNT(*) FROM votes WHERE poll_id = ?", [$poll_id]);
    }
    if ($t_members < $t_voted) { $t_members = $t_voted; }

    $req_quorum = !empty($poll['required_quorum']) ? (int)$poll['required_quorum'] : (floor($t_members / 2) + 1);
    $is_quorum_met = ($t_voted >= $req_quorum);

    $h_snap_raw = $protocol['head_name_snapshot'] ?? '';
    $s_snap_raw = $protocol['secretary_name_snapshot'] ?? '';

    $h_parts = explode('|||', $h_snap_raw);
    $s_parts = explode('|||', $s_snap_raw);

    $head_name = trim($h_parts[0]) ?: 'Головуючий не визначений';
    $secretary_name = trim($s_parts[0]) ?: 'Секретар не визначений'; 
    $sec_name = $secretary_name; 

    $h_id = $protocol['head_user_id'] ?? 0;
    $s_id = $protocol['secretary_user_id'] ?? 0;

    $h_role = $h_parts[1] ?? ($h_id ? \Core\DB::fetchColumn("SELECT role FROM users WHERE id = ?", [$h_id]) : 'member');
    $s_role = $s_parts[1] ?? ($s_id ? \Core\DB::fetchColumn("SELECT role FROM users WHERE id = ?", [$s_id]) : 'member');

    $h_title = ($h_role === 'head') ? 'Голова ППО' : (($h_role === 'manager') ? 'Менеджер ППО' : 'Головуючий зборів (Член ППО)');
    $s_title = ($s_role === 'manager') ? 'Менеджер ППО' : (($s_role === 'head') ? 'Голова ППО' : 'Секретар зборів (Член ППО)');

    $decision_text = $protocol['decision_text_snapshot'] ?? '';
    if (empty($decision_text)) {
        $decision_text = ($t_voted > 0) ? "Рішення прийнято згідно з результатами електронного голосування." : "Рішення не прийнято (немає голосів).";
    }

    if (!$is_quorum_met) {
        $decision_text = "Рішення НЕ ПРИЙНЯТО у зв'язку з відсутністю кворуму.";
    }
    $decision = $decision_text; 

    $desc = $poll['description'] ?? 'Без додаткового опису';
    $desc = str_replace('🗳️ СТАТУС: Очікується пояснення учасника.', '🗳️ СТАТУС: Процедуру закрито. Рішення прийнято.', $desc);
    $t_comment = $protocol['target_comment_snapshot'] ?? null;

    $votes_json = $protocol['votes_result_snapshot'] ?? null;
    if (!empty($votes_json)) {
        $votes = json_decode($votes_json, true) ?: [];
    } else {
        $votes = \Core\DB::fetchAll("SELECT po.option_text, COUNT(v.id) as count FROM poll_options po LEFT JOIN votes v ON v.option_id = po.id WHERE po.poll_id = ? GROUP BY po.id", [$poll_id]) ?: [];
    }

    $protocol_url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $verify_link = $protocol_url . $domain . "/verify.php?id=" . $poll['id'] . "&h=" . $transaction_hash; 
    $qr_img_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verify_link);

    $head_signed_at = $protocol['head_signed_at'] ?? null;
    $sec_signed_at  = $protocol['manager_signed_at'] ?? null;

    $audit_id = \Core\DB::fetchColumn("SELECT id FROM audit_reports WHERE poll_id = ?", [$poll_id]);
    if (!$audit_id) {
        $target = (int)$poll['target_user_id'];
        if ($target > 0) {
            $exists = \Core\DB::fetchColumn("SELECT id FROM audit_reports WHERE id = ?", [$target]);
            if ($exists) $audit_id = $target;
        }
    }

    $title = 'Протокол №' . $protocol_num;
    $header_title = 'Офіційний протокол';
    $view_path = 'protocol_view.php';
    require_once 'views/layout.php';
    exit;
}

// =========================================
// СПИСОК АРХІВУ (ПАГІНАЦІЯ ТА СОРТУВАННЯ)
// =========================================

$user = \Core\DB::fetch("SELECT role, status FROM users WHERE id = ?", [$user_id]);
$is_leadership = in_array($user['role'], ['head', 'auditor', 'manager']) || $user['status'] === 'admin';

$current_tab = $_GET['tab'] ?? 'protocols';

if (in_array($current_tab, ['leave_apps', 'admission_apps']) && !$is_leadership) {
    $current_tab = 'protocols';
}

$limit = 10; 
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$total_pages = 1;

if ($current_tab === 'protocols') {
    $total_records = \Core\DB::fetchColumn("SELECT COUNT(*) FROM polls WHERE ppo_id = ? AND is_active = 0 AND is_processed = 1", [$ppo_id]);
    $total_pages = ceil($total_records / $limit);
    $polls = \Core\DB::fetchAll("SELECT * FROM polls WHERE ppo_id = ? AND is_active = 0 AND is_processed = 1 ORDER BY id DESC LIMIT $limit OFFSET $offset", [$ppo_id]) ?: [];
} elseif ($current_tab === 'leave_apps') {
    $total_records = \Core\DB::fetchColumn("SELECT COUNT(*) FROM leave_applications WHERE ppo_id = ?", [$ppo_id]);
    $total_pages = ceil($total_records / $limit);
    $leave_apps = \Core\DB::fetchAll("SELECT * FROM leave_applications WHERE ppo_id = ? ORDER BY id DESC LIMIT $limit OFFSET $offset", [$ppo_id]) ?: [];
} elseif ($current_tab === 'admission_apps') {
    $total_records = \Core\DB::fetchColumn("SELECT COUNT(*) FROM admission_applications WHERE ppo_id = ?", [$ppo_id]);
    $total_pages = ceil($total_records / $limit);
    $admission_apps = \Core\DB::fetchAll("SELECT * FROM admission_applications WHERE ppo_id = ? ORDER BY id DESC LIMIT $limit OFFSET $offset", [$ppo_id]) ?: [];
}

// 🔥 ВАЖЛИВА ЗМІНА: Використовуємо єдиний layout.php для списку архіву
$title = 'Архів ППО';
$header_title = 'Архів ППО';
$view_path = 'archive_view.php';
require_once 'views/layout.php';
