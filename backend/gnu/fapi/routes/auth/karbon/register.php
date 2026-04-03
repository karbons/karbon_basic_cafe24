<?php
/**
 * Karbon Auth - 회원가입
 * POST /api/auth/karbon/register
 * 
 * 지원 가입 타입:
 * - email: 이메일 + 비밀번호
 * - phone: 휴대폰 번호 + OTP (비밀번호는 자동 생성 또는 선택)
 * 
 * mb_id는 서버에서 UUID로 자동 생성됨 (프론트에서 전달할 필요 없음)
 * 고유성은 이메일 또는 휴대폰 번호로 검증함
 */
function POST()
{
    global $g5, $config;

    $data = json_decode(file_get_contents('php://input'), true);

    $mb_name = isset($data['mb_name']) ? trim($data['mb_name']) : '';
    $mb_nick = isset($data['mb_nick']) ? trim($data['mb_nick']) : $mb_name;
    $mb_password = isset($data['mb_password']) ? trim($data['mb_password']) : '';
    $mb_email = isset($data['mb_email']) ? trim($data['mb_email']) : '';
    $mb_hp = isset($data['mb_hp']) ? trim($data['mb_hp']) : '';
    $mb_otp = isset($data['mb_otp']) ? trim($data['mb_otp']) : '';
    $captcha_key = isset($data['captcha_key']) ? trim($data['captcha_key']) : '';

    $mb_mailling = isset($data['mb_mailling']) ? (int) $data['mb_mailling'] : 1;
    $mb_open = isset($data['mb_open']) ? (int) $data['mb_open'] : 1;

    if (empty($mb_name)) {
        json_return(null, 400, '00002', '이름을 입력해주세요.');
    }

    if (empty($mb_email) && empty($mb_hp)) {
        json_return(null, 400, '00003', '이메일 또는 휴대폰 번호를 입력해주세요.');
    }

    if (!empty($mb_email) && empty($mb_hp) && empty($mb_password)) {
        json_return(null, 400, '00004', '비밀번호를 입력해주세요.');
    }

    if (!empty($mb_hp) && empty($mb_email)) {
        if (empty($mb_otp)) {
            json_return(null, 400, '00005', '인증번호를 입력해주세요.');
        }
    }

    require_once __DIR__ . '/../../../shared/captcha.php';
    if (!captcha_verify($captcha_key)) {
        json_return(null, 400, '00007', '자동등록방지 숫자가 틀렸습니다.');
    }

    if (!empty($mb_email)) {
        $row = sqlx::query("select count(*) as cnt from {$g5['member_table']} where mb_email = ?")
            ->bind($mb_email)
            ->fetch_optional();

        if ($row['cnt']) {
            json_return(null, 409, '00008', '이미 등록된 이메일입니다.');
        }
    }

    if (!empty($mb_hp)) {
        $hp_clean = preg_replace('/[^0-9]/', '', $mb_hp);
        $row = sqlx::query("select count(*) as cnt from {$g5['member_table']} where REPLACE(REPLACE(mb_hp, '-', ''), ' ', '') = ?")
            ->bind($hp_clean)
            ->fetch_optional();

        if ($row['cnt']) {
            json_return(null, 409, '00009', '이미 등록된 휴대폰 번호입니다.');
        }
    }

    function generate_member_id()
    {
        $prefix = date('ymd');
        $random = substr(md5(uniqid(mt_rand(), true)), 0, 10);
        return $prefix . $random;
    }

    $mb_id = '';
    $max_attempts = 5;
    for ($i = 0; $i < $max_attempts; $i++) {
        $mb_id = generate_member_id();
        $row = sqlx::query("select count(*) as cnt from {$g5['member_table']} where mb_id = ?")
            ->bind($mb_id)
            ->fetch_optional();

        if (!$row['cnt']) {
            break;
        }
        if ($i === $max_attempts - 1) {
            json_return(null, 500, '00010', '회원 ID 생성에 실패했습니다. 다시 시도해주세요.');
        }
    }

    $mb_password_hash = get_encrypt_string($mb_password);
    $mb_datetime = G5_TIME_YMDHIS;
    $mb_ip = $_SERVER['REMOTE_ADDR'];
    $mb_level = (int) ($config['cf_register_level'] ?? 1);

    $sql = "INSERT INTO {$g5['member_table']} SET 
        mb_id = ?, mb_password = ?, mb_name = ?, mb_nick = ?,
        mb_email = ?, mb_hp = ?, mb_level = ?, mb_mailling = ?,
        mb_open = ?, mb_today_login = ?, mb_datetime = ?, mb_ip = ?,
        mb_email_certify = ?, mb_signature = '', mb_memo = '', 
        mb_profile = '', mb_agree_log = '', mb_lost_certify = ''";
    
    sqlx::query($sql)
        ->bind($mb_id)
        ->bind($mb_password_hash)
        ->bind($mb_name)
        ->bind($mb_nick)
        ->bind($mb_email)
        ->bind($mb_hp)
        ->bind($mb_level)
        ->bind($mb_mailling)
        ->bind($mb_open)
        ->bind($mb_datetime)
        ->bind($mb_datetime)
        ->bind($mb_ip)
        ->bind($mb_datetime)
        ->execute();

    json_return([
        'mb_id' => $mb_id,
        'mb_name' => $mb_name
    ], 200, '00000', '회원가입이 완료되었습니다.');
}
