<?php
/**
 * 인증 및 보안 관련 함수
 */

/**
 * 토큰 쿠키 이름
 */
function auth_get_cookie_name($type = 'access') {
    return $type === 'refresh' ? 'sapi_refresh_token' : 'sapi_access_token';
}

/**
 * 현재 로그인한 회원 정보 가져오기
 */
function auth_get_member() {
    $token = _auth_get_token();

    if (!$token) {
        $refreshToken = $_COOKIE[auth_get_cookie_name('refresh')] ?? null;
        if ($refreshToken) {
            return auth_refresh_access_token();
        }
        return auth_get_guest_member();
    }

    try {
        if (!class_exists('JWT')) {
            require_once __DIR__ . '/jwt.php';
        }
        $jwt = new JWT();
        $jwtKey = function_exists('config') ? config('jwt.access_token_key', '') : getenv('JWT_ACCESS_TOKEN_KEY');

        $payload = $jwt->decode($token, $jwtKey, ['HS256']);

        if ($payload->exp < time()) {
            return auth_refresh_access_token();
        }

        if (function_exists('api_get_member')) {
            return api_get_member($payload->mb_id);
        }
        
        // 폴백
        return sql_fetch("select * from " . get_table_name('member') . " where mb_id = '{$payload->mb_id}' ");
    } catch (Exception $e) {
        if ($e->getMessage() === 'Expired token') {
            return auth_refresh_access_token();
        }
        return auth_get_guest_member();
    }
}

/**
 * Refresh Token으로 Access Token 갱신
 */
function auth_refresh_access_token() {
    $refreshToken = $_COOKIE[auth_get_cookie_name('refresh')] ?? null;
    if (!$refreshToken) return auth_get_guest_member();

    try {
        if (!class_exists('JWT')) require_once __DIR__ . '/jwt.php';
        $jwt = new JWT();
        $refreshKey = function_exists('config') ? config('jwt.refresh_token_key', '') : getenv('JWT_REFRESH_TOKEN_KEY');
        $payload = $jwt->decode($refreshToken, $refreshKey, ['HS256']);

        $member = null;
        if (function_exists('api_get_member')) {
            $member = api_get_member($payload->mb_id);
        } else {
            $member = sql_fetch("select * from " . get_table_name('member') . " where mb_id = '{$payload->mb_id}' ");
        }

        if ($member && $member['mb_id']) {
            $newAccessToken = auth_generate_access_token($member);
            $accessMtime = function_exists('config') ? config('jwt.access_mtime', 15) : (getenv('JWT_ACCESS_MTIME') ?: 15);
            auth_set_token_cookie($newAccessToken, 'access', $accessMtime * 60);
            return $member;
        }
    } catch (Exception $e) {}

    auth_clear_cookies();
    return auth_get_guest_member();
}

/**
 * Access Token 생성
 */
function auth_generate_access_token($member) {
    if (!class_exists('JWT')) require_once __DIR__ . '/jwt.php';
    $jwt = new JWT();
    $issuedAt = time();
    $accessMtime = function_exists('config') ? config('jwt.access_mtime', 15) : (getenv('JWT_ACCESS_MTIME') ?: 15);
    $expirationTime = $issuedAt + ($accessMtime * 60);

    $payload = [
        "mb_id" => $member['mb_id'],
        "iss" => getenv('APP_URL'),
        "aud" => getenv('JWT_AUDIENCE'),
        "iat" => $issuedAt,
        "exp" => $expirationTime
    ];

    $jwtKey = function_exists('config') ? config('jwt.access_token_key', '') : getenv('JWT_ACCESS_TOKEN_KEY');
    return $jwt->encode($payload, $jwtKey, "HS256");
}

/**
 * Refresh Token 생성
 */
function auth_generate_refresh_token($member, $fcm_token = '', $device_model = '', $os_version = '') {
    if (!class_exists('JWT')) require_once __DIR__ . '/jwt.php';
    $jwt = new JWT();
    $uuid = function_exists('gen_uuid_v4') ? gen_uuid_v4() : bin2hex(random_bytes(16));
    $issuedAt = time();
    $refreshDate = function_exists('config') ? config('jwt.refresh_date', 30) : (getenv('JWT_REFRESH_DATE') ?: 30);
    $expirationTime = $issuedAt + ($refreshDate * 86400);

    $payload = [
        "id" => $uuid,
        "mb_id" => $member['mb_id'],
        "iat" => $issuedAt,
        "exp" => $expirationTime
    ];

    $refreshKey = function_exists('config') ? config('jwt.refresh_token_key', '') : getenv('JWT_REFRESH_TOKEN_KEY');
    $token = $jwt->encode($payload, $refreshKey, "HS256");

    if (function_exists('set_refresh_token')) {
        set_refresh_token($token, $member['mb_id'], $uuid, $_SERVER['HTTP_USER_AGENT'] ?? '', $fcm_token, $device_model, $os_version);
    }

    return $token;
}

