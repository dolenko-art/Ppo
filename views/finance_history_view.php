<style>
    /* 🔥 ІНДИВІДУАЛЬНА ШИРИНА ДЛЯ ЦІЄЇ СТОРІНКИ (2 СЛАЙДИ) */
    #slider { width: 200% !important; display: flex; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); align-items: flex-start; }
    .slide { width: 50% !important; padding: 15px; box-sizing: border-box; flex-shrink: 0; }

    /* Ховаємо глобальне меню Aurora, бо тут є своє місцеве меню */
    .nav-shell { display: none !important; }

    /* Стилі для карток транзакцій */
    .tx-card { 
        background: var(--surface); border: 1.5px solid var(--border); border-radius: 20px; 
        padding: 16px; margin-bottom: 12px; display: flex; align-items: center; gap: 14px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: transform 0.2s, background 0.3s, border-color 0.3s; 
    }
    .tx-card:active { transform: scale(0.98); }
    .tx-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .tx-in { background: rgba(16,185,129,0.1); color: var(--green); }
    .tx-out { background: rgba(239,68,68,0.1); color: var(--red); }
    
    .tx-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
    .tx-title { font-weight: 800; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--txt); }
    .tx-meta { display: flex; flex-direction: column; gap: 2px; }
    .tx-meta-item { font-size: 11px; font-weight: 700; color: var(--txt-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 6px; }
    
    .tx-amount { font-family: 'DM Mono', monospace; font-weight: 800; font-size: 16px; white-space: nowrap; text-align: right; }
    .c-in { color: var(--green); }
    .c-out { color: var(--txt); }

    .empty-card { text-align: center; padding: 50px 20px; background: var(--surface); border: 2px dashed var(--border); border-radius: 24px; color: var(--txt-muted); margin: 20px 0; }
    .empty-card i { font-size: 48px; margin-bottom: 15px; color: var(--accent); opacity: 0.2; }
    .empty-card h4 { font-family: 'Syne'; font-size: 18px; margin-bottom: 5px; color: var(--txt); }

    /* Додаємо простір знизу, щоб меню не перекривало контент */
    body { padding-bottom: 90px !important; }
</style>

<div class="slider-viewport" style="width: 100%; overflow: hidden; position: relative;">
    <div id="slider" style="transform: translateX(<?= $active_fin_tab === 'bank' ? '-50%' : '0' ?>);">
        
        <!-- ========================================== -->
        <!-- 💵 КАСА -->
        <!-- ========================================== -->
        <div class="slide <?= $active_fin_tab !== 'bank' ? 'active-slide' : '' ?>">
            <h2 class="slide-header" style="margin: 0 0 15px 0;">Готівка (Каса)</h2>
            
            <?php if(empty($cash_history)): ?>
                <div class="empty-card">
                    <i class="fa-solid fa-box-open"></i>
                    <h4>Каса порожня</h4>
                    <p style="font-size: 13px;">Тут ще не було жодної операції з готівкою.</p>
                </div>
            <?php else: ?>
                <?php foreach($cash_history as $c): 
                    $is_in = ($c['type'] === 'income');
                ?>
                    <div class="tx-card">
                        <div class="tx-icon <?= $is_in ? 'tx-in' : 'tx-out' ?>"><i class="fa-solid <?= $is_in ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i></div>
                        <div class="tx-info">
                            <div class="tx-title" title="<?= htmlspecialchars($c['reason']) ?>"><?= htmlspecialchars($c['reason']) ?></div>
                            <div class="tx-meta">
                                <span class="tx-meta-item"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y, H:i', strtotime($c['created_at'])) ?></span>
                                <span class="tx-meta-item"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($c['full_name'] ?? 'Невідомо') ?></span>
                                
                                <!-- 🛡️ ВІДМІТКА РЕВІЗОРА -->
                                <?php if(!empty($c['is_audited'])): ?>
                                    <span class="tx-meta-item" style="color: var(--green);"><i class="fa-solid fa-shield-halved"></i> Перевірено</span>
                                <?php else: ?>
                                    <span class="tx-meta-item" style="color: #f59e0b;"><i class="fa-solid fa-hourglass-half"></i> Очікує перевірки</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tx-amount <?= $is_in ? 'c-in' : 'c-out' ?>">
                            <?= $is_in ? '+' : '-' ?><?= number_format($c['amount'], 2, '.', ' ') ?> ₴
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="pag-wrap"><?php for($i=1;$i<=ceil($cash_total_count/$limit);$i++): ?><a href="?p_cash=<?=$i?>&tab=cash&acc=<?=$selected_acc?>" class="pag-link <?=$p_cash==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
            <?php endif; ?>
        </div>

        <!-- ========================================== -->
        <!-- 🏦 БАНК -->
        <!-- ========================================== -->
        <div class="slide <?= $active_fin_tab === 'bank' ? 'active-slide' : '' ?>">
            <h2 class="slide-header" style="margin: 0 0 15px 0;">Рахунки</h2>
            
            <?php if(!empty($mono_accounts)): ?>
                <!-- 🔥 CSP ФІКС: Замінено onchange на клас js-change-account -->
                <select class="input-dark js-change-account" style="box-sizing: border-box; display: block; width: 100%; -webkit-appearance: none; appearance: none; font-size: 16px;">
                    <option value="all" <?= $selected_acc === 'all' ? 'selected' : '' ?>>Усі транзакції</option>
                    <?php foreach($mono_accounts as $acc): ?>
                        <option value="<?= htmlspecialchars($acc['external_id']) ?>" <?= $selected_acc === $acc['external_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($acc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <div id="bank-tx-list" style="transition: opacity 0.3s ease;">
                <?php if(empty($bank_history)): ?>
                    <div class="empty-card">
                        <i class="fa-solid fa-receipt"></i>
                        <h4>Немає транзакцій</h4>
                        <p style="font-size: 13px;">За вибраним рахунком рух коштів відсутній.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($bank_history as $t): 
                        $amt = $t['amount'] / 100;
                        $is_in = ($amt > 0);
                    ?>
                        <div class="tx-card">
                            <div class="tx-icon <?= $is_in ? 'tx-in' : 'tx-out' ?>"><i class="fa-solid <?= $is_in ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i></div>
                            <div class="tx-info">
                                <div class="tx-title" title="<?= htmlspecialchars($t['description'] ?? 'Переказ') ?>"><?= htmlspecialchars($t['description'] ?? 'Переказ') ?></div>
                                <div class="tx-meta">
                                    <span class="tx-meta-item"><i class="fa-regular fa-clock"></i> <?= date('d.m.Y, H:i', $t['time'] ?? strtotime($t['created_at'])) ?></span>
                                    <?php if($selected_acc === 'all'): ?>
                                        <span class="tx-meta-item"><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($t['account_name'] ?? 'Рахунок') ?></span>
                                    <?php endif; ?>
                                    
                                    <!-- 🛡️ ВІДМІТКА РЕВІЗОРА -->
                                    <?php if(!empty($t['is_audited'])): ?>
                                        <span class="tx-meta-item" style="color: var(--green);"><i class="fa-solid fa-shield-halved"></i> Перевірено</span>
                                    <?php else: ?>
                                        <span class="tx-meta-item" style="color: #f59e0b;"><i class="fa-solid fa-hourglass-half"></i> Очікує перевірки</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tx-amount <?= $is_in ? 'c-in' : 'c-out' ?>">
                                <?= $is_in ? '+' : '' ?><?= number_format($amt, 2, '.', ' ') ?> ₴
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($bank_total_count/$limit);$i++): ?><a href="?p_bank=<?=$i?>&tab=bank&acc=<?=$selected_acc?>" class="pag-link <?=$p_bank==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- МІСЦЕВЕ НИЖНЄ МЕНЮ -->
<nav class="view-bar" style="grid-template-columns: repeat(2, 1fr); display: grid;">
    <!-- 🔥 CSP ФІКС: Замінено onclick на класи js-go-to-fin-tab -->
    <a href="javascript:void(0)" class="nav-item <?= $active_fin_tab !== 'bank' ? 'active' : '' ?> js-go-to-fin-tab" data-idx="0" id="fn0"><i class="fa-solid fa-wallet"></i>ГОТІВКА</a>
    <a href="javascript:void(0)" class="nav-item <?= $active_fin_tab === 'bank' ? 'active' : '' ?> js-go-to-fin-tab" data-idx="1" id="fn1"><i class="fa-solid fa-building-columns"></i>БАНК</a>
</nav>
