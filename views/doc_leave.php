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

        /* Гарантуємо друк фону (штамп КЕП) */
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
        line-height: 1.6; 
        position: relative; 
    }

    /* 🔥 ШАПКА: СУВОРО ПРАВОРУЧ (БРОНЕБІЙНИЙ МЕТОД) */
    .doc-top-section {
        width: 50%;          /* Блок займає рівно половину аркуша */
        margin-left: 50%;    /* Відштовхуємо його рівно на праву половину */
        margin-bottom: 50px;
        font-size: 16px; 
        text-align: left;    /* Текст всередині блоку вирівняно по лівому краю */
    }

    /* ТІЛО ДОКУМЕНТА */
    .doc-title { text-align: center; text-transform: uppercase; font-size: 22px; font-weight: bold; margin-bottom: 30px; letter-spacing: 2px; }
    .doc-body { text-indent: 40px; font-size: 17px; text-align: justify; margin-bottom: 50px; }

    /* 🔥 ПІДПИСИ: ДАТА І ПІБ НА ОДНОМУ РІВНІ */
    .doc-sign-line { 
        display: flex; 
        justify-content: space-between; 
        align-items: flex-end; 
        margin-top: 60px; 
        font-size: 16px; 
        font-weight: bold;
    }
    
    /* 🔥 КЕП: ПО ЦЕНТРУ ВНИЗУ */
    .doc-kep-centered { 
        display: flex; 
        justify-content: center; 
        margin-top: 40px; 
    }

    .kep-stamp { 
        width: 100%; min-width: 250px; max-width: 280px;
        border: 2.5px solid #1d4ed8; border-radius: 8px; 
        padding: 14px; background: rgba(29, 78, 216, 0.03);
        font-family: 'DM Mono', monospace; font-size: 11px; color: #1e3a8a;
        position: relative; text-align: left;
        transform: rotate(-1deg); 
        box-shadow: inset 0 0 10px rgba(29, 78, 216, 0.05);
    }
    .kep-stamp i.fa-lock { position: absolute; right: 12px; top: 12px; opacity: 0.08; font-size: 24px; }
    .kep-header { font-weight: 700; border-bottom: 1px dashed rgba(29, 78, 216, 0.4); padding-bottom: 6px; margin-bottom: 8px; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
    .kep-row { margin-bottom: 3px; line-height: 1.3; }
    .kep-row b { color: #000; font-size: 12px; }

    .sys-footer-text {
        margin-top: 50px; border-top: 1px solid #e2e8f0; padding-top: 15px; 
        font-size: 11px; color: #64748b; text-align: center; 
        font-family: 'Manrope', sans-serif;
    }

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
    <a href="archive.php?tab=leave_apps" class="btn-action btn-back-main">
        <i class="fa-solid fa-arrow-left"></i> НАЗАД
    </a>
    <button class="btn-action btn-save-main js-print-doc" title="Завантажити PDF">
        <i class="fa-solid fa-file-pdf"></i> ЗБЕРЕГТИ
    </button>
</div>

<div class="document-wrapper">
    <div class="document-a4">
        
        <!-- ШАПКА: АДРЕСАТ СПРАВА -->
        <div class="doc-top-section">
            <div class="doc-header-text">
                Голові<br>
                Первинної профспілкової організації<br>
                «<?= htmlspecialchars($ppo_name) ?>»<br><br>
                від члена профспілки<br>
                <b><?= htmlspecialchars($app['user_name_snapshot']) ?></b>
            </div>
        </div>
        
        <div class="doc-title">Заява</div>
        
        <div class="doc-body">
            Прошу виключити мене зі складу членів Первинної профспілкової організації «<?= htmlspecialchars($ppo_name) ?>» за власним бажанням з <?= date('d.m.Y', strtotime($app['created_at'])) ?> року. 
            <br><br>
            Претензій фінансового чи майнового характеру до профспілкової організації не маю.
            <br><br>
            <span style="font-style: italic; font-size: 14px; color: #555;">Цей документ сформовано та підписано в електронному вигляді за допомогою Кваліфікованого Електронного Підпису (Режим: Дія.Підпис).</span>
        </div>
        
        <!-- НИЖНІЙ БЛОК: ДАТА І ПІБ НА ОДНОМУ РІВНІ -->
        <div class="doc-sign-line">
            <!-- Дата цифрами зліва -->
            <div><?= date('d.m.Y', strtotime($app['created_at'])) ?></div>
            <!-- ПІБ справа -->
            <div><?= htmlspecialchars($app['user_name_snapshot']) ?></div>
        </div>
        
        <!-- КЕП ПО ЦЕНТРУ ВНИЗУ -->
        <div class="doc-kep-centered">
            <div class="kep-stamp">
                <i class="fa-solid fa-lock"></i>
                <div class="kep-header">Електронний підпис (КЕП)</div>
                <div class="kep-row">ПІБ: <b><?= htmlspecialchars($app['user_name_snapshot']) ?></b></div>
                <div class="kep-row">Дата: <b><?= date('d.m.Y H:i:s', strtotime($app['created_at'])) ?></b></div>
                <div class="kep-row" style="margin-top:5px; opacity:0.6; font-size:9px;">
                    HASH: <span style="font-family: monospace;"><?= htmlspecialchars(substr($app['signature_hash'] ?? '0000000000', 0, 32)) ?>...</span>
                </div>
            </div>
        </div>
        
        <!-- ТЕХНІЧНИЙ ПІДВАЛ -->
        <div class="sys-footer-text">
            Електронний архів документів системи «ACTION: Прозора Спілка». Згенеровано автоматично.<br>
            Унікальний ідентифікатор транзакції: <b style="font-family: monospace;"><?= hash('crc32', ($app['signature_hash'] ?? '') . ($app['created_at'] ?? '')) ?></b>
        </div>
        
    </div>
</div>
