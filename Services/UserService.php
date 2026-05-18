<?php
namespace Services;

use Core\DB;
use Core\Guard;
use Core\OneSignal;
use Exception;

class UserService {

    // ==========================================
    // 👤 СХВАЛЕННЯ ТА ВІДХИЛЕННЯ РЕЄСТРАЦІЇ
    // ==========================================
    public static function approveReg($userId, $ppoId) {
        $user = DB::fetch("SELECT id, full_name, status FROM users WHERE id = ? AND ppo_id = ?", [$userId, $ppoId]);
        if (!$user) return ['success' => false, 'msg' => 'Користувача не знайдено.'];
        
        // Змінюємо статус з pending_reg на candidate (або pending), щоб дати доступ до кабінету
        DB::query("UPDATE users SET status = 'candidate' WHERE id = ?", [$userId]);
        
        $name = class_exists('\Core\Security') ? (\Core\Security::decrypt($user['full_name']) ?: $user['full_name']) : $user['full_name'];
        Guard::logAction($_SESSION['user_id'] ?? 0, 'Схвалення реєстрації', "Кандидат: $name");
        
        // 🔔 ПУШ: Інформуємо інших керівників, що заявку схвалено
        if (class_exists('\Core\OneSignal')) {
            OneSignal::notifyAdmins($ppoId, "Реєстрацію кандидата {$name} успішно схвалено.");
        }
        
        return ['success' => true, 'msg' => 'Заявку на реєстрацію схвалено.'];
    }

    public static function rejectReg($userId, $ppoId) {
        $user = DB::fetch("SELECT id, full_name, status FROM users WHERE id = ? AND ppo_id = ?", [$userId, $ppoId]);
        if (!$user) return ['success' => false, 'msg' => 'Користувача не знайдено.'];
        
        $name = class_exists('\Core\Security') ? (\Core\Security::decrypt($user['full_name']) ?: $user['full_name']) : $user['full_name'];
        
        // Видаляємо користувача, якщо реєстрацію відхилено
        DB::query("DELETE FROM users WHERE id = ?", [$userId]);
        Guard::logAction($_SESSION['user_id'] ?? 0, 'Відхилення реєстрації', "Видалено: $name");
        
        // 🔔 ПУШ: Інформуємо інших керівників, що заявку відхилено
        if (class_exists('\Core\OneSignal')) {
            OneSignal::notifyAdmins($ppoId, "Заявку на реєстрацію відхилено ({$name}). Дані видалено.");
        }
        
        return ['success' => true, 'msg' => 'Заявку відхилено, дані видалено.'];
    }

    // ==========================================
    // 🔍 ПОШУК ТА ІНШІ МЕТОДИ
    // ==========================================
    public static function searchUsers($query, $ppoId) {
        if (mb_strlen($query) < 2) return [];
        $users = DB::fetchAll("SELECT id, full_name FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')", [$ppoId]);
        $results = [];
        foreach ($users as $u) {
            $name = class_exists('\Core\Security') ? (\Core\Security::decrypt($u['full_name']) ?: $u['full_name']) : $u['full_name'];
            if (mb_stripos($name, $query) !== false) { $results[] = ['id' => $u['id'], 'name' => $name]; }
        }
        return array_slice($results, 0, 10);
    }

