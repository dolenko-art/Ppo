<?php
namespace Core;

class OneSignal {
    
    // Дістаємо ключі ТІЛЬКИ з .env
    private static function getAppId() {
        return getenv('ONESIGNAL_APP_ID') ?: $_ENV['ONESIGNAL_APP_ID'] ?? '';
    }

    private static function getRestKey() {
        return getenv('ONESIGNAL_REST_KEY') ?: $_ENV['ONESIGNAL_REST_KEY'] ?? '';
    }

    /**
     * 🛡️ МАСКУВАННЯ ID КОРИСТУВАЧА (Захист від витоку структури БД)
     * Перетворює сирий ID (наприклад, 42) на безпечний незворотний хеш.
     */
    public static function hashUserId($user_id) {
        $secret = getenv('ENCRYPTION_KEY') ?: $_ENV['ENCRYPTION_KEY'] ?? 'ppo_fallback_secret';
        // Генеруємо 64-значний HMAC хеш. OneSignal ніколи не дізнається реальний ID.
        return hash_hmac('sha256', (string)$user_id, $secret);
    }

    // 1. Базова функція відправки (Highload & Secure)
    public static function sendPush($user_ids, $title, $message, $url = '/') {
        if (empty($user_ids)) return;

        $app_id = self::getAppId();
        $rest_key = self::getRestKey();

        if (empty($app_id) || empty($rest_key)) return;

        // 🛡️ Хешуємо всі ID перед відправкою до США (OneSignal)
        $external_ids = array_map([self::class, 'hashUserId'], array_unique($user_ids));
        
        // Розбиваємо на пачки по 2000 (ліміт OneSignal)
        $chunks = array_chunk($external_ids, 2000);

        // 🚀 ВИРІШЕННЯ HIGHLOAD: Готуємо паралельне виконання (Multi-cURL)
        $mh = curl_multi_init();
        $handles = [];

        foreach ($chunks as $chunk) {
            $fields = [
                'app_id' => $app_id,
                'include_external_user_ids' => $chunk, 
                'channel_for_external_user_ids' => 'push',
                'isAnyWeb' => true,
                'headings' => ["en" => $title, "uk" => $title],
                'contents' => ["en" => $message, "uk" => $message]
            ];

            if ($url !== '/' && strpos($url, 'http') === 0) {
                $fields['web_url'] = $url; 
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications"); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . $rest_key
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            
            // 🛡️ ЗАХИСТ ВІД ЗАВИСАННЯ (Таймаути)
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
            
            // 🛡️ АБСОЛЮТНИЙ ЗАХИСТ SSL (Запобігання MITM-атакам)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $handles[] = $ch;
            curl_multi_add_handle($mh, $ch);
        }

        // 🚀 ЗАПУСК УСІХ ЗАПИТІВ ОДНОЧАСНО (ПАРАЛЕЛЬНО)
        // Замість того, щоб чекати кожну пачку по 3 секунди, ми відправляємо їх усі миттєво
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Очищаємо ресурси та логуємо можливі помилки
        foreach ($handles as $ch) {
            $error = curl_error($ch);
            if ($error) {
                error_log("OneSignal CURL Error: " . $error);
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }

    // 2. Швидка відправка Керівникам (Голові та Менеджеру)
    public static function notifyAdmins($ppo_id, $message, $url = '/') {
        self::notifyRoles($ppo_id, ['head', 'manager'], 'Профспілка', $message, $url);
    }

    // 3. Відправка пуша конкретним посадам
    public static function notifyRoles($ppo_id, $roles, $title, $message, $url = '/') {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $params = array_merge([$ppo_id], $roles);
        
        $users = \Core\DB::fetchAll("SELECT id FROM users WHERE ppo_id = ? AND role IN ($placeholders) AND status IN ('member', 'admin')", $params);
        
        $user_ids = array_column($users, 'id');
        if (!empty($user_ids)) {
            self::sendPush($user_ids, $title, $message, $url);
        }
    }

    // 4. Відправка пуша абсолютно ВСІМ учасникам ППО
    public static function notifyAllMembers($ppo_id, $title, $message, $url = '/') {
        $users = \Core\DB::fetchAll("SELECT id FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')", [$ppo_id]);
        $user_ids = array_column($users, 'id');
        if (!empty($user_ids)) {
            self::sendPush($user_ids, $title, $message, $url);
        }
    }
}
