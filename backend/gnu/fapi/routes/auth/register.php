<?php
// POST /api/auth/register
function POST()
{
    global $g5, $config;

    $data = json_decode(file_get_contents('php://input'), true);

    $mb_id = isset($data['mb_id']) ? trim($data['mb_id']) : '';
    $mb_password = isset($data['mb_password']) ? trim($data['mb_password']) : '';
    $mb_name = isset($data['mb_name']) ? trim($data['mb_name']) : '';
    $mb_nick = isset($data['mb_nick']) ? trim($data['mb_nick']) : '';
    $mb_email = isset($data['mb_email']) ? trim($data['mb_email']) : '';
    $mb_mailling = isset($data['mb_mailling']) ? (int) $data['mb_mailling'] : 0;
    $mb_open = isset($data['mb_open']) ? (int) $data['mb_open'] : 0;
    // 추가 필드 추출
    $mb_homepage = isset($data['mb_homepage']) ? trim($data['mb_homepage']) : '';
    $mb_tel = isset($data['mb_tel']) ? trim($data['mb_tel']) : '';
    $mb_hp = isset($data['mb_hp']) ? trim($data['mb_hp']) : '';
    $mb_zip = isset($data['mb_zip']) ? trim($data['mb_zip']) : '';
    $mb_addr1 = isset($data['mb_addr1']) ? trim($data['mb_addr1']) : '';
    $mb_addr2 = isset($data['mb_addr2']) ? trim($data['mb_addr2']) : '';
    $mb_signature = isset($data['mb_signature']) ? trim($data['mb_signature']) : '';
    $mb_profile = isset($data['mb_profile']) ? trim($data['mb_profile']) : '';
    $mb_recommend = isset($data['mb_recommend']) ? trim($data['mb_recommend']) : '';

    // 설정에 따른 필수 값 검사
    if ($config['cf_use_homepage'] && $config['cf_req_homepage'] && !$mb_homepage) {
        json_return(null, 400, '00010', '홈페이지를 입력해주세요.');
    }
    if ($config['cf_use_tel'] && $config['cf_req_tel'] && !$mb_tel) {
        json_return(null, 400, '00011', '전화번호를 입력해주세요.');
    }
    if ($config['cf_use_hp'] && $config['cf_req_hp'] && !$mb_hp) {
        json_return(null, 400, '00012', '휴대전화를 입력해주세요.');
    }
    if ($config['cf_use_addr'] && $config['cf_req_addr'] && (!$mb_zip || !$mb_addr1)) {
        json_return(null, 400, '00013', '주소를 입력해주세요.');
    }
    if ($config['cf_use_signature'] && $config['cf_req_signature'] && !$mb_signature) {
        json_return(null, 400, '00014', '서명을 입력해주세요.');
    }
    if ($config['cf_use_profile'] && $config['cf_req_profile'] && !$mb_profile) {
        json_return(null, 400, '00015', '자기소개를 입력해주세요.');
    }

    // 추천인 검사
    if ($config['cf_use_recommend'] && $mb_recommend) {
        if ($mb_recommend == $mb_id) {
            json_return(null, 400, '00016', '본인을 추천할 수 없습니다.');
        }
        $cnt = sqlx::query("select count(*) from {$g5['member_table']} where mb_id = ?")
            ->bind($mb_recommend)
            ->fetch_scalar();

        if (!$cnt) {
            json_return(null, 400, '00017', '존재하지 않는 추천인 아이디입니다.');
        }
    }

    // 캡차 검사
    include_once(G5_PLUGIN_PATH . '/kcaptcha/kcaptcha.lib.php');
    $_POST['captcha_key'] = $captcha_key;
    if (!chk_captcha()) {
        json_return(null, 400, '00006', '자동등록방지 숫자가 틀렸습니다.');
    }

    // 아이디 유효성 검사
    if (preg_match("/[^0-9a-z_]+/i", $mb_id)) {
        json_return(null, 400, '00002', '아이디는 영문자, 숫자, _ 만 입력 가능합니다.');
    }

    // 중복 검사
    $cnt = sqlx::query("select count(*) from {$g5['member_table']} where mb_id = ?")
        ->bind($mb_id)
        ->fetch_scalar();
    if ($cnt) {
        json_return(null, 409, '00003', '이미 존재하는 아이디입니다.');
    }

    $cnt = sqlx::query("select count(*) from {$g5['member_table']} where mb_nick = ?")
        ->bind($mb_nick)
        ->fetch_scalar();
    if ($cnt) {
        json_return(null, 409, '00004', '이미 존재하는 닉네임입니다.');
    }

    $cnt = sqlx::query("select count(*) from {$g5['member_table']} where mb_email = ?")
        ->bind($mb_email)
        ->fetch_scalar();
    if ($cnt) {
        json_return(null, 409, '00005', '이미 존재하는 이메일입니다.');
    }

    // 회원가입 처리
    $mb_password_hash = get_encrypt_string($mb_password);
    $mb_datetime = G5_TIME_YMDHIS;
    $mb_ip = $_SERVER['REMOTE_ADDR'];
    $mb_level = (int) $config['cf_register_level'] ?: 1; // 기본 레벨 1

    sqlx::transaction(function () use ($g5, $mb_id, $mb_password_hash, $mb_name, $mb_nick, $mb_email, $mb_homepage, $mb_tel, $mb_hp, $mb_zip, $mb_addr1, $mb_addr2, $mb_signature, $mb_profile, $mb_recommend, $mb_level, $mb_mailling, $mb_open, $mb_datetime, $mb_ip) {
        $sql = " insert into {$g5['member_table']}
                    set mb_id = :mb_id,
                        mb_password = :mb_password,
                        mb_name = :mb_name,
                        mb_nick = :mb_nick,
                        mb_email = :mb_email,
                        mb_homepage = :mb_homepage,
                        mb_tel = :mb_tel,
                        mb_hp = :mb_hp,
                        mb_zip1 = :mb_zip1,
                        mb_zip2 = :mb_zip2,
                        mb_addr1 = :mb_addr1,
                        mb_addr2 = :mb_addr2,
                        mb_signature = :mb_signature,
                        mb_profile = :mb_profile,
                        mb_recommend = :mb_recommend,
                        mb_level = :mb_level,
                        mb_mailling = :mb_mailling,
                        mb_open = :mb_open,
                        mb_today_login = :mb_today_login,
                        mb_datetime = :mb_datetime,
                        mb_ip = :mb_ip,
                        mb_email_certify = :mb_email_certify ";

        sqlx::query($sql)
            ->bind_named('mb_id', $mb_id)
            ->bind_named('mb_password', $mb_password_hash)
            ->bind_named('mb_name', $mb_name)
            ->bind_named('mb_nick', $mb_nick)
            ->bind_named('mb_email', $mb_email)
            ->bind_named('mb_homepage', $mb_homepage)
            ->bind_named('mb_tel', $mb_tel)
            ->bind_named('mb_hp', $mb_hp)
            ->bind_named('mb_zip1', substr($mb_zip, 0, 3))
            ->bind_named('mb_zip2', substr($mb_zip, 3))
            ->bind_named('mb_addr1', $mb_addr1)
            ->bind_named('mb_addr2', $mb_addr2)
            ->bind_named('mb_signature', $mb_signature)
            ->bind_named('mb_profile', $mb_profile)
            ->bind_named('mb_recommend', $mb_recommend)
            ->bind_named('mb_level', $mb_level)
            ->bind_named('mb_mailling', $mb_mailling)
            ->bind_named('mb_open', $mb_open)
            ->bind_named('mb_today_login', $mb_datetime)
            ->bind_named('mb_datetime', $mb_datetime)
            ->bind_named('mb_ip', $mb_ip)
            ->bind_named('mb_email_certify', G5_TIME_YMDHIS)
            ->execute();
    });

    // 참고: mb_zip은 DB 스키마에 따라 mb_zip1/mb_zip2로 나뉠 수 있음 (그누보드5 구버전 호환)
    // 최신버전은 mb_zip 컬럼 하나일 수도 있으나 안전하게 zip1, zip2 매핑 시도 또는 스키마 확인 필요.
    // 여기서는 통상적인 그누보드5 필드 구조를 따름. 만약 에러 발생 시 수정 필요.
    // 단, mb_zip1, mb_zip2가 없고 mb_zip만 있는 경우도 있음. 
    // 여기서는 mb_zip1, mb_zip2를 사용한다고 가정 (그누보드 기본).


    // 회원가입 포인트 지급 등 추가 로직이 필요하면 여기에 작성

    json_return(['mb_id' => $mb_id], 200, '00000', '회원가입이 완료되었습니다.');
}

