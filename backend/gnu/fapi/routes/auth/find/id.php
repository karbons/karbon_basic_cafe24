<?php
// POST /api/auth/find/id
function POST()
{
    global $g5;

    $data = json_decode(file_get_contents('php://input'), true);
    $mb_email = isset($data['mb_email']) ? trim($data['mb_email']) : '';

    if (!$mb_email) {
        json_return(null, 400, '00001', '이메일을 입력해주세요.');
    }

    $sql = " select mb_id, mb_datetime from {$g5['member_table']} where mb_email = ? ";
    $mb = sqlx::query($sql)
        ->bind($mb_email)
        ->fetch_optional();

    if (!$mb) {
        json_return(null, 404, '00002', '입력하신 이메일로 등록된 회원이 없습니다.');
    }

    // 아이디 마스킹 (앞 3글자만 보여주고 나머지는 *)
    $mb_id_len = strlen($mb['mb_id']);
    $masked_id = substr($mb['mb_id'], 0, 3) . str_repeat('*', max(0, $mb_id_len - 3));

    // 가입일 포맷
    $reg_date = date("Y.m.d", strtotime($mb['mb_datetime']));

    json_return([
        'mb_id' => $masked_id,
        'reg_date' => $reg_date
    ], 200, '00000', '아이디를 찾았습니다.');
}
