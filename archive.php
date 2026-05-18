<?php
// 🔥 ПРИМУСОВЕ КОДУВАННЯ UTF-8 ДЛЯ ВСЬОГО ФАЙЛУ
header('Content-Type: text/html; charset=utf-8');

require_once 'db.php';

if (!\Core\Auth::loggedIn()) { header("Location: login.php"); exit; }

$ppo_id = (int)$_SESSION['ppo_id'];
$user_id = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) && is_string($_GET['action']) ? htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8') : 'list';

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
    $protocol_num = htmlspecialchars($protocol['protocol_number'] ?? 'Б/Н', ENT_QUOTES, 'UTF-8');
    $transaction_hash = htmlspecialchars($protocol['document_hash'] ?? hash('sha256', $poll_id . time()), ENT_QUOTES, 'UTF-8');

    $h_parts = explode('|||', $protocol['head_name_snapshot'] ?? '');
    $s_parts = explode('|||', $protocol['secretary_name_snapshot'] ?? '');
    
    $head_name = htmlspecialchars(trim($h_parts[0]) ?: 'Головуючий не визначений', ENT_QUOTES, 'UTF-8');
    $secretary_name = htmlspecialchars(trim($s_parts[0]) ?: 'Секретар не визначений', ENT_QUOTES, 'UTF-8');
    
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
            .a4 { background: #fff; width: 210mm; min-height: 297mm; padding: 20mm; box-sizing: border-box; box-shadow: 0 15px 35px rgba(0,0,0,0.15); position: relative; display: flex; flex-direction: column; }
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
            .kep-stamp { width: 100%; max-width: 260px; border: 2px solid #2563eb; border-radius: 10px; padding: 12px; background: rgba(37, 99, 235, 0.02); font-family: 'DM Mono', monospace; font-size: 10px; position: relative; }
            .kep-stamp i.fa-lock { position: absolute; right: 10px; top: 10px; opacity: 0.1; font-size: 20px; }
            .kep-header { font-weight: bold; border-bottom: 1px dashed rgba(37, 99, 235, 0.3); padding-bottom: 4px; margin-bottom: 6px; text-transform: uppercase; }
            .kep-row b { color: #000; }
            .kep-waiting { width: 100%; max-width: 260px; height: 110px; border: 2px dashed #ccc; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 13px; color: #999; }
            .footer-sys { margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px; font-size: 10px; color: #777; text-align: center; font-family: 'Manrope', sans-serif; }
            @media print { body { background: #fff; padding: 0; display: block; } .a4 { box-shadow: none; padding: 20mm; margin: 0; page-break-after: always; } }
        </style>
    </head>
    <body>
        <div class="a4">
            <div class="h-right">ДОДАТОК 1<br>до Протоколу № <?= $protocol_num ?><br>від <?= date('d.m.Y', strtotime($poll['end_date'])) ?></div>
            <div class="title">Відкритий реєстр<br>криптографічних бюлетенів (Токенів)</div>
            <div class="desc">Цей реєстр згенеровано системою автоматично. Він є публічною і незмінною частиною Протоколу зборів.</div>
            <table>
                <thead><tr><th style="width: 40px;">№</th><th>Трек-номер бюлетеня (Токен)</th><th style="width: 150px;">Обраний варіант</th></tr></thead>
                <tbody>
                    <?php $i = 1; foreach ($registry as $row): ?>
                        <tr><td style="text-align: center;"><?= $i++ ?></td><td class="token"><?= htmlspecialchars($row['token']) ?></td><td style="text-align: center;"><b><?= htmlspecialchars($row['option_text'], ENT_QUOTES, 'UTF-8') ?></b></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($registry)): ?><tr><td colspan="3" style="text-align:center;">Урна порожня. Бюлетенів не виявлено.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div class="signatures-row">
                <div class="sig-col">
                    <div class="sig-label">Головуючий зборів</div>
                    <?php if (!empty($head_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= $head_name ?></b></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
                <div class="sig-col">
                    <div class="sig-label">Секретар зборів</div>
                    <?php if (!empty($sec_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= $secretary_name ?></b></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
            </div>
            <div class="footer-sys">Документ сформовано підсистемою E2E-V (Абсолютно таємне голосування).<br>Цифровий ідентифікатор: <?= hash('sha256', $poll_id) ?></div>
        </div>

        <div class="a4">
            <div class="h-right">ДОДАТОК 2<br>до Протоколу № <?= $protocol_num ?><br>від <?= date('d.m.Y', strtotime($poll['end_date'])) ?></div>
            <div class="title">Реєстр видачі бюлетенів<br>(Таблиця явки та ідентифікації)</div>
            <div class="desc">У цьому реєстрі зафіксовано перелік членів профспілки, які підтвердили свою особу за допомогою електронного підпису.</div>
            <table>
                <thead><tr><th style="width: 30px;">№</th><th style="width: 150px;">ПІБ Учасника</th><th>Криптографічний хеш підпису (КЕП)</th><th style="width: 100px;">Час</th></tr></thead>
                <tbody>
                    <?php $i = 1; foreach ($attendance as $row): 
                        $dec_name = class_exists('\Core\Security') 
                            ? (\Core\Security::decrypt($row['full_name']) ?: 'Невідомо') 
                            : $row['full_name'];
                    ?>
                        <tr><td style="text-align: center;"><?= $i++ ?></td><td><b><?= htmlspecialchars($dec_name, ENT_QUOTES, 'UTF-8') ?></b></td><td class="token" style="font-size: 10px; color: #1e3a8a;"><?= htmlspecialchars($row['signature_hash'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td style="text-align: center;"><?= date('H:i', strtotime($row['signed_at'] ?? 'now')) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendance)): ?><tr><td colspan="4" style="text-align:center;">Немає записів про явку.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div class="signatures-row">
                <div class="sig-col">
                    <div class="sig-label">Головуючий зборів</div>
                    <?php if (!empty($head_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= $head_name ?></b></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
                <div class="sig-col">
                    <div class="sig-label">Секретар зборів</div>
                    <?php if (!empty($sec_signed_at)): ?>
                        <div class="kep-stamp"><i class="fa-solid fa-lock"></i><div class="kep-header">Електронний підпис (КЕП)</div><div class="kep-row">ПІБ: <b><?= $secretary_name ?></b></div></div>
                    <?php else: ?><div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує підпису</div><?php endif; ?>
                </div>
            </div>
            <div class="footer-sys">Документ сформовано підсистемою E2E-V (Абсолютно таємне голосування).<br>Цифровий ідентифікатор: <?= hash('sha256', $poll_id) ?></div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================================
// СПИСОК АРХІВУ (ПАГІНАЦІЯ ТА СОРТУВАННЯ)
// =========================================

$user = \Core\DB::fetch("SELECT role, status FROM users WHERE id = ?", [$user_id]);
$is_leadership = in_array($user['role'] ?? '', ['head', 'auditor', 'manager']) || $user['status'] === 'admin';

$current_tab = isset($_GET['tab']) && is_string($_GET['tab']) ? htmlspecialchars($_GET['tab'], ENT_QUOTES, 'UTF-8') : 'protocols';

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
    $polls = \Core\DB::fetchAll(
        "SELECT * FROM polls WHERE ppo_id = ? AND is_active = 0 AND is_processed = 1 ORDER BY id DESC LIMIT ? OFFSET ?",
        [$ppo_id, $limit, $offset]
    ) ?: [];
} elseif ($current_tab === 'leave_apps') {
    $total_records = \Core\DB::fetchColumn("SELECT COUNT(*) FROM leave_applications WHERE ppo_id = ?", [$ppo_id]);
    $total_pages = ceil($total_records / $limit);
    $leave_apps = \Core\DB::fetchAll(
        "SELECT * FROM leave_applications WHERE ppo_id = ? ORDER BY id DESC LIMIT ? OFFSET ?",
        [$ppo_id, $limit, $offset]
    ) ?: [];
} elseif ($current_tab === 'admission_apps') {
    $total_records = \Core\DB::fetchColumn("SELECT COUNT(*) FROM admission_applications WHERE ppo_id = ?", [$ppo_id]);
    $total_pages = ceil($total_records / $limit);
    $admission_apps = \Core\DB::fetchAll(
        "SELECT * FROM admission_applications WHERE ppo_id = ? ORDER BY id DESC LIMIT ? OFFSET ?",
        [$ppo_id, $limit, $offset]
    ) ?: [];
}

$title = 'Архів ППО';
$header_title = 'Архів ППО';
$view_path = 'archive_view.php';
require_once 'views/layout.php';
