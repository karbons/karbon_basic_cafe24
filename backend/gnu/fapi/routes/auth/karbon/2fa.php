<?php
/**
 * Karbon Auth - 2차 인증 (2FA)
 * 
 * POST /api/auth/karbon/2fa/send - 2FA 코드 발송
 * POST /api/auth/karbon/2fa/verify - 2FA 코드 확인
 * GET  /api/auth/karbon/2fa/status - 현재 2FA 상태 확인
 */

/**
 * POST /api/auth/karbon/2fa - 2FA 코드 발송
 */
function POST()
{
    require_once __DIR__ . '/../../../shared/auth.php';
    global $g5;

    // 로그인된 회원 확인
    $member = auth_get_member();
    if (!$member || !$member['mb_id']) {
        json_return(null, 401, '00001', '로그인이 필요합니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $method = isset($data['method']) ? trim($data['method']) : 'sms';

    // 2FA 코드 생성
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // 세션에 저장
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['2fa_' . $member['mb_id']] = [
        'code' => $code,
        'method' => $method,
        'expires' => time() + 300, // 5분 유효
        'verified' => false
    ];

    // 발송 처리
    if ($method === 'sms') {
        $hp = $member['mb_hp'] ?? '';
        if (empty($hp)) {
            json_return(null, 400, '00002', '등록된 휴대폰 번호가 없습니다.');
        }
        // TODO: SMS 발송
        // send_sms($hp, "[서비스명] 2차 인증번호는 {$code} 입니다.");
    } elseif ($method === 'email') {
        $email = $member['mb_email'] ?? '';
        if (empty($email)) {
            json_return(null, 400, '00003', '등록된 이메일 주소가 없습니다.');
        }
        // TODO: 이메일 발송
        // mailer($member['mb_name'], $email, "2차 인증 코드", "인증번호: {$code}");
    } elseif ($method === 'password') {
        // 비밀번호 방식은 코드 발송 불필요
        json_return(['method' => 'password'], 200, '00000', '비밀번호를 입력해주세요.');
    } else {
        json_return(null, 400, '00004', '지원하지 않는 인증 방식입니다.');
    }

    json_return([
        'method' => $method,
        'expires_in' => 300
    ], 200, '00000', '인증번호가 발송되었습니다.');
}

/**
 * GET /api/auth/karbon/2fa - 2FA 상태 확인
 */
function GET()
{
    require_once __DIR__ . '/../../../shared/auth.php';

    $member = auth_get_member();
    if (!$member || !$member['mb_id']) {
        json_return(null, 401, '00001', '로그인이 필요합니다.');
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $session_key = '2fa_' . $member['mb_id'];
    $twofa = $_SESSION[$session_key] ?? null;

    $required = true; // 실제로는 서비스 로직에 따라 결정
    $authenticated = $twofa && $twofa['verified'] === true && $twofa['expires'] > time();

    json_return([
        'required' => $required,
        'authenticated' => $authenticated
    ], 200, '00000', 'OK');
}
