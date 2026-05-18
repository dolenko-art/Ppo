<!-- 🔥 CSP ФІКС: Безпечна передача даних у JS через data-атрибут -->
<div id="pendingSignaturesData" data-count="<?= $pending_signatures ?? 0 ?>" class="d-none"></div>

<!-- 🔥 КОМПАКТНА КАРТКА ПРОФІЛЮ -->
<div class="profile-hero-card ui-card" style="margin: 16px 20px; padding: 20px; display: flex; flex-direction: column; align-items: center; background: linear-gradient(180deg, rgba(59,91,219,0.05) 0%, var(--surface) 100%);">
    <div class="profile-hero-avatar" style="width: 56px; height: 56px; background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: #fff; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px; font-family: 'Syne', sans-serif; font-weight: 800; box-shadow: 0 8px 16px rgba(59,91,219,0.25);">
        <?= htmlspecialchars((string)($user['avatar_initial'] ?? 'U')) ?>
    </div>
    <h2 class="profile-hero-name" style="font-family: 'Manrope', sans-serif; font-size: 16px; margin-bottom: 8px; font-weight: 800; color: var(--txt); letter-spacing: -0.3px;"><?= htmlspecialchars((string)($user['full_name'] ?? '')) ?></h2>
    <span class="ui-badge <?= str_replace('badge-', '', $badge['class']) ?>"><?= $badge['text'] ?></span>
</div>

