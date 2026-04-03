<?php
/**
 * Karbon Auth - OTP 관리
 * POST /api/auth/karbon/otp - OTP 전송
 * 
 * 요청 Body:
 * - hp: 휴대폰 번호
 * - purpose: 'login' | 'register' | '2fa'
 */
function POST()
{
    global $g5;

    $data = json_decode(file_get_contents('php://input'), true);

    $hp = isset($data['hp']) ? trim($data['hp']) : '';
    $purpose = isset($data['purpose']) ? trim($data['purpose']) : 'login';

    if (empty($hp)) {
        json_return(null, 400, '00001', '휴대폰 번호를 입력해주세요.');
    }

    // 휴대폰 번호 정규화
    $hp_clean = preg_replace('/[^0-9]/', '', $hp);
    if (strlen($hp_clean) < 10 || strlen($hp_clean) > 11) {
        json_return(null, 400, '00002', '올바른 휴대폰 번호를 입력해주세요.');
    }

    // 로그인 목적인 경우, 회원 존재 여부 확인
    if ($purpose === 'login') {
        $mb = sqlx::query("SELECT mb_id FROM {$g5['member_table']} WHERE REPLACE(REPLACE(mb_hp, '-', ''), ' ', '') = ? LIMIT 1")
            ->bind($hp_clean)
            ->fetch_optional();

        if (!$mb) {
            json_return(null, 400, '00003', '등록되지 않은 휴대폰 번호입니다.');
        }
    }

    // OTP 생성 (6자리)
    $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // OTP 저장 (세션 또는 DB)
    // 여기서는 세션 기반으로 간단히 구현. 프로덕션에서는 DB 또는 Redis 권장.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['otp_' . $hp_clean] = [
        'code' => $otp_code,
        'purpose' => $purpose,
        'expires' => time() + 180 // 3분 유효
    ];

    // TODO: 실제 SMS 발송 로직 연동
    // send_sms($hp, "[서비스명] 인증번호는 {$otp_code} 입니다.");

    // 개발 환경에서는 코드를 응답에 포함 (프로덕션에서는 제거)
    $isDev = defined('G5_DEBUG') && G5_DEBUG;

    json_return([
        'message' => '인증번호가 발송되었습니다.',
        'expires_in' => 180,
        // 'otp_code' => $isDev ? $otp_code : null // 개발용
    ], 200, '00000', '인증번호가 발송되었습니다.');
}
