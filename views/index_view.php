<?php $safe_nonce = defined('CSP_NONCE') ? CSP_NONCE : ''; ?>
<style nonce="<?= $safe_nonce ?>">
    /* =====================================================================
       СИСТЕМА ДИЗАЙНУ (SENIOR LEVEL) - 100% CSP COMPLIANT
       ===================================================================== */
    
    /* 1. БАЗОВІ ФУНКЦІЇ ТА СЛАЙДЕР */
    .ui-card-wrapper:focus-within { z-index: 50; }
    #slider { width: 600% !important; display: flex; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); align-items: flex-start; }
    .slide { width: 16.66666% !important; flex-shrink: 0; padding: 0 20px 120px; box-sizing: border-box; height: 0; overflow: hidden; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
    .slide.active-slide { height: auto; overflow: visible; opacity: 1; pointer-events: auto; }
    html[data-layout="tile"] #back-btn-dash.active { display: flex !important; }
    html[data-layout="tile"] #main-icon-dash.hidden { display: none !important; }

    /* 2. ВКЛАДКИ (ТАБИ) */
    .ui-tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; scrollbar-width: none; }
    .ui-tabs::-webkit-scrollbar { display: none; }
    .ui-tab { white-space: nowrap; padding: 10px 16px; background: var(--surface); border: 1.5px solid var(--border); border-radius: 16px; font-size: 12px; font-weight: 800; color: var(--txt-muted); text-decoration: none; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.02); display: inline-block; }
    .ui-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }

    /* 3. ПОРОЖНІ СТАНИ ТА АНІМАЦІЇ */
    .ui-empty { text-align: center; padding: 40px 20px; background: var(--surface); border-radius: 16px; border: 2px dashed var(--border); margin-bottom: 15px; color: var(--txt-muted); font-size: 13px; }
    .ui-empty i { font-size: 36px; color: var(--border); margin-bottom: 12px; display: block; opacity: 0.5; }
    @keyframes uiFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .slide .ui-card, .slide .ui-empty, .dash-tile { animation: uiFadeIn 0.3s ease-out forwards; }

    /* 4. КАРТКИ (ГОЛОВНИЙ БЛОК) */
    .ui-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 20px; margin-bottom: 15px; box-shadow: var(--shadow-soft); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; }
    .ui-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); border-color: var(--accent); }
    .ui-card.archived { opacity: 0.65; border: 1px dashed var(--txt-muted); box-shadow: none; filter: grayscale(20%); }
    .ui-card.archived:hover { opacity: 1; filter: grayscale(0%); }

    /* 5. БЕЙДЖІ ТА МЕТА-ІНФОРМАЦІЯ */
    .ui-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; align-items: center; }
    .ui-badge { font-size: 10px; font-weight: 800; padding: 6px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
    .ui-badge.purple { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .ui-badge.red { background: rgba(239,68,68,0.1); color: #ef4444; }
    .ui-badge.green { background: rgba(16,185,129,0.1); color: var(--green); }
    .ui-badge.blue { background: rgba(59,130,246,0.1); color: var(--accent); }
    .ui-badge.orange { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .ui-badge.gray { background: var(--line); color: var(--txt-muted); }
    .ui-badge.right { margin-left: auto; }

    /* 6. ТИПОГРАФІКА В КАРТКАХ (ФІКС ШРИФТУ ЦИФР) */
    .ui-title { font-family: 'Manrope', sans-serif; font-size: 16px; font-weight: 800; margin-bottom: 8px; color: var(--txt); line-height: 1.4; letter-spacing: -0.3px; }
    .ui-desc { font-size: 13px; color: var(--txt-muted); line-height: 1.5; margin-bottom: 15px; }
    .ui-author { font-size: 11px; color: var(--txt-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; font-weight: 700; }
    
    /* 7. ПРОГРЕС БАР (ШКАЛА) ТА КВОРУМ */
    .ui-progress-bg { height: 8px; background: var(--line); border-radius: 10px; overflow: hidden; margin-bottom: 8px; position: relative; }
    .ui-progress-fill { height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-hover)); border-radius: 10px; transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .ui-progress-meta { display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; color: var(--txt-muted); margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* 8. КНОПКИ ТА ДІЇ */
    .ui-actions { display: flex; flex-direction: column; gap: 10px; }
    .ui-btn { width: 100%; padding: 14px; border-radius: 14px; font-size: 14px; font-weight: 800; text-align: center; cursor: pointer; transition: 0.2s; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; box-sizing: border-box; }
    .ui-btn.primary { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: #fff; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2); }
    .ui-btn.primary:active { transform: scale(0.98); }
    .ui-btn.secondary { background: transparent; border: 1.5px dashed var(--border); color: var(--txt-muted); }
    .ui-btn.secondary:hover { border-color: var(--accent); color: var(--accent); }
    .ui-btn.danger { background: var(--red); color: #fff; }

    /* 9. СПЕЦІАЛЬНІ БЛОКИ (Алерти, Захист, Звіти) */
    .ui-alert { padding: 12px 16px; border-radius: 12px; font-size: 12px; font-weight: 800; display: flex; align-items: center; gap: 10px; margin-bottom: 15px; justify-content: center; }
    .ui-alert.purple { background: rgba(139,92,246,0.1); color: #8b5cf6; border: 1px solid rgba(139,92,246,0.2); }
    .ui-alert.green { background: rgba(16,185,129,0.1); color: var(--green); border: 1px solid rgba(16,185,129,0.2); }
    .ui-alert.orange { background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); }
    .ui-alert.blue { background: rgba(59,130,246,0.1); color: var(--accent); border: 1px solid rgba(59,130,246,0.2); }
    
    .ui-defense { background: rgba(245,158,11,0.08); padding: 15px; border-left: 3px solid #f59e0b; border-radius: 8px; margin-bottom: 15px; }
    .ui-defense-title { font-size: 11px; font-weight: 800; color: #d97706; margin-bottom: 6px; text-transform: uppercase; }
    .ui-defense-text { font-size: 13px; font-style: italic; color: var(--txt); line-height: 1.5; }

    .ui-report { background: rgba(59, 130, 246, 0.05); border: 1px dashed var(--accent); border-radius: 14px; padding: 15px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; text-decoration: none; transition: 0.2s; }
    .ui-report:hover { background: rgba(59, 130, 246, 0.1); }
    .ui-report i { font-size: 24px; color: var(--accent); }
    .ui-report-tt { font-size: 14px; font-weight: 800; color: var(--accent); display: block; }
    .ui-report-sub { font-size: 11px; color: var(--txt-muted); }

    /* 10. УТИЛІТИ ДЛЯ ПОДІЙ ТА ФОРМ */
    .ui-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .ui-header-title { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; margin: 0; color: var(--txt); }
    .ui-btn-icon { width: 40px; height: 40px; border-radius: 12px; background: var(--accent); color: #fff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    
    .ui-event-meta { text-align: right; display: flex; flex-direction: column; gap: 4px; }
    .ui-event-date { font-size: 13px; font-weight: 800; color: var(--accent); }
    .ui-event-time { opacity: 0.7; font-size: 11px; margin-left: 4px; }
    .ui-event-seats { font-size: 11px; font-weight: 800; color: var(--txt-muted); }
    .ui-text-green { color: var(--green); font-size: 13px; }
    .ui-text-red { color: var(--red); font-size: 13px; }
    
    .ui-input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid var(--border); background: var(--bg); color: var(--txt); font-family: inherit; font-size: 14px; margin-bottom: 12px; box-sizing: border-box; }
    .ui-textarea { height: 80px; resize: none; }
    
    /* 11. МОДАЛКИ */
    .ui-modal-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px; }
    .ui-modal-icon.blue { background: rgba(59,130,246,0.1); color: var(--accent); }
    .ui-modal-icon.green { background: rgba(16,185,129,0.1); color: var(--green); }
    .ui-modal-title { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; margin-bottom: 12px; }
    .ui-modal-desc { font-size: 12px; color: var(--txt-muted); line-height: 1.5; margin-bottom: 20px; }
    .ui-modal-box { max-width: 340px; text-align: center; padding: 30px 24px; }
    .ui-btn-text { background: transparent; border: none; color: var(--txt-muted); font-weight: 800; font-size: 14px; width: 100%; padding: 12px; margin-top: 5px; cursor: pointer; }

    /* 12. СТИЛІ РЕЗУЛЬТАТІВ У МОДАЛКАХ */
    .ui-res-card { padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 8px; position: relative; overflow: hidden; background: var(--surface); }
    .ui-res-bar { position: absolute; left: 0; top: 0; bottom: 0; background: rgba(99,102,241,0.1); width: 0%; transition: width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .ui-res-content { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; color: var(--txt); }
    .ui-res-label { font-size: 13px; font-weight: 700; }
    .ui-res-val { color: var(--accent); margin: 0; font-size: 14px; font-weight: 800; font-family: 'DM Mono', monospace; }
    
    .ui-token-alert { padding: 12px; background: rgba(139,92,246,0.05); color: #8b5cf6; border-radius: 12px; font-size: 12px; text-align: center; margin-bottom: 15px; border: 1px dashed #8b5cf6; }
    
    .ui-list-item { display: flex; align-items: center; gap: 12px; padding: 12px 10px; border-bottom: 1px solid var(--line); }
    .ui-list-item:last-child { border-bottom: none; }
    .ui-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; flex-shrink: 0; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
    .ui-avatar.token { background: rgba(139,92,246,0.1); color: #8b5cf6; box-shadow: none; }
    
    .ui-list-name { color: var(--txt); font-size: 13px; font-weight: 800; }
    .ui-list-name.token-font { font-family: 'DM Mono', monospace; font-size: 14px; letter-spacing: 0.5px; }
    .ui-list-extra { font-size: 11px; font-weight: 800; color: var(--accent); margin-top: 4px; }
    .ui-list-guests { font-size: 11px; color: var(--txt-muted); font-weight: 700; margin-top: 4px;}

    /* 13. УТИЛІТНІ ВІДСТУПИ КОНТЕНТУ */
    .ppo-alert-wrapper { padding: 16px 20px 0; }
    .ppo-slider-wrapper { margin-top: 5px; }
    .ppo-card-container { padding: 0 20px; }
    .ppo-tab-container { margin: 20px 0 15px 5px; }
</style>

<!-- АЛЕРТИ СТАТУСУ КОРИСТУВАЧА -->
<div class="ppo-alert-wrapper">
    <?php if($user_status === 'pending'): ?>
        <div class="ui-alert orange" style="margin:0;"><i class="fa-solid fa-user-clock"></i> Ваш акаунт очікує схвалення.</div>
    <?php elseif($user_status === 'candidate' || $user_status === 'former'): ?>
        <?php if(!empty($has_admission_poll)): ?>
            <div class="ui-alert blue" style="margin:0;"><i class="fa-solid fa-clock-rotate-left fa-spin"></i> Голосування про вступ триває.</div>
        <?php else: ?>
            <div class="ui-card" style="text-align:center; padding:25px 20px; margin:0;">
                <div class="ui-modal-icon blue" style="margin-bottom:15px; width:50px; height:50px; font-size:20px;"><i class="fa-solid fa-file-signature"></i></div>
                <h4 class="ui-title" style="margin-bottom:8px;">Режим перегляду</h4>
                <p class="ui-desc">Щоб брати участь у житті профспілки, необхідно підписати заяву.</p>
                <button type="button" class="ui-btn primary js-open-admission-modal">Подати заяву (КЕП)</button>
            </div>
        <?php endif; ?>
    <?php elseif(!empty($is_newbee)): ?>
        <div class="ui-alert orange" style="margin:0;"><i class="fa-solid fa-hourglass-start fa-spin"></i> Голос почне впливати на кворум через 72 год.</div>
    <?php endif; ?>
</div>

<!-- 🔥 КАРТКА ФІНАНСІВ (СЕНЬЙОР-ДИЗАЙН) -->
<div style="padding: 16px 20px 0;">
    <?php if (isset($fin)): ?>
        <div class="fin-card js-toggle-fin">
            <i class="fa-solid fa-wallet fin-bg-icon"></i>
            <span class="fin-label">Загальний баланс ППО</span>
            <h2 class="fin-val"><?= number_format($fin['total'] ?? 0, 2, '.', ' ') ?> ₴</h2>
            
            <div class="fin-row">
                <div class="fin-item">
                    <span style="font-size:10px; opacity:0.8; text-transform:uppercase; margin-bottom:2px;">Готівка (Каса)</span>
                    <span class="fin-sub-val"><?= number_format($fin['cash_balance'] ?? 0, 2, '.', ' ') ?> ₴</span>
                </div>
                <div class="fin-item">
                    <span style="font-size:10px; opacity:0.8; text-transform:uppercase; margin-bottom:2px;">Банківські рахунки</span>
                    <span class="fin-sub-val">
                        <?php 
                        $bank_sum = 0;
                        foreach(($fin['accounts'] ?? []) as $acc) { $bank_sum += ($acc['balance']/100); }
                        echo number_format($bank_sum, 2, '.', ' ');
                        ?> ₴
                    </span>
                </div>
                <div id="fin-chevron" style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; background:rgba(255,255,255,0.2); border-radius:10px; transition:0.3s;">
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- ДЕТАЛІ ФІНАНСІВ (Розгортання без інлайн-стилів) -->
        <div id="fin-box" style="margin: 0 0 15px 0;">
            <?php foreach(($fin['accounts'] ?? []) as $acc): ?>
                <div class="fin-detail-row">
                    <span style="color:var(--txt-muted);"><i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars($acc['name'] ?: 'Рахунок') ?></span>
                    <span style="font-family:'DM Mono'; font-weight:800;"><?= number_format($acc['balance']/100, 2, '.', ' ') ?> ₴</span>
                </div>
            <?php endforeach; ?>
            <div class="fin-detail-row">
                <span style="color:var(--txt-muted);"><i class="fa-solid fa-money-bill-wave text-green"></i> Залишок в касі</span>
                <span class="text-green" style="font-family:'DM Mono'; font-weight:800;"><?= number_format($fin['cash_balance'] ?? 0, 2, '.', ' ') ?> ₴</span>
            </div>
            <a href="finance_history.php" class="ui-btn secondary" style="margin-top:12px; padding:10px;">
                <i class="fa-solid fa-clock-rotate-left"></i> Історія транзакцій
            </a>
        </div>
    <?php endif; ?>
</div>

<div id="slider-container" class="ppo-slider-wrapper">
    <div id="slider">
        
        <!-- СЛАЙД 1: DASHBOARD -->
        <div class="slide">
            <h2 class="ui-header-title ppo-tab-container">Головне меню</h2>
            <div class="dashboard-grid" style="padding: 0 20px;">
                <a href="javascript:void(0)" class="dash-tile js-go-to" data-idx="1">
                    <div class="dash-icon" style="background:rgba(59,91,219,0.1); color:var(--accent);"><i class="fa-solid fa-newspaper"></i></div>
                    <div class="dash-title">Новини</div>
                    <div class="dash-sub">Стрічка ППО</div>
                </a>
                <a href="javascript:void(0)" class="dash-tile js-go-to" data-idx="2">
                    <div class="dash-icon" style="background:rgba(16,185,129,0.1); color:var(--green);"><i class="fa-solid fa-check-to-slot"></i></div>
                    <div class="dash-title">Голосування</div>
                    <div class="dash-sub"><?= $badges['polls'] ?? 0 ?> активних</div>
                </a>
                <a href="javascript:void(0)" class="dash-tile js-go-to" data-idx="3">
                    <div class="dash-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;"><i class="fa-solid fa-id-card-clip"></i></div>
                    <div class="dash-title">Висування</div>
                    <div class="dash-sub">Кандидати та ідеї</div>
                </a>
                <a href="javascript:void(0)" class="dash-tile js-go-to" data-idx="4">
                    <div class="dash-icon" style="background:rgba(139,92,246,0.1); color:#8b5cf6;"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="dash-title">Ініціативи</div>
                    <div class="dash-sub">Петиції</div>
                </a>
                <a href="javascript:void(0)" class="dash-tile js-go-to" data-idx="5">
                    <div class="dash-icon" style="background:rgba(52,211,153,0.1); color:#2dd4bf;"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="dash-title">Заходи</div>
                    <div class="dash-sub">Події ППО</div>
                </a>
                <a href="ppo_info.php" class="dash-tile">
                    <div class="dash-icon" style="background:rgba(71,85,105,0.1); color:var(--txt-muted);"><i class="fa-solid fa-circle-info"></i></div>
                    <div class="dash-title">Про ППО</div>
                    <div class="dash-sub">Склад та контакти</div>
                </a>
            </div>
        </div>

        <!-- СЛАЙД 2: СТРІЧКА НОВИН -->
        <div class="slide">
            <h2 class="ui-header-title ppo-tab-container">Стрічка новин</h2>
            <div class="ppo-card-container">
                <?php if(empty($news)): ?>
                    <div class="ui-empty"><i class="fa-regular fa-newspaper"></i>Новин поки що немає.</div>
                <?php else: ?>
                    <?php foreach($news as $n): 
                        $preview_text = str_replace(['<br>', '<br />', '<br/>'], ' ', $n['content']);
                        $preview_text = strip_tags(html_entity_decode($preview_text));
                        $preview_text = str_replace('**', '', $preview_text);
                        
                        $modal_text = htmlspecialchars($n['content'], ENT_QUOTES, 'UTF-8');
                        $modal_text = str_replace(['&lt;br /&gt;', '&lt;br&gt;', '&lt;br/&gt;'], '<br>', $modal_text);
                        $modal_text = str_replace(['&lt;b&gt;', '&lt;/b&gt;', '&lt;strong&gt;', '&lt;/strong&gt;'], ['<b>', '</b>', '<b>', '</b>'], $modal_text);
                        $modal_text = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $modal_text);
                        $modal_text = nl2br($modal_text);
                    ?>
                        <div class="ui-card">
                            <div class="ui-badges">
                                <span class="ui-badge blue"><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y', strtotime($n['created_at'])) ?></span>
                            </div>
                            <h4 class="ui-title"><?= htmlspecialchars($n['title']) ?></h4>
                            <p class="ui-desc"><?= mb_strimwidth($preview_text, 0, 150, '...') ?></p>
                            <button class="ui-btn secondary js-open-news-modal" data-title="<?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?>" data-content="<?= $modal_text ?>">Читати повністю</button>
                        </div>
                    <?php endforeach; ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($news_count/$limit);$i++): ?><a href="?p_n=<?=$i?>&tab=news" class="pag-link <?=$p_news==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- СЛАЙД 3: ГОЛОСУВАННЯ -->
        <div class="slide">
            <div class="ui-header-flex" style="margin: 20px 20px 15px;">
                <h2 class="ui-header-title">Голосування</h2>
                <?php if(!empty($can_act)): ?>
                    <button class="ui-btn-icon js-open-modal-id" data-target="excludeModal" title="Ініціювати виключення"><i class="fa-solid fa-user-minus"></i></button>
                <?php endif; ?>
            </div>

            <?php $s_p = $_GET['sub_poll'] ?? 'action'; ?>
            <div class="ui-tabs" style="padding: 0 20px;">
                <a href="?tab=polls&sub_poll=action" class="ui-tab <?= $s_p == 'action' ? 'active' : '' ?>">Потребують дії</a>
                <a href="?tab=polls&sub_poll=ongoing" class="ui-tab <?= $s_p == 'ongoing' ? 'active' : '' ?>">Тривають</a>
                <a href="?tab=polls&sub_poll=completed" class="ui-tab <?= $s_p == 'completed' ? 'active' : '' ?>">Завершені</a>
            </div>
            
            <div class="ppo-card-container">
                <?php if(empty($polls)): ?>
                    <div class="ui-empty"><i class="fa-solid fa-box-open"></i>У цій категорії порожньо.</div>
                <?php else: ?>
                    <?php foreach($polls as $p): 
                        $req = !empty($p['required_quorum']) ? $p['required_quorum'] : $getThreshold($total_members, $p['threshold_type']);
                        $pct = min(100, round(($p['current_votes'] / max(1, $req)) * 100));
                        $has_quorum = ($p['current_votes'] >= $req);
                        $is_fin = (strtotime($p['end_date']) < time() || (string)$p['is_active'] === '0' || ($p['current_votes'] >= $total_members));
                        
                        $my_v = \Core\DB::fetchColumn("SELECT option_id FROM votes WHERE user_id=? AND poll_id=?", [$user_id, $p['id']]);
                        if (!$my_v && $p['is_secret'] == 2) {
                            $my_v = \Core\DB::fetchColumn("SELECT id FROM voting_participants WHERE user_id=? AND target_type='poll' AND target_id=?", [$user_id, $p['id']]);
                        }

                        $t_raw = $p['threshold_type'];
                        $t_disp = is_numeric($t_raw) ? $t_raw . '%' : ($t_raw === '50+1' ? '50% + 1' : $t_raw);
                    ?>
                        <div class="ui-card <?= $is_fin ? 'archived' : '' ?>">
                            <div class="ui-badges">
                                <span class="ui-badge <?= $p['is_secret'] == 2 ? 'purple' : (!empty($p['is_secret']) ? 'red' : 'green') ?>">
                                    <?= $p['is_secret'] == 2 ? '🔒 Таємне' : (!empty($p['is_secret']) ? '🛡️ Конфіденційне' : '👁️ Відкрите') ?>
                                </span>
                                <?php if($p['require_kep'] == 1): ?>
                                    <span class="ui-badge blue"><i class="fa-solid fa-fingerprint"></i> КЕП</span>
                                <?php endif; ?>
                                <?php if($is_fin): ?>
                                    <span class="ui-badge <?= $has_quorum ? 'green' : 'red' ?> right"><?= $has_quorum ? '✅ Завершено' : '❌ Немає кворуму' ?></span>
                                <?php else: ?>
                                    <span class="ui-badge gray right"><i class="fa-regular fa-clock"></i> До <?= date('d.m', strtotime($p['end_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="ui-title"><?= htmlspecialchars($p['title']) ?></h4>
                            
                            <?php if(!empty($p['description'])): ?>
                                <?php 
                                $is_audit_report = (mb_stripos($p['title'], 'Акт') !== false && mb_stripos($p['title'], 'ревізії') !== false); 
                                if($is_audit_report): 
                                    $report_id = \Core\DB::fetchColumn("SELECT id FROM audit_reports WHERE poll_id = ?", [$p['id']]);
                                ?>
                                    <a href="report.php?id=<?= $report_id ?>" class="ui-report">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                        <div>
                                            <strong class="ui-report-tt">Офіційний звіт</strong>
                                            <span class="ui-report-sub">Реєстр перевірених транзакцій</span>
                                        </div>
                                    </a>
                                <?php else: 
                                    $desc_html = htmlspecialchars($p['description'], ENT_QUOTES, 'UTF-8');
                                    $desc_html = str_replace(['&lt;br /&gt;', '&lt;br&gt;', '&lt;br/&gt;'], '<br>', $desc_html);
                                    $desc_html = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $desc_html);
                                ?>
                                    <p class="ui-desc"><?= nl2br($desc_html) ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if(!empty($p['target_comment'])): ?>
                                <div class="ui-defense">
                                    <div class="ui-defense-title"><i class="fa-solid fa-scale-balanced"></i> Право на захист:</div>
                                    <div class="ui-defense-text">«<?= nl2br(htmlspecialchars($p['target_comment'])) ?>»</div>
                                </div>
                            <?php endif; ?>

                            <div class="ui-progress-bg v-bar">
                                <div class="ui-progress-fill v-fill js-apply-width" id="poll-fill-<?= $p['id'] ?>" data-w="<?= $pct ?>"></div>
                            </div>
                            <div class="ui-progress-meta">
                                <span id="poll-text-<?= $p['id'] ?>">Кворум: <?= $p['current_votes'] ?> / <?= $req ?> (<?= htmlspecialchars($t_disp) ?>)</span>
                                <span id="poll-pct-<?= $p['id'] ?>"><?= $pct ?>%</span>
                            </div>

                            <div class="poll-actions-container ui-actions" id="poll-actions-<?= $p['id'] ?>">
                            <?php if(!$is_fin && !$my_v && !empty($can_act)): 
                                $opts = \Core\DB::fetchAll("SELECT * FROM poll_options WHERE poll_id=?", [$p['id']]) ?: [];
                                foreach($opts as $o): ?>
                                    <button class="ui-btn primary js-vote-btn" 
                                        data-is-secret="<?= $p['is_secret'] ?>" 
                                        data-poll-id="<?= $p['id'] ?>" 
                                        data-option-id="<?= $o['id'] ?>" 
                                        data-require-kep="<?= $p['require_kep'] ?>">
                                        <?= htmlspecialchars($o['option_text']) ?>
                                    </button>
                                <?php endforeach; 
                            elseif($my_v): ?>
                                <?php if($p['is_secret'] == 2): ?>
                                    <div class="ui-alert purple"><i class="fa-solid fa-lock"></i> Анонімний голос в урні</div>
                                <?php else: ?>
                                    <div class="ui-alert green"><i class="fa-solid fa-check"></i> Ваш голос враховано</div>
                                <?php endif; ?>
                            <?php endif; ?>
                            </div>

                            <?php if(empty($p['is_secret']) || $is_fin): ?>
                                <button class="ui-btn secondary js-view-results" style="margin-top: 15px;"
                                    data-type="poll" data-id="<?= $p['id'] ?>" 
                                    data-title="<?= htmlspecialchars($p['title'], ENT_QUOTES) ?>" 
                                    data-is-secret="<?= !empty($p['is_secret']) ? 1 : 0 ?>">
                                    <i class="fa-solid fa-chart-pie"></i> Результати
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($polls_count/$limit);$i++): ?><a href="?p_p=<?=$i?>&tab=polls&sub_poll=<?=$s_p?>" class="pag-link <?=$p_poll==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- СЛАЙД 4: ВИСУВАННЯ / ЗБІР ІДЕЙ -->
        <div class="slide">
            <h2 class="ui-header-title ppo-tab-container">Збір пропозицій</h2>

            <?php $s_n = $_GET['sub_nom'] ?? 'action'; ?>
            <div class="ui-tabs" style="padding: 0 20px;">
                <a href="?tab=noms&sub_nom=action" class="ui-tab <?= $s_n == 'action' ? 'active' : '' ?>">Потребують дії</a>
                <a href="?tab=noms&sub_nom=ongoing" class="ui-tab <?= $s_n == 'ongoing' ? 'active' : '' ?>">Тривають</a>
                <a href="?tab=noms&sub_nom=completed" class="ui-tab <?= $s_n == 'completed' ? 'active' : '' ?>">Завершені</a>
            </div>

            <div class="ppo-card-container">
                <?php if(empty($noms)): ?>
                    <div class="ui-empty"><i class="fa-solid fa-box-open"></i>У цій категорії порожньо.</div>
                <?php else: ?>
                    <?php foreach($noms as $nm): 
                        $req = $getThreshold($total_members, $nm['threshold_type']);
                        $pct = min(100, round(($nm['current_votes'] / max(1, $req)) * 100));
                        $is_fin = (strtotime($nm['end_date']) < time() || $nm['is_processed'] == 1);
                        
                        $my_nom = \Core\DB::fetchColumn("SELECT nominee_id FROM nomination_votes WHERE nomination_id=? AND nominator_id=?", [$nm['id'], $user_id]);
                        if (!$my_nom) {
                            $my_nom = \Core\DB::fetchColumn("SELECT 1 FROM nomination_votes WHERE nomination_id=? AND nominator_id=? AND idea_text IS NOT NULL", [$nm['id'], $user_id]);
                        }
                        if (!$my_nom && $nm['is_secret'] == 2) {
                            $my_nom = \Core\DB::fetchColumn("SELECT id FROM voting_participants WHERE user_id=? AND target_type='nomination' AND target_id=?", [$user_id, $nm['id']]);
                        }

                        $t_raw = $nm['threshold_type'];
                        $t_disp = is_numeric($t_raw) ? $t_raw . '%' : ($t_raw === '50+1' ? '50% + 1' : $t_raw);
                    ?>
                        <div class="ui-card <?= $is_fin ? 'archived' : '' ?>">
                            <div class="ui-badges">
                                <span class="ui-badge <?= $nm['is_secret'] == 2 ? 'purple' : (!empty($nm['is_secret']) ? 'red' : 'green') ?>">
                                    <?= $nm['is_secret'] == 2 ? '🔒 Таємне' : (!empty($nm['is_secret']) ? '🛡️ Конфіденційне' : '👁️ Відкрите') ?>
                                </span>
                                <?php if(!empty($nm['require_kep']) && $nm['require_kep'] == 1): ?>
                                    <span class="ui-badge blue"><i class="fa-solid fa-fingerprint"></i> КЕП</span>
                                <?php endif; ?>
                                <?php if($is_fin): ?>
                                    <span class="ui-badge <?= $nm['current_votes'] >= $req ? 'green' : 'red' ?> right"><?= $nm['current_votes'] >= $req ? '✅ Завершено' : '❌ Немає кворуму' ?></span>
                                <?php else: ?>
                                    <span class="ui-badge gray right"><i class="fa-regular fa-clock"></i> До <?= date('d.m', strtotime($nm['end_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="ui-title"><?= htmlspecialchars($nm['title']) ?></h4>

                            <?php if(!empty($nm['description'])): 
                                $desc_html = htmlspecialchars($nm['description'], ENT_QUOTES, 'UTF-8');
                                $desc_html = str_replace(['&lt;br /&gt;', '&lt;br&gt;', '&lt;br/&gt;'], '<br>', $desc_html);
                                $desc_html = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $desc_html);
                            ?>
                                <p class="ui-desc"><?= nl2br($desc_html) ?></p>
                            <?php endif; ?>

                            <div class="ui-progress-bg v-bar">
                                <div class="ui-progress-fill v-fill js-apply-width" id="nom-fill-<?= $nm['id'] ?>" data-w="<?= $pct ?>"></div>
                            </div>
                            <div class="ui-progress-meta">
                                <span id="nom-text-<?= $nm['id'] ?>">Кворум: <?= $nm['current_votes'] ?> / <?= $req ?> (<?= htmlspecialchars($t_disp) ?>)</span>
                                <span id="nom-pct-<?= $nm['id'] ?>"><?= $pct ?>%</span>
                            </div>

                            <div class="poll-actions-container ui-actions" id="nom-actions-<?= $nm['id'] ?>">
                            <?php if(!$is_fin && !$my_nom && !empty($can_act)): ?>
                                <?php if($nm['target_role'] === 'regular'): ?>
                                    <input type="text" id="idea-input-<?= $nm['id'] ?>" class="ui-input" placeholder="💡 Напишіть вашу ідею / варіант..." style="margin-bottom:0;">
                                    <button class="ui-btn primary js-submit-idea" 
                                        data-nom-id="<?= $nm['id'] ?>" 
                                        data-is-secret="<?= $nm['is_secret'] ?>"
                                        data-require-kep="<?= $nm['require_kep'] ?? 0 ?>">
                                        <i class="fa-regular fa-paper-plane"></i> Відправити
                                    </button>
                                <?php else: ?>
                                    <div style="position:relative;">
                                        <input type="text" class="ui-input js-search-nominee" style="margin-bottom:0;" placeholder="🔍 Пошук кандидата..." 
                                            data-nom-id="<?= $nm['id'] ?>" 
                                            data-is-secret="<?= $nm['is_secret'] ?>"
                                            data-require-kep="<?= $nm['require_kep'] ?? 0 ?>">
                                        <div id="res_<?= $nm['id'] ?>" style="background:var(--surface); border:1px solid var(--border); border-top:none; border-radius:0 0 12px 12px; display:none; position: absolute; z-index: 999; box-shadow: 0 15px 30px rgba(0,0,0,0.3); width: 100%;"></div>
                                    </div>
                                <?php endif; ?>
                            <?php elseif($my_nom): ?>
                                <?php if($nm['is_secret'] == 2): ?>
                                    <div class="ui-alert purple"><i class="fa-solid fa-lock"></i> Анонімний голос в урні</div>
                                <?php else: ?>
                                    <div class="ui-alert green"><i class="fa-solid fa-check"></i> Ваш варіант прийнято системою</div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if($nm['is_secret'] == 0 || $is_fin): ?>
                                <button class="ui-btn secondary js-view-results" style="margin-top: 5px;"
                                    data-type="nom" data-id="<?= $nm['id'] ?>" 
                                    data-title="<?= htmlspecialchars($nm['title'], ENT_QUOTES) ?>" 
                                    data-is-secret="<?= $nm['is_secret'] ?>">
                                    <i class="fa-solid fa-list-ol"></i> Результати
                                </button>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($noms_count/$limit);$i++): ?><a href="?p_v=<?=$i?>&tab=noms&sub_nom=<?=$s_n?>" class="pag-link <?=$p_nom==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- СЛАЙД 5: ІНІЦІАТИВИ (ПЕТИЦІЇ) -->
        <div class="slide">
            <div class="ui-header-flex" style="margin: 20px 20px 15px;">
                <h2 class="ui-header-title">Ініціативи</h2>
                <?php if(!empty($can_act)): ?>
                    <button class="ui-btn-icon js-open-modal-id" data-target="petModal"><i class="fa-solid fa-plus"></i></button>
                <?php endif; ?>
            </div>

            <?php $s_pt = $_GET['sub_pet'] ?? 'action'; ?>
            <div class="ui-tabs" style="padding: 0 20px;">
                <a href="?tab=petitions&sub_pet=action" class="ui-tab <?= $s_pt == 'action' ? 'active' : '' ?>">Збір підписів</a>
                <a href="?tab=petitions&sub_pet=ongoing" class="ui-tab <?= $s_pt == 'ongoing' ? 'active' : '' ?>">Підписані</a>
                <a href="?tab=petitions&sub_pet=completed" class="ui-tab <?= $s_pt == 'completed' ? 'active' : '' ?>">Архів</a>
            </div>

            <div class="ppo-card-container">
                <?php if(empty($pets)): ?>
                    <div class="ui-empty"><i class="fa-solid fa-box-open"></i>У цій категорії порожньо.</div>
                <?php else: ?>
                    <?php foreach($pets as $pt): 
                        $req = ceil($total_members * (($pt['quorum_percent'] ?? 50)/100));
                        $current_signs = (int)\Core\DB::fetchColumn("SELECT COUNT(*) FROM petition_signatures WHERE petition_id = ?", [$pt['id']]);
                        $pct = min(100, round(($current_signs / max(1, $req)) * 100));
                        $my_sign = \Core\DB::fetchColumn("SELECT 1 FROM petition_signatures WHERE petition_id=? AND user_id=?", [$pt['id'], $user_id]);
                        $is_ended = (!empty($pt['end_date']) && strtotime($pt['end_date']) < time());
                        $has_quorum = ($current_signs >= $req);
                        
                        $admin_msg = trim((string)($pt['admin_comment'] ?? ''));
                        $has_admin_msg = !empty($admin_msg);
                        $db_status = strtolower(trim((string)($pt['status'] ?? '0')));
                        $is_approved = in_array($db_status, ['approved', '2']);

                        if ($has_admin_msg) { $b_class = $is_approved ? 'green' : 'red'; $b_txt = 'Розглянуто'; }
                        elseif ($has_quorum || $db_status === '1') { $b_class = 'orange'; $b_txt = 'На розгляді'; }
                        elseif ($is_ended && !$has_quorum) { $b_class = 'red'; $b_txt = 'Не набрала кворум'; }
                        else { $b_class = 'blue'; $b_txt = 'Збір підписів'; }

                        $decrypted_author = !empty($pt['author_name']) ? (\Core\Security::decrypt($pt['author_name']) ?: $pt['author_name']) : '';
                        $author_display = !empty($pt['is_anonymous_creator']) ? 'Анонімно' : htmlspecialchars($decrypted_author);
                    ?>
                        <div class="ui-card <?= ($is_ended || $has_admin_msg) ? 'archived' : '' ?>">
                            <div class="ui-badges">
                                <span class="ui-badge <?= $b_class ?>"><i class="fa-solid fa-pen-nib"></i> <?= $b_txt ?></span>
                                <span class="ui-badge <?= !empty($pt['is_secret_voting']) ? 'red' : 'green' ?>"><?= !empty($pt['is_secret_voting']) ? '🔒 Таємне' : '👁️ Відкрите' ?></span>
                            </div>
                            
                            <h4 class="ui-title"><?= htmlspecialchars($pt['title']) ?></h4>
                            <div class="ui-author"><i class="fa-solid fa-user"></i> Автор: <?= $author_display ?></div>

                            <?php if(!empty($pt['description'])): ?>
                                <p class="ui-desc"><?= nl2br(htmlspecialchars($pt['description'])) ?></p>
                            <?php endif; ?>

                            <div class="ui-progress-bg v-bar">
                                <div class="ui-progress-fill v-fill js-apply-width" id="pet-fill-<?= $pt['id'] ?>" data-w="<?= $pct ?>"></div>
                            </div>
                            <div class="ui-progress-meta">
                                <span id="pet-text-<?= $pt['id'] ?>">Підписів: <?= $current_signs ?> / <?= $req ?> (<?= $pt['quorum_percent'] ?? 50 ?>%)</span>
                                <span id="pet-pct-<?= $pt['id'] ?>"><?= $pct ?>%</span>
                            </div>
                            
                            <?php if($has_admin_msg): ?>
                                <div style="margin-bottom:15px; padding:15px; background:var(--bg); border-radius:0 12px 12px 0; border-left:4px solid <?= $is_approved ? 'var(--green)' : 'var(--red)' ?>;">
                                    <div style="font-size:10px; color:var(--txt-muted); text-transform:uppercase; font-weight:800; margin-bottom:5px;">Офіційна відповідь:</div>
                                    <div style="font-size:13px; font-weight:700; color:var(--txt);"><?= nl2br(htmlspecialchars($admin_msg)) ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="poll-actions-container ui-actions" id="pet-actions-<?= $pt['id'] ?>">
                            <?php if(!$has_admin_msg && !$is_ended && !$has_quorum && !empty($can_act)): ?>
                                <?php if(!$my_sign): ?>
                                    <button class="ui-btn primary js-sign-petition" data-pet-id="<?= $pt['id'] ?>"><i class="fa-solid fa-signature"></i> Підписати</button>
                                <?php else: ?>
                                    <div class="ui-alert green"><i class="fa-solid fa-check"></i> Підписано</div>
                                <?php endif; ?>
                            <?php elseif($my_sign): ?>
                                <div class="ui-alert green"><i class="fa-solid fa-check"></i> Ви підтримали ініціативу</div>
                            <?php endif; ?>

                            <?php if($current_signs > 0): ?>
                                <button class="ui-btn secondary js-view-results" style="margin-top: 5px;"
                                    data-type="pet" data-id="<?= $pt['id'] ?>" 
                                    data-title="<?= htmlspecialchars($pt['title'], ENT_QUOTES) ?>" 
                                    data-is-secret="<?= !empty($pt['is_secret_voting']) ? 1 : 0 ?>">
                                    <i class="fa-solid fa-users-viewfinder"></i> Список підписантів
                                </button>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($pets_count/$limit);$i++): ?><a href="?p_pet=<?=$i?>&tab=petitions&sub_pet=<?=$s_pt?>" class="pag-link <?=$p_pet==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- СЛАЙД 6: ЗАХОДИ -->
        <div class="slide">
            <h2 class="ui-header-title ppo-tab-container">Заходи ППО</h2>
            
            <?php $s_e = $_GET['sub_evt'] ?? 'ongoing'; ?>
            <div class="ui-tabs" style="padding: 0 20px;">
                <a href="?tab=events&sub_evt=ongoing" class="ui-tab <?= $s_e == 'ongoing' ? 'active' : '' ?>">Плануються</a>
                <a href="?tab=events&sub_evt=past" class="ui-tab <?= $s_e == 'past' ? 'active' : '' ?>">Відбулися</a>
            </div>

            <?php 
            $filtered_events = [];
            if (!empty($events)) {
                foreach ($events as $ev) {
                    $is_past = (strtotime($ev['event_date']) < time());
                    if ($s_e === 'ongoing' && !$is_past) $filtered_events[] = $ev;
                    if ($s_e === 'past' && $is_past) $filtered_events[] = $ev;
                }
            }
            ?>

            <div class="ppo-card-container">
                <?php if(empty($filtered_events)): ?>
                    <div class="ui-empty"><i class="fa-regular fa-calendar-xmark"></i>У цій категорії подій немає.</div>
                <?php else: ?>
                    <?php foreach($filtered_events as $ev): 
                        $is_past = (strtotime($ev['event_date']) < time());
                        $available = ($ev['max_seats'] > 0) ? ($ev['max_seats'] - $ev['enrolled_count']) : 9999;
                        $my_enroll = \Core\DB::fetchColumn("SELECT 1 FROM event_enrollments WHERE event_id=? AND user_id=?", [$ev['id'], $user_id]);
                        
                        $date_str = date('d.m.Y', strtotime($ev['event_date']));
                        $time_str = date('H:i', strtotime($ev['event_date']));
                        
                        if ($is_past) { $b_class = 'orange'; $b_txt = 'Завершено'; }
                        elseif ($my_enroll) { $b_class = 'green'; $b_txt = 'Ви записані'; }
                        elseif ($available <= 0) { $b_class = 'red'; $b_txt = 'Місць немає'; }
                        else { $b_class = 'blue'; $b_txt = 'Реєстрація'; }
                        
                        $desc_clean = strip_tags($ev['description'] ?? '');
                        $is_long = mb_strlen($desc_clean, 'UTF-8') > 70;
                    ?>
                        <div class="ui-card" style="padding:16px;">
                            <div class="ui-header-flex" style="margin-bottom:12px; align-items:flex-start;">
                                <span class="ui-badge <?= $b_class ?>" style="margin:0;"><i class="fa-solid fa-circle-dot" style="font-size:8px;"></i> <?= $b_txt ?></span>
                                
                                <div class="ui-event-meta">
                                    <span class="ui-event-date"><i class="fa-regular fa-calendar"></i> <?= $date_str ?> <span class="ui-event-time"><?= $time_str ?></span></span>
                                    <?php if($ev['max_seats'] > 0 && !$is_past): ?>
                                        <div class="ui-event-seats">
                                            Місць: <span id="event-seats-<?= $ev['id'] ?>" class="<?= $available > 0 ? 'ui-text-green' : 'ui-text-red' ?>"><?= max(0, $available) ?></span>/<?= $ev['max_seats'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h4 class="ui-title" style="margin-bottom:6px;"><?= htmlspecialchars($ev['title']) ?></h4>
                            <p class="ui-author" style="color:var(--accent);"><i class="fa-solid fa-location-dot"></i> <span style="color:var(--txt-muted);"><?= htmlspecialchars($ev['location']) ?></span></p>

                            <?php if(!empty($desc_clean)): ?>
                                <div class="js-open-news-modal" style="background:var(--bg); border-radius:12px; padding:12px; font-size:13px; color:var(--txt-muted); margin-bottom:15px; cursor:<?= $is_long ? 'pointer' : 'default' ?>;"
                                     data-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8') ?>" 
                                     data-content="<?= htmlspecialchars(nl2br(htmlspecialchars($ev['description'])), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $is_long ? mb_strimwidth($desc_clean, 0, 70, '...') . ' <span style="color:var(--accent); font-weight:800;">Далі</span>' : $desc_clean ?>
                                </div>
                            <?php endif; ?>

                            <div class="poll-actions-container ui-actions" id="event-actions-<?= $ev['id'] ?>">
                                <?php if(!$is_past): ?>
                                    <?php if(!$my_enroll && $available > 0 && !empty($can_act)): ?>
                                        <button class="ui-btn primary js-open-enroll" data-event-id="<?= $ev['id'] ?>" data-max-seats="<?= $available ?>">Записатись</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <button class="ui-btn secondary js-view-results" style="margin-top: 5px;"
                                    data-type="event" data-id="<?= $ev['id'] ?>" 
                                    data-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES) ?>" 
                                    data-is-secret="0">
                                    <i class="fa-solid fa-users"></i> <?= $is_past ? 'Хто брав участь' : 'Хто йде?' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($events_count/$limit);$i++): ?><a href="?p_e=<?=$i?>&tab=events&sub_evt=<?=$s_e?>" class="pag-link <?=$p_evt==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- НИЖНЄ МЕНЮ -->
<nav class="view-bar" id="main-bottom-nav">
    <a href="javascript:void(0)" class="nav-item active js-go-to" data-idx="1" id="n1"><i class="fa-solid fa-newspaper"></i><span>НОВИНИ</span></a>
    <a href="javascript:void(0)" class="nav-item js-go-to" data-idx="2" id="n2"><i class="fa-solid fa-check-to-slot"></i><span>ГОЛОСИ</span><?php if(!empty($badges['polls'])): ?><span class="nav-dot" id="badge-polls"><?= $badges['polls'] ?></span><?php endif; ?></a>
    <a href="javascript:void(0)" class="nav-item js-go-to" data-idx="3" id="n3"><i class="fa-solid fa-id-card-clip"></i><span>ВИСУВАННЯ</span><?php if(!empty($badges['noms'])): ?><span class="nav-dot" id="badge-noms"><?= $badges['noms'] ?></span><?php endif; ?></a>
    <a href="javascript:void(0)" class="nav-item js-go-to" data-idx="4" id="n4"><i class="fa-solid fa-bullhorn"></i><span>ПЕТИЦІЇ</span><?php if(!empty($badges['pets'])): ?><span class="nav-dot" id="badge-pets"><?= $badges['pets'] ?></span><?php endif; ?></a>
    <a href="javascript:void(0)" class="nav-item js-go-to" data-idx="5" id="n5"><i class="fa-solid fa-calendar-day"></i><span>ПОДІЇ</span><?php if(!empty($badges['events'])): ?><span class="nav-dot" id="badge-events"><?= $badges['events'] ?></span><?php endif; ?></a>
</nav>

<!-- МОДАЛЬНІ ВІКНА -->
<div id="mainModal" class="modal js-close-modal-click" data-target="mainModal">
    <div class="modal-card js-stop-propagation" style="padding: 25px;">
        <div class="modal-header"><div class="modal-title" id="mTitle"></div></div>
        <div class="modal-body custom-scroll" id="mBody"></div>
        <div class="modal-footer"><button class="ui-btn primary js-close-modal" data-target="mainModal">Зрозуміло</button></div>
    </div>
</div>

<div id="petModal" class="modal js-close-modal-click" data-target="petModal">
    <div class="modal-card js-stop-propagation ui-modal-box">
        <h3 class="ui-modal-title">Нова ініціатива</h3>
        <form id="createPetitionForm" class="ajax-admin-form" action="action.php?route=create_petition" method="POST" data-redirect="?tab=petitions">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="text" name="title" required placeholder="Назва" class="ui-input">
            <textarea name="description" required placeholder="Опис..." class="ui-input ui-textarea"></textarea>
            <select name="is_anon" class="ui-input"><option value="0">Відкрите авторство</option><option value="1">🔒 Анонімно</option></select>
            <select name="is_secret" class="ui-input"><option value="0">Відкрите голосування</option><option value="1">🔒 Таємне</option></select>
            <button type="submit" class="ui-btn primary" style="margin-top:10px;">Опублікувати</button>
            <button type="button" class="ui-btn-text js-close-modal" data-target="petModal">Скасувати</button>
        </form>
    </div>
</div>

<div id="enrollModal" class="modal js-close-modal-click" data-target="enrollModal">
    <div class="modal-card js-stop-propagation ui-modal-box">
        <h3 class="ui-modal-title">Запис на подію</h3>
        <form id="enrollForm" class="ajax-admin-form" action="action.php?route=enroll" method="POST" data-redirect="?tab=events">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="event_id" id="e_id">
            <input type="number" name="guests_count" id="g_count" min="0" value="0" class="ui-input" placeholder="Кількість гостей">
            <textarea name="guests_info" class="ui-input ui-textarea" placeholder="Дані гостей..."></textarea>
            <button type="submit" class="ui-btn primary" style="margin-top:10px;">Підтвердити</button>
            <button type="button" class="ui-btn-text js-close-modal" data-target="enrollModal">Скасувати</button>
        </form>
    </div>
</div>

<div id="excludeModal" class="modal js-close-modal-click" data-target="excludeModal">
    <div class="modal-card js-stop-propagation ui-modal-box">
        <h3 class="ui-modal-title">Виключення</h3>
        <p class="ui-modal-desc">Буде автоматично створено голосування на 7 днів. Вимагається підпис КЕП.</p>
        <form id="exclusionForm" class="ajax-admin-form" action="action.php?route=create_exclusion" method="POST" data-redirect="?tab=polls">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <select name="target_user_id" required class="ui-input">
                <option value="" disabled selected>Оберіть члена ППО...</option>
                <?php if(!empty($active_members_list)): foreach($active_members_list as $am): ?>
                    <option value="<?= $am['id'] ?>"><?= htmlspecialchars($am['full_name']) ?></option>
                <?php endforeach; endif; ?>
            </select>
            <button type="submit" class="ui-btn danger" style="margin-top:10px;">Створити голосування</button>
            <button type="button" class="ui-btn-text js-close-modal" data-target="excludeModal">Скасувати</button>
        </form>
    </div>
</div>

<div id="kepModal" class="modal js-close-modal-click" data-target="kepModal" style="z-index: 2500;">
    <div class="modal-card js-stop-propagation ui-modal-box">
        <div class="ui-modal-icon blue"><i class="fa-solid fa-fingerprint"></i></div>
        <h3 class="ui-modal-title">Накладання КЕП</h3>
        <p class="ui-modal-desc">Для участі у цьому голосуванні вимагається цифровий підпис.</p>
        <div id="kep-status" style="font-size:13px; font-weight:800; color:var(--txt); margin-bottom:20px; height:20px;"></div>
        <button class="ui-btn primary js-simulate-kep" id="btn-sign-kep">Підписати</button>
        <button class="ui-btn-text js-close-modal" id="btn-cancel-kep" data-target="kepModal">Скасувати</button>
    </div>
</div>

<div id="admissionKepModal" class="modal js-close-modal-click" data-target="admissionKepModal" style="z-index: 2500; display:none;">
    <div class="modal-card js-stop-propagation ui-modal-box">
        <div class="ui-modal-icon green"><i class="fa-solid fa-file-signature"></i></div>
        <h3 class="ui-modal-title">Заява на вступ</h3>
        <p class="ui-modal-desc">Підписуючи цю заяву, ви офіційно подаєте запит на вступ до профспілки та погоджуєтесь із її Статутом.</p>
        <div id="admission-kep-status" style="font-size:13px; font-weight:800; color:var(--txt); margin-bottom:20px; height:20px;"></div>
        <form id="admissionForm" action="action.php?route=apply" method="POST" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="ajax" value="1">
            <button type="button" class="ui-btn primary js-simulate-admission-kep" id="btn-sign-admission">Накласти Дія.Підпис</button>
            <button type="button" class="ui-btn-text js-close-modal" id="btn-cancel-admission" data-target="admissionKepModal">Скасувати</button>
        </form>
    </div>
</div>
