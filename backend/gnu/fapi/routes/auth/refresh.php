<?php
// POST /api/auth/refresh
function POST() {
    require_once __DIR__ . '/../../shared/auth.php';
    require_once __DIR__ . '/../../shared/Auth.php';
    require_once __DIR__ . '/../../shared/jwt.php';
    
    $refreshToken = $_COOKIE['sapi_refresh_token'] ?? null;
    
    if (!$refreshToken) {
        json_return(null, 401, '00002', 'Refresh Token이 없습니다.');
    }
    
    try {
        $jwt = new JWT();
        $refreshKey = config('jwt.refresh_token_key', '');
        $payload = $jwt->decode($refreshToken, $refreshKey, ['HS256']);
        
        // Refresh Token이 유효한지 DB에서 확인
        $tokenData = get_refresh_token($payload->id);
        if (!$tokenData || $tokenData['mb_id'] !== $payload->mb_id) {
            json_return(null, 401, '00002', '유효하지 않은 Refresh Token입니다.');
        }
        
        // 회원 정보 가져오기
        $member = api_get_member($tokenData['mb_id']);
        if (!$member) {
            json_return(null, 401, '00002', '회원 정보를 찾을 수 없습니다.');
        }
        
        // 새 Access Token 생성
        $newAccessToken = Auth::generateAccessToken($member);
        
        $csrfToken = bin2hex(random_bytes(32));
        $cookieDomain = getenv('APP_COOKIE_DOMAIN') ?: '';
        setcookie('sapi_csrf_token', $csrfToken, [
            'expires' => 0,
            'path' => '/',
            'domain' => $cookieDomain,
            'secure' => (bool)getenv('APP_HTTPS_ONLY'),
            'httponly' => false,
            'samesite' => 'Strict'
        ]);
        
        json_return([
            'mb' => [
                'mb_id' => $member['mb_id'],
                'mb_name' => $member['mb_name'],
                'mb_nick' => $member['mb_nick'],
                'mb_level' => $member['mb_level'],
                'mb_point' => $member['mb_point']
            ],
            'access_token' => $newAccessToken,
            'csrf_token' => $csrfToken
        ], 200, '00000', '토큰이 갱신되었습니다.');
        
    } catch (Exception $e) {
        // Refresh Token도 만료됨
        auth_clear_cookies();
        json_return(null, 401, '00002', 'Refresh Token이 만료되었습니다. 다시 로그인해주세요.');
    }
}

