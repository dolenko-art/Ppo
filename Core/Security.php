<?php
namespace Core;

class Security {
    
    // ==========================================
    // 🔒 ШИФРУВАННЯ (Військовий стандарт AES-256-GCM)
    // ==========================================
    public static function encrypt($data) {
        if (empty($data)) return $data;
        
        $key = getenv('ENCRYPTION_KEY') ?: $_ENV['ENCRYPTION_KEY'] ?? '';
        $encryption_key = hash('sha256', $key, true);
        
        // 🛡️ Вимога 2: Кріптографічно стійкий CSPRNG на рівні ядра ОС
        // Для режиму GCM стандартна та найбезпечніша довжина IV = 12 байтів
        $iv = random_bytes(12); 
        $tag = ""; // Сюди openssl запише тег автентичності
        
        // 🛡️ Вимога 1: AEAD-режим (GCM)
        $enc = openssl_encrypt($data, 'aes-256-gcm', $encryption_key, 0, $iv, $tag);
        
        if ($enc === false) return $data;

        // Зберігаємо 3 компоненти: Шифротекст :: Вектор :: Тег
        return base64_encode($enc . '::' . $iv . '::' . $tag);
    }

    // ==========================================
    // 🔓 РОЗШИФРУВАННЯ (З підтримкою зворотної сумісності)
    // ==========================================
    public static function decrypt($data) {
        if (empty($data)) return $data;
        
        $key = getenv('ENCRYPTION_KEY') ?: $_ENV['ENCRYPTION_KEY'] ?? '';
        $encryption_key = hash('sha256', $key, true);
        
        // Додано strict-режим для base64 (відкидає сміття)
        $decoded = base64_decode($data, true);
        if ($decoded === false) return $data; // Якщо це звичайний текст, повертаємо як є

        $parts = explode('::', $decoded);
        $count = count($parts);

        // 🛡️ НОВИЙ СТАНДАРТ (AES-256-GCM) - 3 частини: [Дані, IV, Тег]
        if ($count === 3) {
            $decrypted = openssl_decrypt($parts[0], 'aes-256-gcm', $encryption_key, 0, $parts[1], $parts[2]);
            // Якщо тег не співпав (була атака Bit-Flipping), повернеться false. 
            // Віддаємо зашифрований рядок, щоб захистити систему від витоку через помилки
            return $decrypted !== false ? $decrypted : $data;
        } 
        // ⚠️ СТАРИЙ СТАНДАРТ (AES-256-CBC) - 2 частини: [Дані, IV] (Для тих, хто вже в базі)
        elseif ($count === 2) {
            $decrypted = openssl_decrypt($parts[0], 'aes-256-cbc', $encryption_key, 0, $parts[1]);
            return $decrypted !== false ? $decrypted : $data;
        }

        // Якщо формат не підпадає під шифрування — повертаємо як є
        return $data;
    }

    // ==========================================
    // 🧹 ОЧИЩЕННЯ (XSS)
    // ==========================================
    public static function xss($data) {
        // Додано ENT_HTML5 та приведення до рядка для максимальної сумісності
        return htmlspecialchars((string)$data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
