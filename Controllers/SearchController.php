<?php
namespace Controllers;

class SearchController
{
    public static function searchCandidate(): array
    {
        $q = trim((string)($_GET['q'] ?? ''));
        
        if (mb_strlen($q, 'UTF-8') < 2) { 
            return []; 
        }
        
        $ppo_id = (int)($_SESSION['ppo_id'] ?? 0);
        if (!$ppo_id) return [];
        
        try {
            // 🛡️ ФІКС OOM: LIMIT 300. Ніколи не тягнемо всю зашифровану базу в оперативну пам'ять!
            $users = \Core\DB::fetchAll("
                SELECT id, full_name 
                FROM users 
                WHERE ppo_id = ? 
                  AND status IN ('member', 'admin') 
                  AND (role IS NULL OR role NOT IN ('head', 'auditor', 'manager'))
                LIMIT 300
            ", [$ppo_id]);
            
            $results = [];
            $has_security = class_exists('\Core\Security');
            
            foreach ($users as $u) {
                // Дешифруємо в ОЗП
                $name = $u['full_name'];
                if ($has_security) {
                    $decrypted = \Core\Security::decrypt($name);
                    $name = $decrypted !== false ? $decrypted : $name;
                }
                
                // Перевірка входження підрядка
                if (mb_stripos($name, $q, 0, 'UTF-8') !== false) {
                    $results[] = [
                        'id' => (int)$u['id'], 
                        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
                    ];
                }
                
                // 🛡️ Повертаємо тільки ТОП-10 результатів на фронтенд
                if (count($results) >= 10) {
                    break;
                }
            }
            
            return $results;
            
        } catch (\Exception $e) {
            error_log("SearchCandidate Error: " . $e->getMessage());
            return [];
        }
    }

    public static function searchUsers(): array
    {
        return ['success' => true, 'msg' => 'Метод підключено'];
    }
}
