<?php
// POST /api/member/password_check
function POST()
{
    global $member;

    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $password = isset($data['mb_password']) ? trim($data['mb_password']) : '';

    if (!$password) {
        json_return(null, 400, '00003', '비밀번호를 입력해주세요.');
    }

    if (!check_password($password, $member['mb_password'])) {
        json_return(null, 400, '00004', '비밀번호가 틀립니다.');
    }

    json_return(null, 200, '00000', '비밀번호가 확인되었습니다.');
}
