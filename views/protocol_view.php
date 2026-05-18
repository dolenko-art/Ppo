<script>
    // 🔥 Примусово наказуємо телефону віддалити сторінку, щоб А4 помістився цілком
    var metaViewport = document.querySelector('meta[name="viewport"]');
    if (metaViewport) {
        metaViewport.setAttribute('content', 'width=840');
    }
</script>

<style>
    /* ==========================================
       🚫 ХОВАЄМО СТАНДАРТНИЙ ІНТЕРФЕЙС LAYOUT.PHP
       ========================================== */
    header, .nav-shell, .view-bar, .ui-btn-inline { 
        display: none !important; 
    }
    body {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        background: #e5e7eb !important;
    }

    /* ==========================================
       🖨️ СПЕЦІАЛЬНІ НАЛАШТУВАННЯ ДЛЯ ДРУКУ (А4)
       ========================================== */
    @page {
        size: A4;
        margin: 0; 
    }

    @media print {
        body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        .document-wrapper { background: #fff !important; padding: 0 !important; margin: 0 !important; display: block !important; }
        .bottom-nav-wrap { display: none !important; }

        .document-a4 {
            width: 210mm !important;
            height: 297mm !important;
            margin: 0 !important;
            padding: 20mm 25mm !important; /* Стандартні береги ДСТУ */
            box-shadow: none !important;
            border: none !important;
            page-break-after: always;
            box-sizing: border-box !important;
        }

        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }

    /* ==========================================
       🖥️ ЕКРАННЕ ВІДОБРАЖЕННЯ (Імітація паперу)
       ========================================== */
    .document-wrapper { 
        background: #e5e7eb; 
        margin: 0; 
        padding: 40px 10px 140px 10px; 
        min-height: 100vh;
        display: flex;
        justify-content: center;
        font-family: 'Times New Roman', Times, serif !important;
    }

    .document-a4 { 
        background: #ffffff; 
        color: #000000; 
        width: 210mm;
        min-height: 297mm;
        padding: 20mm 25mm;
        box-sizing: border-box; 
        box-shadow: 0 15px 40px rgba(0,0,0,0.12); 
        line-height: 1.6; /* Комфортний інтервал для читання */
        position: relative; 
    }

    /* ШАПКА ДОКУМЕНТА */
    .header-wrap { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px; }
    .header-text { flex: 1; text-align: center; padding-right: 20px; }
    
    .qr-box { width: 85px; text-align: center; flex-shrink: 0; }
    .qr-box img { width: 80px; height: 80px; border: 1px solid #ddd; padding: 4px; background: #fff; border-radius: 6px; }
    .qr-label { font-size: 8px; font-family: 'Manrope', sans-serif; color: #555; margin-top: 5px; text-transform: uppercase; font-weight: 700; }

    h1.doc-title { font-size: 20px; margin-bottom: 8px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; }
    h2.doc-subtitle { font-size: 15px; font-weight: normal; margin: 0; line-height: 1.4; color: #111; }

    /* ІНФОРМАЦІЙНИЙ БЛОК */
    .meta-line { 
        display: flex; justify-content: space-between; 
        margin-bottom: 25px; font-size: 14px; 
        border-bottom: 2px solid #000; padding-bottom: 10px; 
        font-weight: bold; 
    }

    .block { margin-bottom: 20px; font-size: 15px; text-align: justify; }
    .block strong { text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; display: inline-block; margin-bottom: 4px; }
    
    .quorum-ok { color: #15803d; font-style: italic; font-weight: bold; }
    .quorum-fail { color: #b91c1c; font-weight: bold; text-decoration: underline; }

    ul, ol { margin-top: 6px; margin-bottom: 10px; padding-left: 25px; }
    li { margin-bottom: 4px; }

    /* БЛОК ПОЯСНЕНЬ (ПРАВО НА ЗАХИСТ) */
    .defense-block {
        background: #fcfcfc; padding: 15px 20px; border-left: 4px solid #94a3b8; 
        font-size: 14px; margin-bottom: 20px; font-style: italic;
    }

    /* ПЕЧАТКИ ТА ПІДПИСИ */
    .signatures-row { margin-top: 60px; display: flex; justify-content: space-between; gap: 40px; }
    .sig-col { flex: 1; display: flex; flex-direction: column; align-items: center; }
    .sig-label { font-weight: bold; font-size: 15px; margin-bottom: 15px; text-transform: uppercase; }

    .kep-stamp { 
        width: 100%; max-width: 270px;
        border: 2.5px solid #1d4ed8; border-radius: 8px; 
        padding: 14px; background: rgba(29, 78, 216, 0.03);
        font-family: 'DM Mono', monospace; font-size: 11px; color: #1e3a8a;
        position: relative; text-align: left;
        transform: rotate(-1deg); 
        box-shadow: inset 0 0 10px rgba(29, 78, 216, 0.05);
    }
    .kep-stamp i.fa-lock { position: absolute; right: 12px; top: 12px; opacity: 0.08; font-size: 24px; }
    .kep-header { font-weight: 700; border-bottom: 1px dashed rgba(29, 78, 216, 0.4); padding-bottom: 6px; margin-bottom: 8px; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
    .kep-row { margin-bottom: 3px; }
    .kep-row b { color: #000; font-size: 12px; }

    .kep-waiting { 
        width: 100%; max-width: 270px; height: 115px;
        border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: #64748b; font-size: 13px; font-family: 'Manrope', sans-serif; font-weight: 600;
    }

    /* ФУТЕР */
    .footer-sys { margin-top: 60px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; text-align: center; font-family: 'Manrope', sans-serif; }

    /* ==========================================
       🔥 ПРЕМІУМ-КНОПКИ УПРАВЛІННЯ (Гласморфізм)
       ========================================== */
    .bottom-nav-wrap {
        position: fixed; bottom: 30px; left: 0; right: 0;
        z-index: 1000; display: flex; justify-content: center; gap: 20px;
        pointer-events: none; padding: 0 20px;
    }
    .btn-action {
        pointer-events: auto; display: flex; align-items: center; justify-content: center; gap: 12px;
        padding: 20px 32px; border-radius: 100px;
        font-family: 'Manrope', sans-serif; font-size: 18px; font-weight: 800;
        text-decoration: none; border: none; cursor: pointer;
        box-shadow: 0 12px 30px rgba(0,0,0,0.25);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%; max-width: 300px;
    }
    .btn-back-main { background: rgba(30, 41, 59, 0.95); color: #fff; }
    .btn-back-main:hover { background: rgba(15, 23, 42, 1); transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.3); }
    
    .btn-save-main { background: rgba(37, 99, 235, 0.95); color: #fff; }
    .btn-save-main:hover { background: rgba(29, 78, 216, 1); transform: translateY(-5px); box-shadow: 0 15px 35px rgba(37, 99, 235, 0.4); }

</style>

<!-- 🔥 ПЛАВАЮЧА ПАНЕЛЬ КНОПОК -->
<div class="bottom-nav-wrap">
    <a href="<?= $back_link ?>" class="btn-action btn-back-main">
        <i class="fa-solid fa-arrow-left"></i> НАЗАД
    </a>
    <button class="btn-action btn-save-main js-print-doc" title="Завантажити PDF">
        <i class="fa-solid fa-file-pdf"></i> ЗБЕРЕГТИ
    </button>
</div>

<div class="document-wrapper">
    <div class="document-a4">
        
        <div class="header-wrap">
            <div style="width: 85px;"></div> 
            <div class="header-text">
                <h1 class="doc-title">ПРОТОКОЛ № <?= $protocol_num ?></h1>
                <h2 class="doc-subtitle">Загальних зборів Первинної профспілкової організації<br>«<?= htmlspecialchars($ppo['name'] ?? 'Профспілка') ?>»</h2>
            </div>
            <div class="qr-box">
                <img src="<?= $qr_img_url ?>" alt="QR">
                <div class="qr-label">Перевірити документ</div>
            </div>
        </div>

        <?php
            $format_text = "Відкрите електронне голосування";
            if ((int)$poll['is_secret'] === 1) $format_text = "Конфіденційне електронне голосування";
            if ((int)$poll['is_secret'] === 2) $format_text = "Абсолютно таємне (криптографічне) голосування";
        ?>
        <div class="meta-line">
            <span>Формат голосування: <?= $format_text ?></span>
            <span>Дата завершення: <?= date('d.m.Y H:i', strtotime($poll['end_date'])) ?></span>
        </div>

        <div class="block">
            Загальна кількість членів ППО, що мають право голосу: <b><?= $t_members ?> осіб.</b><br>
            Взяли участь у голосуванні: <b><?= $t_voted ?> осіб.</b><br>
            <?php if ($is_quorum_met): ?>
                <span class="quorum-ok">Кворум наявний. Збори визнано правомочними.</span>
            <?php else: ?>
                <span class="quorum-fail">Кворум ВІДСУТНІЙ. Збори не є правомочними.</span>
            <?php endif; ?>
        </div>

        <div class="block">
            <b>Головуючий зборів:</b> <?= htmlspecialchars($head_name) ?> (<?= $h_title ?>)<br>
            <b>Секретар зборів:</b> <?= htmlspecialchars($secretary_name) ?> (<?= $s_title ?>)
        </div>

        <div class="block" style="margin-top: 25px;">
            <strong>ПОРЯДОК ДЕННИЙ:</strong>
            <ol>
                <li>Про розгляд питання: «<?= htmlspecialchars($poll['title']) ?>».</li>
            </ol>
        </div>

        <div class="block">
            <strong>СЛУХАЛИ:</strong><br>
            <?php if(!empty($audit_id)): ?>
                Звіт Ревізійної комісії щодо фінансово-господарської діяльності. Оригінал Акту зафіксовано в цифровій системі профспілки.
            <?php else: ?>
                Інформацію щодо питання порядку денного:<br>
                <?= nl2br(htmlspecialchars($desc)) ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($t_comment)): ?>
            <div class="defense-block">
                <strong>ПОЯСНЕННЯ УЧАСНИКА (РЕАЛІЗАЦІЯ ПРАВА НА ЗАХИСТ):</strong><br>
                <?= nl2br(htmlspecialchars($t_comment)) ?>
            </div>
        <?php endif; ?>

        <div class="block">
            <strong>РЕЗУЛЬТАТИ ГОЛОСУВАННЯ:</strong>
            <ul>
                <?php foreach($votes as $v): ?>
                    <li>Варіант «<?= htmlspecialchars($v['option_text']) ?>» — <b><?= $v['count'] ?></b> голосів;</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="block">
            <strong>ПОСТАНОВИЛИ:</strong><br>
            <?= $decision_text ?>
        </div>

        <?php if ((int)$poll['is_secret'] === 2): ?>
            <div class="block" style="margin-top: 30px; font-size: 13px; font-style: italic; border-top: 1px solid #e2e8f0; padding-top: 15px; color: #475569;">
                * Примітка: Додаток 1 «Відкритий реєстр криптографічних бюлетенів» та Додаток 2 «Реєстр видачі бюлетенів» генеруються автоматично, додаються до цього протоколу в електронному вигляді та є його невід'ємною частиною, що забезпечує математичну та юридичну достовірність результатів.
            </div>
        <?php endif; ?>

        <div class="signatures-row">
            <div class="sig-col">
                <div class="sig-label">Головуючий зборів</div>
                <?php if (!empty($head_signed_at)): ?>
                    <div class="kep-stamp">
                        <i class="fa-solid fa-lock"></i>
                        <div class="kep-header">Електронний підпис (КЕП)</div>
                        <div class="kep-row">ПІБ: <b><?= htmlspecialchars($head_name) ?></b></div>
                        <div class="kep-row">Дата: <b><?= date('d.m.Y H:i:s', strtotime($head_signed_at)) ?></b></div>
                        <div class="kep-row" style="margin-top:5px; opacity:0.6; font-size:9px;">ID: <?= hash('crc32', $head_signed_at) ?></div>
                    </div>
                <?php else: ?>
                    <div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує накладання КЕП</div>
                <?php endif; ?>
            </div>

            <div class="sig-col">
                <div class="sig-label">Секретар зборів</div>
                <?php if (!empty($sec_signed_at)): ?>
                    <div class="kep-stamp">
                        <i class="fa-solid fa-lock"></i>
                        <div class="kep-header">Електронний підпис (КЕП)</div>
                        <div class="kep-row">ПІБ: <b><?= htmlspecialchars($secretary_name) ?></b></div>
                        <div class="kep-row">Дата: <b><?= date('d.m.Y H:i:s', strtotime($sec_signed_at)) ?></b></div>
                        <div class="kep-row" style="margin-top:5px; opacity:0.6; font-size:9px;">ID: <?= hash('crc32', $sec_signed_at) ?></div>
                    </div>
                <?php else: ?>
                    <div class="kep-waiting"><i class="fa-solid fa-pen-nib"></i> Очікує накладання КЕП</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-sys">
            Документ сформовано в цифровій системі «ACTION: Прозора Спілка».<br>
            Унікальний криптографічний ідентифікатор транзакції: <b style="font-family: monospace;"><?= $transaction_hash ?></b>
        </div>
    </div>
</div>
