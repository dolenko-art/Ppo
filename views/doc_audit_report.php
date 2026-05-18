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
        margin: 0; /* Вимикаємо системні відступи браузера */
    }

    @media print {
        body { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        .document-wrapper { background: #fff !important; padding: 0 !important; margin: 0 !important; display: block !important; }
        
        /* Ховаємо панель кнопок при друці */
        .bottom-nav-wrap { display: none !important; }

        .document-a4 {
            width: 210mm !important;
            height: 297mm !important;
            margin: 0 !important;
            padding: 20mm 25mm !important; /* Стандартні поля діловодства */
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
        padding: 40px 10px 140px 10px; /* Збільшений відступ знизу для кнопок */
        min-height: 100vh;
        color: #000;
        font-family: 'Manrope', sans-serif !important; 
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    /* 🔥 СПРАВЖНІЙ ФОРМАТ А4 */
    .document-a4 { 
        background: #ffffff; 
        color: #000000; 
        width: 210mm; 
        min-height: 297mm; 
        padding: 20mm 25mm; 
        box-sizing: border-box; 
        box-shadow: 0 15px 40px rgba(0,0,0,0.12); 
        line-height: 1.6; 
        position: relative; 
    }

    /* ВМІСТ ДОКУМЕНТА */
    .doc-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f3f4f6; }
    .doc-title { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 800; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 1px; color: #111827; }
    .doc-meta { font-size: 14px; color: #4b5563; }
    
    .status-badge { display: inline-block; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; background: rgba(0,0,0,0.02); color: <?= $status_color ?>; border: 1px solid <?= $status_color ?>; margin-top: 10px; text-transform: uppercase; }

    .doc-body { font-size: 15px; line-height: 1.6; color: #111827; margin-bottom: 40px; text-align: justify; }
    
    .section-title { font-family: 'Syne', sans-serif; font-size: 18px; margin: 40px 0 15px; color: #111827; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
    
    /* Таблиця транзакцій */
    .tx-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .tx-table th, .tx-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; color: #111827; }
    .tx-table th { background: #f9fafb; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; font-size: 11px; }
    .tx-table tr:last-child td { border-bottom: none; }
    
    .amount-pos { color: #10b981; font-weight: 800; }
    .amount-neg { color: #ef4444; font-weight: 800; }
    .type-badge { font-size: 10px; padding: 4px 6px; border-radius: 4px; font-weight: 800; }
    .type-bank { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .type-cash { background: rgba(16,185,129,0.1); color: #10b981; }

    /* Блок підпису */
    .signature-block { background: rgba(59,91,219,0.03); border: 1px dashed rgba(59,91,219,0.3); border-radius: 12px; padding: 20px; margin-top: 50px; display: flex; align-items: flex-start; gap: 15px; }
    .signature-icon { width: 40px; height: 40px; border-radius: 50%; background: #3b5bdb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .signature-text { font-size: 12px; color: #111827; line-height: 1.5; }
    .signature-hash { font-family: 'DM Mono', monospace; font-size: 13px; color: #111827; word-break: break-all; margin-top: 5px; font-weight: 600; }

    .sys-footer-text { margin-top: 60px; border-top: 1px solid #ccc; padding-top: 15px; font-size: 11px; color: #64748b; text-align: center; }

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
    <!-- Використовуємо js-history-back, щоб повертатись на попередню сторінку без нових вкладок -->
    <button class="btn-action btn-back-main js-history-back" title="Повернутися назад">
        <i class="fa-solid fa-arrow-left"></i> НАЗАД
    </button>
    <button class="btn-action btn-save-main js-print-doc" title="Зберегти як PDF">
        <i class="fa-solid fa-file-pdf"></i> ЗБЕРЕГТИ
    </button>
</div>

<div class="document-wrapper">
    <div class="document-a4">

        <div class="doc-header">
            <h1 class="doc-title">Акт ревізійної комісії</h1>
            <div class="doc-meta">Період перевірки: <strong><?= htmlspecialchars($report['report_period']) ?></strong></div>
            <div class="status-badge"><?= $status_text ?></div>
        </div>

        <div class="doc-body">
            Мною, <strong><?= htmlspecialchars($auditor_name) ?></strong>, як обраним представником Ревізійної комісії, було проведено повну перевірку фінансово-господарської діяльності ППО за вказаний період.<br><br>
            <strong>РЕЗУЛЬТАТИ ПЕРЕВІРКИ:</strong><br>
            1. Всі банківські та готівкові транзакції успішно перевірені.<br>
            2. Нецільового використання коштів — <strong>НЕ ВИЯВЛЕНО</strong>.<br>
            3. Документація ведеться у відповідності до Статуту.<br><br>
            Прошу Загальні збори затвердити даний Акт.
        </div>

        <h3 class="section-title"><i class="fa-solid fa-list-check" style="color:#f59e0b;"></i> Реєстр транзакцій</h3>
        
        <?php if(empty($bank_tx) && empty($cash_tx)): ?>
            <p style="font-size:13px; color:#6b7280; text-align:center; padding:20px; background: #f9fafb; border-radius: 8px;">За цей період транзакцій не зафіксовано.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Тип</th>
                            <th>Призначення</th>
                            <th style="text-align:right;">Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bank_tx as $tx): 
                            $amt = $tx['amount'] / 100;
                            $is_expense = ($amt < 0);
                            $display_amt = abs($amt);
                            $desc = $tx['description'] ?? $tx['purpose'] ?? $tx['comment'] ?? 'Без призначення';
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= date('d.m.Y', $tx['time']) ?></td>
                            <td><span class="type-badge type-bank">Банк</span></td>
                            <td><?= htmlspecialchars($desc) ?></td>
                            <td style="text-align:right; white-space:nowrap;" class="<?= $is_expense ? 'amount-neg' : 'amount-pos' ?>">
                                <?= $is_expense ? '-' : '+' ?><?= number_format($display_amt, 2, '.', ' ') ?> ₴
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php foreach($cash_tx as $tx): 
                            $amt = (float)$tx['amount'];
                            $is_expense = ($tx['type'] === 'expense' || $amt < 0);
                            $display_amt = abs($amt);
                            $desc = $tx['reason'] ?? 'Готівкова операція';
                        ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= date('d.m.Y', strtotime($tx['created_at'])) ?></td>
                            <td><span class="type-badge type-cash">Каса</span></td>
                            <td><?= htmlspecialchars($desc) ?></td>
                            <td style="text-align:right; white-space:nowrap;" class="<?= $is_expense ? 'amount-neg' : 'amount-pos' ?>">
                                <?= $is_expense ? '-' : '+' ?><?= number_format($display_amt, 2, '.', ' ') ?> ₴
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="signature-block">
            <div class="signature-icon"><i class="fa-solid fa-fingerprint"></i></div>
            <div>
                <div class="signature-text">
                    Документ підписано кваліфікованим електронним підписом (КЕП).<br>
                    <strong>Ревізор:</strong> <?= htmlspecialchars($auditor_name) ?><br>
                    <strong>Дата підписання:</strong> <?= date('d.m.Y H:i:s', strtotime($report['auditor_signed_at'])) ?>
                </div>
                <div class="signature-hash">Серійний номер ключа: <?= htmlspecialchars($report['signature_hash']) ?></div>
            </div>
        </div>

        <div class="sys-footer-text">
            Електронний архів документів ACTION: Прозора Спілка. Згенеровано автоматично.<br>
            Унікальний ідентифікатор: <b style="font-family: monospace;"><?= hash('crc32', $report['signature_hash'] . $report['report_period']) ?></b>
        </div>

    </div>
</div>
