<?php
namespace Controllers;

use Core\DB;
use Core\Security;

class ProfileController {

    public static function handle() {
        // Очищаємо буфер на випадок сміття
        if (ob_get_length()) ob_clean();
        
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $action  = $_POST['action'] ?? '';
        $is_ajax = !empty($_POST['ajax']); 

        if (!$user_id) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'msg' => 'Не авторизовано.']);
                exit;
            }
            header("Location: login.php");
            exit;
        }

        try {
            // ==========================================
            // 📝 ОНОВЛЕННЯ АНКЕТИ
            // ==========================================
            if ($action === 'update_info') {
                $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
                $children_info = mb_substr(trim($_POST['children_info'] ?? ''), 0, 500, 'UTF-8');
                
                // 🛡️ ЗАХИСТ 3 (Приватність): Шифруємо дані про родину перед записом в БД
                $encrypted_children = Security::encrypt($children_info);
                
                DB::query("UPDATE users SET dob = ?, children_info = ? WHERE id = ?", [$dob, $encrypted_children, $user_id]);
                $msg = "Анкетні дані успішно збережено!";
            } 
            
            // ==========================================
            // 🔐 ЗМІНА ПАРОЛЯ
            // ==========================================
            elseif ($action === 'update_password') {
                $old_password = $_POST['old_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                
                // 🛡️ ЗАХИСТ 2 (Анти-Брутфорс): Дістаємо поточний хеш пароля з бази
                $user = DB::fetch("SELECT password FROM users WHERE id = ?", [$user_id]);
                
                // Валідуємо старий пароль
                if (!$user || !password_verify($old_password, $user['password'])) {
                    throw new \Exception("Старий пароль введено неправильно!");
                }
                
                if (mb_strlen($new_password) < 6) {
                    throw new \Exception("Новий пароль має містити мінімум 6 символів.");
                }
                
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                DB::query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $user_id]);
                $msg = "Пароль успішно оновлено!";
            } 
            
            else {
                // Логуємо спроби підбору маршрутів, користувачу нічого не показуємо
                error_log("Security Warning: Спроба виклику невідомої дії в ProfileController: " . htmlspecialchars($action));
                throw new \Exception("Невідома дія. Доступ заблоковано.");
            }

            // Відповідь про успіх
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'msg' => $msg]);
                exit;
            }

            $_SESSION['toast_msg'] = "✅ " . $msg;
            header("Location: profile.php");
            exit;

        // 🛡️ ЗАХИСТ 1: Перехоплення системних збоїв БД (Information Exposure)
        } catch (\PDOException $e) {
            error_log("ProfileController DB Error: " . $e->getMessage());
            $safe_error = "Сталася системна помилка. Спробуйте пізніше.";
            
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'msg' => $safe_error]);
                exit;
            }
            $_SESSION['toast_msg'] = "❌ " . $safe_error;
            header("Location: profile.php");
            exit;

        // Перехоплення бізнес-помилок (короткий пароль, неправильний старий пароль)
        } catch (\Exception $e) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
                exit;
            }
            $_SESSION['toast_msg'] = "❌ Помилка: " . $e->getMessage();
            header("Location: profile.php");
            exit;
        }
    }
}
