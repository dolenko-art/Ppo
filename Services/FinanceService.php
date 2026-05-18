<?php
namespace Services;

use Core\DB;
use Core\OneSignal;
use Core\Security;

class FinanceService {
    
    // ==========================================
    // 💵 ДОДАВАННЯ ГОТІВКОВОЇ ТРАНЗАКЦІЇ (З ОНОВЛЕННЯМ КЕШУ)
    // ==========================================
    public static function addCash($data, $user_id, $ppo_id) {
        // 1. Жорстка валідація суми (сума завжди має бути > 0 і в копійках)
        $amount = (int)($data['amount'] ?? 0);
        if ($amount <= 0) {
            return ['success' => false, 'msg' => 'Помилка: Сума має бути більшою за нуль.'];
        }

        $type = ($data['type'] ?? '') === 'income' ? 'income' : 'expense';
        $reason = trim($data['reason'] ?? '');
        
        // Визначаємо математичний знак для оновлення загального балансу
        $math_amount = ($type === 'income') ? $amount : -$amount;

        DB::beginTransaction();
        try {
            // 2. Додаємо запис в історію
            DB::query(
                "INSERT INTO cash_transactions (user_id, type, amount, reason, ppo_id) VALUES (?, ?, ?, ?, ?)", 
                [$user_id, $type, $amount, $reason, $ppo_id]
            );
            
            // 3. 🔥 МИТТЄВО ОНОВЛЮЄМО СТАТИЧНИЙ БАЛАНС ППО (Highload Fix)
            DB::query(
                "UPDATE ppos SET cash_balance_cents = cash_balance_cents + ? WHERE id = ?", 
                [$math_amount, $ppo_id]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'msg' => 'Помилка бази даних під час збереження транзакції.'];
        }
        
        // 🔔 ПУШ-сповіщення
        if (class_exists('\Core\OneSignal')) {
            OneSignal::notifyRoles($ppo_id, ['auditor'], '💸 Нова касова операція', 'Керівництво додало нову готівкову транзакцію.', '/finance_history.php');
        }
        return ['success' => true, 'msg' => '💸 Запис додано в касу!'];
    }

