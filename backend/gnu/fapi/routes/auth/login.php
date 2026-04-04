<?php
// POST /api/auth/login
function POST()
{
    require_once G5_API_PATH . '/domains/auth/service.php';
    require_once G5_API_PATH . '/shared/response.php';
    require_once G5_API_PATH . '/shared/firebase.php';

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['mb_id']) || empty($data['mb_password'])) {
        response_error('아이디와 비밀번호를 입력해주세요.');
    }

    $mb_id = $data['mb_id'];
    $mb_password = $data['mb_password'];
    $fcm_token = $data['fcm_token'] ?? '';
    $device_model = $data['device_model'] ?? '';
    $os_version = $data['os_version'] ?? '';

    $auth_data = auth_service_login($mb_id, $mb_password, $fcm_token, $device_model, $os_version);

    if (!$auth_data) {
        response_error('가입된 회원아이디가 아니거나 비밀번호가 틀립니다.');
    }

    $mb = $auth_data['member'];
    $accessToken = $auth_data['access_token'];
    $refreshToken = $auth_data['refresh_token'];

    $accessMtime = Config::get('jwt.access_mtime', 15);
    $refreshDate = Config::get('jwt.refresh_date', 30);
    set_refresh_token_cookie($refreshToken, $refreshDate * 86400);

    $deviceId = $data['device_id'] ?? '';
    if (empty($deviceId)) {
        $deviceId = bin2hex(random_bytes(16));
    }
    $cookieDomain = getenv('APP_COOKIE_DOMAIN') ?: '';
    setcookie('sapi_device_id', $deviceId, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => (bool)getenv('APP_HTTPS_ONLY'),
        'httponly' => false,
        'samesite' => getenv('APP_COOKIE_SAMESITE') ?: 'Lax'
    ]);

    $csrfToken = bin2hex(random_bytes(32));
    setcookie('sapi_csrf_token', $csrfToken, [
        'expires' => 0,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => (bool)getenv('APP_HTTPS_ONLY'),
        'httponly' => false,
        'samesite' => 'Strict'
    ]);

    $firebaseToken = null;
    if (firebase_is_enabled()) {
        $firebaseToken = firebase_create_custom_token($mb['mb_id'], [
            'mb_nick' => $mb['mb_nick'],
            'mb_level' => $mb['mb_level']
        ]);
    }

    response_success([
        'mb' => [
            'mb_id' => $mb['mb_id'],
            'mb_name' => $mb['mb_name'],
            'mb_nick' => $mb['mb_nick'],
            'mb_level' => $mb['mb_level'],
            'mb_point' => $mb['mb_point'],
            'mb_memo_cnt' => $mb['mb_memo_cnt'] ?? 0,
            'mb_scrap_cnt' => $mb['mb_scrap_cnt'] ?? 0
        ],
        'firebase_token' => $firebaseToken,
        'access_token' => $accessToken,
        'csrf_token' => $csrfToken,
        'device_id' => $deviceId
    ], '로그인 성공');
}


