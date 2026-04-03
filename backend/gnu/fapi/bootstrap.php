<?php
/**
 * API 부트스트랩 - 독립 초기화
 * 
 * 그누보드 common.php 의존성 없이 API를 독립적으로 실행합니다.
 */

// ============================================================================
// 1. 환경 변수 및 설정 로드
// ============================================================================
require_once __DIR__ . '/config/env.php';

// ============================================================================
// 2. 경로 상수 정의
// ============================================================================
if (!defined('G5_API_PATH')) {
    define('G5_API_PATH', __DIR__);
}

// 프로젝트 루트 경로 (그누보드 경로)
if (!defined('G5_PATH')) {
    define('G5_PATH', dirname(__DIR__));
}

// 플러그인 경로
if (!defined('G5_PLUGIN_PATH')) {
    define('G5_PLUGIN_PATH', G5_PATH . '/plugin');
}

// URL 상수
$domain = env('APP_DOMAIN', 'http://localhost');
if (!defined('G5_URL')) {
    define('G5_URL', $domain);
}
if (!defined('G5_API_URL')) {
    define('G5_API_URL', $domain . '/fapi');
}

// 데이터 경로
if (!defined('G5_DATA_PATH')) {
    define('G5_DATA_PATH', G5_PATH . '/data');
}
if (!defined('G5_DATA_URL')) {
    define('G5_DATA_URL', G5_URL . '/data');
}

// 시간 상수
if (!defined('G5_SERVER_TIME')) {
    define('G5_SERVER_TIME', time());
}
if (!defined('G5_TIME_YMDHIS')) {
    define('G5_TIME_YMDHIS', date('Y-m-d H:i:s', G5_SERVER_TIME));
}
if (!defined('G5_TIME_YMD')) {
    define('G5_TIME_YMD', date('Y-m-d', G5_SERVER_TIME));
}

// ============================================================================
// 3. 라이브러리 로드 (순서 중요)
// ============================================================================
require_once __DIR__ . '/shared/logger.php';       // 로거
require_once __DIR__ . '/shared/cors.php';         // CORS
require_once __DIR__ . '/shared/sqlx.php';         // DB 라이브러리
require_once __DIR__ . '/shared/compat.php';       // 그누보드 호환 레이어
require_once __DIR__ . '/shared/config_class.php'; // 설정 클래스
require_once __DIR__ . '/shared/config.php';       // 설정 함수
require_once __DIR__ . '/shared/response.php';     // 응답 함수
require_once __DIR__ . '/shared/db.php';           // DB 함수
require_once __DIR__ . '/shared/auth.php';         // 인증 함수
require_once __DIR__ . '/shared/router.php';       // 라우터

// ============================================================================
// 4. API 공통 함수 로드
// ============================================================================
require_once __DIR__ . '/_common.php';

// ============================================================================
// 5. 설정 로드 및 DB 연결
// ============================================================================
Config::load();
try {
    sqlx::connect();  // DB 연결
} catch (PDOException $e) {
    if (PHP_SAPI !== 'cli') {
        throw $e;
    }
}

// ============================================================================
// 6. 시작 시간 기록
// ============================================================================
$begin_time = get_microtime();
if (!isset($response_begin_time)) {
    $response_begin_time = $begin_time;
}

// Composer autoload (Firebase SDK 등) - 출력 억제
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    ob_start();
    require_once __DIR__ . '/vendor/autoload.php';
    ob_end_clean();
}

// ============================================================================
// 7. 에러 핸들러 설정
// ============================================================================
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if ($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) {
        if (function_exists('log_error')) {
            log_error('PHP Fatal Error', [
                'error_no' => $errno,
                'error_message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ]);
        }
        if (function_exists('response_error')) {
            response_error('서버 오류가 발생했습니다.', '00001', 500);
        } else {
            json_return(null, 500, '00001', '서버 오류가 발생했습니다.');
        }
    }
    return false;
}, E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

set_exception_handler(function ($exception) {
    if (function_exists('log_error')) {
        log_error('Uncaught Exception', [
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_trace' => $exception->getTraceAsString()
        ]);
    }
    if (function_exists('response_error')) {
        response_error('서버 오류가 발생했습니다.', '00001', 500);
    } else {
        json_return(null, 500, '00001', '서버 오류가 발생했습니다.');
    }
});
