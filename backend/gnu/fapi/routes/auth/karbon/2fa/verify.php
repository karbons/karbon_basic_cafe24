<?php
/**
 * Karbon Auth - 2차 인증 확인
 * POST /api/auth/karbon/2fa/verify
 * 
 * 요청 Body:
 * - code: 인증 코드 또는 비밀번호
 * - type: 'sms' | 'email' | 'password'
 */
function POST()
{
    require_once __DIR__ . '/../../../../shared/auth.php';
    global $g5;

    $member = auth_get_member();
    if (!$member || !$member['mb_id']) {
        json_return(null, 401, '00001', '로그인이 필요합니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $code = isset($data['code']) ? trim($data['code']) : '';
    $type = isset($data['type']) ? trim($data['type']) : 'sms';

    if (empty($code)) {
        json_return(null, 400, '00002', '인증 코드를 입력해주세요.');
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $session_key = '2fa_' . $member['mb_id'];

    if ($type === 'password') {
        // 비밀번호 재확인 방식
        $row = sqlx::query("SELECT mb_password FROM {$g5['member_table']} WHERE mb_id = ?")
            ->bind($member['mb_id'])
            ->fetch_optional();

        if (!$row || !login_password_check($row, $code, $row['mb_password'])) {
            json_return(null, 400, '00003', '비밀번호가 올바르지 않습니다.');
        }

        // 인증 성공
        $_SESSION[$session_key] = [
            'code' => '',
            'method' => 'password',
            'expires' => time() + 1800, // 30분 유효
            'verified' => true
        ];

        json_return(['verified' => true], 200, '00000', '인증되었습니다.');
    } else {
        // SMS/Email 코드 확인
        $twofa = $_SESSION[$session_key] ?? null;

        if (!$twofa || $twofa['expires'] < time()) {
            json_return(null, 400, '00004', '인증 시간이 만료되었습니다. 다시 시도해주세요.');
        }

        if ($twofa['code'] !== $code) {
            json_return(null, 400, '00005', '인증 코드가 올바르지 않습니다.');
        }

        // 인증 성공
        $_SESSION[$session_key] = [
            'code' => '',
            'method' => $twofa['method'],
            'expires' => time() + 1800, // 30분 유효
            'verified' => true
        ];

        json_return(['verified' => true], 200, '00000', '인증되었습니다.');
    }
}
