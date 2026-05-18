<div style="padding: 10px 15px;">
    <?php if (empty($logs)): ?>
        <div class="card" style="text-align: center; padding: 60px 20px; border: 2px dashed var(--border);">
            <i class="fa-solid fa-clock-rotate-left" style="font-size: 40px; opacity: 0.2; margin-bottom: 15px;"></i>
            <h4 style="font-family:'Syne'; margin-bottom: 5px;">Історія порожня</h4>
            <p style="font-size: 12px; color: var(--txt-muted);">Жодних системних дій ще не зафіксовано.</p>
        </div>
    <?php else: ?>
        <?php foreach ($logs as $log): 
            // ВІЗУАЛЬНА ЛОГІКА: Визначаємо, хто зробив дію
            $admin_id = (int)$log['admin_id'];
            
            if ($admin_id === 0 || empty($log['admin_name'])) {
                $actor_name  = 'Система';
                $actor_icon  = 'fa-robot';
                $actor_color = '#8b5cf6'; // Фіолетовий
                $bg_color    = 'rgba(139, 92, 246, 0.05)';
            } else {
                $role = trim((string)$log['admin_role']);
                $actor_name = htmlspecialchars($log['admin_name']); 
                
                if ($role === 'head') {
                    $actor_icon  = 'fa-user-tie';
                    $actor_color = 'var(--accent)';
                    $bg_color    = 'rgba(59, 130, 246, 0.05)';
                } elseif ($role === 'manager') {
                    $actor_icon  = 'fa-user-pen';
                    $actor_color = 'var(--green)';
                    $bg_color    = 'rgba(16, 185, 129, 0.05)';
                } elseif ($role === 'auditor') {
                    $actor_icon  = 'fa-user-shield';
                    $actor_color = '#f59e0b';
                    $bg_color    = 'rgba(245, 158, 11, 0.05)';
                } else {
                    $actor_icon  = 'fa-user-gear';
                    $actor_color = 'var(--txt-muted)';
                    $bg_color    = 'var(--surface)';
                }
            }
        ?>
            <!-- КОМПАКТНА КАРТКА -->
            <div class="card" style="margin-bottom: 10px; padding: 12px; border-left: 4px solid <?= $actor_color ?>; background: <?= $bg_color ?>;">
                
                <!-- Верхній рядок: Дата + Автор (Гнучка верстка) -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px;">
                    <!-- Дата і час (не стискаються) -->
                    <div style="font-size:9px; font-weight:800; color:var(--txt-muted); display:flex; gap:6px; flex-shrink:0;">
                        <span style="background:var(--bg); padding:3px 6px; border-radius:4px; border:1px solid var(--border);">
                            <i class="fa-regular fa-calendar"></i> <?= date('d.m.y', strtotime($log['created_at'])) ?>
                        </span>
                        <span style="background:var(--bg); padding:3px 6px; border-radius:4px; border:1px solid var(--border);">
                            <i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($log['created_at'])) ?>
                        </span>
                    </div>
                    
                    <!-- Автор (Займає весь вільний простір, красиво обрізається, якщо надто довгий) -->
                    <div style="font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:flex-end; gap:4px; color:<?= $actor_color ?>; min-width:0; flex:1;">
                        <i class="fa-solid <?= $actor_icon ?>" style="flex-shrink:0;"></i> 
                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $actor_name ?></span>
                    </div>
                </div>
                
                <!-- Нижній рядок: Дія + Ціль -->
                <div style="display:flex; align-items:flex-start; gap:8px;">
                    <div style="width:24px; height:24px; border-radius:6px; background:var(--bg); color:<?= $actor_color ?>; display:flex; align-items:center; justify-content:center; border:1px solid var(--border); flex-shrink:0; margin-top:2px;">
                        <i class="fa-solid fa-bolt" style="font-size:10px;"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13px; font-weight:800; color:var(--txt); line-height:1.2; margin-bottom:4px;">
                            <?= htmlspecialchars($log['action_type']) ?>
                        </div>
                        <?php if(!empty($log['target_name'])): ?>
                        <div style="font-size:11px; color:var(--txt-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <i class="fa-solid fa-arrow-right-to-bracket" style="opacity:0.5; margin-right:3px;"></i> Стосується: <strong style="color:var(--txt);"><?= htmlspecialchars($log['target_name']) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

        <!-- КОМПАКТНА ПАГІНАЦІЯ -->
        <?php if ($total_logs > $limit): ?>
            <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:6px; padding: 15px 0;">
                <?php 
                $total_pages = ceil($total_logs / $limit);
                // Показуємо максимум 5 кнопок сторінок
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if($start_page > 1) echo '<span style="color:var(--txt-muted); align-self:flex-end; font-size:12px; margin-right:4px;">...</span>';
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?p=<?= $i ?>" class="pag-link <?= $page == $i ? 'active' : '' ?>" style="text-decoration:none; width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:8px; background:<?= $page==$i ? 'var(--accent)' : 'var(--surface)' ?>; color:<?= $page==$i ? '#fff' : 'var(--txt)' ?>; font-size:12px; font-weight:800; border:1px solid var(--border);"><?= $i ?></a>
                <?php endfor; 
                
                if($end_page < $total_pages) echo '<span style="color:var(--txt-muted); align-self:flex-end; font-size:12px; margin-left:4px;">...</span>';
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
