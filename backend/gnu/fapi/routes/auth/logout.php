<?php
// POST /api/auth/logout
function POST() {
    require_once __DIR__ . '/../../shared/auth.php';
    
    global $member;
    
    // Refresh Token DB에서 삭제
    if ($member['mb_id']) {
        delete_refresh_token($member['mb_id']);
    }
    
    // 쿠키 삭제
    auth_clear_cookies();
    
    $options = [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => getenv('APP_COOKIE_DOMAIN') ?: '',
        'secure' => (bool)getenv('APP_HTTPS_ONLY'),
        'httponly' => false,
        'samesite' => getenv('APP_COOKIE_SAMESITE') ?: 'Lax'
    ];
    setcookie('sapi_csrf_token', '', $options);
    setcookie('sapi_device_id', '', $options);
    
    json_return(null, 200, '00000', '로그아웃 성공');
}

