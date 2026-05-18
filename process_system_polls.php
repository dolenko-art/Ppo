<?php
/**
 * SYSTEM CRON: Автоматична обробка системних голосувань
 * Цей файл має запускатися планувальником Cron кожні 5-15 хвилин.
 */

// 1. ПІДКЛЮЧАЄМО ЯДРО
// Використовуємо повний шлях до файлу для стабільності в Cron
require_once __DIR__ . '/db.php';

// 2. БЕЗПЕКА (Опціонально)
// Якщо файл доступний через браузер, можна додати перевірку секретного ключа
// if (php_sapi_name() !== 'cli' && ($_GET['key'] ?? '') !== 'YOUR_SECRET_KEY') {
//     die("Restricted access");
// }

try {
    // 3. ЗАПУСКАЄМО ПРОЦЕС
    // Цей метод перевіряє чергу заявок і створює необхідні опитування
    \Controllers\ActionController::processSystemPolls();
    
    // Можна додати запис у лог для контролю (якщо потрібно)
    // error_log("[" . date('Y-m-d H:i:s') . "] System Polls processed successfully.");

} catch (\Exception $e) {
    // Якщо щось пішло не так — записуємо в системний лог
    error_log("CRON ERROR (processSystemPolls): " . $e->getMessage());
}
