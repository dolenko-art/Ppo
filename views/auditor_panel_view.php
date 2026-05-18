<style>
    /* 🔥 ІНДИВІДУАЛЬНА ШИРИНА ДЛЯ ЦІЄЇ СТОРІНКИ (3 СЛАЙДИ) */
    #slider { width: 300% !important; display: flex; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); align-items: flex-start; }
    .slide { width: 33.33333% !important; padding: 15px; box-sizing: border-box; flex-shrink: 0; }

    /* Ховаємо глобальне меню Aurora, бо тут є своє місцеве меню */
    .nav-shell { display: none !important; }

    /* Додаємо простір знизу, щоб меню не перекривало контент */
    body { padding-bottom: 90px !important; }
</style>

<!-- 🔥 Відступ під шапкою 16px, знизу 0px (щоб не сумувався з відступом заголовків) -->
<div class="alert-box" style="margin: 16px 16px 0px; background: rgba(245,158,11,0.1); border-color: #f59e0b; color: #d97706; display: flex; align-items: center; gap: 10px;">
    <i class="fa-solid fa-shield-halved" style="font-size: 20px;"></i>
    <div>
        <strong>Незалежний контроль</strong><br>
        <span style="font-size: 11px;">Ви дієте від імені Загальних зборів. Всі ваші дії (Акти) фіксуються криптографічно.</span>
    </div>
</div>

