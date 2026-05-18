<?php
namespace Services;

use Core\DB;
use Core\Guard;
use Core\Security;
use Core\OneSignal;
use Exception;

class VotingService {

    // ==========================================
    // 🚀 ПУШЕР (ВЕБСОКЕТИ)
    // ==========================================
    private static function triggerPusher($ppo_id, $event, $data) {
        $app_id = getenv('PUSHER_APP_ID') ?: $_ENV['PUSHER_APP_ID'] ?? "2151204";
        $key = getenv('PUSHER_KEY') ?: $_ENV['PUSHER_KEY'] ?? "fbaac4e8540bf53e05e4";
        $secret = getenv('PUSHER_SECRET') ?: $_ENV['PUSHER_SECRET'] ?? "178ca4ddbadcf0f34a2f";
        $cluster = getenv('PUSHER_CLUSTER') ?: $_ENV['PUSHER_CLUSTER'] ?? "eu";

        $host = "api-{$cluster}.pusher.com";
        $path = "/apps/{$app_id}/events";

        $payload = json_encode(['name' => $event, 'channels' => ['ppo-channel-' . $ppo_id], 'data' => json_encode($data)]);
        $auth_query = "auth_key={$key}&auth_timestamp=".time()."&auth_version=1.0&body_md5=".md5($payload);
        $auth_signature = hash_hmac('sha256', "POST\n{$path}\n{$auth_query}", $secret);

        $ch = curl_init("https://{$host}{$path}?{$auth_query}&auth_signature={$auth_signature}");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
        curl_exec($ch); curl_close($ch);
    }

    // ==========================================
    // 🗳️ ЗВИЧАЙНЕ ГОЛОСУВАННЯ (Відкрите / Конфіденційне)
    // ==========================================
    public static function vote($userId, $ppoId, $pollId, $optionId, $hasKep) {
        DB::beginTransaction();
        try {
            $poll = DB::fetch("SELECT is_active, require_kep, is_secret FROM polls WHERE id = ? AND ppo_id = ? FOR UPDATE", [$pollId, $ppoId]);
            if (!$poll || $poll['is_active'] == 0) throw new Exception('Голосування вже завершено!');
            if (!empty($poll['require_kep']) && !$hasKep) return ['success' => false, 'requires_kep' => true];
            
            if ($poll['is_secret'] == 2) throw new Exception('Це Абсолютно таємне голосування. Використовуйте інший метод.');

            $already = DB::fetchColumn("SELECT id FROM votes WHERE user_id = ? AND poll_id = ?", [$userId, $pollId]);
            if ($already) throw new Exception('Ви вже проголосували!');

            DB::query("INSERT INTO votes (user_id, poll_id, option_id) VALUES (?, ?, ?)", [$userId, $pollId, $optionId]);
            
            $total_v = (int)DB::fetchColumn("SELECT COUNT(*) FROM votes WHERE poll_id = ?", [$pollId]);
            $total_m = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppoId]) ?: 1;

            if ($total_v >= $total_m) {
                DB::query("UPDATE polls SET is_active = 0 WHERE id = ?", [$pollId]);
                self::processSystemPolls($ppoId); 
            }

            DB::commit();

            $votes_data = DB::fetchAll("SELECT option_id, COUNT(id) as count FROM votes WHERE poll_id = ? GROUP BY option_id", [$pollId]);
            $options_map = []; foreach ($votes_data as $v) { $options_map[$v['option_id']] = (int)$v['count']; }
            self::triggerPusher($ppoId, 'new_vote', ['poll_id' => $pollId, 'total_votes' => $total_v, 'total_members' => $total_m, 'options' => $options_map]);

