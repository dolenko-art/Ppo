<?php
require_once 'db.php';

if (!\Core\Auth::loggedIn()) { header("Location: login.php"); exit; }

$ppo_id = (int)$_SESSION['ppo_id'];

// Пагінація (10 записів на сторінку)
$limit = 10;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;

// Отримуємо логи + РОЛІ користувачів, щоб знати, хто є хто
$logs = \Core\DB::fetchAll("
    SELECT a.*, u.full_name as admin_name, u.role as admin_role 
    FROM audit_logs a
    LEFT JOIN users u ON a.admin_id = u.id
    WHERE a.ppo_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT $limit OFFSET $offset
", [$ppo_id]);

// 🔥 РОЗШИФРОВУЄМО ІМЕНА АДМІНІСТРАТОРІВ
if (class_exists('\Core\Security')) {
    foreach ($logs as &$log) {
        if (!empty($log['admin_name'])) {
            $decrypted = \Core\Security::decrypt($log['admin_name']);
            if ($decrypted) {
                $log['admin_name'] = $decrypted;
            }
        }
    }
    unset($log); // Очищаємо посилання, щоб не було помилок пам'яті
}

$total_logs = \Core\DB::fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE ppo_id = ?", [$ppo_id]);

// Підключаємо візуал
$title = 'Журнал дій ППО';
$header_title = 'Журнал дій';
$view_path = 'audit_log_view.php';
require_once 'views/layout.php';

