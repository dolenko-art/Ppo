<?php
namespace Models;
use Core\DB;

class Finance {
    
    // ==========================================
    // 💰 ОТРИМАННЯ ФІНАНСОВОЇ СТАТИСТИКИ 
    // ==========================================
    public static function getStats($ppo_id): array 
    {
        $ppo_id = (int)$ppo_id; 
        if ($ppo_id <= 0) {
            return [
                'accounts'     => [],
                'cash_balance' => 0,
                'total'        => 0
            ];
        }

        // 🛡️ Беремо всі дані (щоб не вгадувати ваші колонки і не ламати SQL)
        $raw_accounts = DB::fetchAll("SELECT * FROM bank_accounts WHERE ppo_id = ?", [$ppo_id]);
        
        $bank_cents = 0;
        $safe_accounts = []; // Сюди покладемо тільки безпечні дані

        if (!empty($raw_accounts)) {
            foreach ($raw_accounts as $acc) {
                $bal = (int)($acc['balance'] ?? 0);
                $bank_cents += $bal; 
                
                // 🛡️ ЗАХИСТ ВІД ВИТОКУ: Формуємо повністю новий масив без API-ключів
                $safe_accounts[] = [
                    'name'    => $acc['name'] ?? 'Банківський рахунок',
                    'balance' => $bal
                ];
            }
        }

        // 3. БЕЗПЕЧНИЙ ЧИТАЧ КАСИ
        $cash_cents = (int)DB::fetchColumn("
            SELECT COALESCE(cash_balance_cents, 0) 
            FROM ppos 
            WHERE id = ?
        ", [$ppo_id]);

        // 4. ФОРМУВАННЯ РЕЗУЛЬТАТУ
        return [
            'accounts'     => $safe_accounts, // Віддаємо безпечний очищений масив
            'cash_balance' => $cash_cents,
            'total'        => $bank_cents + $cash_cents
        ];
    }
}
