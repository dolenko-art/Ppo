<?php $safe_nonce = defined('CSP_NONCE') ? CSP_NONCE : ''; ?>
<style nonce="<?= $safe_nonce ?>">
    /* 🔥 Специфічні стилі (100% CSP Compliant) */
    body { padding-bottom: 90px !important; overflow-x: hidden; }
    .nav-shell { display: none !important; }
    
    #slider { width: 500% !important; display: flex; transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1); align-items: flex-start; }
    .slide { width: 20% !important; padding: 0 15px 120px; box-sizing: border-box; flex-shrink: 0; }
    
    .ppo-hero-card { margin: 15px; padding: 20px 15px; border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .ppo-hero-top { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
    .ppo-hero-icon { width: 50px; height: 50px; background: var(--accent); color: #fff; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .ppo-hero-title { font-family: 'Syne', sans-serif; font-size: 18px; margin-bottom: 4px; color: var(--txt); }
    .ppo-hero-sub { font-size: 11px; color: var(--txt-muted); font-weight: 700; }
    
    .ppo-fin-status { background: var(--bg); padding: 12px; border-radius: 12px; }
    .ppo-fin-status.active { border: 1px solid var(--green); }
    .ppo-fin-status.inactive { border: 1px solid var(--border); }
    .ppo-fin-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .ppo-fin-title { font-size: 13px; font-weight: 800; color: var(--txt); }
    .ppo-fin-desc { font-size: 11px; color: var(--txt-muted); line-height: 1.5; }
    
    .leader-card { padding: 16px; margin-bottom: 12px; }
    .leader-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .leader-icon-box { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px; }
    .leader-icon-box.active { background: rgba(99,102,241,0.1); color: var(--accent); }
    .leader-icon-box.empty { background: var(--bg); color: var(--txt-muted); border: 1px solid var(--border); }
    .leader-role { font-size: 10px; text-transform: uppercase; font-weight: 800; margin-bottom: 2px; }
    .leader-role.active { color: var(--accent); }
    .leader-role.empty { color: var(--txt-muted); }
    .leader-name { font-size: 15px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .leader-name.active { color: var(--txt); }
    .leader-name.empty { color: var(--txt-muted); }
    
    .leader-vacant { font-size: 11px; color: var(--txt-muted); font-weight: 700; background: var(--bg); border-radius: 8px; padding: 8px 12px; text-align: center; }
    .leader-progress-box { background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 10px; }
    .leader-dates { display: flex; justify-content: space-between; font-size: 10px; color: var(--txt-muted); margin-bottom: 8px; font-weight: 700; }
    .leader-bar-bg { height: 6px; background: var(--surface); border-radius: 3px; overflow: hidden; border: 1px solid var(--border); margin-bottom: 8px; position: relative; }
    .leader-bar-fill { height: 100%; transition: width 1s ease; position: absolute; left: 0; top: 0; }
    .leader-badge-wrap { display: flex; justify-content: flex-end; }
    
    .audit-link { display: flex; align-items: center; justify-content: space-between; text-decoration: none; margin-top: 15px; padding: 16px; border: 1px solid var(--border); border-left: 4px solid #8b5cf6; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: 0.2s; }
    .audit-link-left { display: flex; align-items: center; gap: 12px; }
    .audit-link-icon { width: 36px; height: 36px; background: rgba(139,92,246,0.1); color: #8b5cf6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .audit-link-title { font-size: 14px; font-weight: 800; color: var(--txt); font-family: 'Manrope', sans-serif; }
    .audit-link-sub { font-size: 10px; color: var(--txt-muted); }
    .audit-link-chevron { width: 24px; height: 24px; border-radius: 50%; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--txt-muted); font-size: 10px; }
    
    .list-wrapper { padding: 0; overflow: hidden; margin-bottom: 20px; }
    .list-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--line); }
    .list-item:last-child { border-bottom: none; }
    .list-idx { width: 25px; font-size: 11px; font-family: 'DM Mono', monospace; font-weight: 800; color: var(--txt-muted); text-align: right; }
    .list-avatar { width: 36px; height: 36px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: var(--txt); flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .list-info { flex: 1; min-width: 0; }
    .list-name { font-size: 13px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--txt); }
    .list-sub { font-size: 10px; color: var(--txt-muted); font-weight: 700; }
    
    .list-former { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid var(--line); }
    .former-name { font-size: 14px; font-weight: 800; color: var(--txt); text-decoration: line-through; opacity: 0.6; margin-bottom: 2px; }
    
    .top-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--line); }
    .top-left { display: flex; align-items: center; gap: 12px; }
    .top-medal { font-size: 24px; width: 36px; text-align: center; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
    .top-num { width: 36px; height: 36px; background: var(--surface); border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-family: 'DM Mono', monospace; font-weight: 800; color: var(--txt-muted); }
    .top-name { font-size: 14px; font-weight: 800; color: var(--txt); }
    
    .archive-card { text-align: center; padding: 40px 20px; }
    .archive-icon { width: 64px; height: 64px; background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: var(--accent); margin: 0 auto 15px; }
    
    .stat-hero { text-align: center; padding: 24px 20px; margin-bottom: 16px; border: 2px solid var(--accent); background: linear-gradient(180deg, rgba(59,91,219,0.08) 0%, var(--bg) 100%); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15); }
    .stat-hero-label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--accent); margin-bottom: 6px; }
    .stat-hero-val { font-family: 'Syne', sans-serif; font-size: 42px; font-weight: 800; color: var(--txt); }
    .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .stat-box { padding: 16px; text-align: center; margin: 0; box-shadow: var(--shadow-soft); }
    .stat-val { font-family: 'DM Mono', monospace; font-size: 24px; font-weight: 800; color: var(--accent); }
    .stat-label { font-size: 10px; color: var(--txt-muted); text-transform: uppercase; font-weight: 800; margin-top: 4px; }