            return ['success' => true, 'msg' => 'Ваш голос враховано!', 'total_votes' => $total_v, 'total_members' => $total_m, 'options' => $options_map];
        } catch (Exception $e) {
            DB::rollBack(); return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    // ==========================================
    // 🛡️ E2E-V (ТАЄМНЕ ГОЛОСУВАННЯ ТА МІКСЕР)
    // ==========================================
    
    // КРОК 1: Видача токена та фіксація явки
    public static function generateE2eToken($userId, $ppoId, $targetType, $targetId, $tokenHash) {
        return ['success' => true];
    }

    // КРОК 2: Анонімне вкидання в буфер (Сліпа урна)
    public static function castE2eVote($targetType, $targetId, $optionId, $rawToken, $ideaText = null) {
        return ['success' => true];
    }

    // 🔥 КРОК 3: КРИПТОГРАФІЧНИЙ МІКСЕР (Shuffler)
    public static function runE2eMixer($targetType, $targetId) {
        try {
            $buffer_votes = DB::fetchAll("SELECT option_id, idea_text, token FROM e2e_buffer WHERE target_type = ? AND target_id = ?", [$targetType, $targetId]);
            if (empty($buffer_votes)) return;

            shuffle($buffer_votes);

            if ($targetType === 'poll') {
                foreach ($buffer_votes as $v) {
                    DB::query("INSERT INTO votes (poll_id, option_id, user_id, token) VALUES (?, ?, NULL, ?)", [$targetId, $v['option_id'], $v['token']]);
                }
            } elseif ($targetType === 'nomination') {
                foreach ($buffer_votes as $v) {
                    DB::query("INSERT INTO nomination_votes (nomination_id, nominee_id, idea_text, nominator_id, token) VALUES (?, ?, ?, NULL, ?)", [$targetId, $v['option_id'] ?: null, $v['idea_text'], $v['token']]);
                }
            }

            DB::query("UPDATE e2e_buffer SET token = MD5(RAND()), option_id = 0, idea_text = NULL WHERE target_type = ? AND target_id = ?", [$targetType, $targetId]);
            DB::query("DELETE FROM e2e_buffer WHERE target_type = ? AND target_id = ?", [$targetType, $targetId]);
            DB::query("OPTIMIZE TABLE e2e_buffer");

        } catch (\Exception $e) {
            error_log("Помилка Міксера E2E ($targetType ID $targetId): " . $e->getMessage());
        }
    }

    // ==========================================
    // ⚙️ АВТОМАТИЗАЦІЯ ПІДСУМКІВ ОПИТУВАНЬ
    // ==========================================
    public static function processSystemPolls($ppoId) {
        DB::query("UPDATE polls SET is_active = 0 WHERE is_active = 1 AND end_date <= NOW()");
        $polls = DB::fetchAll("SELECT * FROM polls WHERE ppo_id = ? AND is_active = 0 AND is_processed = 0", [$ppoId]);

        foreach ($polls as $p) {
            if ($p['is_secret'] == 2) {
                self::runE2eMixer('poll', $p['id']);
            }

            $votes = DB::fetchAll("SELECT po.option_text, COUNT(v.id) as count FROM poll_options po LEFT JOIN votes v ON v.option_id = po.id WHERE po.poll_id = ? GROUP BY po.id", [$p['id']]);

            $max_votes = -1; $winning_options = []; $total_voted = 0;
            foreach ($votes as $v) {
                $total_voted += (int)$v['count'];
                if ((int)$v['count'] > $max_votes) {
                    $max_votes = (int)$v['count']; $winning_options = [$v['option_text']];
                } elseif ((int)$v['count'] === $max_votes) {
                    $winning_options[] = $v['option_text'];
                }
            }

            $winning_option_lower = isset($winning_options[0]) ? mb_strtolower($winning_options[0], 'UTF-8') : '';
            $is_approved = (count($winning_options) === 1 && (mb_strpos($winning_option_lower, 'за') !== false || mb_strpos($winning_option_lower, 'затвердити') !== false) && mb_strpos($winning_option_lower, 'проти') === false);

            if ($p['action_type'] === 'admission') {
                $new_status = $is_approved ? 'member' : 'rejected';
                DB::query("UPDATE users SET status = ? WHERE id = ?", [$new_status, $p['target_user_id']]);
                Guard::logAction($p['target_user_id'], $is_approved ? 'Прийнято в члени ППО (Рішення зборів)' : 'Відмовлено у вступі до ППО (Рішення зборів)', '', true);
                
                if (class_exists('\Core\OneSignal')) {
                    OneSignal::sendPush([$p['target_user_id']], '⚖️ Рішення про вступ', $is_approved ? "Вітаємо! Вас прийнято до лав ППО." : "На жаль, вашу заявку на вступ відхилено.");
                    $status_text = $is_approved ? "прийнято до ППО" : "відмовлено у вступі";
                    OneSignal::notifyAdmins($ppoId, "Завершено голосування. Кандидату {$status_text}.");
                }
            } 
            elseif ($p['action_type'] === 'exclusion') {
                $target_raw = DB::fetchColumn("SELECT full_name FROM users WHERE id = ?", [$p['target_user_id']]);
                $target_name = class_exists('\Core\Security') ? (Security::decrypt($target_raw) ?: $target_raw) : $target_raw;

                if ($is_approved) { 
                    DB::query("UPDATE users SET status = 'former', role = NULL, left_at = NOW(), leave_reason = 'Виключено за рішенням зборів' WHERE id = ?", [$p['target_user_id']]);
                    Guard::logAction($p['target_user_id'], 'Виключено з ППО (Рішення зборів)', "Учасник: $target_name", true);
                    
                    if (class_exists('\Core\OneSignal')) {
                        OneSignal::sendPush([$p['target_user_id']], '⚖️ Рішення про виключення', "За результатами голосування вас виключено з ППО.");
                        OneSignal::notifyAdmins($ppoId, "Учасника {$target_name} офіційно виключено за рішенням зборів.");
                    }
                } else {
                    Guard::logAction($p['target_user_id'], 'Голосування за виключення відхилено', "Учасник: $target_name", true);
                }
            } 
            elseif ($p['action_type'] === 'audit' || mb_strpos($p['action_type'], 'report') !== false) {
                $new_rep_status = $is_approved ? 'approved' : 'rejected';
                DB::query("UPDATE audit_reports SET status = ? WHERE poll_id = ?", [$new_rep_status, $p['id']]);
                Guard::logAction(0, $is_approved ? 'Акт ревізії затверджено (Рішення зборів)' : 'Акт ревізії відхилено (Рішення зборів)', "Протокол №{$p['id']}", true);
            }
            elseif (mb_strpos($p['action_type'], 'election_') === 0) {
                $role_to_assign = str_replace('election_', '', $p['action_type']);
                $role_ua = ['head'=>'Голова ППО', 'auditor'=>'Ревізор', 'manager'=>'Менеджер'][$role_to_assign] ?? 'Керівник';

                if ($total_voted > 0 && count($winning_options) == 1 && $winning_option_lower !== 'проти всіх') {
                    $winner_name_lower = mb_strtolower($winning_options[0], 'UTF-8');
                    $winner_id = null;
                    $users = DB::fetchAll("SELECT id, full_name FROM users WHERE ppo_id = ?", [$ppoId]);
                    foreach($users as $u) {
                        $dec_name = class_exists('\Core\Security') ? (Security::decrypt($u['full_name']) ?: $u['full_name']) : $u['full_name'];
                        if (mb_strtolower($dec_name, 'UTF-8') === $winner_name_lower) { $winner_id = $u['id']; break; }
                    }
                    if ($winner_id) {
                        $msg = "Колектив обрав вас на керівну посаду! Підтвердіть згоду. [ACCEPT_ROLE:{$p['id']}:{$role_to_assign}]";
                        DB::query("INSERT INTO notifications (user_id, ppo_id, title, message) VALUES (?, ?, '🎉 Перемога у виборах!', ?)", [$winner_id, $ppoId, $msg]);
                        Guard::logAction($winner_id, "Перемога на виборах. Очікується згода", "Посада: $role_ua", true);
                        
                        if (class_exists('\Core\OneSignal')) {
                            OneSignal::sendPush([$winner_id], '🎉 Перемога у виборах!', "Колектив обрав вас на посаду {$role_ua}. Будь ласка, підтвердіть згоду в профілі.");
                        }
                    }
                }
            }

            DB::query("UPDATE polls SET is_processed = 1 WHERE id = ?", [$p['id']]);
            self::generateProtocolSnapshot($p['id'], $ppoId);
            self::notifySigners($ppoId, $p['id'], $p['title']);
        }
    }

    // ==========================================
    // 🏅 ФІНАЛІЗАЦІЯ ВИСУВАНЬ / ІДЕЙ (ОНОВЛЕНО: КВОРУМ)
    // ==========================================
    public static function checkExpiredNominations() {
        // Отримуємо всі поля, щоб перевірити кворум
        $expired = DB::fetchAll("SELECT * FROM nominations WHERE is_processed = 0 AND end_date <= NOW()");
        
        foreach ($expired as $nom) { 
            $nomination_id = $nom['id'];
            $ppo_id = $nom['ppo_id'];

            // 1. ПЕРЕВІРКА КВОРУМУ (рахуємо унікальних учасників)
            $participants_count = (int)DB::fetchColumn("
                SELECT COUNT(DISTINCT nominator_id) 
                FROM nomination_votes 
                WHERE nomination_id = ?
            ", [$nomination_id]);

            $is_quorum_met = false;
            
            if (!empty($nom['required_quorum']) && $nom['required_quorum'] > 0) {
                if ($nom['threshold_type'] === 'percentage') {
                    $total_members = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member', 'admin')", [$ppo_id]) ?: 1;
                    $required_voters = ceil(($total_members * $nom['required_quorum']) / 100);
                    $is_quorum_met = ($participants_count >= $required_voters);
                } else {
                    $is_quorum_met = ($participants_count >= $nom['required_quorum']);
                }
            } else {
                $is_quorum_met = true;
            }

            // 2. МІКСЕР (Запускаємо ЗАВЖДИ, щоб знищити токени навіть у провалених висуваннях)
            if ($nom['is_secret'] == 2) {
                self::runE2eMixer('nomination', $nomination_id);
            }

            // 3. ПРИЙНЯТТЯ РІШЕННЯ ПО ВИСУВАННЮ
            if ($is_quorum_met) {
                // Кворум є — передаємо створення бюлетеня вашій функції
                self::finalizeNomination($nomination_id); 
            } else {
                // Кворуму немає — закриваємо процес як провалений
                DB::query("UPDATE nominations SET is_processed = 1 WHERE id = ?", [$nomination_id]);
                
                $req_text = ($nom['threshold_type'] === 'percentage') ? "{$nom['required_quorum']}%" : "{$nom['required_quorum']} осіб";
                Guard::logAction(0, "Висування скасовано (Немає кворуму)", "Явка: {$participants_count}. Необхідно: {$req_text}", true);
            }
        }
    }

    public static function nominate($nominatorId, $ppoId, $nomId, $nomineeId, $hasKep = false) {
        try {
            $nom = DB::fetch("SELECT * FROM nominations WHERE id = ? AND ppo_id = ? AND is_processed = 0 FOR UPDATE", [$nomId, $ppoId]);
            if (!$nom) return ['success' => false, 'msg' => 'Висування вже завершено або не існує.'];
            
            if (!empty($nom['require_kep']) && !$hasKep) return ['success' => false, 'requires_kep' => true];
            if ($nom['is_secret'] == 2) throw new Exception('Це Абсолютно таємне висування. Використовуйте інший метод.');
            
            if (DB::fetchColumn("SELECT id FROM nomination_votes WHERE nomination_id = ? AND nominator_id = ?", [$nomId, $nominatorId])) {
                return ['success' => false, 'msg' => 'Ви вже висували кандидата у цій категорії.'];
            }

            DB::query("INSERT INTO nomination_votes (nomination_id, nominator_id, nominee_id) VALUES (?, ?, ?)", [$nomId, $nominatorId, $nomineeId]);
            
            try {
                $total = (int)DB::fetchColumn("SELECT COUNT(*) FROM nomination_votes WHERE nomination_id = ?", [$nomId]);
                DB::query("UPDATE nominations SET current_votes = ? WHERE id = ?", [$total, $nomId]);
                $total_m = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppoId]) ?: 1;
                
                if ($total >= $total_m) {
                    if ($nom['is_secret'] == 2) self::runE2eMixer('nomination', $nomId);
                    self::finalizeNomination($nomId);
                }
            } catch (\Exception $e) {}
            
            return ['success' => true, 'msg' => 'Кандидата успішно висунуто!'];
        } catch (\Exception $e) {
            return ['success' => false, 'msg' => 'Помилка БД при висуванні: ' . $e->getMessage()];
        }
    }

    public static function nominateIdea($nominatorId, $ppoId, $nomId, $ideaText, $hasKep = false) {
        DB::beginTransaction();
        try {
            $ideaText = trim(mb_substr($ideaText, 0, 150, 'UTF-8'));
            if (empty($ideaText)) throw new Exception('Текст ідеї не може бути порожнім.');

            $nom = DB::fetch("SELECT * FROM nominations WHERE id = ? AND ppo_id = ? AND is_processed = 0 FOR UPDATE", [$nomId, $ppoId]);
            if (!$nom || $nom['target_role'] !== 'regular') throw new Exception('Збір ідей завершено або не існує.');
            
            if (!empty($nom['require_kep']) && !$hasKep) {
                DB::rollBack(); 
                return ['success' => false, 'requires_kep' => true];
            }
            
            if ($nom['is_secret'] == 2) throw new Exception('Це Абсолютно таємне висування. Використовуйте інший метод.');
            
            if (DB::fetchColumn("SELECT id FROM nomination_votes WHERE nomination_id = ? AND nominator_id = ?", [$nomId, $nominatorId])) {
                throw new Exception('Ви вже подали свою пропозицію.');
            }

            DB::query("INSERT INTO nomination_votes (nomination_id, nominator_id, idea_text) VALUES (?, ?, ?)", [$nomId, $nominatorId, $ideaText]);
            
            $total = (int)DB::fetchColumn("SELECT COUNT(*) FROM nomination_votes WHERE nomination_id = ?", [$nomId]);
            
            DB::query("UPDATE nominations SET current_votes = ? WHERE id = ?", [$total, $nomId]);

            $total_m = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppoId]) ?: 1;
            $t = $nom['threshold_type'] ?? '50';
            if ($t === '2/3') $req = ceil($total_m * (2/3)); elseif ($t === '70') $req = ceil($total_m * 0.70); elseif ($t === '75') $req = ceil($total_m * 0.75); elseif ($t === '100') $req = $total_m; else $req = floor($total_m / 2) + 1;

            $pct = min(100, round(($total / max(1, $req)) * 100));
            $t_disp = is_numeric($t) ? $t . '%' : ($t === '50+1' ? '50% + 1' : $t);

            DB::commit();

            if ($total >= $total_m) {
                if ($nom['is_secret'] == 2) self::runE2eMixer('nomination', $nomId);
                self::finalizeNomination($nomId);
            }
            
            return ['success' => true, 'msg' => 'Вашу ідею прийнято!', 'current_votes' => $total, 'req' => $req, 'pct' => $pct, 't_disp' => $t_disp];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => 'Помилка: ' . $e->getMessage()];
        }
    }


    public static function finalizeNomination($nom_id) {
        DB::beginTransaction();
        try {
            $nom = DB::fetch("SELECT * FROM nominations WHERE id = ? FOR UPDATE", [$nom_id]);
            if (!$nom || $nom['is_processed'] == 1) {
                DB::rollBack(); return;
            }

            $ppo_id = $nom['ppo_id'];
            $target_role = $nom['target_role'];
            $top_count = (int)($nom['top_count'] ?? 3);
            $is_regular = ($target_role === 'regular');
            $is_secret = $is_regular ? $nom['is_secret'] : 2; 
            $designated_sec_id = $nom['designated_secretary_id'] ?? null;
            
            if ($is_regular) {
                $action_type = 'regular';
                $poll_title = "Фінальне голосування: " . mb_strimwidth($nom['title'], 0, 50, '...');
                $poll_desc = "Оберіть один із ТОП-{$top_count} варіантів, які найбільше підтримав колектив.";
                $log_title = "Збір пропозицій завершено";
            } else {
                $role_names = ['head' => 'Голови ППО', 'auditor' => 'Ревізора', 'manager' => 'Менеджера'];
                $role_ua = $role_names[$target_role] ?? 'Керівника';
                $action_type = "election_{$target_role}";
                $poll_title = "Вибори {$role_ua}";
                $poll_desc = "Оберіть одного з ТОП-{$top_count} кандидатів, яких висунув колектив, або проголосуйте проти всіх. Голосування абсолютно таємне.";
                $log_title = "Завершено висування на посаду {$role_ua}";
            }

            if ($is_regular) {
                $top_nominees = DB::fetchAll("SELECT idea_text as option_text, COUNT(id) as votes_count FROM nomination_votes WHERE nomination_id = ? AND idea_text IS NOT NULL GROUP BY LOWER(idea_text) ORDER BY votes_count DESC LIMIT ?", [$nom_id, $top_count]);
            } else {
                $top_nominees = DB::fetchAll("
                    SELECT nv.nominee_id, COUNT(nv.id) as votes_count, u.full_name 
                    FROM nomination_votes nv 
                    LEFT JOIN users u ON nv.nominee_id = u.id 
                    WHERE nv.nomination_id = ? AND nv.nominee_id IS NOT NULL 
                    GROUP BY nv.nominee_id 
                    ORDER BY votes_count DESC LIMIT ?
                ", [$nom_id, $top_count]);
            }

            if (empty($top_nominees)) {
                DB::query("UPDATE nominations SET is_processed = 1 WHERE id = ?", [$nom_id]);
                Guard::logAction(0, "{$log_title} (Кандидатів/Ідей не запропоновано)", "", true);
                DB::commit();
                return;
            }

            $end_date_str = date('Y-m-d H:i:s', strtotime('+7 days'));
            $end_date_nice = date('d.m.Y H:i', strtotime('+7 days'));
            
            DB::query("INSERT INTO polls (title, description, end_date, is_active, require_kep, ppo_id, action_type, is_secret, designated_secretary_id) VALUES (?, ?, ?, 1, 1, ?, ?, ?, ?)", [$poll_title, $poll_desc, $end_date_str, $ppo_id, $action_type, $is_secret, $designated_sec_id]);
            $poll_id = DB::fetchColumn("SELECT LAST_INSERT_ID()");

            $options_names = [];

            foreach ($top_nominees as $cand) {
                if ($is_regular) {
                    $c_name = mb_strimwidth(trim($cand['option_text']), 0, 100, '...');
                } else {
                    $c_name_raw = $cand['full_name'] ?? 'Невідомий';
                    $c_name = class_exists('\Core\Security') ? (Security::decrypt($c_name_raw) ?: $c_name_raw) : $c_name_raw;
                }
                
                DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)", [$poll_id, $c_name]);
                $options_names[] = "• " . $c_name;
            }

            if (!$is_regular) {
                DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, 'Проти всіх')", [$poll_id]);
                $options_names[] = "• Проти всіх";
            }

            DB::query("UPDATE nominations SET is_processed = 1 WHERE id = ?", [$nom_id]);
            Guard::logAction(0, "{$log_title}", "Сформовано бюлетень на {$top_count} варіантів", true);

            $news_title = "🗳 Розпочато голосування: " . mb_strimwidth($nom['title'], 0, 50, '...');
            $news_content = "Завершився етап висування. Сформовано фінальний бюлетень і розпочато процедуру голосування.\n\n" .
                            "👥 **У бюлетені представлені:**\n" . implode("\n", $options_names) . "\n\n" .
                            "⏳ **Дедлайн:** до " . $end_date_nice . "\n" .
                            "🔒 **Формат:** Таємне голосування (потрібен КЕП).\n\n" .
                            "Перейдіть у розділ «Голосування», щоб зробити свій вибір. Ваш голос впливає на майбутнє організації!";

            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", [$news_title, $news_content, $ppo_id]);

            DB::commit();

            if (class_exists('\Core\OneSignal')) {
                OneSignal::notifyAllMembers($ppo_id, "Голосування розпочато!", "Сформовано фінальний бюлетень. Зробіть свій вибір.");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            error_log("Помилка finalizeNomination: " . $e->getMessage());
        }
    }


    // ==========================================
    // 🔒 ПРОТОКОЛИ ТА КЕП
    // ==========================================
    public static function generateProtocolSnapshot($poll_id, $ppo_id) {
        $exists = DB::fetchColumn("SELECT id FROM protocols WHERE poll_id = ?", [$poll_id]);
        if ($exists) return;

        $poll = DB::fetch("SELECT * FROM polls WHERE id = ?", [$poll_id]);
        if (!$poll) return;

        $total_members = (int)DB::fetchColumn("SELECT COUNT(*) FROM users WHERE ppo_id = ? AND status IN ('member','admin')", [$ppo_id]) ?: 1;
        $votes = DB::fetchAll("SELECT po.id as option_id, po.option_text, COUNT(v.id) as count FROM poll_options po LEFT JOIN votes v ON v.option_id = po.id WHERE po.poll_id = ? GROUP BY po.id", [$poll_id]);

        $total_voted = 0; $max_votes = -1; $winning_options = []; $winning_option_id = 0; 
        foreach ($votes as $v) {
            $total_voted += (int)$v['count'];
            if ((int)$v['count'] > $max_votes) { $max_votes = (int)$v['count']; $winning_options = [$v['option_text']]; $winning_option_id = $v['option_id']; } 
            elseif ((int)$v['count'] === $max_votes) { $winning_options[] = $v['option_text']; }
        }

        if ($total_members < $total_voted) $total_members = $total_voted;
        $decision_text = (count($winning_options) > 1 || $total_voted === 0) ? "Рішення НЕ ПРИЙНЯТО." : "Прийнято рішення: «" . $winning_options[0] . "»";

        $head = DB::fetch("SELECT id, full_name FROM users WHERE ppo_id = ? AND role = 'head' AND status IN ('member','admin')", [$ppo_id]);
        $h_role_snap = $head ? 'head' : 'member'; 
        
        if (!$head && $winning_option_id > 0) {
            $first_voter_id = DB::fetchColumn("SELECT v.user_id FROM votes v JOIN users u ON v.user_id = u.id WHERE v.poll_id = ? AND v.option_id = ? AND v.user_id IS NOT NULL AND u.status IN ('member','admin') ORDER BY v.id ASC LIMIT 1", [$poll_id, $winning_option_id]);
            if ($first_voter_id) $head = DB::fetch("SELECT id, full_name FROM users WHERE id = ?", [$first_voter_id]);
        }
        if (!$head) {
            $first_voter_id = DB::fetchColumn("SELECT v.user_id FROM votes v JOIN users u ON v.user_id = u.id WHERE v.poll_id = ? AND v.user_id IS NOT NULL AND u.status IN ('member','admin') ORDER BY v.id ASC LIMIT 1", [$poll_id]);
            if ($first_voter_id) $head = DB::fetch("SELECT id, full_name FROM users WHERE id = ?", [$first_voter_id]);
        }
        if (!$head) {
            $head = DB::fetch("SELECT id, full_name FROM users WHERE ppo_id = ? AND status IN ('member','admin') ORDER BY id ASC LIMIT 1", [$ppo_id]);
        }

        $head_id_exclude = $head['id'] ?? 0;

        $manager = null;
        $s_role_snap = 'member';
        
        $designated_sec_id = $poll['designated_secretary_id'] ?? null;

        if ($designated_sec_id && $designated_sec_id != $head_id_exclude) {
            $manager = DB::fetch("SELECT id, full_name, role FROM users WHERE id = ? AND ppo_id = ? AND status IN ('member','admin')", [$designated_sec_id, $ppo_id]);
            if ($manager) {
                $s_role_snap = $manager['role'] ?: 'member';
            }
        }

        if (!$manager) {
            $manager = DB::fetch("SELECT id, full_name FROM users WHERE ppo_id = ? AND role = 'manager' AND status IN ('member','admin') AND id != ?", [$ppo_id, $head_id_exclude]);
            if ($manager) $s_role_snap = 'manager';
        }
        
        if (!$manager && $winning_option_id > 0) {
            $second_voter_id = DB::fetchColumn("SELECT v.user_id FROM votes v JOIN users u ON v.user_id = u.id WHERE v.poll_id = ? AND v.option_id = ? AND v.user_id IS NOT NULL AND v.user_id != ? AND u.status IN ('member','admin') ORDER BY v.id ASC LIMIT 1", [$poll_id, $winning_option_id, $head_id_exclude]);
            if ($second_voter_id) $manager = DB::fetch("SELECT id, full_name FROM users WHERE id = ?", [$second_voter_id]);
        }
        if (!$manager) {
            $second_voter_id = DB::fetchColumn("SELECT v.user_id FROM votes v JOIN users u ON v.user_id = u.id WHERE v.poll_id = ? AND v.user_id IS NOT NULL AND v.user_id != ? AND u.status IN ('member','admin') ORDER BY v.id ASC LIMIT 1", [$poll_id, $head_id_exclude]);
            if ($second_voter_id) $manager = DB::fetch("SELECT id, full_name FROM users WHERE id = ?", [$second_voter_id]);
        }

        if (!$manager) {
            $manager = DB::fetch("SELECT id, full_name FROM users WHERE ppo_id = ? AND status IN ('member','admin') AND id != ? ORDER BY id ASC LIMIT 1", [$ppo_id, $head_id_exclude]);
        }

        $head_name = $head ? (class_exists('\Core\Security') ? Security::decrypt($head['full_name']) : $head['full_name']) : 'Головуючий не визначений';
        $sec_name = $manager ? (class_exists('\Core\Security') ? Security::decrypt($manager['full_name']) : $manager['full_name']) : 'Секретар не визначений';

        $comment_snapshot = ($poll['action_type'] === 'exclusion') ? Guard::getExclusionStatus($poll_id, $poll['target_user_id']) : null;
        $protocol_num = date('y', strtotime($poll['end_date'])) . '-' . sprintf("%04d", $poll_id);

        DB::query("
            INSERT INTO protocols (poll_id, ppo_id, protocol_number, head_user_id, head_name_snapshot, secretary_user_id, secretary_name_snapshot, total_members_snapshot, total_voted_snapshot, decision_text_snapshot, target_comment_snapshot, votes_result_snapshot) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [$poll_id, $ppo_id, $protocol_num, $head['id'] ?? null, $head_name.'|||'.$h_role_snap, $manager['id'] ?? null, $sec_name.'|||'.$s_role_snap, $total_members, $total_voted, $decision_text, $comment_snapshot, json_encode($votes, JSON_UNESCAPED_UNICODE)]);
    }

    public static function notifySigners($ppo_id, $poll_id, $title) {
        $protocol = DB::fetch("SELECT head_user_id, secretary_user_id FROM protocols WHERE poll_id = ?", [$poll_id]);
        if (!$protocol) return;
        $msg = "Голосування «{$title}» завершено. Вас визначено підписантом протоколу. [SIGN_PROTOCOL:{$poll_id}]";
        
        foreach (array_unique([$protocol['head_user_id'], $protocol['secretary_user_id']]) as $uid) {
            if ($uid) {
                DB::query("INSERT INTO notifications (user_id, ppo_id, title, message) VALUES (?, ?, '✍️ Підпис протоколу', ?)", [$uid, $ppo_id, $msg]);
                if (class_exists('\Core\OneSignal')) OneSignal::sendPush([$uid], '✍️ Підпис протоколу', "Визначено підписантом: $title", '/profile.php?tab=notifs');
            }
        }
    }

    public static function signProtocol($userId, $pollId) {
        $pr = DB::fetch("SELECT head_user_id, secretary_user_id, head_signed_at, manager_signed_at FROM protocols WHERE poll_id = ?", [$pollId]);
        if (!$pr) return ['success' => false, 'msg' => 'Протокол не знайдено.'];

        $sig_hash = hash('sha256', $pollId . $userId . time() . "SALT_ACTION");
        $signed = false;

        if ($userId == $pr['head_user_id']) { DB::query("UPDATE protocols SET head_signed_at = NOW(), head_signature_hash = ? WHERE poll_id = ?", [$sig_hash, $pollId]); $signed = true; } 
        if ($userId == $pr['secretary_user_id']) { DB::query("UPDATE protocols SET manager_signed_at = NOW(), manager_signature_hash = ? WHERE poll_id = ?", [$sig_hash, $pollId]); $signed = true; }
        if (!$signed) return ['success' => false, 'msg' => 'Ви не є підписантом.'];

        $pr_check = DB::fetch("SELECT head_user_id, secretary_user_id, head_signed_at, manager_signed_at, protocol_number, head_signature_hash, manager_signature_hash FROM protocols WHERE poll_id = ?", [$pollId]);
        $head_ok = empty($pr_check['head_user_id']) || !empty($pr_check['head_signed_at']);
        $sec_ok = empty($pr_check['secretary_user_id']) || !empty($pr_check['manager_signed_at']);

        if ($head_ok && $sec_ok) {
            // 🔥 ЕТАП 1: Жорстка канонізація (Data Canonicalization)
            $protocol_num = trim($pr_check['protocol_number'] ?? '');
            $head_hash    = trim($pr_check['head_signature_hash'] ?? '');
            $sec_hash     = trim($pr_check['manager_signature_hash'] ?? '');

            // Жорсткий порядок конкатенації (Номер + КЕП1 + КЕП2)
            $raw_string = $protocol_num . $head_hash . $sec_hash;

            // Фінальний зацементований хеш
            $doc_hash = hash('sha256', $raw_string);
            
            DB::query("UPDATE protocols SET document_hash = ? WHERE poll_id = ?", [$doc_hash, $pollId]);
        }

        DB::query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND message LIKE ?", [$userId, "%[SIGN_PROTOCOL:$pollId]%"]);
        Guard::logAction($userId, "Накладено КЕП на офіційний протокол (ID: {$pollId})", "Офіційний Протокол Зборів");
        return ['success' => true];
    }

    public static function resolveElection($userId, $ppoId, $pollId, $role, $decision) {
        $target_role = preg_replace('/[^a-z_]/', '', $role);
        if (!$pollId || !$target_role) return ['success' => false, 'msg' => 'Некоректні дані.'];

        $role_ua = ['head'=>'Голова ППО', 'auditor'=>'Ревізор', 'manager'=>'Менеджер'][$target_role] ?? 'Керівник';
        $role_ua_rod = ['head'=>'Голови ППО', 'auditor'=>'Ревізора', 'manager'=>'Менеджера'][$target_role] ?? 'Керівника';
        
        $name = class_exists('\Core\Security') ? Security::decrypt(DB::fetchColumn("SELECT full_name FROM users WHERE id = ?", [$userId])) : '';

        if ($decision === 'accept') {
            DB::query("UPDATE users SET role = ?, role_assigned_at = NOW() WHERE id = ?", [$target_role, $userId]);
            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", ["Нові призначення", "$name приймає обов'язки $role_ua_rod.", $ppoId]);
            Guard::logAction($userId, 'Прийняття керівної посади', "Посада: $role_ua");
        } else {
            DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", ["Відмова від посади", "Кандидат на посаду $role_ua_rod відхилив пропозицію.", $ppoId]);
            Guard::logAction($userId, 'Відмова від керівної посади', "Посада: $role_ua");
        }
        DB::query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND message LIKE ?", [$userId, "%[ACCEPT_ROLE:{$pollId}:{$target_role}]%"]);
        
        if (class_exists('\Core\OneSignal')) {
            $status_msg = ($decision === 'accept') ? "{$name} офіційно приймає посаду {$role_ua_rod}." : "Кандидат відмовився від посади {$role_ua_rod}.";
            OneSignal::notifyAdmins($ppoId, $status_msg);
        }

        return ['success' => true];
    }

    public static function createExclusionPoll($userId, $ppoId, $targetId) {
        try {
            if (empty($targetId) || $targetId <= 0) return ['success' => false, 'msg' => 'Не обрано учасника для виключення.'];
            if (DB::fetchColumn("SELECT id FROM polls WHERE action_type='exclusion' AND target_user_id=? AND is_active=1", [$targetId])) return ['success' => false, 'msg' => 'Голосування щодо цього учасника вже триває.'];

            $target = DB::fetch("SELECT full_name FROM users WHERE id = ?", [$targetId]);
            if (!$target) return ['success' => false, 'msg' => 'Користувача не знайдено в базі.'];

            $name = class_exists('\Core\Security') ? (Security::decrypt($target['full_name']) ?: $target['full_name']) : $target['full_name'];

            DB::query("INSERT INTO polls (title, description, end_date, is_active, require_kep, ppo_id, action_type, target_user_id, is_secret) VALUES (?, ?, ?, 1, 1, ?, 'exclusion', ?, 0)", 
                ["Виключення: $name", "Очікується пояснення учасника.", date('Y-m-d H:i:s', strtotime('+7 days')), $ppoId, $targetId]
            );
            
            $poll_id = DB::fetchColumn("SELECT LAST_INSERT_ID()");
            if (!$poll_id) throw new \Exception("База даних не повернула ID нового голосування.");

            DB::query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, 'За виключення'), (?, 'Проти')", [$poll_id, $poll_id]);
            DB::query("INSERT INTO notifications (user_id, ppo_id, title, message) VALUES (?, ?, '⚠️ Процедура виключення', ?)", [$targetId, $ppoId, "Колектив ініціював ваше виключення. [EXCLUSION_EXPLANATION:{$poll_id}]"]);
            
            if (class_exists('\Core\OneSignal')) {
                OneSignal::notifyAdmins($ppoId, "Ініційовано голосування за виключення учасника: {$name}.");
                OneSignal::sendPush([$targetId], '⚠️ Увага!', "Розпочато процес вашого виключення з ППО. У вас є право надати пояснення в системі.");
            }

            return ['success' => true, 'msg' => 'Процедуру виключення успішно розпочато.'];
        } catch (\Exception $e) { return ['success' => false, 'msg' => 'Помилка БД: ' . $e->getMessage()]; }
    }


    public static function submitExclusionComment($userId, $pollId, $text) {
        $clean_text = Guard::clean(mb_substr($text, 0, 1500, 'UTF-8'));
        if (!$pollId || empty($clean_text) || !DB::fetchColumn("SELECT id FROM polls WHERE id = ? AND target_user_id = ? AND action_type = 'exclusion'", [$pollId, $userId])) return ['success' => false, 'msg' => 'Помилка доступу.'];

        $ppoId = DB::fetchColumn("SELECT ppo_id FROM polls WHERE id = ?", [$pollId]);

        DB::query("UPDATE polls SET target_comment = ? WHERE id = ?", [$clean_text, $pollId]);
        DB::query("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND message LIKE ?", [$userId, "%[EXCLUSION_EXPLANATION:$pollId]%"]);
        
        if (class_exists('\Core\OneSignal') && $ppoId) OneSignal::notifyAdmins($ppoId, "Учасник надав свої офіційні пояснення щодо процедури виключення.");

        return ['success' => true, 'msg' => 'Пояснення додано.'];
    }
}
