<?php
// DELETE /api/member/leave
function DELETE() {
    global $member;
    
    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    
    // 그누보드 회원탈퇴 로직 활용
    // 실제 구현은 그누보드의 member_leave.php 참고
    json_return(null, 200, '00001', '회원탈퇴 기능은 준비 중입니다.');
}