    public static function resignRole($userId, $ppoId) {
        DB::beginTransaction();
        try {
            $pending_prot = (int)DB::fetchColumn("SELECT COUNT(*) FROM protocols WHERE ppo_id = ? AND ((head_user_id = ? AND head_signed_at IS NULL) OR (secretary_user_id = ? AND manager_signed_at IS NULL))", [$ppoId, $userId, $userId]);
            if ($pending_prot > 0) throw new Exception('🛑 Підпишіть очікуючі протоколи!');

            $user = DB::fetch("SELECT role, full_name FROM users WHERE id = ?", [$userId]);
            if (empty($user['role'])) throw new Exception('У вас немає посади.');

            $role_names = ['head' => 'Голова ППО', 'auditor' => 'Ревізор', 'manager' => 'Менеджер'];
            $role_ua = $role_names[$user['role']] ?? 'Керівник';
            $user_name = class_exists('\Core\Security') ? (\Core\Security::decrypt($user['full_name']) ?: $user['full_name']) : $user['full_name'];

            DB::query("UPDATE users SET role = NULL, role_assigned_at = NULL WHERE id = ?", [$userId]);
            Guard::logAction($userId, 'Достроково склав повноваження', "Посада: {$role_ua}");

            $news_text = "Офіційно повідомляємо, що $user_name достроково складає з себе повноваження ($role_ua).";
            $is_head_resign = false;
            
            if ($user['role'] === 'head') {
                $end_date = date('Y-m-d H:i:s', strtotime('+7 days'));
                DB::query("INSERT INTO nominations (title, description, end_date, ppo_id, target_role) VALUES (?, ?, ?, ?, 'head')", ["Висування кандидатів на посаду Голови ППО", "Колишній Голова склав повноваження. Пропонуйте кандидатів!", $end_date, $ppoId]);
                $news_text .= " Система автоматично розпочала процедуру висування нових кандидатів.";
                $is_head_resign = true;
            }

            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", ["Складання повноважень", $news_text, $ppoId]);
            
            DB::commit();

            // 🔔 ПУШ: Інформуємо про складання повноважень
            if (class_exists('\Core\OneSignal')) {
                if ($is_head_resign) {
                    // Якщо це Голова, повідомляємо всіх, бо почалося висування
                    OneSignal::notifyAllMembers($ppoId, "⚠️ Кадрові зміни", "Голова ППО достроково склав повноваження. Розпочато збір кандидатур.");
                } else {
                    // Якщо це Менеджер/Ревізор, повідомляємо керівництво
                    OneSignal::notifyAdmins($ppoId, "{$user_name} достроково складає повноваження ({$role_ua}).");
                }
            }

            return ['success' => true, 'msg' => 'Ви успішно склали повноваження.'];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public static function leavePpo($userId, $ppoId) {
        DB::beginTransaction();
        try {
            $pending_prot = (int)DB::fetchColumn("SELECT COUNT(*) FROM protocols WHERE ppo_id = ? AND ((head_user_id = ? AND head_signed_at IS NULL) OR (secretary_user_id = ? AND manager_signed_at IS NULL))", [$ppoId, $userId, $userId]);
            if ($pending_prot > 0) throw new Exception('🛑 Підпишіть очікуючі протоколи!');

            $user = DB::fetch("SELECT role, full_name, status FROM users WHERE id = ?", [$userId]);
            if (!$user || !in_array($user['status'], ['member', 'admin'])) throw new Exception('Ви не є дійсним членом ППО.');
            if ($user['role'] === 'head') throw new Exception('Спочатку складіть повноваження.');

            $name = class_exists('\Core\Security') ? (\Core\Security::decrypt($user['full_name']) ?: $user['full_name']) : $user['full_name'];
            $sig_hash = hash('sha256', $userId . $ppoId . time() . "LEAVE_PPO");

            DB::query("INSERT INTO leave_applications (ppo_id, user_id, user_name_snapshot, signature_hash) VALUES (?, ?, ?, ?)", [$ppoId, $userId, $name, $sig_hash]);
            DB::query("UPDATE users SET status = 'former', role = NULL, left_at = NOW(), leave_reason = 'За власним бажанням' WHERE id = ?", [$userId]);
            Guard::logAction($userId, 'Вихід з ППО', "Учасник: $name", true);

            DB::commit();

            // 🔔 ПУШ: Інформуємо керівництво про вихід учасника
            if (class_exists('\Core\OneSignal')) {
                OneSignal::notifyAdmins($ppoId, "Учасник {$name} підписав заяву на вихід з організації.");
            }

            return ['success' => true, 'msg' => 'Ви успішно вийшли з ППО. Ваша заява зафіксована.'];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    // ==========================================
    // 📝 ПОДАЧА ЗАЯВИ НА ВСТУП (КЕП)
    // ==========================================
    public static function apply($userId, $ppoIdStr) {
        $ppoId = (int)$ppoIdStr;
        if ($ppoId === 0) {
            $ppoId = (int)DB::fetchColumn("SELECT ppo_id FROM users WHERE id = ?", [$userId]);
        }

        if ($ppoId <= 0) {
            return ['success' => false, 'msg' => 'Не знайдено організацію для вступу.'];
        }

        DB::beginTransaction();
        try {
            $userData = DB::fetch("SELECT full_name, status FROM users WHERE id = ?", [$userId]);
            if (!$userData) throw new Exception('Користувача не знайдено.');

            $currentStatus = $userData['status'];
            $fullName = class_exists('\Core\Security') ? (\Core\Security::decrypt($userData['full_name']) ?: $userData['full_name']) : $userData['full_name'];
            $sigHash = hash('sha256', $userId . $ppoId . time() . "ADMISSION_PPO");
            
            $existsDoc = DB::fetchColumn("SELECT id FROM admission_applications WHERE user_id=? AND ppo_id=?", [$userId, $ppoId]);
            if (!$existsDoc) {
                DB::query("INSERT INTO admission_applications (ppo_id, user_id, user_name_snapshot, signature_hash) VALUES (?, ?, ?, ?)", [$ppoId, $userId, $fullName, $sigHash]);
            }

            if ($currentStatus === 'candidate' || $currentStatus === 'former') {
                $existsPoll = DB::fetchColumn("SELECT id FROM polls WHERE action_type='admission' AND target_user_id=? AND is_active=1", [$userId]);
                if (!$existsPoll) {
                    $endDate = date('Y-m-d H:i:s', strtotime('+3 days')); 
                    $totalMembers = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppoId]) ?: 1;
                    $reqQuorum = floor($totalMembers / 2) + 1; 

                    DB::query("INSERT INTO polls (title, description, end_date, is_active, ppo_id, action_type, target_user_id, threshold_type, required_quorum) VALUES (?, ?, ?, 1, ?, 'admission', ?, '50+1', ?)", ["Прийняття в ППО: $fullName", "Просимо проголосувати за прийняття.", $endDate, $ppoId, $userId, $reqQuorum]);
                    $pollId = DB::fetchColumn("SELECT LAST_INSERT_ID()");
                    DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, 'За прийняття'), (?, 'Проти')", [$pollId, $pollId]);
                    
                    // 🔔 ПУШ: Сповіщаємо керівництво про нову заяву на вступ та створене голосування
                    if (class_exists('\Core\OneSignal')) {
                        OneSignal::notifyAdmins($ppoId, "Нова заява на вступ! {$fullName} очікує рішення зборів.");
                    }
                }
            } else {
                DB::query("UPDATE users SET ppo_id = ?, status = 'pending' WHERE id = ?", [$ppoId, $userId]);
                // 🔔 ПУШ: Сповіщаємо керівництво про нову заявку (якщо статус ще pending)
                if (class_exists('\Core\OneSignal')) {
                    OneSignal::notifyAdmins($ppoId, "Новий користувач {$fullName} подав запит на реєстрацію.");
                }
            }
            
            DB::commit();
            return ['success' => true, 'msg' => 'Заяву успішно підписано та відправлено!', 'ppo_id' => $ppoId];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}
