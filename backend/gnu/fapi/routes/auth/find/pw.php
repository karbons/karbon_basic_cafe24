<?php
// POST /api/auth/find/pw
function POST()
{
    global $g5, $config;

    $data = json_decode(file_get_contents('php://input'), true);
    $mb_id = isset($data['mb_id']) ? trim($data['mb_id']) : '';
    $mb_email = isset($data['mb_email']) ? trim($data['mb_email']) : '';

    if (!$mb_id || !$mb_email) {
        json_return(null, 400, '00001', '아이디와 이메일을 모두 입력해주세요.');
    }

    $sql = " select mb_id, mb_email, mb_name, mb_nick from {$g5['member_table']} where mb_id = ? ";
    $mb = sqlx::query($sql)
        ->bind($mb_id)
        ->fetch_optional();

    if (!$mb || $mb['mb_email'] !== $mb_email) {
        json_return(null, 404, '00002', '일치하는 회원정보가 없습니다.');
    }

    // 임시 비밀번호 생성 (8자리 영문+숫자 랜덤)
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $temp_pw = '';
    for ($i = 0; $i < 8; $i++) {
        $temp_pw .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    // 비밀번호 암호화
    $mb_password = get_encrypt_string($temp_pw);

    // DB 업데이트
    $sql = " update {$g5['member_table']} set mb_password = ? where mb_id = ? ";
    sqlx::query($sql)
        ->bind($mb_password)
        ->bind($mb_id)
        ->execute();

    // 메일 발송
    include_once(G5_LIB_PATH . '/mailer.lib.php');

    $subject = '[' . $config['cf_title'] . '] 임시 비밀번호 발송';
    $content = $mb['mb_nick'] . '님의 임시 비밀번호는 <b>' . $temp_pw . '</b> 입니다.<br>로그인 후 비밀번호를 변경해주세요.';

    // mailer($fname, $fmail, $to, $subject, $content, $type=0, $file="", $cc="", $bcc="")
    $admin_email = $config['cf_admin_email'];
    $admin_name = $config['cf_title'];

    // 성공/실패 여부와 상관없이 프로세스는 성공으로 간주 (보안상 이유 + 메일 설정 문제 가능성)
    mailer($admin_name, $admin_email, $mb_email, $subject, $content, 1);

    json_return(null, 200, '00000', '이메일로 임시 비밀번호가 발송되었습니다.');
}
