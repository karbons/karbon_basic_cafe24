<?php
/**
 * Shared General Utilities
 * Ported from _common.php
 */

if (!function_exists('get_microtime')) {
    function get_microtime() {
        return microtime(true);
    }
}

if (!function_exists('gen_uuid_v4')) {
    function gen_uuid_v4() {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('encrypt')) {
    function encrypt($data) {
        $key = (function_exists('config') ? config('jwt.crypt_key', '') : '');
        if (!$key && class_exists('Config')) {
            $key = Config::get('jwt.crypt_key', '');
        }
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
}

if (!function_exists('decrypt')) {
    function decrypt($data) {
        $key = (function_exists('config') ? config('jwt.crypt_key', '') : '');
        if (!$key && class_exists('Config')) {
            $key = Config::get('jwt.crypt_key', '');
        }
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
}

if (!function_exists('api_log')) {
    function api_log($level = 'info', $message = '', $context = []) {
        $configLogLevel = (int) (function_exists('env') ? env('LOG_LEVEL', 3) : 3);

        $levelPriority = [
            'debug' => 4,
            'info' => 3,
            'warning' => 2,
            'error' => 1
        ];

        $currentPriority = $levelPriority[strtolower($level)] ?? 3;

        if ($configLogLevel === 0 || $currentPriority > $configLogLevel) {
            return;
        }

        $logDir = defined('G5_API_PATH') ? G5_API_PATH . '/logs' : __DIR__ . '/../logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/api-' . date('Y-m-d') . '.log';

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ];

        if (!empty($context)) {
            $logEntry['context'] = $context;
        }

        @file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }
}
