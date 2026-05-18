<?php
namespace Core;

class Guard {

    /**
     * 🛡️ МАКСИМАЛЬНИЙ ЗАХИСТ ВІД XSS ТА ІН'ЄКЦІЙ
     */
    public static function clean($text) {
        if (empty($text)) return "";
        
        $text = trim($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = strip_tags($text);
        
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 🤬 АБСОЛЮТНИЙ ЦЕНЗОР (Regex + Homoglyphs + Safe UTF-8)
     */
    public static function smartCensor($text) {
        if (empty($text)) return "";

        // Очищаємо рядок від невалідних UTF-8 символів (захист від падіння Regex-рушія)
        $clean_text = mb_scrub($text, 'UTF-8');

        $bad_patterns = [
            'хуй', 'хуя', 'хуї', 'хує', 'пизд', 'пізд', 'єбл', 'єбат', 'ебат', 'їбат', 
            'бля', 'курв', 'сук', 'сука', 'гандон', 'мудак', 'нах', 'підор', 'підар', 
            'пидор', 'пидар', 'залуп', 'шлюх', 'чмо', 'уйоб', 'долбой', 'дроч', 'педик'
        ];

        $replacements = [
            'а' => '[аa@4]', 'б' => '[б6b]', 'в' => '[вv8]', 'г' => '[гg]', 'д' => '[дd]',
            'е' => '[еeє3]', 'є' => '[еeє3]', 'ж' => '[жzh]', 'з' => '[з3z]', 'и' => '[иu]', 
            'і' => '[іi1!\|]', 'ї' => '[їi]', 'к' => '[кk]', 'л' => '[лl]', 'м' => '[мm]', 
            'н' => '[нh]', 'о' => '[оo0]', 'п' => '[пp]', 'р' => '[рp]', 'с' => '[сsc]',
            'т' => '[тt]', 'у' => '[уy]', 'ф' => '[фf]', 'х' => '[хx]', 'ц' => '[цc]', 
            'ч' => '[ч4ch]', 'ш' => '[шsh]', 'щ' => '[щshch]', 'ю' => '[юyu]', 'я' => '[яya]'
        ];

        $found_bad = false;

        foreach ($bad_patterns as $pattern) {
            $regex_parts = [];
            $chars = mb_str_split($pattern, 1, 'UTF-8');
            foreach ($chars as $char) {
                $regex_parts[] = isset($replacements[$char]) ? $replacements[$char] : preg_quote($char);
            }

            // Шукає лайку з урахуванням пробілів, крапок, ком, дефісів
            $final_regex = '/' . implode('[\s\.\-_*,\d]*', $regex_parts) . '/iu';

            if (preg_match($final_regex, $clean_text)) {
                $clean_text = preg_replace($final_regex, '***', $clean_text);
                $found_bad = true;
            }
        }

        if ($found_bad) {
            $clean_text .= "\n\n[СИСТЕМА: Частину тексту приховано через порушення етичних норм.]";
        }

        return $clean_text;
    }

    /**
     * ⚖️ ЮРИДИЧНИЙ ВИСНОВОК ДЛЯ ПРОТОКОЛУ (ВИКЛЮЧЕННЯ)
     */
    public static function getExclusionStatus($poll_id, $target_id) {
        $poll_id = (int)$poll_id;
        $target_id = (int)$target_id;

        $comment = \Core\DB::fetchColumn("SELECT target_comment FROM polls WHERE id = ?", [$poll_id]);

        if (!empty(trim((string)$comment))) {
            return "Коментар учасника: \"" . self::smartCensor(self::clean($comment)) . "\"";
        }

        // Безпечна параметризація LIKE-запиту
        $search_term = "%[EXCLUSION_EXPLANATION:{$poll_id}]%";
        $notif = \Core\DB::fetch("
            SELECT is_read, read_at FROM notifications 
            WHERE user_id = ? AND message LIKE ? 
            ORDER BY id DESC LIMIT 1
        ", [$target_id, $search_term]);

        if ($notif && (int)$notif['is_read'] === 1) {
            // Примусове переведення часу в український пояс для протоколу ДСТУ
            $dt = new \DateTime($notif['read_at']);
            $dt->setTimezone(new \DateTimeZone('Europe/Kyiv'));
            $time = $dt->format('d.m.Y H:i');
            
            return "Особа була ознайомлена з процедурою через електронну систему {$time}, проте правом на надання пояснень не скористалася.";
        }

        return "Повідомлення про початок процедури виключення надіслано в електронну систему; офіційних пояснень від особи не отримано.";
    }

    /**
     * 📖 СУПЕР-КАНОНІЧНЕ ЛОГУВАННЯ ДІЙ (AUDIT LOG)
     */
    public static function logAction($target_user_id, $action_type, $target_name = "", $is_system = false, $ppo_id = 0) {
        $admin_id = 0;

        if (!$is_system) {
            // Дія викликана реальною людиною з браузера
            $admin_id = (int)($_SESSION['user_id'] ?? 0);
            if ($ppo_id === 0) {
                $ppo_id = (int)($_SESSION['ppo_id'] ?? 0);
            }
        } else {
            // Дія викликана Системою (Крон або E2E-V) — повністю ігноруємо сесію задля безпеки
            if ($ppo_id === 0 && $target_user_id > 0) {
                $ppo_id = (int)\Core\DB::fetchColumn("SELECT ppo_id FROM users WHERE id = ?", [$target_user_id]);
            }
        }

        if ($ppo_id === 0) return false;

        // Дістаємо та розшифровуємо ім'я
        if (empty(trim((string)$target_name)) && $target_user_id > 0) {
            $encrypted_name = \Core\DB::fetchColumn("SELECT full_name FROM users WHERE id = ?", [$target_user_id]);
            
            if ($encrypted_name) {
                if (class_exists('\Core\Security')) {
                    $decrypted = \Core\Security::decrypt($encrypted_name);
                    $target_name = $decrypted ?: $encrypted_name;
                } else {
                    $target_name = $encrypted_name;
                }
            }
        }

        return \Core\DB::execute(
            "INSERT INTO audit_logs (ppo_id, admin_id, target_name, action_type, created_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$ppo_id, $admin_id, $target_name, $action_type]
        );
    }
}
