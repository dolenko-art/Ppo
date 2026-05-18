<?php if(!empty($is_leadership)): ?>
    <div class="ui-tabs" style="margin: 20px 20px 10px;">
        <a href="archive.php?tab=protocols" class="ui-tab <?= $current_tab == 'protocols' ? 'active' : '' ?>">Протоколи</a>
        <a href="archive.php?tab=admission_apps" class="ui-tab <?= $current_tab == 'admission_apps' ? 'active' : '' ?>">Заяви на вступ</a>
        <a href="archive.php?tab=leave_apps" class="ui-tab <?= $current_tab == 'leave_apps' ? 'active' : '' ?>">Заяви на вихід</a>
    </div>
<?php else: ?>
    <div style="height: 15px;"></div>
<?php endif; ?>

<div style="padding: 0 20px 20px;">

    <?php if($current_tab === 'protocols'): ?>
        <?php if(empty($polls)): ?>
            <div class="ui-empty">
                <i class="fa-solid fa-folder-open"></i>
                Архів порожній. Жодне голосування ще не завершено.
            </div>
        <?php else: ?>
            <?php foreach($polls as $p): 
                $p_num = date('y', strtotime($p['end_date'])) . '-' . sprintf("%04d", $p['id']);
                
                $audit_id = \Core\DB::fetchColumn("SELECT id FROM audit_reports WHERE poll_id = ?", [$p['id']]);
                if (!$audit_id) {
                    $act_type = $p['action_type'] ?? '';
                    if ($act_type === 'audit' || mb_strpos((string)$act_type, 'report') !== false) {
                        $target = (int)($p['target_user_id'] ?? 0);
                        if ($target > 0) {
                            $exists = \Core\DB::fetchColumn("SELECT id FROM audit_reports WHERE id = ?", [$target]);
                            if ($exists) $audit_id = $target;
                        }
                    }
                }
            ?>
                <div class="ui-card">
                    <div class="ui-badges">
                        <span class="ui-badge green"><i class="fa-solid fa-check"></i> Завершено</span>
                        <span class="ui-badge gray right">Протокол №<?= $p_num ?></span>
                    </div>
                    
                    <h4 class="ui-title"><?= htmlspecialchars($p['title']) ?></h4>
                    <div class="ui-desc"><i class="fa-regular fa-calendar"></i> Затверджено: <?= date('d.m.Y', strtotime($p['end_date'])) ?></div>
                    
                    <div class="ui-actions">
                        <a href="archive.php?action=protocol&id=<?= $p['id'] ?>" class="ui-btn secondary">
                            <i class="fa-solid fa-file-signature"></i> Офіційний протокол
                        </a>
                        
                        <!-- 🔥 КНОПКА ДОДАТКА (ТІЛЬКИ ДЛЯ E2E-V) -->
                        <?php if((int)$p['is_secret'] === 2): ?>
                            <a href="archive.php?action=registry&id=<?= $p['id'] ?>" target="_blank" class="ui-btn secondary" style="color:#8b5cf6; border-color:rgba(139,92,246,0.5);">
                                <i class="fa-solid fa-receipt"></i> Додаток 1 (Реєстр)
                            </a>
                        <?php endif; ?>
                        
                        <?php if($audit_id): ?>
                            <a href="report.php?id=<?= $audit_id ?>" target="_blank" class="ui-btn secondary" style="color:var(--green); border-color:rgba(16,185,129,0.5);">
                                <i class="fa-solid fa-paperclip"></i> Акт ревізії
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if($total_pages > 1): ?>
                <div class="pag-wrap">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?tab=protocols&page=<?= $i ?>" class="pag-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($current_tab === 'admission_apps' && !empty($is_leadership)): ?>
        <?php if(empty($admission_apps)): ?>
            <div class="ui-empty">
                <i class="fa-solid fa-user-plus"></i>
                Заяв на вступ не знайдено.
            </div>
        <?php else: ?>
            <?php foreach($admission_apps as $app): ?>
                <div class="ui-card">
                    <div class="ui-badges">
                        <span class="ui-badge green"><i class="fa-solid fa-user-plus"></i> Заява на вступ</span>
                        <span class="ui-badge gray right">№<?= sprintf("%04d", $app['id']) ?></span>
                    </div>
                    <h4 class="ui-title"><?= htmlspecialchars($app['user_name_snapshot']) ?></h4>
                    <div class="ui-desc"><i class="fa-regular fa-calendar"></i> Оформлено: <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></div>
                    
                    <a href="view_admission.php?id=<?= $app['id'] ?>" class="ui-btn primary">
                        <i class="fa-regular fa-eye"></i> Переглянути документ
                    </a>
                </div>
            <?php endforeach; ?>

            <?php if($total_pages > 1): ?>
                <div class="pag-wrap">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?tab=admission_apps&page=<?= $i ?>" class="pag-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($current_tab === 'leave_apps' && !empty($is_leadership)): ?>
        <?php if(empty($leave_apps)): ?>
            <div class="ui-empty">
                <i class="fa-solid fa-person-walking-arrow-right"></i>
                Заяв на вихід не знайдено.
            </div>
        <?php else: ?>
            <?php foreach($leave_apps as $app): ?>
                <div class="ui-card">
                    <div class="ui-badges">
                        <span class="ui-badge orange"><i class="fa-solid fa-file-signature"></i> Заява на вихід</span>
                        <span class="ui-badge gray right">№<?= sprintf("%04d", $app['id']) ?></span>
                    </div>
                    <h4 class="ui-title"><?= htmlspecialchars($app['user_name_snapshot']) ?></h4>
                    <div class="ui-desc"><i class="fa-regular fa-calendar"></i> Оформлено: <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></div>
                    
                    <a href="view_application.php?id=<?= $app['id'] ?>" class="ui-btn primary">
                        <i class="fa-regular fa-eye"></i> Переглянути документ
                    </a>
                </div>
            <?php endforeach; ?>

            <?php if($total_pages > 1): ?>
                <div class="pag-wrap">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?tab=leave_apps&page=<?= $i ?>" class="pag-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

</div>
