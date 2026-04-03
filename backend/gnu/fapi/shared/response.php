<?php
/**
 * Shared Response Utilities
 * Ported from lib/Response.php and _common.php
 */

if (!isset($response_begin_time)) {
    $response_begin_time = (function_exists('get_microtime') ? get_microtime() : microtime(true));
}

/**
 * JSON Response with buffering cleanup and proper headers
 */
function response_json($data, $code = '00000', $msg = '', $http_code = 200) {
    global $response_begin_time;

    // 모든 출력 버퍼 정리
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code($http_code);
        header('Content-Type: application/json; charset=utf-8');
    }

    $now = (function_exists('get_microtime') ? get_microtime() : microtime(true));
    $responseTime = round($now - ($response_begin_time ?? $now), 4);

    $response = [
        'code' => $code,
        'data' => $data,
        'time' => $responseTime
    ];

    if ($msg) {
        $response['msg'] = $msg;
    }

    // 응답 로그 기록
    if (function_exists('api_log')) {
        $logLevel = $http_code >= 500 ? 'error' : ($http_code >= 400 ? 'warning' : 'info');
        api_log($logLevel, 'API Response', [
            'http_code' => $http_code,
            'error_code' => $code,
            'response_time' => $responseTime,
            'message' => $msg
        ]);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    flush();
    exit;
}

function response_success($data, $msg = '') {
    response_json($data, '00000', $msg, 200);
}

function response_error($msg, $code = '00001', $http_code = 200) {
    response_json(null, $code, $msg, $http_code);
}

function response_unauthorized($msg = '로그인이 필요합니다.') {
    response_json(null, '00002', $msg, 401);
}

function response_forbidden($msg = '권한이 없습니다.') {
    response_json(null, '00003', $msg, 403);
}

function response_not_found($msg = '페이지를 찾을 수 없습니다.') {
    response_json(null, '00001', $msg, 404);
}

function response_token_expired($msg = '토큰이 만료되었습니다.') {
    response_json(null, '00004', $msg, 401);
}

/**
 * Legacy alias for response_json
 */
function json_return($result, $http_code = 200, $error_code = '00000', $msg = "") {
    response_json($result, $error_code, $msg, $http_code);
}
