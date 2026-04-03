<?php
/**
 * 환경 변수 (.env) 로드 설정
 * 
 * .env 파일에서 환경 변수를 읽어 $_ENV와 getenv()에서 사용할 수 있도록 설정
 */

$envPath = dirname(__DIR__) . '/.env';

if (file_exists($envPath)) {
    $env = file_get_contents($envPath);
    $lines = explode("\n", $env);

    foreach ($lines as $line) {
        $line = trim($line);

        // 빈 줄이나 주석 건너뛰기
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // KEY=VALUE 파싱
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // 따옴표 제거 (있는 경우)
            $value = trim($value, '"\'');

            // $_ENV와 putenv 모두 설정
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

if (!function_exists('env')) {
    /**
     * 환경 변수 조회 헬퍼 함수
     * 
     * @param string $key 환경 변수 키
     * @param mixed $default 기본값
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // 불리언 문자열 변환
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}