    // ==========================================
    // 🐈 ПІДКЛЮЧЕННЯ ТА ВІДКЛЮЧЕННЯ МОНОБАНКУ
    // ==========================================
    public static function saveMonoToken($data, $ppo_id) {
        $token = trim($data['mono_token'] ?? '');
        
        DB::beginTransaction();
        try {
            if (isset($data['delete_mono'])) {
                DB::query("UPDATE finance_config SET mono_token=NULL WHERE ppo_id=?", [$ppo_id]);
                DB::query("DELETE FROM bank_accounts WHERE bank_type='mono' AND ppo_id=?", [$ppo_id]);
                DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", ["🔌 Відключення від Monobank", "Голова ППО відключив інтеграцію.", $ppo_id]);
                
                if (class_exists('\Core\OneSignal')) {
                    OneSignal::notifyRoles($ppo_id, ['head', 'manager', 'auditor'], '🔌 Відключення банку', 'Інтеграцію з Monobank було повністю відключено.');
                }

                $res = ['success' => true, 'msg' => '🔌 Monobank відключено.'];
            } elseif ($token !== '') {
                require_once __DIR__ . '/../monobank.php'; 
                $mono = new \MonobankClient($token); 
                $info = $mono->getClientInfo(); 
                
                $enc = class_exists('\Core\Security') ? Security::encrypt($token) : $token;
                
                DB::query("INSERT INTO finance_config (ppo_id, mono_token) VALUES (?, ?) ON DUPLICATE KEY UPDATE mono_token=?", [$ppo_id, $enc, $enc]);
                DB::query("DELETE FROM bank_accounts WHERE bank_type='mono' AND ppo_id=?", [$ppo_id]);
                
                foreach ($info['accounts'] as $acc) {
                    $name = ($acc['type']==='fop'?'💼 ФОП':'🏦 Картка').' (*'.substr($acc['maskedPan'][0]??'Рахунок',-4).')';
                    DB::query("INSERT INTO bank_accounts (bank_type, external_id, name, balance, ppo_id) VALUES ('mono', ?, ?, ?, ?)", [$acc['id'], $name, $acc['balance'], $ppo_id]);
                }
                DB::query("INSERT INTO news (title, content, ppo_id) VALUES (?, ?, ?)", ["🏦 Інтеграція з Monobank", "Голова ППО успішно підключив інтеграцію.", $ppo_id]);
                
                if (class_exists('\Core\OneSignal')) {
                    OneSignal::notifyRoles($ppo_id, ['head', 'manager', 'auditor'], '🏦 Підключення банку', 'Успішно налаштовано фінансову інтеграцію з рахунками Monobank.');
                }

                $res = ['success' => true, 'msg' => '🐈 Monobank успішно підключено!'];
            }
            DB::commit();
            return $res ?? ['success' => false, 'msg' => 'Невідома дія.'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

     // ==========================================
    // 🔄 АВТОМАТИЧНА СИНХРОНІЗАЦІЯ ВИПИСКИ МОНОБАНКУ (КРОН)
    // ==========================================
    public static function syncMonobank($ppo_id) {
        $config = DB::fetch("SELECT mono_token FROM finance_config WHERE ppo_id = ?", [$ppo_id]);
        if (!$config || empty($config['mono_token'])) return false;

        $token = class_exists('\Core\Security') ? Security::decrypt($config['mono_token']) : $config['mono_token'];
        if (!$token) return false;

        require_once __DIR__ . '/../monobank.php';
        
        try {
            $mono = new \MonobankClient($token);
            $info = $mono->getClientInfo(); 

            if (!isset($info['accounts'])) return false;

            foreach ($info['accounts'] as $acc) {
                
                // 1. Оновлюємо загальний залишок по рахунку
                DB::query(
                    "UPDATE bank_accounts SET balance = ? WHERE bank_type = 'mono' AND external_id = ? AND ppo_id = ?", 
                    [$acc['balance'], $acc['id'], $ppo_id]
                );

                // 2. Витягуємо час останньої транзакції
                $last_time = (int)DB::fetchColumn("SELECT MAX(time) FROM bank_transactions WHERE account_id = ?", [$acc['id']]);
                $min_time = time() - (30 * 24 * 60 * 60);
                
                if (!$last_time || $last_time < $min_time) {
                    $last_time = $min_time; 
                } else {
                    $last_time += 1; 
                }

                // 3. 🔥 АСИНХРОННИЙ ПІДХІД ЗАМІСТЬ СМЕРТЕЛЬНОГО SLEEP(61)
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.monobank.ua/personal/statement/{$acc['id']}/{$last_time}");
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Token: $token"]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Швидкий таймаут
                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpcode == 200) {
                    $transactions = json_decode($response, true);
                    if (is_array($transactions) && !empty($transactions)) {
                        foreach ($transactions as $t) {
                            DB::query("
                                INSERT IGNORE INTO bank_transactions 
                                (id, ppo_id, account_id, time, amount, balance, description, comment, mcc) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ", [
                                $t['id'], $ppo_id, $acc['id'], $t['time'], $t['amount'], 
                                $t['balance'], $t['description'] ?? '', $t['comment'] ?? '', $t['mcc'] ?? 0
                            ]);
                        }
                    }
                } elseif ($httpcode == 429) {
                    // Якщо Монобанк каже "Зачекайте 1 хвилину" (Too Many Requests), 
                    // ми просто перериваємо цикл! Наступного разу Крон запуститься через 
                    // хвилину і опрацює наступну картку. Жодних зависань сервера!
                    break; 
                } else {
                    error_log("Монобанк API Помилка ($httpcode) для рахунку {$acc['id']} (ППО $ppo_id): " . $response);
                }
            }
            return true;
            
        } catch (\Exception $e) {
            error_log("Помилка синхронізації Монобанку (ППО $ppo_id): " . $e->getMessage());
            return false;
        }
    }
}
