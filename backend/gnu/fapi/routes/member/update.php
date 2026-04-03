<?php
// PUT /api/member/update
function PUT()
{
    global $g5, $member, $config, $is_admin;

    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    // 캡차 검사
    include_once(G5_PLUGIN_PATH . '/kcaptcha/kcaptcha.lib.php');
    if (isset($data['captcha_key']) && $data['captcha_key']) {
        $_POST['captcha_key'] = $data['captcha_key'];
        if (!chk_captcha()) {
            json_return(null, 400, '00006', '자동등록방지 숫자가 틀렸습니다.');
        }
    } else {
        json_return(null, 400, '00006', '자동등록방지 문자열을 입력해주세요.');
    }

    $mb_id = $member['mb_id'];
    $mb_password = isset($data['mb_password']) ? trim($data['mb_password']) : '';
    $mb_nick = isset($data['mb_nick']) ? trim($data['mb_nick']) : '';
    $mb_email = isset($data['mb_email']) ? trim($data['mb_email']) : '';
    $mb_homepage = isset($data['mb_homepage']) ? trim($data['mb_homepage']) : '';
    $mb_tel = isset($data['mb_tel']) ? trim($data['mb_tel']) : '';
    $mb_hp = isset($data['mb_hp']) ? trim($data['mb_hp']) : '';
    $mb_zip = isset($data['mb_zip']) ? trim($data['mb_zip']) : '';
    $mb_addr1 = isset($data['mb_addr1']) ? trim($data['mb_addr1']) : '';
    $mb_addr2 = isset($data['mb_addr2']) ? trim($data['mb_addr2']) : '';
    $mb_signature = isset($data['mb_signature']) ? trim($data['mb_signature']) : '';
    $mb_profile = isset($data['mb_profile']) ? trim($data['mb_profile']) : '';
    $mb_mailling = isset($data['mb_mailling']) ? (int) $data['mb_mailling'] : 0;
    $mb_open = isset($data['mb_open']) ? (int) $data['mb_open'] : 0;

    // 채팅 사용 여부 (mb_1)
    $mb_1 = isset($data['mb_1']) ? trim($data['mb_1']) : '';

    // 닉네임 변경 제한
    if ($mb_nick !== $member['mb_nick']) {
        if ($config['cf_nick_modify'] > 0) {
            $nick_date = $member['mb_nick_date'];
            $nick_modify_date = date("Y-m-d", G5_SERVER_TIME - ($config['cf_nick_modify'] * 86400));
            if ($nick_date > $nick_modify_date) {
                json_return(null, 400, '00020', '닉네임은 ' . $config['cf_nick_modify'] . '일마다 변경할 수 있습니다.');
            }
        }

        // 닉네임 중복 검사
        $cnt = sqlx::query("SELECT COUNT(*) FROM {$g5['member_table']} WHERE mb_nick = :mb_nick AND mb_id <> :mb_id")
            ->bind_named('mb_nick', $mb_nick)
            ->bind_named('mb_id', $mb_id)
            ->fetch_scalar();

        if ($cnt > 0) {
            json_return(null, 409, '00004', '이미 존재하는 닉네임입니다.');
        }
    }

    // 이메일 중복 검사
    if ($mb_email !== $member['mb_email']) {
        $cnt = sqlx::query("SELECT COUNT(*) FROM {$g5['member_table']} WHERE mb_email = :mb_email AND mb_id <> :mb_id")
            ->bind_named('mb_email', $mb_email)
            ->bind_named('mb_id', $mb_id)
            ->fetch_scalar();

        if ($cnt > 0) {
            json_return(null, 409, '00005', '이미 존재하는 이메일입니다.');
        }
    }

    $sets = [
        'mb_nick' => $mb_nick,
        'mb_email' => $mb_email,
        'mb_homepage' => $mb_homepage,
        'mb_tel' => $mb_tel,
        'mb_hp' => $mb_hp,
        'mb_zip1' => substr($mb_zip, 0, 3),
        'mb_zip2' => substr($mb_zip, 3),
        'mb_addr1' => $mb_addr1,
        'mb_addr2' => $mb_addr2,
        'mb_signature' => $mb_signature,
        'mb_profile' => $mb_profile,
        'mb_mailling' => $mb_mailling,
        'mb_open' => $mb_open,
        'mb_1' => $mb_1
    ];

    if ($mb_password) {
        $sets['mb_password'] = get_encrypt_string($mb_password);
    }

    if ($mb_nick !== $member['mb_nick']) {
        $sets['mb_nick_date'] = G5_TIME_YMD;
    }

    $setParts = [];
    foreach ($sets as $field => $value) {
        $setParts[] = "{$field} = :{$field}";
    }

    $sql = "UPDATE {$g5['member_table']} SET " . implode(', ', $setParts) . " WHERE mb_id = :mb_id";

    $query = sqlx::query($sql);
    foreach ($sets as $field => $value) {
        $query->bind_named($field, $value);
    }
    $query->bind_named('mb_id', $mb_id)
        ->execute();

    // 성공 시 변경된 회원정보 로드
    // sqlx 트랜잭션/연결은 이미 커밋되었으므로 legacy 함수도 변경사항을 볼 수 있음
    $mb = get_member($mb_id);

    // 비밀번호 등 민감정보 제거
    unset($mb['mb_password']);

    json_return(['mb' => $mb], 200, '00000', '회원정보가 수정되었습니다.');
}
