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
    
    json_return(null, 200, '00000', '로그아웃 성공');
}

