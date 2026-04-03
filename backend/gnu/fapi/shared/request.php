<?php
/**
 * Shared Request Utilities
 * Ported from _common.php
 */

/**
 * Query 파라미터 추출
 */
function query(?string $key = null, $default = null) {
    if ($key === null) {
        return $_GET;
    }
    return $_GET[$key] ?? $default;
}

/**
 * JSON Body 추출
 */
function json_body(?string $key = null, $default = null) {
    static $body = null;

    if ($body === null) {
        $input = file_get_contents('php://input');
        $body = json_decode($input, true) ?? [];
    }

    if ($key === null) {
        return $body;
    }
    return $body[$key] ?? $default;
}

/**
 * Form Body 추출
 */
function form_body(?string $key = null, $default = null) {
    if ($key === null) {
        return $_POST;
    }
    return $_POST[$key] ?? $default;
}

/**
 * Request Header 추출
 */
function headers(?string $key = null, $default = null) {
    static $headers = null;

    if ($headers === null) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headerName = strtolower(str_replace('_', '-', substr($name, 5)));
                $headers[$headerName] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }
    }

    if ($key === null) {
        return $headers;
    }
    return $headers[strtolower($key)] ?? $default;
}

/**
 * HTTP 메서드 반환
 */
function request_method(): string {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * 필수 파라미터 검증 후 JSON Body 추출
 */
function require_json_body(array $required): array {
    $body = json_body();
    $missing = [];

    foreach ($required as $key) {
        if (!isset($body[$key]) || $body[$key] === '') {
            $missing[] = $key;
        }
    }

    if (!empty($missing)) {
        $msg = '필수 항목이 누락되었습니다: ' . implode(', ', $missing);
        if (function_exists('response_error')) {
            response_error($msg, '00002', 400);
        } else {
            http_response_code(400);
            echo json_encode(['code' => '00002', 'msg' => $msg], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    return $body;
}

/**
 * 필수 Query 파라미터 검증
 */
function require_query(array $required): array {
    $params = query();
    $missing = [];

    foreach ($required as $key) {
        if (!isset($params[$key]) || $params[$key] === '') {
            $missing[] = $key;
        }
    }

    if (!empty($missing)) {
        $msg = '필수 파라미터가 누락되었습니다: ' . implode(', ', $missing);
        if (function_exists('response_error')) {
            response_error($msg, '00002', 400);
        } else {
            http_response_code(400);
            echo json_encode(['code' => '00002', 'msg' => $msg], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    return $params;
}
