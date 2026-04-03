<?php
/**
 * 설정 관리 클래스
 */
class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * 설정 로드
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        // .env 파일 로드
        $envFile = defined('G5_API_PATH') ? G5_API_PATH . '/.env' : __DIR__ . '/../.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0)
                    continue;

                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    if (preg_match('/^"(.*)"$/s', $value, $matches)) {
                        $value = $matches[1];
                    } elseif (preg_match("/^'(.*)'$/s", $value, $matches)) {
                        $value = $matches[1];
                    }

                    self::$config[$key] = $value;
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }

        // config 폴더의 PHP 파일들 로드
        $configDir = defined('G5_API_PATH') ? G5_API_PATH . '/config' : __DIR__ . '/../config';

        if (is_dir($configDir)) {
            $files = glob($configDir . '/*.php');
            foreach ($files as $file) {
                $name = basename($file, '.php');
                $configData = require_once $file;
                if (is_array($configData)) {
                    self::$config[$name] = $configData;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * 설정 값 가져오기
     */
    public static function get($key, $default = null)
    {
        self::load();

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * 설정 값 설정 (런타임)
     */
    public static function set($key, $value)
    {
        self::load();

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * 모든 설정 가져오기
     */
    public static function all()
    {
        self::load();
        return self::$config;
    }
}
