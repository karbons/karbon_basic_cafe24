<?php
// GET /api/member/profile
function GET()
{
    global $member;

    // 로그인 체크 - 비회원일 경우 401 대신 null 반환 (콘솔 에러 방지)
    if (!$member['mb_id']) {
        // json_return(null, 401, '00002', '로그인이 필요합니다.');
        json_return(null, 200, '00000', '비회원');
    }

    // 회원 정보 반환
    json_return([
        'mb_id' => $member['mb_id'],
        'mb_name' => $member['mb_name'],
        'mb_nick' => $member['mb_nick'],
        'mb_level' => $member['mb_level'],
        'mb_point' => $member['mb_point'],
        'mb_email' => $member['mb_email'],
        'mb_homepage' => $member['mb_homepage'],
        'mb_tel' => $member['mb_tel'],
        'mb_hp' => $member['mb_hp'],
        'mb_zip' => $member['mb_zip1'] . $member['mb_zip2'],
        'mb_addr1' => $member['mb_addr1'],
        'mb_addr2' => $member['mb_addr2'],
        'mb_signature' => $member['mb_signature'],
        'mb_profile' => $member['mb_profile'],
        'mb_mailling' => $member['mb_mailling'],
        'mb_open' => $member['mb_open'],
        'mb_1' => $member['mb_1']
    ], 200, '00000');
}