<div id="slider-container" style="overflow: hidden; width: 100vw; position: relative;">
    <div id="slider" class="profile-slider" style="width: 400% !important; display: flex; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); align-items: flex-start;">
        
        <!-- ==================================== -->
        <!-- 1. АНКЕТА -->
        <!-- ==================================== -->
        <div class="slide active-slide" style="width: 25% !important;">
            <h2 class="ui-header-title" style="margin: 0 20px 15px;">Анкета</h2>
            
            <div class="stat-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 0 20px 18px;">
                <div class="ui-card" style="margin:0; padding:16px 12px; text-align:center;">
                    <div style="font-family: 'DM Mono', monospace; font-size: 20px; font-weight: 800; color: var(--accent);"><?= $joined_date_str ?? '-' ?></div>
                    <div style="font-size: 10px; font-weight: 800; color: var(--txt-muted); text-transform: uppercase; margin-top: 4px;">Дата вступу</div>
                </div>
                <div class="ui-card" style="margin:0; padding:16px 12px; text-align:center;">
                    <div style="font-family: 'DM Mono', monospace; font-size: 20px; font-weight: 800; color: var(--accent);"><?= $joined_days_str ?? '-' ?></div>
                    <div style="font-size: 10px; font-weight: 800; color: var(--txt-muted); text-transform: uppercase; margin-top: 4px;">Стаж</div>
                </div>
            </div>
            
            <div class="ui-card" style="margin: 0 20px;">
                <form action="action.php?route=profile_action" method="POST" class="ajax-admin-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="action" value="update_info">
                    
                    <label style="font-size: 11px; font-weight: 800; color: var(--txt-muted); display: block; margin-bottom: 6px;">Телефон</label>
                    <input type="text" value="<?= htmlspecialchars((string)($user['phone'] ?? '')) ?>" disabled class="ui-input" style="opacity:0.6;">
                    
                    <label style="font-size: 11px; font-weight: 800; color: var(--txt-muted); display: block; margin-bottom: 6px;">Дата народження (<?= $age_str ?? '-' ?>)</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars((string)($user['dob'] ?? '')) ?>" class="ui-input">
                    
                    <label style="font-size: 11px; font-weight: 800; color: var(--txt-muted); display: block; margin-bottom: 6px;">Діти (імена/вік)</label>
                    <textarea name="children_info" class="ui-input ui-textarea" placeholder="Вкажіть дані..."><?= htmlspecialchars((string)($user['children_decrypted'] ?? '')) ?></textarea>
                    
                    <button type="submit" class="ui-btn primary mt-10">Зберегти дані</button>
                </form>
            </div>
        </div>

        <!-- ==================================== -->
        <!-- 2. СПОВІЩЕННЯ -->
        <!-- ==================================== -->
        <div class="slide" style="width: 25% !important;">
            <h2 class="ui-header-title" style="margin: 0 20px 15px;">Сповіщення</h2>
            
            <div id="push-banner" class="ui-card" style="display:flex; justify-content:space-between; align-items:center; margin: 0 20px 15px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div id="push-icon-box" style="width:38px; height:38px; border-radius:10px; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center;"><i class="fa-regular fa-bell"></i></div>
                    <div>
                        <div style="font-weight:800; font-size:13px; color:var(--txt);">Push-сповіщення</div>
                        <div id="push-status-text" style="font-size:10px; font-weight:700; color:var(--txt-muted);">Вимкнено</div>
                    </div>
                </div>
                <button id="push-btn" class="ui-btn primary js-toggle-pushes" style="width:auto; padding:8px 14px; font-size:11px;">Увімкнути</button>
            </div>

            <div style="padding: 0 20px;">
                <?php if(empty($action_notifs) && empty($info_notifs)): ?>
                    <div class="ui-empty">
                        <i class="fa-regular fa-bell-slash"></i>
                        <h3 style="color:var(--txt); margin-bottom:5px;">Сповіщень немає</h3>
                        <p>Тут з'являтимуться важливі повідомлення.</p>
                    </div>
                <?php else: ?>
                    
                    <!-- АКТИВНІ СПОВІЩЕННЯ -->
                    <?php if(!empty($action_notifs)): ?>
                        <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:var(--red); margin-bottom:12px;"><i class="fa-solid fa-circle-exclamation"></i> Потребують дії</div>
                        
                        <?php foreach($action_notifs as $ntf): ?>
                            <div class="ui-card" style="border-left: 4px solid var(--red); padding: 15px; background: rgba(239,68,68,0.02);">
                                <div style="font-size:10px; color:var(--txt-muted); display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars((string)($ntf['formatted_date'] ?? '')) ?></span>
                                    <span style="color:var(--red); font-weight:800;">Очікує виконання</span>
                                </div>
                                <div class="ui-title" style="font-size:14px; margin-bottom:6px;"><?= htmlspecialchars((string)($ntf['title'] ?? '')) ?></div>
                                <div class="ui-desc" style="font-size:12px; margin-bottom:12px;"><?= nl2br(htmlspecialchars((string)($ntf['clean_message'] ?? ''))) ?></div>
                                
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <?php if (!empty($ntf['has_sign']) && !empty($ntf['poll_id'])): ?>
                                        <button class="ui-btn danger js-open-protocol-kep" data-id="<?= (int)$ntf['poll_id'] ?>"><i class="fa-solid fa-file-signature"></i> Підписати КЕП</button>
                                    <?php elseif (!empty($ntf['has_election']) && !empty($ntf['poll_id'])): ?>
                                        <div style="display:flex; gap:8px;">
                                            <button class="ui-btn primary flex-1 js-resolve-election" data-id="<?= (int)$ntf['poll_id'] ?>" data-role="<?= htmlspecialchars((string)$ntf['target_role']) ?>" data-decision="accept">Прийняти</button>
                                            <button class="ui-btn secondary flex-1 js-resolve-election" data-id="<?= (int)$ntf['poll_id'] ?>" data-role="<?= htmlspecialchars((string)$ntf['target_role']) ?>" data-decision="reject">Відмовитись</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(!empty($ntf['show_defense']) && !empty($ntf['p_id'])): ?>
                                    <div class="ui-defense mt-10" id="defense-box-<?= (int)$ntf['p_id'] ?>" style="margin-bottom:0;">
                                        <div class="ui-defense-title"><i class="fa-solid fa-scale-balanced"></i> ВАШЕ ПРАВО НА ЗАХИСТ:</div>
                                        <textarea id="defense_text_<?= (int)$ntf['p_id'] ?>" required class="ui-input ui-textarea" style="margin-bottom:8px;" placeholder="Напишіть ваші аргументи..."></textarea>
                                        <button type="button" class="ui-btn danger js-submit-defense" data-id="<?= (int)$ntf['p_id'] ?>">Опублікувати</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- ІНФОРМАЦІЙНІ СПОВІЩЕННЯ -->
                    <?php if(!empty($info_notifs)): ?>
                        <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:var(--txt-muted); margin-bottom:12px; margin-top:20px;"><i class="fa-solid fa-info-circle"></i> Інформаційні</div>
                        
                        <?php foreach($info_notifs as $index => $ntf): 
                            $ntf_read = (int)($ntf['is_read'] ?? 1);
                            $is_hidden_class = ($index >= 5) ? 'display:none;' : '';
                            $border_color = $ntf_read === 0 ? 'var(--accent)' : 'var(--border)';
                            $opacity = $ntf_read === 0 ? '1' : '0.7';
                        ?>
                            <div class="ui-card info-notif-item" style="border-left: 4px solid <?= $border_color ?>; opacity: <?= $opacity ?>; padding: 15px; <?= $is_hidden_class ?>">
                                <div style="font-size:10px; color:var(--txt-muted); display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars((string)($ntf['formatted_date'] ?? '')) ?></span>
                                    <?php if($ntf_read === 0): ?><span style="color:var(--accent); font-weight:800;">Нове</span><?php endif; ?>
                                </div>
                                <div class="ui-title" style="font-size:14px; margin-bottom:4px;"><?= htmlspecialchars((string)($ntf['title'] ?? '')) ?></div>
                                <div class="ui-desc" style="font-size:12px; margin-bottom:0;"><?= nl2br(htmlspecialchars((string)($ntf['clean_message'] ?? ''))) ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($info_notifs) > 5): ?>
                            <button id="loadMoreNotifsBtn" class="ui-btn secondary js-load-more-notifs"><i class="fa-solid fa-chevron-down"></i> Показати старіші</button>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================================== -->
        <!-- 3. БЕЗПЕКА -->
        <!-- ==================================== -->
        <div class="slide" style="width: 25% !important;">
            <h2 class="ui-header-title" style="margin: 0 20px 15px;">Безпека</h2>
            
            <div class="ui-card" style="padding:0; overflow:hidden; margin: 0 20px 15px;">
                <div style="padding:24px 20px; text-align:center; border-bottom:1px solid var(--line); background:var(--bg);">
                    <div style="width:56px; height:56px; background:rgba(245,158,11,0.1); color:#f59e0b; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 12px;"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3 style="font-family:'Syne', sans-serif; font-size:16px; font-weight:800; color:var(--txt);">Пароль доступу</h3>
                    <p style="font-size:11px; color:var(--txt-muted);">Регулярно оновлюйте пароль для захисту.</p>
                </div>
                <form action="action.php?route=profile_action" method="POST" class="ajax-admin-form" style="padding:20px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="action" value="update_password">
                    
                    <input type="password" name="old_password" required placeholder="Поточний пароль" class="ui-input">
                    <input type="password" name="new_password" required minlength="6" placeholder="Новий пароль (мінімум 6 симв.)" class="ui-input">
                    
                    <button type="submit" class="ui-btn primary mt-10" style="background:var(--accent);">Оновити пароль</button>
                </form>
            </div>

            <div class="ui-card" style="display:flex; align-items:center; gap:14px; margin:0 20px;">
                <div style="width:40px; height:40px; border-radius:12px; background:rgba(16,185,129,0.1); color:var(--green); display:flex; align-items:center; justify-content:center; font-size:18px;"><i class="fa-solid fa-shield-check"></i></div>
                <div style="flex:1;">
                    <div style="font-size:13px; font-weight:800; color:var(--txt);">Статус захисту</div>
                    <div style="font-size:10px; color:var(--txt-muted); font-weight:700;">Сесія активна та зашифрована</div>
                </div>
                <span style="font-size:10px; color:var(--green); font-weight:800; background:rgba(16,185,129,0.1); padding:4px 8px; border-radius:6px;">OK</span>
            </div>
        </div>

        <!-- ==================================== -->
        <!-- 4. СТАТУС ТА ПОСАДА -->
        <!-- ==================================== -->
        <div class="slide" style="width: 25% !important;">
            <h2 class="ui-header-title" style="margin: 0 20px 15px;">Статус і Посада</h2>
            
            <div style="padding: 0 20px;">
                <?php if(isset($role) && in_array($role, ['head', 'manager', 'auditor'])): ?>
                    <div class="ui-card" style="border:1px solid #f59e0b; background:rgba(245,158,11,0.05);">
                        <h4 style="color:#f59e0b; font-size:14px; margin-bottom:10px;"><i class="fa-solid fa-briefcase"></i> Керівна посада</h4>
                        <p style="font-size:12px; color:var(--txt-muted); margin-bottom:15px;">Ви обіймаєте посаду <b><?= $badge['text'] ?></b>. Це накладає відповідальність за підписання протоколів.</p>
                        <button type="button" class="ui-btn primary js-open-resign-modal" style="background:#f59e0b; box-shadow:none;">Скласти повноваження</button>
                    </div>
                <?php endif; ?>
                
                <div class="ui-card mt-15" style="border:1px solid var(--red); background:rgba(239,68,68,0.05);">
                    <h4 style="color:var(--red); font-size:14px; margin-bottom:10px;"><i class="fa-solid fa-door-open"></i> Членство в ППО</h4>
                    <p style="font-size:12px; color:var(--txt-muted); margin-bottom:15px;">Ви маєте право вийти з організації за власним бажанням.</p>
                    <?php if(isset($role) && $role !== 'head'): ?>
                        <button type="button" class="ui-btn danger js-open-leave-modal">Вийти з ППО</button>
                    <?php else: ?>
                        <p style="font-size:11px; color:var(--red); font-weight:800;">Голова організації не може вийти, не передавши повноваження.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- НИЖНЄ МЕНЮ -->
