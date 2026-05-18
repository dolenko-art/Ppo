<?php
// Підключаємо ядро (автозавантажувач та підключення до БД)
require_once 'db.php';

$poll_id = (int)($_GET['id'] ?? 0);
// БЕЗПЕКА: Гарантуємо, що $hash - це рядок (захист від TypeError, якщо передадуть масив ?h[]=...)
$hash = isset($_GET['h']) && is_string($_GET['h']) ? $_GET['h'] : '';

// Шукаємо протокол у зафіксованій таблиці
$protocol = \Core\DB::fetch("SELECT * FROM protocols WHERE poll_id = ?", [$poll_id]);

$is_valid = false;
$status_color = "#ef4444"; // Червоний
$status_icon = "fa-circle-xmark";
$status_title = "Документ не знайдено або змінено";
$status_text = "Криптографічний підпис не збігається. Можливо, документ був підроблений або ще не підписаний усіма сторонами.";

// Змінні для чистих імен
$head_clean_name = '';
$sec_clean_name = '';

if ($protocol) {
    // 🔥 ВІДРІЗАЄМО ТЕХНІЧНУ ПРИСТАВКУ ПОСАДИ (|||head)
    $h_parts = explode('|||', $protocol['head_name_snapshot']);
    $s_parts = explode('|||', $protocol['secretary_name_snapshot']);
    
    $head_clean_name = $h_parts[0] ?: 'Не визначено';
    $sec_clean_name = $s_parts[0] ?: 'Не визначено';

    // Перевіряємо фінальний хеш документа
    if (!empty($protocol['document_hash']) && hash_equals($protocol['document_hash'], $hash)) {
        $is_valid = true;
        $status_color = "#10b981"; // Зелений
        $status_icon = "fa-circle-check";
        $status_title = "Документ дійсний (Верифіковано)";
        $status_text = "Цей електронний протокол є незмінним. Кваліфіковані електронні підписи підтверджено системою «ACTION».";
    } 
    // Тимчасовий хеш (якщо документ ще в процесі підписання)
    elseif (empty($protocol['document_hash'])) {
        $temp_hash = hash('sha256', $poll_id . $protocol['created_at']);
        if (hash_equals($temp_hash, $hash)) {
            $is_valid = true;
            $status_color = "#f59e0b"; // Помаранчевий
            $status_icon = "fa-clock";
            $status_title = "Документ у процесі підписання";
            $status_text = "Дані протоколу зафіксовані, але він ще очікує накладання КЕП від керівництва ППО.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Верифікація документа</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Manrope:wght@400;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Manrope', sans-serif; background: #f3f4f6; color: #1f2937; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .verify-card { background: #fff; max-width: 450px; width: 100%; border-radius: 24px; padding: 30px 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .icon-wrap { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; color: <?= $status_color ?>; background: <?= $status_color ?>20; }
        h1 { font-family: 'Syne', sans-serif; font-size: 22px; margin: 0 0 10px; color: <?= $status_color ?>; }
        p { font-size: 14px; line-height: 1.6; color: #6b7280; margin-bottom: 25px; }
        
        .data-list { text-align: left; background: #f9fafb; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; border: 1px solid #e5e7eb; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 8px; }
        .data-row:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
        .data-label { color: #6b7280; font-weight: 600; }
        .data-val { font-weight: 800; color: #111827; text-align: right; word-break: break-all; max-width: 60%; }
        .hash-text { font-family: 'DM Mono', monospace; font-size: 10px; color: #3b82f6; }

        .btn { display: inline-block; padding: 12px 25px; background: #111827; color: #fff; text-decoration: none; border-radius: 12px; font-weight: 800; font-size: 14px; transition: 0.2s; }
        .btn:hover { background: #374151; }
    </style>
</head>
<body>

<div class="verify-card">
    <div class="icon-wrap">
        <i class="fa-solid <?= $status_icon ?>"></i>
    </div>
    <h1><?= $status_title ?></h1>
    <p><?= $status_text ?></p>

    <?php if ($is_valid && $protocol): ?>
        <div class="data-list">
            <div class="data-row">
                <span class="data-label">Номер протоколу:</span>
                <span class="data-val">№ <?= htmlspecialchars($protocol['protocol_number']) ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Створено:</span>
                <span class="data-val"><?= date('d.m.Y H:i', strtotime($protocol['created_at'])) ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Головуючий:</span>
                <span class="data-val"><?= htmlspecialchars($head_clean_name) ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Секретар:</span>
                <span class="data-val"><?= htmlspecialchars($sec_clean_name) ?></span>
            </div>
        </div>

        <?php if (!empty($protocol['document_hash'])): ?>
            <div class="data-list" style="background: rgba(59, 130, 246, 0.05); border-color: rgba(59, 130, 246, 0.2);">
                <div style="font-size: 11px; font-weight: 800; color: #3b82f6; text-transform: uppercase; margin-bottom: 10px; text-align: center;">Криптографічні дані КЕП</div>
                
                <div class="data-row" style="flex-direction: column; align-items: flex-start;">
                    <span class="data-label" style="margin-bottom: 4px;">Слід Голови:</span>
                    <span class="data-val hash-text" style="text-align: left; max-width: 100%;"><?= htmlspecialchars($protocol['head_signature_hash']) ?></span>
                </div>
                <div class="data-row" style="flex-direction: column; align-items: flex-start;">
                    <span class="data-label" style="margin-bottom: 4px;">Слід Секретаря:</span>
                    <span class="data-val hash-text" style="text-align: left; max-width: 100%;"><?= htmlspecialchars($protocol['manager_signature_hash']) ?></span>
                </div>
                <div class="data-row" style="flex-direction: column; align-items: flex-start;">
                    <span class="data-label" style="margin-bottom: 4px; color: #10b981;">Фінальний SHA-256 документа:</span>
                    <span class="data-val hash-text" style="text-align: left; max-width: 100%; color: #10b981;"><?= htmlspecialchars($protocol['document_hash']) ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <a href="index.php" class="btn">Повернутися на сайт</a>
</div>

</body>
</html>