</style>

<!-- ШАПКА ППО -->
<div class="ui-card ppo-hero-card"> 
    <div class="ppo-hero-top">
        <div class="ppo-hero-icon"><i class="fa-solid fa-users"></i></div>
        <div>
            <h2 class="ppo-hero-title"><?= htmlspecialchars($ppo_name) ?></h2>
            <div class="ppo-hero-sub">Засновано: <?= $foundation_date ?></div>
        </div>
    </div>
    <div class="ppo-fin-status <?= !empty($bank_connected) ? 'active' : 'inactive' ?>">
        <div class="ppo-fin-head">
            <i class="fa-solid <?= !empty($bank_connected) ? 'fa-building-columns' : 'fa-keyboard' ?>" style="color: <?= !empty($bank_connected) ? 'var(--green)' : 'var(--txt-muted)' ?>;"></i>
            <span class="ppo-fin-title">Фінансова звітність</span>
        </div>
        <div class="ppo-fin-desc">
            <?php if(!empty($bank_connected)): ?>
                <span style="color:var(--green); font-weight:800;">✅ Автоматизовано.</span> Інтеграція з Monobank підключена.
            <?php else: ?>
                <span style="font-weight:800;">⚠️ Ручний режим.</span> Дані вносяться адміністратором.
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="slider-container"> 
    <div id="slider">
        
        <!-- ВКЛАДКА 0: КЕРІВНИЦТВО -->
        <div class="slide active-slide">
            <h3 class="slide-header">Керівництво</h3>
            
            <?php foreach($leaders as $ld): ?>
            <div class="ui-card leader-card">
                <div class="leader-head">
                    <div class="leader-icon-box <?= $ld['is_empty'] ? 'empty' : 'active' ?>">
                        <i class="fa-solid <?= $ld['icon'] ?>"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="leader-role <?= $ld['is_empty'] ? 'empty' : 'active' ?>"><?= htmlspecialchars($ld['role_name']) ?></div>
                        <div class="leader-name <?= $ld['is_empty'] ? 'empty' : 'active' ?>"><?= htmlspecialchars($ld['name']) ?></div>
                    </div>
                </div>
                
                <?php if($ld['is_empty']): ?>
                    <div class="leader-vacant">Посада вакантна. Очікуються вибори.</div>
                <?php else: 
                    $total_days = max(1, $ld['period'] * 365);
                    $pct = min(100, max(0, (($total_days - $ld['days_left']) / $total_days) * 100));
                    $is_urgent = $ld['days_left'] <= 30;
                    $is_expired = $ld['days_left'] <= 0;
                    $bar_color = $is_urgent ? 'var(--red)' : 'linear-gradient(90deg, var(--accent), var(--accent-hover))';
                ?>
                    <div class="leader-progress-box">
                        <div class="leader-dates">
                            <span>Обрано: <?= $ld['elected_date'] ?></span>
                            <span style="color: <?= $is_expired ? 'var(--red)' : 'inherit' ?>;">Наст. вибори: <?= $ld['next_election'] ?></span>
                        </div>
                        
                        <!-- 🔥 МАГІЯ CSP: Передаємо дані через атрибути -->
                        <div class="leader-bar-bg">
                            <div class="leader-bar-fill js-apply-width" data-w="<?= $pct ?>" data-bg="<?= $bar_color ?>"></div>
                        </div>
                        
                        <div class="leader-badge-wrap">
                            <?php if($is_expired): ?>
                                <span class="ui-badge red"><i class="fa-solid fa-triangle-exclamation"></i> Час вийшов</span>
                            <?php else: ?>
                                <span class="ui-badge <?= $is_urgent ? 'orange' : 'green' ?>"><?= $ld['days_left'] ?> дн. залишилось</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <a href="audit_log.php" class="ui-card audit-link">
                <div class="audit-link-left">
                    <div class="audit-link-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div>
                        <div class="audit-link-title">Журнал дій (Лог)</div>
                        <div class="audit-link-sub">Історія рішень, вступів та виключень</div>
                    </div>
                </div>
                <div class="audit-link-chevron"><i class="fa-solid fa-chevron-right"></i></div>
            </a>
        </div>

        <!-- ВКЛАДКА 1: СКЛАД -->
        <div class="slide">
            <h3 class="slide-header">Учасники</h3>
            <?php 
                $s_m = $_GET['sub_member'] ?? 'active'; 
                $former_members = \Core\DB::fetchAll("SELECT * FROM users WHERE ppo_id = ? AND status = 'former' ORDER BY left_at DESC", [$_SESSION['ppo_id']]) ?: [];
            ?>

            <div class="ui-tabs">
                <a href="?tab=members&sub_member=active" class="ui-tab <?= $s_m == 'active' ? 'active' : '' ?>">Діючі (<?= $total_members_count ?>)</a>
                <a href="?tab=members&sub_member=former" class="ui-tab <?= $s_m == 'former' ? 'active' : '' ?>">Колишні (<?= count($former_members) ?>)</a>
            </div>

            <?php if($s_m === 'active'): ?>
                <div class="ui-card list-wrapper"> 
                    <?php if(empty($members_list)): ?>
                        <div class="ui-empty" style="border:none; margin:0;">Учасників не знайдено</div>
                    <?php else: ?>
                        <?php foreach($members_list as $idx => $m): $g_idx = ($p_m - 1) * $limit + $idx + 1; ?>
                        <div class="list-item">
                            <div class="list-idx">#<?= $g_idx ?></div>
                            <div class="list-avatar"><?= mb_substr($m['name'], 0, 1, 'UTF-8') ?></div>
                            <div class="list-info">
                                <div class="list-name"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="list-sub">з <?= date('d.m.Y', strtotime($m['joined'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if(ceil($total_members_count/$limit) > 1): ?>
                    <div class="pag-wrap"><?php for($i=1;$i<=ceil($total_members_count/$limit);$i++): ?><a href="?tab=members&sub_member=active&p_m=<?=$i?>" class="pag-link <?=$p_m==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div>
                <?php endif; ?>

            <?php elseif($s_m === 'former'): ?>
                <div class="ui-card list-wrapper">
                    <?php if(empty($former_members)): ?>
                        <div class="ui-empty" style="border:none; margin:0;"><i class="fa-solid fa-box-archive"></i> Історія виходів порожня</div>
                    <?php else: ?>
                        <?php foreach($former_members as $fm): 
                            $f_name = class_exists('\Core\Security') ? (\Core\Security::decrypt($fm['full_name']) ?: $fm['full_name']) : $fm['full_name'];
                            $is_exp = (mb_stripos($fm['leave_reason'] ?? '', 'Виключено') !== false);
                        ?>
                        <div class="list-former">
                            <div>
                                <div class="former-name"><?= htmlspecialchars($f_name) ?></div>
                                <div class="list-sub">з <?= date('d.m.y', strtotime($fm['joined_at'])) ?> по <?= date('d.m.y', strtotime($fm['left_at'])) ?></div>
                            </div>
                            <span class="ui-badge <?= $is_exp ? 'red' : 'orange' ?>"><?= htmlspecialchars($fm['leave_reason'] ?? 'Невідомо') ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ВКЛАДКА 2: ТОП -->
        <div class="slide">
            <h3 class="slide-header">Рейтинг активності</h3>
            <?php if(empty($top_activists)): ?>
                <div class="ui-empty"><i class="fa-solid fa-ranking-star"></i>Рейтинг формується...<br>Беріть участь у голосуваннях!</div>
            <?php else: ?>
                <div class="ui-card list-wrapper">
                    <?php foreach($top_activists as $idx => $act): ?>
                    <div class="top-item">
                        <div class="top-left">
                            <?php if($idx < 3): ?>
                                <div class="top-medal"><?= ['🥇','🥈','🥉'][$idx] ?></div>
                            <?php else: ?>
                                <div class="top-num">#<?= $idx+1 ?></div>
                            <?php endif; ?>
                            <span class="top-name"><?= htmlspecialchars($act['name']) ?></span>
                        </div>
                        <span class="ui-badge blue"><?= $act['score'] ?> балів</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ВКЛАДКА 3: АРХІВ -->
        <div class="slide">
            <h3 class="slide-header">Документи</h3>
            <div class="ui-card archive-card">
                <div class="archive-icon"><i class="fa-solid fa-box-archive"></i></div>
                <h4 style="font-family:'Syne'; margin-bottom:10px; font-size:18px;">Архів протоколів</h4>
                <p class="ui-desc">Усі завершені рішення та результати голосувань.</p>
                <a href="archive.php" class="ui-btn primary">Відкрити архів</a>
            </div>
        </div>

        <!-- ВКЛАДКА 4: АНАЛІТИКА -->
        <div class="slide">
            <h3 class="slide-header">Аналітика</h3>
            <div class="ui-card stat-hero">
                <div class="stat-hero-label">Сер. явка учасників</div>
                <div class="stat-hero-val"><?= $participation_rate ?>%</div>
            </div>
            <div class="stat-grid">
                <div class="ui-card stat-box"><div class="stat-val"><?= $stats['polls'] ?></div><div class="stat-label">Голосувань</div></div>
                <div class="ui-card stat-box"><div class="stat-val"><?= $stats['pets'] ?></div><div class="stat-label">Петицій</div></div>
                <div class="ui-card stat-box"><div class="stat-val"><?= $stats['noms'] ?></div><div class="stat-label">Висувань</div></div>
                <div class="ui-card stat-box"><div class="stat-val"><?= $stats['events'] ?></div><div class="stat-label">Заходів</div></div>
            </div>
        </div>

    </div>
</div>

<nav class="view-bar" id="info-bottom-nav">
    <a href="javascript:void(0)" class="nav-item active js-go-to-info-tab" data-idx="0" id="in0">
        <i class="fa-solid fa-user-shield"></i><span>ЛІДЕРИ</span>
    </a>
    <a href="javascript:void(0)" class="nav-item js-go-to-info-tab" data-idx="1" id="in1">
        <i class="fa-solid fa-users-rectangle"></i><span>СКЛАД</span>
    </a>
    <a href="javascript:void(0)" class="nav-item js-go-to-info-tab" data-idx="2" id="in2">
        <i class="fa-solid fa-trophy"></i><span>ТОП</span>
    </a>
    <a href="javascript:void(0)" class="nav-item js-go-to-info-tab" data-idx="3" id="in3">
        <i class="fa-solid fa-folder-closed"></i><span>АРХІВ</span>
    </a>
    <a href="javascript:void(0)" class="nav-item js-go-to-info-tab" data-idx="4" id="in4">
        <i class="fa-solid fa-chart-pie"></i><span>СТАТ</span>
    </a>
</nav>