<nav class="view-bar" id="profile-bottom-nav">
    <a href="javascript:void(0)" class="nav-item active js-go-to-profile-tab" data-idx="0" id="pn0"><i class="fa-solid fa-address-card"></i><span>АНКЕТА</span></a>
    <a href="javascript:void(0)" class="nav-item js-go-to-profile-tab" data-idx="1" id="pn1"><i class="fa-solid fa-bell"></i><span>СПОЩ.</span></a>
    <a href="javascript:void(0)" class="nav-item js-go-to-profile-tab" data-idx="2" id="pn2"><i class="fa-solid fa-shield-halved"></i><span>БЕЗПЕКА</span></a>
    <a href="javascript:void(0)" class="nav-item js-go-to-profile-tab" data-idx="3" id="pn3"><i class="fa-solid fa-door-open"></i><span>СТАТУС</span></a>
</nav>

<!-- МОДАЛКИ (Єдиний стандарт дизайну UI) -->
<div id="protocolKepModal" class="modal js-close-modal-click" data-target="protocolKepModal">
    <div class="modal-card ui-modal-box js-stop-propagation">
        <div class="ui-modal-icon blue"><i class="fa-solid fa-fingerprint"></i></div>
        <h3 class="ui-modal-title">Підпис Протоколу</h3>
        <p class="ui-modal-desc">Натискаючи "Підписати", ви офіційно завіряєте рішення зборів.</p>
        <div id="protocol-kep-status" style="font-size:13px; font-weight:800; color:var(--txt); margin-bottom:20px; height:20px;"></div>
        <button type="button" class="ui-btn primary js-simulate-protocol-kep" id="btn-sign-protocol-kep">Підписати</button>
        <button type="button" class="ui-btn-text js-close-modal" data-target="protocolKepModal">Скасувати</button>
        <!-- Схований інпут для передачі ID -->
        <input type="hidden" id="protocol_poll_id" value="">
    </div>
