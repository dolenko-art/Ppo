<?php
namespace Services;

use Core\DB;
use Core\OneSignal;

class PollService {
    
    public static function createPoll($data, $ppo_id) {
        // Отримуємо кількість учасників для розрахунку кворуму
        $total_members = DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppo_id]) ?: 1;
        $t = $data['threshold_type'] ?? '50';
        
        if ($t === '2/3') $req = ceil($total_members * (2/3));
        elseif ($t === '70') $req = ceil($total_members * 0.70);
        elseif ($t === '75') $req = ceil($total_members * 0.75);
        elseif ($t === '100') $req = $total_members;
        else $req = floor($total_members / 2) + 1;

        $action_type = $data['poll_type'] ?? 'regular';
        $description = trim($data['description'] ?? '');
        
        $designated_secretary_id = !empty($data['designated_secretary_id']) ? (int)$data['designated_secretary_id'] : null;
        
        // 🔥 Беремо ID автора (адміна, який це створює) для аудиту
        $created_by = !empty($data['created_by']) ? (int)$data['created_by'] : ($_SESSION['user_id'] ?? 0);

        DB::beginTransaction();
        try {
            // 🔥 ДОДАНО `created_by` у запит
            DB::query(
                "INSERT INTO polls (title, description, end_date, is_active, is_secret, threshold_type, required_quorum, require_kep, ppo_id, action_type, designated_secretary_id, created_by) 
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)", 
                [
                    $data['title'], $description, $data['end_date'], 
                    (int)($data['is_secret'] ?? 0), $t, $req, (int)($data['require_kep'] ?? 0), 
                    $ppo_id, $action_type, $designated_secretary_id, $created_by
                ]
            );
            
            $poll_id = DB::fetchColumn("SELECT LAST_INSERT_ID()");
            
            if (!empty($data['options']) && $poll_id) {
                foreach ($data['options'] as $opt) { 
                    if (trim($opt) !== '') {
                        DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)", [$poll_id, trim($opt)]);
                    }
                }
            }

            // 🔥 ДОДАНО АВТОПУБЛІКАЦІЮ В НОВИНИ
            $news_title = "🗳 Розпочато нове голосування";
            $news_content = "**Тема:** {$data['title']}\n**Дедлайн:** " . date('d.m.Y H:i', strtotime($data['end_date'])) . "\n\nПерейдіть у розділ «Голосування», щоб зробити свій вибір!";
            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", [$news_title, $news_content, $ppo_id]);

            DB::commit();

            // 🔔 ПУШ: Інформуємо ВСІХ УЧАСНИКІВ (а не тільки адмінів)
            if (class_exists('\Core\OneSignal')) {
                OneSignal::notifyAllMembers($ppo_id, "🗳 Нове голосування!", "У вашій організації створено нове голосування: «{$data['title']}». Ваш голос важливий!");
            }

            return ['success' => true, 'msg' => '🗳️ Опитування успішно створено!'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function createNomination($data, $ppo_id) {
        $total_members = DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppo_id]) ?: 1;
        $t = $data['threshold_type'] ?? '50';
        
        if ($t === '2/3') $req = ceil($total_members * (2/3));
        elseif ($t === '70') $req = ceil($total_members * 0.70);
        elseif ($t === '75') $req = ceil($total_members * 0.75);
        elseif ($t === '100') $req = $total_members;
        else $req = floor($total_members / 2) + 1;

        $top_count = max(1, (int)($data['top_count'] ?? 3));
        $is_secret = (int)($data['is_secret'] ?? 0);
        $require_kep = (int)($data['require_kep'] ?? 0);
        $target_role = $data['target_role'] ?? 'regular';
        $description = trim($data['description'] ?? '');
        $designated_secretary_id = !empty($data['designated_secretary_id']) ? (int)$data['designated_secretary_id'] : null;

        try {
            DB::query(
                "INSERT INTO nominations (title, description, end_date, top_count, threshold_type, required_quorum, is_secret, require_kep, ppo_id, target_role, designated_secretary_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                [
                    $data['title'], $description, $data['end_date'], $top_count, $t, $req, 
                    $is_secret, $require_kep, $ppo_id, $target_role, $designated_secretary_id
                ]
            );

            // 🔥 ДОДАНО АВТОПУБЛІКАЦІЮ В НОВИНИ
            $news_title = "🎯 Розпочато збір пропозицій";
            $news_content = "**Тема:** {$data['title']}\n**Дедлайн:** " . date('d.m.Y H:i', strtotime($data['end_date'])) . "\n\nДолучіться до формування фінального списку кандидатів або запропонуйте свою ідею!";
            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", [$news_title, $news_content, $ppo_id]);

            // 🔔 ПУШ: Інформуємо ВСІХ УЧАСНИКІВ
            if (class_exists('\Core\OneSignal')) {
                OneSignal::notifyAllMembers($ppo_id, "🎯 Розпочато висування!", "Система відкрила збір пропозицій: «{$data['title']}». Запропонуйте свій варіант.");
            }

            return ['success' => true, 'msg' => '🎯 Процес висування розпочато!'];
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
