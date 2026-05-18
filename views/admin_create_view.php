<style>

/* 🔥 ФІКС ВИПАДАЮЧОГО СПИСКУ (Щоб картки не перекривали пошук) */
.card:focus-within { z-index: 50; }

    /* 🔥 ФІКС ГОРИЗОНТАЛЬНОГО СКРОЛУ ТА ІНДИВІДУАЛЬНА ШИРИНА (6 слайдів) */
    #slider { width: 600% !important; }
    .slide { width: 16.66666% !important; }

    .acc-filters { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; scrollbar-width: none; }
    .acc-filters::-webkit-scrollbar { display: none; }
    .acc-pill { white-space: nowrap; padding: 10px 16px; background: var(--surface); border: 1.5px solid var(--border); border-radius: 16px; font-size: 12px; font-weight: 800; color: var(--txt-muted); text-decoration: none; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.02); display: inline-block; }
    .acc-pill.active { background: var(--accent); color: #fff; border-color: var(--accent); }
    
    .empty-state { text-align: center; padding: 40px 20px; background: var(--surface); border-radius: 16px; border: 2px dashed var(--border); margin-bottom: 15px; color: var(--txt-muted); font-size: 13px; }
    .empty-state i { font-size: 36px; color: var(--border); margin-bottom: 12px; display: block; opacity: 0.5; }

    @keyframes fadeInCards {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .slide .card, .slide .empty-state, .dash-tile {
        animation: fadeInCards 0.3s ease-out forwards;
    }

    /* Керування кнопкою Назад у режимі плиток */
    html[data-layout="tile"] #back-btn-dash.active { display: flex !important; }
    html[data-layout="tile"] #main-icon-dash.hidden { display: none !important; }

    /* 🔥 ІНДИВІДУАЛЬНА ШИРИНА ДЛЯ АДМІНКИ (8 СЛАЙДІВ) */
    #slider { width: 800% !important; display: flex; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); align-items: flex-start; }
    .slide { width: 12.5% !important; padding: 15px; box-sizing: border-box; flex-shrink: 0; }

    /* Ховаємо глобальне меню Aurora, бо тут є своє місцеве меню */
    .nav-shell { display: none !important; }

    /* 🔥 ДАШБОРД (ПЛИТКИ) */
    .dashboard-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .dash-tile { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 24px 15px; text-align: center; cursor: pointer; transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .dash-tile:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); border-color: var(--accent); }
    .dash-tile:active { transform: scale(0.96); }
    .dash-icon { width: 54px; height: 54px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px; transition: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .dash-tile:hover .dash-icon { transform: scale(1.1) rotate(-5deg); }
    .dash-title { font-family: 'Syne'; font-size: 15px; font-weight: 800; color: var(--txt); margin-bottom: 6px; line-height: 1.2; }
    .dash-sub { font-size: 11px; color: var(--txt-muted); line-height: 1.3; font-weight: 700; }

    .btn-modern.danger { background: rgba(239,68,68,0.1); color: var(--red); border-color: transparent; }
    
    .collapse-content { display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* 🔥 СУЧАСНИЙ НИЖНІЙ БАР ДЛЯ АДМІНКИ */
    #adminBottomNav { 
        display: none; 
        position: fixed; 
        bottom: 0; left: 0; right: 0;
        background: var(--surface-glass); 
        border-top: 1px solid var(--border); 
        padding-bottom: env(safe-area-inset-bottom); 
        z-index: 1000; 
        backdrop-filter: blur(24px) saturate(150%);
        -webkit-backdrop-filter: blur(24px) saturate(150%);
        grid-template-columns: repeat(2, 1fr); 
        max-width: 500px;
        margin: 0 auto;
        height: 68px;
    }
    
    .input-dark, select.input-dark, textarea.input-dark {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
</style>

<?php
    $tab_map = ['menu'=>0, 'reg'=>1, 'news'=>2, 'polls'=>3, 'noms'=>4, 'events'=>5, 'pets'=>6, 'fin'=>7];
    $active_tab = $_GET['tab'] ?? 'menu';
    $start_idx = $tab_map[$active_tab] ?? 0;

    if (!isset($user_status) || !isset($user_role)) {
        $u_id = $_SESSION['user_id'] ?? 0;
        if ($u_id) {
            $c_user = \Core\DB::fetch("SELECT status, role FROM users WHERE id = ?", [$u_id]);
            $user_status = $c_user['status'] ?? 'member';
            $user_role = $c_user['role'] ?? null;
        } else {
            $user_status = 'member';
            $user_role = null;
        }
    }
?>

<div class="slider-viewport" style="width: 100%; overflow: hidden; position: relative;">
    <div id="slider" style="transform: translateX(-<?= ($start_idx/8)*100 ?>%);">
        
        <div class="slide active-slide">
            <div class="dashboard-grid">
                <div class="dash-tile js-admin-go-to" data-idx="1">
                    <div class="dash-icon" style="background:rgba(16,185,129,0.1); color:var(--green);"><i class="fa-solid fa-user-plus"></i></div>
                    <div class="dash-title">Заявки</div>
                    <div class="dash-sub">Нові учасники (<?= $reg_count ?? 0 ?>)</div>
                </div>
                <div class="dash-tile js-admin-go-to" data-idx="2">
                    <div class="dash-icon" style="background:rgba(59,130,246,0.1); color:#3b82f6;"><i class="fa-solid fa-newspaper"></i></div>
                    <div class="dash-title">Новини</div>
                    <div class="dash-sub">Стрічка ППО</div>
                </div>
                <div class="dash-tile js-admin-go-to" data-idx="3">
                    <div class="dash-icon" style="background:rgba(139,92,246,0.1); color:#8b5cf6;"><i class="fa-solid fa-check-to-slot"></i></div>
                    <div class="dash-title">Опитування</div>
                    <div class="dash-sub">Рішення та вибори</div>
                </div>
                <div class="dash-tile js-admin-go-to" data-idx="4">
                    <div class="dash-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;"><i class="fa-solid fa-lightbulb"></i></div>
                    <div class="dash-title">Пропозиції</div>
                    <div class="dash-sub">Збір ідей / висування</div>
                </div>
                <div class="dash-tile js-admin-go-to" data-idx="5">
                    <div class="dash-icon" style="background:rgba(236,72,153,0.1); color:#ec4899;"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="dash-title">Події</div>
                    <div class="dash-sub">Заходи та запис</div>
                </div>
                <div class="dash-tile js-admin-go-to" data-idx="6">
                    <div class="dash-icon" style="background:rgba(20,184,166,0.1); color:#14b8a6;"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="dash-title">Петиції</div>
                    <div class="dash-sub">Модерація ініціатив</div>
                </div>
                <div class="dash-tile js-admin-go-to" data-idx="7" style="grid-column: span 2;">
                    <div class="dash-icon" style="background:rgba(59,91,219,0.1); color:var(--accent);"><i class="fa-solid fa-wallet"></i></div>
                    <div class="dash-title">Фінанси та Інтеграція</div>
                    <div class="dash-sub">Каса і Monobank API</div>
                </div>
            </div>
        </div>

        <div class="slide">
            <?php if(empty($reg_paginated)): ?>
                <div class="card" style="text-align:center; padding:40px 20px; border:2px dashed var(--border); box-shadow:none;">
                    <div style="width:70px; height:70px; background:rgba(16,185,129,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; color:var(--green); margin:0 auto 15px;">
                        <i class="fa-solid fa-check-double"></i>
                    </div>
                    <h3 style="font-family:'Syne'; font-size:18px; margin-bottom:8px;">Усі заявки розглянуті!</h3>
                    <p style="font-size:12px; color:var(--txt-muted); line-height:1.5;">На даний момент немає нових користувачів, які очікують підтвердження.</p>
                </div>
            <?php else: ?>
                <p style="font-size:11px; color:var(--txt-muted); margin-bottom:15px; line-height:1.4;">Схвалення дає кандидату доступ до додатку. Його повноцінний вступ вирішується автоматичним голосуванням колективу після подачі ним заяви.</p>
                <?php foreach($reg_paginated as $u): ?>
                    <div class="card" style="padding:15px; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:0;">
                            <div style="width:42px; height:42px; background:var(--surface); border:1px solid var(--border); border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; color:var(--txt); flex-shrink:0;">
                                <?= mb_substr($u['full_name'], 0, 1, 'UTF-8') ?>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:800; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($u['full_name']) ?></div>
                                <div style="font-size:10px; color:var(--txt-muted); margin-top:2px;">
                                    <?php if(!empty($u['joined_at'])): ?>
                                        <i class="fa-regular fa-clock"></i> <?= date('d.m.Y H:i', strtotime($u['joined_at'])) ?>
                                    <?php else: ?>
                                        Нова реєстрація
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display:flex; gap:6px; margin:0; flex-shrink:0;">
                            <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="approve_reg">
                                <button type="submit" class="sq-btn" style="background:rgba(16,185,129,0.1); color:var(--green); border:none; width:40px; height:40px;" title="Схвалити"><i class="fa-solid fa-check"></i></button>
                            </form>
                            <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="reject_reg">
                                <button type="submit" class="sq-btn" style="background:rgba(239,68,68,0.1); color:var(--red); border:none; width:40px; height:40px;" title="Відхилити"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if($reg_count > $limit): ?><div class="pag-wrap" style="margin-bottom:20px;"><?php for($i=1;$i<=ceil($reg_count/$limit);$i++): ?><a href="?tab=reg&p_reg=<?=$i?>" class="pag-link <?=$p_reg==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div><?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="slide">
            <div class="card">
                <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="add_news">
                    <label class="label">Заголовок</label>
                    <input type="text" name="title" required class="input-dark">
                    <label class="label">Текст новини</label>
                    <textarea name="content" required class="input-dark" style="height:120px;"></textarea>
                    <button type="submit" class="btn-modern primary" style="width:100%; justify-content:center;">Опублікувати</button>
                </form>
            </div>
        </div>

        <div class="slide">
            <div class="card">
               <form action="action.php?route=admin_action" method="POST" id="pollForm" class="ajax-admin-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="add_poll">
                    
                    <?php if($user_status === 'admin' || $user_role === 'head'): ?>
                    <label class="label">Тип голосування</label>
                    <div style="position: relative; margin-bottom: 10px;">
                        <select name="poll_type" id="pollTypeSelect" class="input-dark js-toggle-poll-type" style="appearance: none; -webkit-appearance: none; cursor: pointer;">
                            <option value="regular">📊 Звичайне опитування</option>
                            <option value="election_auditor">⚖️ Вибори Ревізора</option>
                            <option value="election_manager">💼 Вибори Менеджера</option>
                        </select>
                        <i class="fa-solid fa-chevron-down" style="position: absolute; right: 15px; top: 15px; font-size: 12px; color: var(--txt-muted); pointer-events: none;"></i>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="poll_type" value="regular">
                    <?php endif; ?>

                    <div id="candidateSearchBlock" style="display: none; margin-bottom: 15px; padding: 15px; background: rgba(99,102,241,0.05); border: 1px dashed var(--accent); border-radius: 12px;">
                        <label class="label" style="color: var(--accent);"><i class="fa-solid fa-user-plus" style="margin-right: 4px;"></i> Оберіть кандидата</label>
                        <input type="text" id="candidateSearchInput" class="input-dark js-search-candidate" placeholder="Почніть вводити ім'я...">
                        <input type="hidden" name="candidate_id" id="candidateIdInput" value="">
                        <div id="candidateSearchResults" style="max-height: 160px; overflow-y: auto; background: var(--bg); border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: empty;"></div>
                    </div>

                    <div id="regularTitleBlock">
                        <label class="label">Питання або Назва</label>
                        <input type="text" name="title" id="pollTitleInput" class="input-dark" required>
                    </div>
                    
                    <label class="label">Деталі або опис</label>
                    <textarea name="description" class="input-dark" rows="3"></textarea>
                    
                    <div style="margin-bottom: 15px; padding: 15px; background: rgba(16,185,129,0.05); border: 1px dashed var(--green); border-radius: 12px;">
                        <label class="label" style="color: var(--green);"><i class="fa-solid fa-user-pen" style="margin-right: 4px;"></i> Секретар зборів (Необов'язково)</label>
                        <p style="font-size: 10px; color: var(--txt-muted); margin-bottom: 10px; line-height: 1.3;">Якщо залишити порожнім, система автоматично призначить Менеджера або іншого активного члена ППО.</p>
                        
                        <input type="text" id="secSearchInput_poll" class="input-dark js-search-secretary" data-type="poll" placeholder="Пошук секретаря..." style="margin-bottom: 0;">
                        <input type="hidden" name="designated_secretary_id" id="secIdInput_poll" value="">
                        
                        <div id="secSearchResults_poll" style="max-height: 160px; overflow-y: auto; background: var(--bg); border-radius: 10px; margin-top: 5px;"></div>
                    </div>

                    <div id="regularOptionsBlock">
                        <label class="label">Варіанти відповідей</label>
                        <div id="poll-opts">
                            <input type="text" name="options[]" class="input-dark opt-input" placeholder="Варіант 1" required style="margin-bottom: 8px;">
                            <input type="text" name="options[]" class="input-dark opt-input" placeholder="Варіант 2" required style="margin-bottom: 8px;">
                        </div>
                        <button type="button" class="btn-modern js-add-opt" style="background:var(--surface); border:1px dashed var(--border); color:var(--txt-muted); width:100%; justify-content:center; margin-bottom:15px; margin-top:5px;">
                            <i class="fa-solid fa-plus" style="margin-right: 6px;"></i> Додати варіант
                        </button>
                    </div>

                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <div style="flex:1; min-width:0;">
                            <label class="label">Таємність</label>
                            <select name="is_secret" class="input-dark" required>
                                <option value="0">👁️ Відкрите (Іменне)</option>
                                <option value="1">🛡️ Конфіденційне (Без імен)</option>
                                <option value="2">🔒 Абсолютно таємне (E2E-V)</option>
                            </select>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <label class="label">Кворум</label>
                            <select name="threshold_type" id="quorumSelect" class="input-dark"><option value="50+1">50% + 1</option><option value="2/3">2/3 голосів</option><option value="100">100% (Всі)</option></select>
                        </div>
                    </div>

                    <label class="label">Дія.Підпис (КЕП)</label>
                    <select name="require_kep" class="input-dark"><option value="0">Без КЕП</option><option value="1">Обов'язковий КЕП</option></select>

                    <label class="label">Діє до (Макс. 7 днів)</label>
                    <div style="display: flex; width: 100%; overflow: hidden; margin-bottom: 20px; border-radius: 12px;">
                        <input type="datetime-local" name="end_date" id="pollEndDate" required class="input-dark" style="flex: 1; min-width: 0; margin: 0;">
                    </div>

                    <button type="submit" class="btn-modern primary" style="width: 100%; justify-content: center; font-size: 15px;">
                        <i class="fa-solid fa-rocket" style="margin-right: 8px;"></i> Створити
                    </button>
                </form>
            </div>
        </div>

        <!-- 🔥 ВИСУВАННЯ / ЗБІР ІДЕЙ (АДМІНКА) З ДОДАНИМ СЕЛЕКТОМ КЕП -->
        <div class="slide">
            <p style="font-size:11px; color:var(--txt-muted); margin-bottom:15px; line-height:1.4;">Використовуйте цей інструмент для збору ідей (наприклад, куди поїхати) або для висування кандидатів на керівні посади.</p>
            
            <div class="card">
                <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="add_nomination">

                    <label class="label">Тип збору / Посада</label>
                    <select name="target_role" id="nomTypeSelect" class="input-dark js-toggle-nom-type">
                        <option value="regular">💡 Звичайний збір ідей / пропозицій</option>
                        <option value="auditor">⚖️ Висування кандидатів: Ревізор</option>
                        <option value="manager">💼 Висування кандидатів: Менеджер</option>
                    </select>

                    <label class="label">Тема</label>
                    <input type="text" name="title" id="nomTitleInput" required class="input-dark" placeholder="Наприклад: Куди поїдемо на екскурсію?">

                    <label class="label">Опис</label>
                    <textarea name="description" class="input-dark" style="height:60px;"></textarea>

                    <div style="margin-bottom: 15px; padding: 15px; background: rgba(16,185,129,0.05); border: 1px dashed var(--green); border-radius: 12px;">
                        <label class="label" style="color: var(--green);"><i class="fa-solid fa-user-pen" style="margin-right: 4px;"></i> Секретар зборів (Необов'язково)</label>
                        <p style="font-size: 10px; color: var(--txt-muted); margin-bottom: 10px; line-height: 1.3;">Цей секретар буде автоматично переданий у фінальне голосування.</p>
                        
                        <input type="text" id="secSearchInput_nom" class="input-dark js-search-secretary" data-type="nom" placeholder="Пошук секретаря..." style="margin-bottom: 0;">
                        <input type="hidden" name="designated_secretary_id" id="secIdInput_nom" value="">
                        
                        <div id="secSearchResults_nom" style="max-height: 160px; overflow-y: auto; background: var(--bg); border-radius: 10px; margin-top: 5px;"></div>
                    </div>

                    <label class="label">Таємність (Хто запропонував)</label>
                    <select name="is_secret" class="input-dark" required>
                        <option value="0">👁️ Відкрите (Іменне)</option>
                        <option value="1">🛡️ Конфіденційне (Без імен)</option>
                        <option value="2">🔒 Абсолютно таємне (E2E-V)</option>
                    </select>

                    <!-- 🔥 ОСЬ СЕЛЕКТ ДЛЯ КЕП -->
                    <label class="label">Дія.Підпис (КЕП)</label>
                    <select name="require_kep" id="nomRequireKep" class="input-dark">
                        <option value="0">Без КЕП</option>
                        <option value="1">Обов'язковий КЕП</option>
                    </select>

                    <label class="label">Скільки варіантів пройдуть у фінал (ТОП)?</label>
                    <input type="number" name="top_count" value="3" min="1" max="10" class="input-dark">

                    <label class="label">Діє до</label>
                    <div style="display: flex; width: 100%; overflow: hidden; margin-bottom: 20px; border-radius: 12px;">
                        <input type="datetime-local" name="end_date" id="nomEndDate" required class="input-dark" style="flex: 1; min-width: 0; margin: 0;">
                    </div>

                    <button type="submit" class="btn-modern primary" style="width: 100%; justify-content: center; font-size: 15px;">
                        <i class="fa-solid fa-bullhorn" style="margin-right: 8px;"></i> Оголосити збір
                    </button>
                </form>
            </div>
        </div>

        <div class="slide">
            <?php if($sub_events == 'create'): ?>
                <div class="card">
                    <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="add_event">
                        
                        <label class="label">Назва події</label><input type="text" name="title" required class="input-dark">
                        
                        <label class="label">Дата та час</label>
                        <div style="display: flex; width: 100%; overflow: hidden; border-radius: 12px;">
                            <input type="datetime-local" name="event_date" required class="input-dark" style="flex: 1; min-width: 0; margin: 0;">
                        </div>

                        <label class="label">Локація</label><input type="text" name="location" required class="input-dark">
                        <label class="label">Ліміт місць (0 = безлім)</label><input type="number" name="max_seats" value="0" min="0" class="input-dark">
                        <label class="label">Опис події</label><textarea name="description" class="input-dark" style="height:80px;"></textarea>
                        
                        <button type="submit" class="btn-modern primary" style="width:100%; justify-content:center; margin-top:10px;">Опублікувати подію</button>
                    </form>
                </div>
            <?php else: ?>
                <?php if(empty($events_paginated)): ?>
                    <div class="card" style="text-align:center; padding:40px 20px; border:2px dashed var(--border); box-shadow:none;">
                        <div style="width:70px; height:70px; background:rgba(236,72,153,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; color:#ec4899; margin:0 auto 15px;">
                            <i class="fa-regular fa-calendar-xmark"></i>
                        </div>
                        <h3 style="font-family:'Syne'; font-size:18px; margin-bottom:8px;">Подій ще немає</h3>
                    </div>
                <?php endif; ?>
                
                <?php foreach($events_paginated as $ev): ?>
                    <div class="card" style="padding:15px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                            <div style="max-width:70%;">
                                <div style="font-weight:800; font-size:15px; line-height:1.3;"><?= htmlspecialchars($ev['title']) ?></div>
                                <div style="font-size:11px; color:var(--txt-muted); margin-top:4px;">
                                    <i class="fa-regular fa-calendar"></i> <?= date('d.m.Y H:i', strtotime($ev['event_date'])) ?>
                                </div>
                            </div>
                            <div class="status-badge" style="background:var(--accent); color:#fff; border:none; font-size:10px;">
                                <?= count($ev['participants']) ?> чол.
                            </div>
                        </div>
                        <button class="btn-modern js-toggle-participants" data-id="<?= $ev['id'] ?>" style="margin:0; width:100%; justify-content:center; background:var(--bg); border:1px solid var(--border);">
                            <i class="fa-solid fa-users-viewfinder" style="margin-right:8px;"></i> Список учасників
                        </button>
                        <div id="ev-<?= $ev['id'] ?>" class="collapse-content" style="display:none; padding-top:10px;">
                            <div id="plist-<?= $ev['id'] ?>"></div>
                            <div id="ppag-<?= $ev['id'] ?>" style="display:flex; justify-content:center; align-items:center; margin-top:15px; gap:10px;"></div>
                        </div>
                    </div>
                    <!-- 🔥 CSP ФІКС: Безпечна передача даних учасників через JSON замість inline-script -->
                    <script type="application/json" id="ev-data-<?= $ev['id'] ?>">
                        <?= json_encode($ev['participants']) ?>
                    </script>
                <?php endforeach; ?>
                <?php if($events_count > $limit): ?><div class="pag-wrap" style="margin-bottom:20px;"><?php for($i=1;$i<=ceil($events_count/$limit);$i++): ?><a href="?tab=events&sub_events=list&p_evt=<?=$i?>" class="pag-link <?=$p_evt==$i?'active':''?>"><?=$i?></a><?php endfor; ?></div><?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="slide">
            <?php $has_pets = false; foreach($admin_pets as $pt): if($pt['status'] != 1) continue; $has_pets = true; ?>
                <div class="card">
                    <div style="font-weight:800; font-size:16px; margin-bottom:10px;"><?= htmlspecialchars($pt['title']) ?></div>
                    <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="answer_petition">
                        <input type="hidden" name="petition_id" value="<?= $pt['id'] ?>">
                        <select name="status" class="input-dark" style="margin-bottom:8px;">
                            <option value="4">⏳ Готується до голосування</option>
                            <option value="2">✅ Прийнято до виконання</option>
                            <option value="3">❌ Відхилено</option>
                        </select>
                        <textarea name="comment" class="input-dark" style="height:60px;" placeholder="Ваш коментар/відповідь..."></textarea>
                        <button type="submit" class="btn-modern primary" style="width:100%; justify-content:center; margin-top:10px;">Опублікувати рішення</button>
                    </form>
                </div>
            <?php endforeach; ?>
            
            <?php if(!$has_pets): ?>
                <div class="card" style="text-align:center; padding:40px 20px; border:2px dashed var(--border); box-shadow:none;">
                    <div style="width:70px; height:70px; background:rgba(20,184,166,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; color:#14b8a6; margin:0 auto 15px;">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <h3 style="font-family:'Syne'; font-size:18px; margin-bottom:8px;">Немає нових петицій</h3>
                </div>
            <?php endif; ?>
        </div>

        <div class="slide">
            <?php if($sub_fin == 'cash'): ?>
                <div class="card">
                    <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="add_cash">
                        
                        <label class="label">Тип операції</label>
                        <select name="type" class="input-dark"><option value="income">Внесок (Дохід)</option><option value="expense">Витрата (Списання)</option></select>
                        <label class="label">Сума (₴)</label>
                        <input type="number" step="0.01" name="amount" required class="input-dark">
                        <label class="label">Призначення платежу</label>
                        <input type="text" name="reason" required class="input-dark">
                        
                        <button type="submit" class="btn-modern primary" style="width:100%; justify-content:center; margin-top:15px;">Записати</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="card">
                    <form action="action.php?route=admin_action" method="POST" class="ajax-admin-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="save_mono_token">
                        
                        <?php if($has_mono_token): ?>
                            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); padding:15px; border-radius:12px; margin-bottom:15px; text-align:center;">
                                <div style="color:var(--green); font-size:14px; font-weight:800; margin-bottom:5px;"><i class="fa-solid fa-check-circle"></i> Банк успішно підключено</div>
                            </div>
                            <button type="submit" name="delete_mono" value="1" class="btn-modern danger" style="width:100%; justify-content:center;">Відключити Monobank</button>
                        <?php else: ?>
                            <label class="label">Персональний токен (X-Token)</label>
                            <input type="text" name="mono_token" required class="input-dark" placeholder="Введіть ваш токен тут...">
                            <button type="submit" class="btn-modern primary" style="width:100%; justify-content:center;">Підключити авто-звіти</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<nav id="adminBottomNav"></nav>
