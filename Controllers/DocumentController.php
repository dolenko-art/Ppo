<?php
namespace Controllers;

class DocumentController {

    // ==========================================
    // 📝 ПЕРЕГЛЯД ЗАЯВИ НА ВИХІД
    // ==========================================
    public static function viewLeave($app_id) {
        $ppo_id = (int)$_SESSION['ppo_id'];
        $user_id = (int)$_SESSION['user_id'];

        // Перевірка прав (Доступ має ТІЛЬКИ керівництво)
        $user = \Core\DB::fetch("SELECT role, status FROM users WHERE id = ?", [$user_id]);
        $is_leadership = in_array($user['role'], ['head', 'auditor', 'manager']) || $user['status'] === 'admin';

        if (!$is_leadership) {
            $_SESSION['toast_msg'] = "❌ У вас немає доступу до архівів заяв.";
            header("Location: archive.php"); 
            exit;
        }

        // Шукаємо заяву
        $app = \Core\DB::fetch("SELECT * FROM leave_applications WHERE id = ? AND ppo_id = ?", [$app_id, $ppo_id]);
        if (!$app) {
            $_SESSION['toast_msg'] = "❌ Заяву не знайдено.";
            header("Location: archive.php?tab=leave_apps"); 
            exit;
        }

        $ppo_name = \Core\DB::fetchColumn("SELECT name FROM ppos WHERE id = ?", [$ppo_id]) ?: 'Первинної профспілкової організації';

        // 🔥 Підключення через загальний макет (Layout)
        $title = 'Заява на вихід №' . $app_id;
        $header_title = 'Офіційний документ';
        $view_path = 'doc_leave.php'; 
        require_once 'views/layout.php';
    }

    // ==========================================
    // 📝 ПЕРЕГЛЯД ЗАЯВИ НА ВСТУП
    // ==========================================
    public static function viewAdmission($app_id) {
        $ppo_id = (int)$_SESSION['ppo_id'];
        $user_id = (int)$_SESSION['user_id'];

        $user = \Core\DB::fetch("SELECT role, status FROM users WHERE id = ?", [$user_id]);
        $is_leadership = in_array($user['role'], ['head', 'auditor', 'manager']) || $user['status'] === 'admin';

        if (!$is_leadership) {
            $_SESSION['toast_msg'] = "❌ У вас немає доступу до архівів заяв.";
            header("Location: archive.php"); 
            exit;
        }

        $app = \Core\DB::fetch("SELECT * FROM admission_applications WHERE id = ? AND ppo_id = ?", [$app_id, $ppo_id]);
        if (!$app) {
            $_SESSION['toast_msg'] = "❌ Заяву не знайдено.";
            header("Location: archive.php?tab=admission_apps"); 
            exit;
        }

        $ppo_name = \Core\DB::fetchColumn("SELECT name FROM ppos WHERE id = ?", [$ppo_id]) ?: 'Первинної профспілкової організації';

        // 🔥 Підключення через загальний макет (Layout)
        $title = 'Заява на вступ №' . $app_id;
        $header_title = 'Офіційний документ';
        $view_path = 'doc_admission.php'; 
        require_once 'views/layout.php';
    }

