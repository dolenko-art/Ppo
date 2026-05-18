<?php
namespace Services; // Або namespace Core; (залежно від того, в якій папці він лежить)

class MonobankClient {
    const BASE_URL = 'https://api.monobank.ua';
    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    public function getClientInfo() {
        return $this->request('GET', '/personal/client-info');
    }

    public function getStatement($account, $from, $to = null) {
        if ($to === null) $to = time();
        $diff = $to - $from;
        
        // Авто-коригування до 31 доби (ліміт Монобанку)
        if ($diff > 2682000) {
            $from = $to - 2682000; 
        }
        return $this->request('GET', '/personal/statement/' . $account . '/' . $from . '/' . $to);
    }

    public static function formatAmount($kopecks) {
        return number_format(round($kopecks / 100, 2), 2, '.', ' ');
    }

    private function request($method, $path, $body = array(), $requiresAuth = true) {
        $ch = curl_init(self::BASE_URL . $path);
        $headers = array('Content-Type: application/json');
        if ($requiresAuth) $headers[] = 'X-Token: ' . $this->token;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) throw new \Exception('cURL error: ' . $curlError);
        $decoded = json_decode($response, true);

        if ($httpCode !== 200) {
            $errMsg = $decoded['errorDescription'] ?? ($decoded['error'] ?? 'HTTP ' . $httpCode);
            throw new \Exception('Monobank API error: ' . $errMsg);
        }
        
        return $decoded ? $decoded : array();
    }
}
