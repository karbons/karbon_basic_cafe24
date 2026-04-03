<?php
/**
 * 웹서버 레벨 에러 핸들러
 * Apache의 ErrorDocument에서 사용하거나 직접 호출 가능
 * 
 * 사용법 (httpd-vhosts.conf):
 * ErrorDocument 404 /api/error_handler.php
 * ErrorDocument 403 /api/error_handler.php
 * ErrorDocument 500 /api/error_handler.php
 */

// 에러 코드에 따라 다른 응답
$errorCode = $_SERVER['REDIRECT_STATUS'] ?? (isset($_SERVER['HTTP_STATUS']) ? (int)$_SERVER['HTTP_STATUS'] : 500);

// 그누보드 common.php 로드 시도 (없어도 동작하도록)
if (file_exists(dirname(dirname(__FILE__)) . '/gnuboard/common.php')) {
    @include_once dirname(dirname(__FILE__)) . '/gnuboard/common.php';
}

// json_return 함수가 있으면 사용, 없으면 직접 출력
if (function_exists('json_return')) {
    $responses = [
        404 => ['code' => '00001', 'msg' => '페이지를 찾을 수 없습니다.'],
        403 => ['code' => '00003', 'msg' => '접근 권한이 없습니다.'],
        500 => ['code' => '00001', 'msg' => '서버 오류가 발생했습니다.'],
        502 => ['code' => '00001', 'msg' => '서버 연결 오류가 발생했습니다.'],
        503 => ['code' => '00001', 'msg' => '서비스를 일시적으로 사용할 수 없습니다.'],
    ];
    
    $response = $responses[$errorCode] ?? ['code' => '00001', 'msg' => '서버 오류가 발생했습니다.'];
    json_return(null, $errorCode, $response['code'], $response['msg']);
} else {
    // json_return 함수가 없으면 직접 JSON 출력
    http_response_code($errorCode);
    header('Content-Type: application/json');
    
    $responses = [
        404 => ['code' => '00001', 'msg' => '페이지를 찾을 수 없습니다.'],
        403 => ['code' => '00003', 'msg' => '접근 권한이 없습니다.'],
        500 => ['code' => '00001', 'msg' => '서버 오류가 발생했습니다.'],
        502 => ['code' => '00001', 'msg' => '서버 연결 오류가 발생했습니다.'],
        503 => ['code' => '00001', 'msg' => '서비스를 일시적으로 사용할 수 없습니다.'],
    ];
    
    $response = $responses[$errorCode] ?? ['code' => '00001', 'msg' => '서버 오류가 발생했습니다.'];
    
    echo json_encode([
        'code' => $response['code'],
        'msg' => $response['msg'],
        'data' => null,
        'time' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