<div class="slider-viewport" style="width: 100%; overflow: hidden; position: relative;">
    <div id="slider">
        
        <!-- ========================================== -->
        <!-- Вкладка 1: ТРАНЗАКЦІЇ -->
        <!-- ========================================== -->
        <div class="slide active-slide">
            <h2 class="slide-header" style="margin-top: 5px;">Фінансовий контроль</h2>
            
            <?php if(empty($bank_unverified) && empty($cash_unverified)): ?>
                <div class="card" style="text-align:center; padding:50px 20px; color:var(--txt-muted); border: 2px dashed var(--border); background: var(--surface);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fa-solid fa-check-double" style="font-size:24px; color:var(--green);"></i>
                    </div>
                    <strong style="color:var(--txt); font-size:16px; display:block; margin-bottom:5px;">Усі транзакції перевірено!</strong>
                    <span style="font-size:12px;">Підозрілих витрат не знайдено.</span>
                </div>
            <?php else: ?>
                
                <!-- БАНКІВСЬКІ ТРАНЗАКЦІЇ -->
                <?php if(!empty($bank_unverified)): ?>
                    <?php foreach($bank_unverified as $tx): 
                        $amount_uah = $tx['amount'] / 100;
                        $is_expense = ($amount_uah < 0);
                        $color = $is_expense ? 'var(--red)' : 'var(--green)'; 
                        $sign = $is_expense ? '' : '+'; 
                    ?>
                        <div class="card" id="tx-card-<?= htmlspecialchars($tx['id']) ?>" style="border-left: 4px solid #3b82f6;">
                            <div style="font-size:10px; color:var(--txt-muted); display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span style="background: rgba(59,130,246,0.1); color: #3b82f6; padding: 4px 8px; border-radius: 6px; font-weight: 800;"><i class="fa-solid fa-building-columns"></i> Безготівка</span>
                                <span style="display: flex; align-items: center;"><i class="fa-regular fa-clock" style="margin-right:4px;"></i> <?= date('d.m.Y H:i', $tx['time']) ?></span>
                            </div>
                            
                            <div style="font-weight:800; font-size:20px; margin: 8px 0; color: <?= $color ?>;">
                                <?= $sign . number_format($amount_uah, 2, '.', ' ') ?> ₴
                            </div>
                            
                            <div style="font-size:13px; color:var(--txt); margin-bottom: 8px;">
                                <strong>Призначення:</strong> <?= htmlspecialchars($tx['description']) ?>
                            </div>
                            
                            <?php if(!empty($tx['comment'])): ?>
                                <div style="font-size:12px; color:var(--txt-muted); margin-bottom: 15px; font-style: italic; background: var(--bg); padding: 8px; border-radius: 6px;">
                                    💬 <?= htmlspecialchars($tx['comment']) ?>
                                </div>
                            <?php else: ?>
                                <div style="margin-bottom: 15px;"></div>
                            <?php endif; ?>

                            <!-- 🔥 CSP ФІКС: Замінено onclick на клас js-approve-tx -->
                            <button class="btn-modern primary js-approve-tx" data-id="<?= htmlspecialchars($tx['id']) ?>" data-type="bank" style="width:100%; justify-content:center; background:var(--green); box-shadow: 0 4px 10px rgba(16,185,129,0.2);">
                                <i class="fa-solid fa-check-to-slot"></i> Підтвердити
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- ГОТІВКОВІ ТРАНЗАКЦІЇ -->
                <?php if(!empty($cash_unverified)): ?>
                    <?php foreach($cash_unverified as $tx): 
                        $amount_uah = (float)$tx['amount'];
                        $is_expense = ($amount_uah < 0);
                        $color = $is_expense ? 'var(--red)' : 'var(--green)';
                        $sign = $is_expense ? '' : '+';
                    ?>
                        <div class="card" id="tx-card-cash-<?= htmlspecialchars($tx['id']) ?>" style="border-left: 4px solid var(--green);">
                            <div style="font-size:10px; color:var(--txt-muted); display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span style="background: rgba(16,185,129,0.1); color: var(--green); padding: 4px 8px; border-radius: 6px; font-weight: 800;"><i class="fa-solid fa-wallet"></i> Готівка (Каса)</span>
                                <span style="display: flex; align-items: center;"><i class="fa-regular fa-clock" style="margin-right:4px;"></i> <?= date('d.m.Y H:i', strtotime($tx['created_at'])) ?></span>
                            </div>
                            
                            <div style="font-weight:800; font-size:20px; margin: 8px 0; color: <?= $color ?>;">
                                <?= $sign . number_format($amount_uah, 2, '.', ' ') ?> ₴
                            </div>
                            
                            <div style="font-size:13px; color:var(--txt); margin-bottom: 15px;">
                                <strong>Призначення:</strong> <?= htmlspecialchars($tx['description']) ?>
                            </div>

                            <!-- 🔥 CSP ФІКС: Замінено onclick на клас js-approve-tx -->
                            <button class="btn-modern primary js-approve-tx" data-id="<?= htmlspecialchars($tx['id']) ?>" data-type="cash" style="width:100%; justify-content:center; background:var(--green); box-shadow: 0 4px 10px rgba(16,185,129,0.2);">
                                <i class="fa-solid fa-check-to-slot"></i> Підтвердити
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- ========================================== -->
        <!-- Вкладка 2: МАЙНО -->
        <!-- ========================================== -->
        <div class="slide">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h2 class="slide-header" style="margin:0;">Реєстр майна</h2>
                <button class="sq-btn" style="background:var(--accent); color:#fff; border:none; box-shadow: 0 2px 8px rgba(59,130,246,0.3);"><i class="fa-solid fa-plus"></i></button>
            </div>

            <?php if(empty($assets)): ?>
                <div class="card" style="text-align:center; padding:50px 20px; color:var(--txt-muted); border: 2px dashed var(--border); background: var(--surface);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fa-solid fa-boxes-stacked" style="font-size:24px; color:var(--txt-muted);"></i>
                    </div>
                    <strong style="color:var(--txt); font-size:14px; display:block; margin-bottom:5px;">Майна ще немає</strong>
                    <span style="font-size:12px;">На балансі ППО порожньо.</span>
                </div>
            <?php else: ?>
                <!-- Вивід майна -->
            <?php endif; ?>
        </div>

        <!-- ========================================== -->
        <!-- Вкладка 3: АКТИ РЕВІЗІЇ -->
        <!-- ========================================== -->
        <div class="slide">
            <h2 class="slide-header" style="margin-top: 5px;">Звіти (Акти)</h2>
            <!-- 🔥 CSP ФІКС: Замінено onclick на data-target для модалки -->
            <button class="btn-modern primary js-open-modal-id" data-target="reportModal" style="width:100%; justify-content:center; margin-bottom: 20px;">
                <i class="fa-solid fa-file-signature"></i> Сформувати новий Акт
            </button>

            <?php if(empty($reports)): ?>
                <div class="card" style="text-align:center; padding:40px 20px; color:var(--txt-muted); border: 2px dashed var(--border); background: var(--surface);">
                    <i class="fa-regular fa-folder-open" style="font-size:30px; margin-bottom:10px; opacity:0.5;"></i><br>
                    Ви ще не формували актів.
                </div>
            <?php else: ?>
                <?php foreach($reports as $rep): 
                    // Статус акта
                    $status_badge = '<span class="status-badge badge-orange">На голосуванні</span>';
                    if($rep['status'] == 'approved') $status_badge = '<span class="status-badge badge-green">Затверджено</span>';
                    if($rep['status'] == 'rejected') $status_badge = '<span class="status-badge badge-red">Відхилено</span>';
                ?>
                    <a href="report.php?id=<?= $rep['id'] ?>" style="text-decoration:none; color:inherit; display:block;">
                        <div class="card" style="border-left: 4px solid #f59e0b; cursor:pointer;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px; align-items: center;">
                                <?= $status_badge ?>
                                <span style="font-size:10px; color:var(--txt-muted); background: var(--bg); padding: 4px 8px; border-radius: 6px;"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y', strtotime($rep['created_at'] ?? 'now')) ?></span>
                            </div>
                            <h4 style="font-family:'Syne'; font-size:16px; margin-bottom:8px;">Акт: <?= htmlspecialchars($rep['report_period']) ?></h4>
                            <div style="background:rgba(59,91,219,0.05); color:var(--accent); font-family:'DM Mono', monospace; font-size:10px; padding:8px 10px; border-radius:8px; word-break:break-all; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-fingerprint" style="font-size: 14px;"></i> 
                                <span>КЕП: <?= htmlspecialchars(substr($rep['signature_hash'] ?? '', 0, 16)) ?>...</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>

                <!-- 🔥 БЛОК ПАГІНАЦІЇ ДЛЯ АКТІВ -->
                <?php 
                $total_pages = $total_pages ?? 1;
                $current_page = $current_page ?? 1;
                
                if ($total_pages > 1): 
                ?>
                    <div class="pag-wrap" style="margin-top: 25px;">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?tab=acts&p=<?= $i ?>" class="pag-link <?= ($current_page == $i) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</div>

