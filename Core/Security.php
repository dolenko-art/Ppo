<?php
/**
 * Core\Security - Encryption & Decryption Service
 * Алгоритм: AES-256-GCM (AEAD cipher)
 * ВЕРСІЯ: 2.0 (Security Hardened)
 */

namespace Core;

class Security {
    
    /**
     * Шифрування даних
     * @param string $plaintext
     * @return string|false Encrypted data with nonce, or false on error
     */
    public static function encrypt($plaintext) {
        if (empty($plaintext)) return false;
        
        try {
            $key = self::getKey();
            if (!$key) return false;
            
            // 🔥 Generate random 96-bit nonce (recommended for GCM)
            $nonce = openssl_random_pseudo_bytes(12);
            if (!$nonce) return false;
            
            // 🔥 Encrypt with authentication tag
            $tag = '';
            $ciphertext = openssl_encrypt(
                $plaintext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag
            );
            
            if ($ciphertext === false) return false;
            
            // 🔥 Return: base64(nonce || tag || ciphertext)
            return base64_encode($nonce . $tag . $ciphertext);
            
        } catch (\Exception $e) {
            error_log("Encryption Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Дешифрування даних
     * @param string $encrypted
     * @return string|false Plaintext or false on error
     */
    public static function decrypt($encrypted) {
        if (empty($encrypted) || !is_string($encrypted)) return false;
        
        try {
            $key = self::getKey();
            if (!$key) return false;
            
            // 🔥 Decode base64
            $data = base64_decode($encrypted, true);
            if (!$data || strlen($data) < 28) return false; // Min: 12 (nonce) + 16 (tag) = 28
            
            // 🔥 Extract components
            $nonce = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $ciphertext = substr($data, 28);
            
            // 🔥 Decrypt with authentication
            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag
            );
            
            return $plaintext !== false ? $plaintext : false;
            
        } catch (\Exception $e) {
            error_log("Decryption Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Отримати ключ шифрування
     * @return string|false 32-byte key or false
     */
    private static function getKey() {
        // 🔥 Use environment variable or stored key
        $key = getenv('ENCRYPTION_KEY');
        
        if (!$key) {
            // 🔥 Fallback: derive from config (if exists)
            if (class_exists('\Core\Config')) {
                $key = \Core\Config::get('encryption_key');
            }
        }
        
        // 🔥 Validate key length (must be 32 bytes for AES-256)
        if (!$key || strlen($key) !== 32) {
            error_log("ERROR: Invalid encryption key. Set ENCRYPTION_KEY environment variable.");
            return false;
        }
        
        return $key;
    }
    
    /**
     * Генерувати безпечний ключ
     * @return string 32-byte hex string
     */
    public static function generateKey() {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            // Fallback for older PHP
            return bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    
    /**
     * Хешування паролю
     * @param string $password
     * @return string bcrypt hash
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Перевірка паролю
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Генерувати безпечний токен
     * @param int $length Default: 32 bytes = 64 hex chars
     * @return string hex string
     */
    public static function generateToken($length = 32) {
        try {
            return bin2hex(random_bytes($length));
        } catch (\Exception $e) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }
    
    /**
     * Verify CSRF Token (timing-safe)
     * @param string $token1
     * @param string $token2
     * @return bool
     */
    public static function verifyCsrfToken($token1, $token2) {
        return hash_equals((string)$token1, (string)$token2);
    }
}
