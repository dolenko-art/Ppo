<?php
namespace Services;

use Core\DB;
use Core\OneSignal;
use Exception;

class EventService {
    
    // ==========================================
    // 📅 СТВОРЕННЯ ПОДІЇ
    // ==========================================
    public static function create($data, $ppo_id) {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $location = trim($data['location'] ?? '');
        $seats = max(0, (int)($data['max_seats'] ?? 0));
        $event_date = $data['event_date'];

        if (empty($title) || empty($event_date)) {
            throw new Exception("Назва та дата події є обов'язковими.");
        }

        DB::beginTransaction();
        try {
            DB::query(
                "INSERT INTO events (title, description, event_date, location, max_seats, ppo_id) VALUES (?, ?, ?, ?, ?, ?)", 
                [$title, $description, $event_date, $location, $seats, $ppo_id]
            );
            
            // 🔥 ДОДАНО: Автоматична публікація в Стрічку Новин
            $news_title = "📅 Анонс: " . mb_strimwidth($title, 0, 50, '...');
            $date_nice = date('d.m.Y H:i', strtotime($event_date));
            $loc_text = $location ? "\n**Локація:** {$location}" : "";
            
            $news_content = "**Заплановано нову подію!**\n\n**Коли:** {$date_nice}{$loc_text}\n\nПоспішайте записатися у розділі «Події», кількість місць може бути обмежена!";
            
            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", [$news_title, $news_content, $ppo_id]);

            DB::commit();

            // 🔔 ПУШ: Інформуємо ВСІХ учасників профспілки про нову подію
            if (class_exists('\Core\OneSignal')) {
                OneSignal::notifyAllMembers($ppo_id, "🎉 Нова подія!", "Заплановано: «{$title}». Встигніть записатися!", "https://myppo.pp.ua/?tab=events");
            }

            return ['success' => true, 'msg' => '📅 Подію створено та опубліковано!'];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ==========================================
    // 🎟 ЗАПИС НА ПОДІЮ
    // ==========================================
    public static function enroll($userId, $ppoId, $eventId, $guestsCount, $guestsInfo) {
        if (!$eventId) return ['success' => false, 'msg' => 'Подію не вказано'];

        // 🔥 ЗАХИСТ: Не даємо передати від'ємну кількість гостей
        $guestsCount = max(0, (int)$guestsCount);
        $guestsInfo = trim((string)$guestsInfo);

        DB::beginTransaction();
        try {
            // FOR UPDATE блокує рядок події, щоб два юзери одночасно не зайняли останнє місце
            $event = DB::fetch("SELECT * FROM events WHERE id = ? AND ppo_id = ? FOR UPDATE", [$eventId, $ppoId]);
            if (!$event) throw new Exception('Подію не знайдено.');

            // 🔥 ЗАХИСТ: Перевіряємо, чи подія ще не пройшла (або не почалась)
            if (strtotime($event['event_date']) < time()) {
                throw new Exception('Запис закрито: подія вже відбулася або розпочалася.');
            }

            $already = DB::fetchColumn("SELECT id FROM event_enrollments WHERE event_id = ? AND user_id = ?", [$eventId, $userId]);
            if ($already) throw new Exception('Ви вже записані на цей захід!');

            $total_to_add = 1 + $guestsCount;
            $current_count = (int)DB::fetchColumn("SELECT SUM(1 + guests_count) FROM event_enrollments WHERE event_id = ?", [$eventId]);

            if ($event['max_seats'] > 0 && ($current_count + $total_to_add) > $event['max_seats']) {
                $left = max(0, $event['max_seats'] - $current_count);
                if ($left === 0) {
                    throw new Exception("На жаль, вільних місць більше немає.");
                } else {
                    throw new Exception("Залишилося місць: {$left}. Зменшіть кількість гостей.");
                }
            }

            DB::query("INSERT INTO event_enrollments (event_id, user_id, guests_count, guests_info) VALUES (?, ?, ?, ?)", [$eventId, $userId, $guestsCount, $guestsInfo]);
            
            DB::commit();

            // 🔔 ПУШ: Персональне підтвердження запису самому користувачу
            if (class_exists('\Core\OneSignal')) {
                OneSignal::sendPush([$userId], "✅ Запис підтверджено", "Вас успішно записано на подію «{$event['title']}».", "https://myppo.pp.ua/?tab=events");
            }

            return ['success' => true, 'msg' => 'Вас успішно записано!'];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}
