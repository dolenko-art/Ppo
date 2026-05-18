<?php
namespace Core;

/**
 * Rate Limiter Service
 * Захист від DDoS та перебирання
 */
class RateLimiter {
    private $user_id;
    private $ip_address;
    private $cache_dir;
    
    public function __construct($user_id = 0, $ip_address = '0.0.0.0') {
        $this->user_id = (int)$user_id;
        $this->ip_address = $this->normalizeIp($ip_address);
        $this->cache_dir = sys_get_temp_dir() . '/ppo_rate_limit';
        
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0750, true);
        }
    }
    
    /**
     * Перевірити чи дозволено запит
     */
    public function isAllowed($limit = 100, $window = 3600) {
        $key = $this->getKey();
        $file = $this->cache_dir . '/' . hash('sha256', $key) . '.json';
        
        $now = time();
        $data = [];
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
        }
        
        // Очистити старі записи
        $data = array_filter($data, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Перевірити ліміт
        if (count($data) >= $limit) {
            return false;
        }
        
        // Додати новий запит
        $data[] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);
        
        return true;
    }
    
    private function normalizeIp($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            $packed = substr($packed, 0, 8) . str_repeat("\0", 8);
            return inet_ntop($packed);
        }
        return $ip;
    }
    
    private function getKey() {
        return $this->user_id > 0 ? "user_{$this->user_id}" : "ip_{$this->ip_address}";
    }
}
