<?php

namespace Services;

use Core\DB;
use Exception;

class PetitionService
{
    /**
     * Створення нової петиції
     */
    public static function create($userId, $ppoId, $title, $description, $isAnon, $isSecret)
    {
        if (empty($title) || empty($description)) {
            return ['success' => false, 'msg' => 'Будь ласка, заповніть назву та опис ініціативи'];
        }

        DB::beginTransaction();
        try {
            DB::execute(
                "INSERT INTO petitions (ppo_id, user_id, title, description, is_anonymous_creator, is_secret_voting, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 0, NOW())",
                [$ppoId, $userId, $title, $description, $isAnon, $isSecret]
            );

            DB::commit();

            // 🔔 ПУШ: Інформуємо керівництво про появу нової ініціативи
            if (class_exists('\Core\OneSignal')) {
                \Core\OneSignal::notifyAdmins($ppoId, "Нова ініціатива: «{$title}».", "https://myppo.pp.ua/?tab=initiatives");
            }

            return ['success' => true, 'msg' => 'Ініціативу успішно опубліковано! Оновіть сторінку.'];
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Petition Create Error: " . $e->getMessage());
            return ['success' => false, 'msg' => 'Помилка бази даних під час збереження.'];
        }
    }

    /**
     * Підписання петиції (з перевіркою кворуму та дедлайну 7 днів)
     */
    public static function sign($userId, $petitionId)
    {
        DB::beginTransaction();
        try {
            // Додали вибірку created_at для перевірки дедлайну
            $petition = DB::fetch("SELECT id, ppo_id, title, quorum_percent, status, created_at FROM petitions WHERE id = ? FOR UPDATE", [$petitionId]);
            if (!$petition) throw new Exception('Ініціативу не знайдено');

            // Перевірка, чи не минуло 7 днів
            if (strtotime($petition['created_at']) <= strtotime('-7 days')) {
                throw new Exception('Час на збір підписів (7 днів) вже минув. Ініціатива закрита.');
            }

            if ((string)$petition['status'] !== '0') {
                throw new Exception('Ця ініціатива вже закрита або розглядається керівництвом.');
            }

            $alreadySigned = DB::fetchColumn("SELECT id FROM petition_signatures WHERE petition_id = ? AND user_id = ?", [$petitionId, $userId]);
            if ($alreadySigned) throw new Exception('Ви вже підтримали цю ініціативу!');

            DB::query("INSERT INTO petition_signatures (petition_id, user_id, created_at) VALUES (?, ?, NOW())", [$petitionId, $userId]);
            
            $new_signs_count = (int)DB::fetchColumn("SELECT COUNT(*) FROM petition_signatures WHERE petition_id = ?", [$petitionId]);
            $total_members = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')", [$petition['ppo_id']]) ?: 1;
            $quorum_needed = ceil($total_members * (($petition['quorum_percent'] ?: 50) / 100));

            // Перевірка досягнення кворуму
            if ($new_signs_count >= $quorum_needed) {
                // Змінюємо статус на 1 (Очікує розгляду)
                DB::query("UPDATE petitions SET status = '1' WHERE id = ?", [$petitionId]);
                
                // Сповіщення в базу
                $head_id = DB::fetchColumn("SELECT id FROM users WHERE ppo_id = ? AND role = 'head'", [$petition['ppo_id']]);
                if ($head_id) {
                    DB::query("INSERT INTO notifications (user_id, ppo_id, title, message) VALUES (?, ?, '📢 Петиція набрала кворум', 'Одна з ініціатив зібрала необхідну кількість підписів. Перейдіть в Адмін-панель для розгляду.')", [$head_id, $petition['ppo_id']]);
                }
                
                // 🔔 ПУШ: Сповіщаємо керівництво
                if (class_exists('\Core\OneSignal')) {
                    \Core\OneSignal::notifyAdmins($petition['ppo_id'], "Увага! Ініціатива «{$petition['title']}» набрала необхідну кількість підписів.", "https://myppo.pp.ua/?tab=initiatives");
                }
            }

            DB::commit();
            return ['success' => true, 'msg' => 'Ваш голос успішно враховано!'];

        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    // ==========================================
    // 📢 ВІДПОВІДЬ НА ПЕТИЦІЮ (АДМІНКА)
    // ==========================================
    public static function answerPetition($petitionId, $status, $comment, $ppoId) {
        // Допустимі статуси: 2 (Прийнято), 3 (Відхилено), 4 (Голосування)
        $validStatuses = [2, 3, 4];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'msg' => '❌ Невірний статус.'];
        }

        try {
            $petition = DB::fetch("SELECT title, user_id FROM petitions WHERE id = ? AND ppo_id = ?", [$petitionId, $ppoId]);
            if (!$petition) {
                return ['success' => false, 'msg' => '❌ Ініціативу не знайдено.'];
            }

            DB::query(
                "UPDATE petitions SET status = ?, admin_comment = ? WHERE id = ? AND ppo_id = ?", 
                [$status, $comment, $petitionId, $ppoId]
            );
            
            if (class_exists('\Core\OneSignal')) {
                $statusText = [
                    2 => '✅ Прийнято до виконання', 
                    3 => '❌ Відхилено', 
                    4 => '🗳 Переведено у повноцінне голосування'
                ][$status] ?? 'Розглянуто';

                DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", ["Офіційна відповідь на ініціативу", "Ініціатива «{$petition['title']}» отримала офіційне рішення керівництва: {$statusText}.", $ppoId]);
                \Core\OneSignal::notifyAllMembers($ppoId, "Офіційна відповідь керівництва!", "Ініціатива «{$petition['title']}» отримала статус: {$statusText}.", "https://myppo.pp.ua/?tab=initiatives");
            }
            
            return ['success' => true, 'msg' => '✅ Рішення по петиції успішно опубліковано!'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => '❌ Помилка БД: ' . $e->getMessage()];
        }
    }

    // ==========================================
    // 🗑 АВТОМАТИЧНЕ СКАСУВАННЯ ПРОВАЛЕНИХ ІНІЦІАТИВ
    // ==========================================
    public static function checkExpired() {
        try {
            // Знаходимо петиції зі статусом 0 (збирають підписи), яким понад 7 днів
            // Переводимо їх у статус 3 (Відхилено) та додаємо системний коментар
            $stmt = DB::query("
                UPDATE petitions 
                SET status = 3, admin_comment = 'Автоматично скасовано: не набрано кворум за 7 днів' 
                WHERE status = 0 AND created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Помилка автоматичного скасування петицій: " . $e->getMessage());
            return 0;
        }
    }
}