<!-- МІСЦЕВЕ НИЖНЄ МЕНЮ РЕВІЗОРА -->
<nav class="view-bar">
    <!-- 🔥 CSP ФІКС: Замінено onclick на класи навігації -->
    <a href="javascript:void(0)" class="nav-item active js-go-to-auditor-tab" data-idx="0" id="an0"><i class="fa-solid fa-money-bill-transfer"></i>ТРАНЗАКЦІЇ</a>
    <a href="javascript:void(0)" class="nav-item js-go-to-auditor-tab" data-idx="1" id="an1"><i class="fa-solid fa-boxes-stacked"></i>МАЙНО</a>
    <a href="javascript:void(0)" class="nav-item js-go-to-auditor-tab" data-idx="2" id="an2"><i class="fa-solid fa-file-contract"></i>АКТИ</a>
</nav>

<!-- МОДАЛКИ СТВОРЕННЯ АКТА І КЕП -->
<div id="reportModal" class="modal js-close-modal-click" data-target="reportModal">
    <div class="modal-card js-stop-propagation" style="width: 90%; max-width: 400px; box-sizing: border-box; padding: 25px 20px;">
        <h3 class="modal-title"><i class="fa-solid fa-file-shield text-accent" style="margin-right:5px;"></i> Сформувати Акт</h3>
        
        <div style="background: rgba(16,185,129,0.08); border-left: 3px solid var(--green); border-radius: 0 8px 8px 0; padding: 12px; margin-bottom: 20px;">
            <p style="font-size:11px; color:var(--green); margin:0; line-height:1.4;">
                <i class="fa-solid fa-microchip"></i> <strong>Smart-Контроль:</strong> Вкажіть період перевірки. Система автоматично перевірить всі транзакції, згенерує текст Акта і винесе його на голосування.
            </p>
        </div>
        
        <form id="reportForm" style="width: 100%; box-sizing: border-box;">
            <div style="margin-bottom: 15px; width: 100%;">
                <label style="font-size:12px; color:var(--txt-muted); margin-bottom:6px; display:block;"><i class="fa-regular fa-calendar-check" style="margin-right: 4px;"></i> Дата початку періоду</label>
                <input type="date" id="date_from" required class="input-dark" style="width: 100%; box-sizing: border-box; display: block; -webkit-appearance: none; margin: 0;">
            </div>
            
            <div style="margin-bottom: 25px; width: 100%;">
                <label style="font-size:12px; color:var(--txt-muted); margin-bottom:6px; display:block;"><i class="fa-regular fa-calendar-xmark" style="margin-right: 4px;"></i> Дата кінця періоду</label>
                <input type="date" id="date_to" required class="input-dark" style="width: 100%; box-sizing: border-box; display: block; -webkit-appearance: none; margin: 0;">
            </div>
            
            <!-- 🔥 CSP ФІКС: Замінено onclick на клас -->
            <button type="button" class="btn-modern primary js-open-kep-for-report" style="width: 100%; justify-content: center; box-sizing: border-box;">
                <i class="fa-solid fa-fingerprint"></i> Підписати КЕП
            </button>
            <button type="button" class="btn-modern mt-10 js-close-modal" data-target="reportModal" style="background: transparent; border: 1px solid var(--border); width: 100%; justify-content: center; box-sizing: border-box;">
                Скасувати
            </button>
        </form>
    </div>
</div>

<div id="kepModal" class="modal" style="z-index: 2500;">
    <div class="modal-card" style="width: 90%; max-width: 320px; box-sizing: border-box; text-align: center; align-items: center; padding: 30px 20px;">
        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(59,91,219,0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 15px;">
            <i class="fa-solid fa-fingerprint"></i>
        </div>
        <h3 class="modal-title">Накладання КЕП</h3>
        <p style="font-size:11px; color:var(--txt-muted); margin-bottom:20px; line-height: 1.5;">Підписання офіційного Акта ревізії. (Режим імітації Дія.Підпис)</p>
        <div id="kep-status" style="font-size:13px; font-weight:800; margin-bottom:20px; color:var(--txt); height: 20px;"></div>
        
        <!-- 🔥 CSP ФІКС: Замінено onclick на клас -->
        <button class="btn-modern primary js-simulate-kep-report" id="btn-sign-kep" style="width:100%; justify-content:center; box-sizing: border-box;">Підписати</button>
        <button class="btn-modern mt-10 js-close-modal" id="btn-cancel-kep" data-target="kepModal" style="background:transparent; border:1px solid var(--border); width:100%; justify-content:center; box-sizing: border-box;">Скасувати</button>
    </div>
</div>