/**
 * 토큰 쿠키 설정
 */
function auth_set_token_cookie($token, $type = 'access', $expiresIn = 900) {
    $name = auth_get_cookie_name($type);
    $options = [
        'expires' => time() + $expiresIn,
        'path' => '/',
        'domain' => getenv('APP_COOKIE_DOMAIN') ?: '',
        'secure' => (bool)getenv('APP_HTTPS_ONLY'),
        'httponly' => true,
        'samesite' => getenv('APP_COOKIE_SAMESITE') ?: 'Lax'
    ];
    setcookie($name, $token, $options);
}

/**
 * 쿠키 삭제 (로그아웃)
 */
function auth_clear_cookies() {
    $options = [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => getenv('APP_COOKIE_DOMAIN') ?: '',
        'secure' => (bool)getenv('APP_HTTPS_ONLY'),
        'httponly' => true,
        'samesite' => getenv('APP_COOKIE_SAMESITE') ?: 'Lax'
    ];
    setcookie(auth_get_cookie_name('access'), '', $options);
    setcookie(auth_get_cookie_name('refresh'), '', $options);
}

/**
 * 게스트 회원 정보
 */
function auth_get_guest_member() {
    return [
        'mb_id' => '',
        'mb_level' => 1,
        'mb_name' => 'Guest'
    ];
}

/**
 * 인증 필수 체크
 */
function auth_require_auth() {
    $member = auth_get_member();
    if (!$member['mb_id']) {
        if (function_exists('response_unauthorized')) {
            response_unauthorized();
        } else {
            json_return(null, 401, '00002', '로그인이 필요합니다.');
        }
    }
    return $member;
}

/**
 * 권한 레벨 체크
 */
function auth_require_level($level) {
    $member = auth_require_auth();
    if ($member['mb_level'] < $level) {
        if (function_exists('response_forbidden')) {
            response_forbidden();
        } else {
            json_return(null, 403, '00003', '권한이 없습니다.');
        }
    }
    return $member;
}

/**
 * 토큰 가져오기 (내부용)
 */
function _auth_get_token() {
    $token = $_COOKIE[auth_get_cookie_name('access')] ?? null;
    if (!$token) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader);
        }
    }
    return $token;
}

/**
 * Legacy aliases for _common.php compatibility
 */
function get_jwt_member() { return auth_get_member(); }
function set_access_token_cookie($token, $expiresIn = 900) { auth_set_token_cookie($token, 'access', $expiresIn); }
function set_refresh_token_cookie($token, $expiresIn = 2592000) { auth_set_token_cookie($token, 'refresh', $expiresIn); }
function clear_auth_cookies() { auth_clear_cookies(); }

/**
 * Auth 클래스 - JWT 토큰 관리 (정적 메서드)
 */
class Auth
{
    public static function generateAccessToken($member)
    {
        return auth_generate_access_token($member);
    }

    public static function generateRefreshToken($member, $fcm_token = '', $device_model = '', $os_version = '')
    {
        return auth_generate_refresh_token($member, $fcm_token, $device_model, $os_version);
    }

    public static function setAccessTokenCookie($token, $expiresIn = 900)
    {
        auth_set_token_cookie($token, 'access', $expiresIn);
    }

    public static function setRefreshTokenCookie($token, $expiresIn = 2592000)
    {
        auth_set_token_cookie($token, 'refresh', $expiresIn);
    }

    public static function verifyAccessToken($token)
    {
        if (!class_exists('JWT')) {
            require_once __DIR__ . '/jwt.php';
        }
        
        try {
            $jwt = new JWT();
            $jwtKey = function_exists('config') ? config('jwt.access_token_key', '') : getenv('JWT_ACCESS_TOKEN_KEY');
            $payload = $jwt->decode($token, $jwtKey, ['HS256']);
            
            if ($payload->exp < time()) {
                return ['valid' => false, 'reason' => 'expired'];
            }
            
            return ['valid' => true, 'payload' => $payload];
        } catch (Exception $e) {
            return ['valid' => false, 'reason' => $e->getMessage()];
        }
    }
}
