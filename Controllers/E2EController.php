<?php
namespace Controllers;

class E2EController
{
    public static function generateToken(): array
    {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $target_type = trim((string)($_POST['target_type'] ?? ''));
        $target_id = (int)($_POST['target_id'] ?? 0);
        $token_hash = trim((string)($_POST['token_hash'] ?? ''));

        if (!$target_type || !$target_id || empty($token_hash) || !$user_id) {
            return ['success' => false, 'msg' => 'Помилка валідації даних.'];
        }

        // 1. Швидка (брудна) перевірка без блокувань для 99% нормальних юзерів
        $check = \Core\DB::fetch("SELECT id FROM e2e_voters WHERE target_type = ? AND target_id = ? AND user_id = ?", [$target_type, $target_id, $user_id]);
        if ($check) { 
            return ['success' => false, 'msg' => 'Ви вже отримали бюлетень! Переголосування заборонено.']; 
        }

        $pdo = \Core\DB::connect();

        try {
            // 🔥 СТАРТ ТРАНЗАКЦІЇ
            $pdo->beginTransaction();

            // 🛡️ Замість SELECT FOR UPDATE, ми покладаємося на UNIQUE INDEX у базі даних!
            // Якщо хакер пустив 2 потоки, один з них впаде тут з помилкою Integrity Constraint Violation
            \Core\DB::query("INSERT INTO e2e_voters (target_type, target_id, user_id) VALUES (?, ?, ?)", [$target_type, $target_id, $user_id]);

            // Генеруємо фейковий КЕП
            $salt = random_bytes(16);
            $fake_kep_container = "KEP-SIM-" . hash('sha256', $user_id . $target_type . $target_id . time() . bin2hex($salt));
            $action_context = 'e2e_request_' . $target_type; 

            // Записуємо підпис
            \Core\DB::query(
                "INSERT INTO digital_signatures (user_id, action_context, target_id, signature_hash) VALUES (?, ?, ?, ?)", 
                [$user_id, $action_context, $target_id, $fake_kep_container]
            );

            // Видаємо токен
            \Core\DB::query("INSERT INTO e2e_tokens (target_type, target_id, token) VALUES (?, ?, ?)", [$target_type, $target_id, $token_hash]);
            
            // 🔥 ФІКСАЦІЯ ТРАНЗАКЦІЇ
            $pdo->commit();
            return ['success' => true];

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) { 
                $pdo->rollBack(); 
            }
            
            $errMsg = $e->getMessage();
            // Відловлюємо помилку дублювання (MySQL Error 1062 / SQLSTATE 23000)
            if (strpos($errMsg, '1062') !== false || strpos($errMsg, '23000') !== false) {
                return ['success' => false, 'msg' => 'Ви вже отримали бюлетень! (Заблоковано спробу подвійного запиту)'];
            }
            
            error_log("E2E Generate Error: " . $errMsg);
            return ['success' => false, 'msg' => 'Помилка генерації ключа. Спробуйте пізніше.'];
        }
    }

    public static function castVote(): array
    {
        // Розблоковуємо файл сесії, щоб інші AJAX-запити клієнта не зависали
        session_write_close(); 

        $target_type = trim((string)($_POST['target_type'] ?? ''));
        $target_id = (int)($_POST['target_id'] ?? 0);
        $option_id = (int)($_POST['option_id'] ?? 0);
        $idea_text = isset($_POST['idea_text']) ? trim((string)$_POST['idea_text']) : null;
        $raw_token = trim((string)($_POST['token'] ?? ''));

        if (!$target_type || !$target_id || empty($raw_token)) {
            return ['success' => false, 'msg' => 'Пошкоджені дані бюлетеня.'];
        }

        $token_hash = hash('sha256', $raw_token);
        $pdo = \Core\DB::connect();

        try {
            $pdo->beginTransaction();

            // 🛡️ Тут FOR UPDATE працює ідеально, бо запис (токен) ГАРАНТОВАНО ІСНУЄ в базі, 
            // тому MySQL заблокує конкретний рядок (Row Lock), а не створить Deadlock!
            $valid_token = \Core\DB::fetch("SELECT id FROM e2e_tokens WHERE target_type = ? AND target_id = ? AND token = ? FOR UPDATE", [$target_type, $target_id, $token_hash]);
            
            if (!$valid_token) {
                $pdo->rollBack();
                return ['success' => false, 'msg' => 'Недійсний або вже використаний бюлетень!'];
            }

            // Знищуємо токен (спалюємо бюлетень)
            \Core\DB::query("DELETE FROM e2e_tokens WHERE id = ?", [$valid_token['id']]);

            // Кидаємо голос у БУФЕР
            \Core\DB::query(
                "INSERT INTO e2e_buffer (target_type, target_id, option_id, idea_text, token) VALUES (?, ?, ?, ?, ?)",
                [$target_type, $target_id, $option_id ?: null, $idea_text, $raw_token]
            );

            $pdo->commit();

            // Рахуємо явку
            $v = \Core\DB::fetch("SELECT COUNT(id) as c FROM e2e_voters WHERE target_type = ? AND target_id = ?", [$target_type, $target_id]);
            return ['success' => true, 'total_votes' => (int)($v['c'] ?? 0)];

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("E2E Cast Error: " . $e->getMessage());
            return ['success' => false, 'msg' => 'Помилка збереження голосу.'];
        }
    }
}
