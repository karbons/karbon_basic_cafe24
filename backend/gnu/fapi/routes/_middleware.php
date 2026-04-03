<?php
// 전역 미들웨어 (Private 파일 - _ 접두사로 시작)

// Config 클래스 로드 및 초기화
require_once __DIR__ . '/../shared/config_class.php';
Config::load();

// CORS 처리
function cors()
{
    $allowedOrigins = Config::get('cors.allowed_origins', ['http://localhost:5173', 'http://localhost:3000']);
    $allowCredentials = Config::get('cors.allow_credentials', true);
    $allowedHeaders = Config::get('cors.allowed_headers', ['Content-Type', 'X-Requested-With']);
    $allowedMethods = Config::get('cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
    $maxAge = Config::get('cors.max_age', 86400);

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    $isAllowed = false;

    if (Config::get('app.env') === 'development') {
        // 개발 환경: 모든 Origin 허용 또는 특정 Origin 허용
        if (empty($allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }
    } else {
        // 프로덕션 환경: 허용 목록에 있는 Origin만 허용
        if (!empty($origin) && in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }
    }

    // 허용된 Origin이면 CORS 헤더 설정
    if ($isAllowed) {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: " . ($allowCredentials ? 'true' : 'false'));
        header("Access-Control-Allow-Headers: " . implode(', ', $allowedHeaders));
        header("Access-Control-Allow-Methods: " . implode(', ', $allowedMethods));
        header("Access-Control-Max-Age: {$maxAge}");
    }

    // OPTIONS 요청 (Preflight) 처리
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// 요청 로깅
function logRequest()
{
    // API 로그 기록
    $context = [
        'response_time' => null, // 응답 시간은 json_return에서 기록
    ];

    // 에러 로그만 기록하거나, debug 모드일 때만 모든 요청 기록
    $logLevel = Config::get('app.debug', false) ? 'info' : null;

    if ($logLevel) {
        api_log($logLevel, 'API Request', $context);
    }
}

// 실행
cors();
logRequest();

// 전역 변수 설정 (인증이 필요한 엔드포인트에서만)
// index.php에서 $isAuthEndpoint가 true이면 실행하지 않음
if (!isset($isAuthEndpoint) || !$isAuthEndpoint) {
    global $member;
    $member = get_jwt_member();
}

