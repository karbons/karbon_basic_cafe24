<?php
/**
 * API Logger 라이브러리
 */

if (!defined('G5_API_PATH')) {
    define('G5_API_PATH', dirname(__DIR__));
}

// 로그 레벨 상수 정의
if (!defined('LOG_LEVEL_NONE')) define('LOG_LEVEL_NONE', 0);
if (!defined('LOG_LEVEL_ERROR')) define('LOG_LEVEL_ERROR', 1);
if (!defined('LOG_LEVEL_WARNING')) define('LOG_LEVEL_WARNING', 2);
if (!defined('LOG_LEVEL_INFO')) define('LOG_LEVEL_INFO', 3);
if (!defined('LOG_LEVEL_DEBUG')) define('LOG_LEVEL_DEBUG', 4);

// 이미 정의된 함수 목록 (중복 체크용)
$_LOGGER_DEFINED_FUNCTIONS = [];

/**
 * 에러 로그 기록
 */
function log_error($message, $context = [])
{
    _write_log('error', $message, $context, 'error');
}

/**
 * 데이터베이스 에러 로그 기록
 */
function log_db_error($sql, $error_message, $context = [])
{
    $context['sql'] = $sql;
    $context['db_error'] = $error_message;
    _write_log('error', 'Database Error', $context, 'db');
}

/**
 * 경고 로그 기록
 */
function log_warning($message, $context = [])
{
    _write_log('warning', $message, $context, 'api');
}

/**
 * 정보 로그 기록
 */
function log_info($message, $context = [])
{
    _write_log('info', $message, $context, 'api');
}

/**
 * 디버그 로그 기록
 */
function log_debug($message, $data = null)
{
    $context = [];
    if ($data !== null) {
        $context['data'] = $data;
    }
    _write_log('debug', $message, $context, 'api');
}

/**
 * 함수 중복 정의 체크
 */
function check_function_conflict($function_name, $file = '')
{
    global $_LOGGER_DEFINED_FUNCTIONS;

    if (function_exists($function_name)) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller_file = $trace[1]['file'] ?? $file;
        $caller_line = $trace[1]['line'] ?? 0;

        log_warning("Function conflict: '{$function_name}' is already defined", [
            'function' => $function_name,
            'conflict_file' => $caller_file,
            'conflict_line' => $caller_line,
            'type' => 'php_builtin_or_loaded'
        ]);
        return false;
    }

    if (isset($_LOGGER_DEFINED_FUNCTIONS[$function_name])) {
        $original = $_LOGGER_DEFINED_FUNCTIONS[$function_name];
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller_file = $trace[1]['file'] ?? $file;
        $caller_line = $trace[1]['line'] ?? 0;

        log_warning("Function conflict: '{$function_name}' was already registered", [
            'function' => $function_name,
            'original_file' => $original['file'],
            'original_line' => $original['line'],
            'conflict_file' => $caller_file,
            'conflict_line' => $caller_line
        ]);
        return false;
    }

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $_LOGGER_DEFINED_FUNCTIONS[$function_name] = [
        'file' => $trace[1]['file'] ?? $file,
        'line' => $trace[1]['line'] ?? 0
    ];

    return true;
}

/**
 * 내부: 로그 파일에 기록
 */
function _write_log($level, $message, $context = [], $type = 'api')
{
    $configLogLevel = (int) (getenv('LOG_LEVEL') ?: 3);
    if ($configLogLevel === 0) return;

    $levelPriority = [
        'debug' => LOG_LEVEL_DEBUG,
        'info' => LOG_LEVEL_INFO,
        'warning' => LOG_LEVEL_WARNING,
        'error' => LOG_LEVEL_ERROR
    ];

    $currentPriority = $levelPriority[strtolower($level)] ?? LOG_LEVEL_INFO;
    if ($currentPriority > $configLogLevel) return;

    $logDir = G5_API_PATH . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

    $date = date('Y-m-d');
    $logFile = $logDir . '/' . $type . '-' . $date . '.log';

    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => strtoupper($level),
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'uri' => $_SERVER['REQUEST_URI'] ?? ''
    ];

    if ($level === 'error' && !isset($context['trace'])) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $context['trace'] = array_slice($trace, 2);
    }

    if ($configLogLevel >= LOG_LEVEL_DEBUG) {
        $logEntry['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $logEntry['memory'] = round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
    }

    if (!empty($context)) $logEntry['context'] = $context;

    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

    if (mt_rand(1, 100) === 1) _cleanup_logs($logDir);
}

/**
 * 내부: 오래된 로그 파일 정리
 */
function _cleanup_logs($logDir)
{
    $retentionDays = (int) (getenv('LOG_RETENTION_DAYS') ?: 30);
    if ($retentionDays === 0 || !is_dir($logDir)) return;

    $cutoffTime = time() - ($retentionDays * 86400);
    $patterns = ['api-*.log', 'error-*.log', 'db-*.log'];

    foreach ($patterns as $pattern) {
        $files = glob($logDir . '/' . $pattern);
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) @unlink($file);
        }
    }
}

/**
 * 기존 api_log 함수 호환성
 */
if (!function_exists('api_log')) {
    function api_log($level, $message, $context = []) {
        _write_log($level, $message, $context, 'api');
    }
}
