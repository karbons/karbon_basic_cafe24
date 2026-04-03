<?php
/**
 * Karbon Auth - 로그인
 * POST /api/auth/karbon/login
 * 
 * 지원 로그인 타입:
 * - email: 이메일 + 비밀번호
 * - phone: 휴대폰 번호 + OTP
 */
function POST()
{
    require_once __DIR__ . '/../../../shared/auth.php';
    require_once __DIR__ . '/../../../shared/firebase.php';
    global $g5;

    $data = json_decode(file_get_contents('php://input'), true);

    $login_type = $data['login_type'] ?? 'email';
    $fcm_token = $data['fcm_token'] ?? '';
    $device_model = $data['device_model'] ?? '';
    $os_version = $data['os_version'] ?? '';
    $auto_login = $data['auto_login'] ?? false;

    $mb = null;

    if ($login_type === 'email') {
        // 이메일 + 비밀번호 로그인
        $mb_email = $data['mb_email'] ?? ($data['mb_id'] ?? '');
        $mb_password = $data['mb_password'] ?? '';

        if (empty($mb_email) || empty($mb_password)) {
            json_return(null, 200, '00001', '이메일과 비밀번호를 입력해주세요.');
        }

        // 이메일로 회원 찾기
        $mb = sqlx::query("SELECT * FROM {$g5['member_table']} WHERE mb_email = ? LIMIT 1")
            ->bind($mb_email)
            ->fetch_optional();

        // 아이디로도 찾아보기 (하위 호환)
        if (!$mb) {
            $mb = sqlx::query("SELECT * FROM {$g5['member_table']} WHERE mb_id = ? LIMIT 1")
                ->bind($mb_email)
                ->fetch_optional();
        }

        if (!$mb) {
            json_return(null, 200, '00001', '가입된 이메일이 아닙니다.');
        }

        // 비밀번호 검증
        if (!login_password_check($mb, $mb_password, $mb['mb_password'])) {
            run_event('password_is_wrong', 'login', $mb);
            json_return(null, 200, '00001', '비밀번호가 틀립니다.');
        }

    } elseif ($login_type === 'phone') {
        // 휴대폰 + OTP 로그인
        $mb_hp = $data['mb_hp'] ?? '';
        $mb_otp = $data['mb_otp'] ?? '';

        if (empty($mb_hp) || empty($mb_otp)) {
            json_return(null, 200, '00001', '휴대폰 번호와 인증번호를 입력해주세요.');
        }

        // TODO: OTP 검증 로직 (외부 SMS 서비스 연동 필요)
        // 여기서는 간단 예시로 세션 또는 DB 기반 OTP 확인
        // 실제 구현 시에는 otp_verify($mb_hp, $mb_otp) 와 같은 함수 사용
        // if (!otp_verify($mb_hp, $mb_otp)) {
        //     json_return(null, 200, '00001', '인증번호가 올바르지 않습니다.');
        // }

        // 휴대폰으로 회원 찾기
        $hp_clean = preg_replace('/[^0-9]/', '', $mb_hp);
        $mb = sqlx::query("SELECT * FROM {$g5['member_table']} WHERE REPLACE(REPLACE(mb_hp, '-', ''), ' ', '') = ? LIMIT 1")
            ->bind($hp_clean)
            ->fetch_optional();

        if (!$mb) {
            json_return(null, 200, '00001', '등록된 휴대폰 번호가 아닙니다. 회원가입을 진행해주세요.');
        }

    } else {
        json_return(null, 400, '00002', '지원하지 않는 로그인 타입입니다.');
    }

    // 공통 검증: 차단, 탈퇴, 메일인증
    if ($mb['mb_intercept_date'] && $mb['mb_intercept_date'] <= date("Ymd", G5_SERVER_TIME)) {
        $date = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1년 \\2월 \\3일", $mb['mb_intercept_date']);
        json_return(null, 200, '00001', '회원님의 아이디는 접근이 금지되어 있습니다. 처리일 : ' . $date);
    }

    if ($mb['mb_leave_date'] && $mb['mb_leave_date'] <= date("Ymd", G5_SERVER_TIME)) {
        $date = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1년 \\2월 \\3일", $mb['mb_leave_date']);
        json_return(null, 200, '00001', '탈퇴한 아이디이므로 접근하실 수 없습니다. 탈퇴일 : ' . $date);
    }

    if (is_use_email_certify() && !preg_match("/[1-9]/", $mb['mb_email_certify'])) {
        json_return(null, 200, '00001', $mb['mb_email'] . ' 메일로 메일인증을 받으셔야 로그인 가능합니다.');
    }

    run_event('login_session_before', $mb, false);

    // JWT 토큰 생성
    $accessToken = Auth::generateAccessToken($mb);
    $refreshToken = Auth::generateRefreshToken($mb, $fcm_token, $device_model, $os_version);

    // HTTP Only Cookie로 설정
    $accessMtime = Config::get('jwt.access_mtime', 15);
    $refreshDate = Config::get('jwt.refresh_date', 30);
    Auth::setAccessTokenCookie($accessToken, $accessMtime * 60);
    Auth::setRefreshTokenCookie($refreshToken, $refreshDate * 86400);

    // Firebase Custom Token 생성 (채팅용)
    $firebaseToken = null;
    if (firebase_is_enabled()) {
        $firebaseToken = firebase_create_custom_token($mb['mb_id'], [
            'mb_nick' => $mb['mb_nick'],
            'mb_level' => $mb['mb_level']
        ]);
    }

    // 응답
    json_return([
        'mb' => [
            'mb_id' => $mb['mb_id'],
            'mb_name' => $mb['mb_name'],
            'mb_nick' => $mb['mb_nick'],
            'mb_email' => $mb['mb_email'],
            'mb_level' => $mb['mb_level'],
            'mb_point' => $mb['mb_point'],
            'mb_memo_cnt' => $mb['mb_memo_cnt'] ?? 0,
            'mb_scrap_cnt' => $mb['mb_scrap_cnt'] ?? 0
        ],
        'firebase_token' => $firebaseToken
    ], 200, '00000', '로그인 성공');
}