    // ==========================================
    // 📊 ПЕРЕГЛЯД АКТУ РЕВІЗІЇ
    // ==========================================
    public static function viewAuditReport($report_id) {
        $ppo_id = (int)($_SESSION['ppo_id'] ?? 0);

        // Отримуємо Акт
        $report = \Core\DB::fetch("SELECT * FROM audit_reports WHERE id = ? AND ppo_id = ?", [$report_id, $ppo_id]);

        if (!$report) {
            $_SESSION['toast_msg'] = "❌ Акт не знайдено або у вас немає доступу.";
            header("Location: auditor_panel.php?tab=2"); 
            exit;
        }

        // Отримуємо ПІБ ревізора
        $auditor_raw = \Core\DB::fetchColumn("SELECT full_name FROM users WHERE id = ?", [$report['auditor_user_id']]);
        $auditor_name = class_exists('\Core\Security') ? (\Core\Security::decrypt($auditor_raw) ?: $auditor_raw) : $auditor_raw;

        // Парсимо дати з періоду
        $dates = explode(" - ", $report['report_period']);
        if (count($dates) == 2) {
            $time_from = strtotime($dates[0] . ' 00:00:00');
            $time_to = strtotime($dates[1] . ' 23:59:59');
            $db_date_from = date('Y-m-d 00:00:00', $time_from);
            $db_date_to = date('Y-m-d 23:59:59', $time_to);
        } else {
            $_SESSION['toast_msg'] = "❌ Помилка формату дати в Акті.";
            header("Location: auditor_panel.php?tab=2"); 
            exit;
        }

        // Витягуємо транзакції, які потрапили у цей Акт
               // Витягуємо транзакції, які потрапили у цей Акт (ТІЛЬКИ ПЕРЕВІРЕНІ)
        $bank_tx = \Core\DB::fetchAll("SELECT * FROM bank_transactions WHERE ppo_id = ? AND time >= ? AND time <= ? AND is_audited = 1 ORDER BY time DESC", [$ppo_id, $time_from, $time_to]);
        
        $cash_tx = \Core\DB::fetchAll("SELECT * FROM cash_transactions WHERE ppo_id = ? AND created_at >= ? AND created_at <= ? AND is_audited = 1 ORDER BY created_at DESC", [$ppo_id, $db_date_from, $db_date_to]);


        // Форматуємо статус
        $db_status = strtolower(trim((string)$report['status']));
        $status_color = '#f59e0b'; 
        $status_text = 'Очікує затвердження';

        if (in_array($db_status, ['approved', '1'])) { 
            $status_color = '#10b981'; 
            $status_text = 'Затверджено Зборами'; 
        } 
        elseif (in_array($db_status, ['rejected', '2'])) { 
            $status_color = '#ef4444'; 
            $status_text = 'Відхилено Зборами'; 
        } 
        else {
            // Перевіряємо РЕЗУЛЬТАТ голосування
            $poll = \Core\DB::fetch("SELECT is_active, is_processed FROM polls WHERE id = ?", [$report['poll_id']]);
            if ($poll && $poll['is_active'] == 0 && $poll['is_processed'] == 1) {
                
                $votes = \Core\DB::fetchAll("SELECT po.option_text, COUNT(v.id) as count FROM poll_options po LEFT JOIN votes v ON v.option_id = po.id WHERE po.poll_id = ? GROUP BY po.id", [$report['poll_id']]);
                
                $max_votes = -1; $winning_options = [];
                foreach ($votes as $v) {
                    if ((int)$v['count'] > $max_votes) {
                        $max_votes = (int)$v['count']; $winning_options = [$v['option_text']];
                    } elseif ((int)$v['count'] === $max_votes) {
                        $winning_options[] = $v['option_text'];
                    }
                }

                $winning_option_lower = isset($winning_options[0]) ? mb_strtolower($winning_options[0], 'UTF-8') : '';
                $is_approved = (count($winning_options) === 1 && (mb_strpos($winning_option_lower, 'за') !== false || mb_strpos($winning_option_lower, 'затвердити') !== false) && mb_strpos($winning_option_lower, 'проти') === false);

                if ($is_approved) {
                    $status_color = '#10b981'; $status_text = 'Затверджено Зборами';
                    \Core\DB::query("UPDATE audit_reports SET status = 'approved' WHERE id = ?", [$report_id]);
                } else {
                    $status_color = '#ef4444'; $status_text = 'Відхилено Зборами';
                    \Core\DB::query("UPDATE audit_reports SET status = 'rejected' WHERE id = ?", [$report_id]);
                }
            }
        }

        // 🔥 Підключення через загальний макет (Layout)
        $title = 'Акт ревізії';
        $header_title = 'Офіційний звіт';
        $view_path = 'doc_audit_report.php';
        require_once 'views/layout.php';
    }

}
