<?php
namespace Controllers;

class AuditorController {

    public static function handle() {
        if (!\Core\Auth::loggedIn()) {
            if (isset($_POST['ajax'])) { echo json_encode(['success' => false, 'msg' => 'Сесія закінчилась']); exit; }
            header("Location: login.php"); exit;
        }

        $user_id = (int)$_SESSION['user_id'];
        $role = \Core\DB::fetchColumn("SELECT role FROM users WHERE id = ?", [$user_id]);
        
        if ($role !== 'auditor') {
            if (isset($_POST['ajax'])) { echo json_encode(['success' => false, 'msg' => 'Доступ заборонено.']); exit; }
            $_SESSION['toast_msg'] = "❌ Доступ заборонено.";
            header("Location: index.php"); exit;
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'audit_tx': self::auditTransaction(); break;
            case 'generate_report': self::generateReport(); break;
            default:
                if (isset($_POST['ajax'])) { echo json_encode(['success' => false, 'msg' => 'Невідома дія']); exit; }
                header("Location: auditor_panel.php"); exit;
        }
    }

    private static function auditTransaction() {
        $user_id = (int)$_SESSION['user_id'];
        $ppo_id = (int)$_SESSION['ppo_id'];
        $tx_id = (int)($_POST['tx_id'] ?? 0); 
        $tx_type = $_POST['tx_type'] ?? 'bank'; 
        $is_ajax = isset($_POST['ajax']) ? true : false;
        
        // Захист від підміни назви таблиці (SQL Injection Prevent)
        $table = ($tx_type === 'cash') ? 'cash_transactions' : 'bank_transactions';

        \Core\DB::query("UPDATE {$table} SET is_audited = 1, audited_at = NOW(), auditor_id = ? WHERE id = ? AND ppo_id = ?", [$user_id, $tx_id, $ppo_id]);

        if ($is_ajax) { echo json_encode(['success' => true, 'msg' => '✅ Транзакцію підтверджено!']); exit; }
        header("Location: auditor_panel.php"); exit;
    }