</div>

<div id="leavePpoModal" class="modal js-close-modal-click" data-target="leavePpoModal">
    <div class="modal-card ui-modal-box js-stop-propagation">
        <div class="ui-modal-icon" style="background:rgba(239,68,68,0.1); color:var(--red);"><i class="fa-solid fa-file-signature"></i></div>
        <h3 class="ui-modal-title">Заява на вихід</h3>
        <p class="ui-modal-desc">Ви дійсно бажаєте вийти з ППО? Ця дія накладе КЕП на вашу заяву і миттєво позбавить вас прав учасника.</p>
        <div id="leave-kep-status" style="font-size:13px; font-weight:800; color:var(--txt); margin-bottom:20px; height:20px;"></div>
        <button type="button" class="ui-btn danger js-simulate-leave-kep" id="btn-sign-leave">Підписати і Вийти</button>
        <button type="button" class="ui-btn-text js-close-modal" data-target="leavePpoModal">Скасувати</button>
    </div>
</div>

<div id="resignRoleModal" class="modal js-close-modal-click" data-target="resignRoleModal">
    <div class="modal-card ui-modal-box js-stop-propagation">
        <div class="ui-modal-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;"><i class="fa-solid fa-briefcase"></i></div>
        <h3 class="ui-modal-title">Скласти повноваження</h3>
        <p class="ui-modal-desc">Ви маєте намір достроково відмовитись від своєї керівної посади.</p>
        <div id="resign-kep-status" style="font-size:13px; font-weight:800; color:var(--txt); margin-bottom:20px; height:20px;"></div>
        <button type="button" class="ui-btn primary js-simulate-resign-kep" style="background:#f59e0b;" id="btn-sign-resign">Підписати і Скласти</button>
        <button type="button" class="ui-btn-text js-close-modal" data-target="resignRoleModal">Скасувати</button>
    </div>
</div>