    // ==========================================
    // 🛡️ 2. ГЕНЕРАЦІЯ АКТА (ПРОФЕСІЙНА ВЕРСІЯ)
    // ==========================================
    private static function generateReport() {
        $user_id = (int)$_SESSION['user_id'];
        $ppo_id = (int)$_SESSION['ppo_id'];
        
        $date_from = trim($_POST['date_from'] ?? '');
        $date_to = trim($_POST['date_to'] ?? '');
        $signature = trim($_POST['signature_hash'] ?? ''); 
        $is_ajax = isset($_POST['ajax']) ? true : false;

        if (empty($date_from) || empty($date_to) || empty($signature)) {
            if ($is_ajax) { echo json_encode(['success' => false, 'msg' => 'Вкажіть дати та накладіть КЕП!']); exit; }
            exit;
        }

        try {
            // 🔥 Відкриваємо безпечну транзакцію. Якщо щось піде не так - база відкотить всі зміни назад!
            \Core\DB::beginTransaction();

            $time_from = strtotime($date_from . ' 00:00:00');
            $time_to = strtotime($date_to . ' 23:59:59');
            $db_date_from = date('Y-m-d 00:00:00', $time_from);
            $db_date_to = date('Y-m-d 23:59:59', $time_to);
            $period = date('d.m.Y', $time_from) . " - " . date('d.m.Y', $time_to);

            // 1. ПЕРЕВІРКА ТРАНЗАКЦІЙ
            $unaudited_bank = \Core\DB::fetchColumn("SELECT COUNT(*) FROM bank_transactions WHERE ppo_id = ? AND is_audited = 0 AND time >= ? AND time <= ?", [$ppo_id, $time_from, $time_to]);
            $unaudited_cash = \Core\DB::fetchColumn("SELECT COUNT(*) FROM cash_transactions WHERE ppo_id = ? AND is_audited = 0 AND created_at >= ? AND created_at <= ?", [$ppo_id, $db_date_from, $db_date_to]);

            if ($unaudited_bank > 0 || $unaudited_cash > 0) {
                \Core\DB::rollBack(); // Відкат
                $msg = "❌ За обраний період ($period) є неперевірені транзакції (Банк: $unaudited_bank, Каса: $unaudited_cash). Спочатку підтвердіть їх!";
                if ($is_ajax) { echo json_encode(['success' => false, 'msg' => $msg]); exit; }
                exit;
            }

            // 2. ОТРИМУЄМО ПІБ
            $auditor_raw = \Core\DB::fetchColumn("SELECT full_name FROM users WHERE id = ?", [$user_id]);
            $auditor_name = class_exists('\Core\Security') ? (\Core\Security::decrypt($auditor_raw) ?: $auditor_raw) : $auditor_raw;

            // 3. ЮРИДИЧНИЙ ТЕКСТ
            $report_text = "АКТ РЕВІЗІЙНОЇ КОМІСІЇ\n";
            $report_text .= "Період перевірки: " . $period . "\n";
            $report_text .= "Ревізор: " . $auditor_name . "\n\n";
            $report_text .= "Мною, як обраним представником Ревізійної комісії, було проведено повну перевірку фінансово-господарської діяльності ППО за вказаний період.\n\n";
            $report_text .= "РЕЗУЛЬТАТИ ПЕРЕВІРКИ:\n";
            $report_text .= "1. Всі банківські та готівкові транзакції успішно перевірені.\n";
            $report_text .= "2. Нецільового використання коштів — НЕ ВИЯВЛЕНО.\n";
            $report_text .= "3. Документація ведеться у відповідності до Статуту.\n\n";
            $report_text .= "Прошу Загальні збори затвердити даний Акт.";

            // 4. СТВОРЮЄМО ГОЛОСУВАННЯ
            $poll_title = "Затвердження Акта ревізії (" . $period . ")";
            $poll_desc = "Ревізійна комісія завершила перевірку. Текст Акта прикріплено до цього голосування.\n\n**Текст Акта:**\n" . $report_text;
            $end_date = date('Y-m-d H:i:s', strtotime('+7 days'));

            // 🔥 ВИПРАВЛЕНО: Додали action_type = 'audit' 
            \Core\DB::query("INSERT INTO polls (ppo_id, title, description, end_date, threshold_type, is_active, require_kep, action_type) VALUES (?, ?, ?, ?, '50+1', 1, 1, 'audit')", [$ppo_id, $poll_title, $poll_desc, $end_date]);
            
            // 🔥 Отримуємо правильний ID моментально
            $poll_id = \Core\DB::lastInsertId();

            // Записуємо варіанти
            \Core\DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, 'Затвердити Акт')", [$poll_id]);
            \Core\DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, 'Відхилити')", [$poll_id]);

            // 5. РЕЄСТРУЄМО АКТ У АРХІВ
            \Core\DB::query("INSERT INTO audit_reports (ppo_id, auditor_user_id, report_period, report_text, poll_id, status, auditor_signed_at, signature_hash) VALUES (?, ?, ?, ?, ?, 'pending_approval', NOW(), ?)", [$ppo_id, $user_id, $period, $report_text, $poll_id, $signature]);

            // Фіксуємо всі зміни (якщо все пройшло без помилок)
            \Core\DB::commit();

            if ($is_ajax) {
                echo json_encode(['success' => true, 'msg' => '✅ Акт згенеровано, підписано та винесено на голосування!']);
                exit;
            }
        } catch (\Exception $e) {
            \Core\DB::rollBack(); // Відкат при будь-якій помилці БД
            if ($is_ajax) { echo json_encode(['success' => false, 'msg' => 'Помилка бази даних: ' . $e->getMessage()]); exit; }
        }
    }
}
