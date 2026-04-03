<?php
/**
 * Common Domain - SMS Service
 * 
 * SMS 문자 발송 서비스 레이어
 * - 비즈니스 로직 처리
 * - 발송 방식 선택 (그누보드/알리고/쿨에스엠에스/Twilio)
 * - MSA 분리 시 외부 API 연동 담당
 * 
 * @author SAPI Team
 * @since 1.0.0
 */

/**
 * SMS 발송 (공용 인터페이스)
 * 
 * @param string $to 받는 사람 전화번호 (01012345678 형식)
 * @param string $message 메시지 내용
 * @param array $options 추가 옵션
 *   - from: 발신번호 (provider별 설정 우선)
 *   - type: LMS/MMS 여부 (기본 SMS)
 *   - subject: LMS/MMS 제목
 * @return array 발송 결과
 *   - success: bool 성공 여부
 *   - message: string 결과 메시지
 *   - provider: string 사용된 제공자
 */
function common_sms_send($to, $message, $options = []) {
    $provider = getenv('SMS_PROVIDER') ?: 'gnuboard';
    
    // MSA 분리 시 외부 API 호출
    if (getenv('COMMON_SERVICE_URL')) {
        return common_sms_send_via_api($to, $message, $options);
    }
    
    // 전화번호 정규화
    $to = common_sms_normalize_phone($to);
    
    switch ($provider) {
        case 'aligo':
            return common_sms_send_aligo($to, $message, $options);
            
        case 'coolsms':
        case 'ncloud':
            return common_sms_send_coolsms($to, $message, $options);
            
        case 'twilio':
            return common_sms_send_twilio($to, $message, $options);
            
        case 'gnuboard':
        default:
            return common_sms_send_gnuboard($to, $message, $options);
    }
}

/**
 * 그누보드 SMS 플러그인 사용
 */
function common_sms_send_gnuboard($to, $message, $options = []) {
    global $config, $g5;
    
    // 그누보드 SMS 플러그인 체크 (예: SMS5)
    $sms5_path = G5_PATH . '/plugin/sms5';
    
    if (!is_dir($sms5_path)) {
        return [
            'success' => false,
            'message' => '그누보드 SMS 플러그인을 찾을 수 없습니다.',
            'provider' => 'gnuboard'
        ];
    }
    
    try {
        // SMS5 발송 시도
        if (function_exists('sms5_send')) {
            $result = sms5_send($to, $message, $options['from'] ?? '');
            return [
                'success' => true,
                'message' => 'SMS가 발송되었습니다.',
                'provider' => 'gnuboard',
                'to' => $to
            ];
        }
        
        return [
            'success' => false,
            'message' => 'SMS 발송 함수를 찾을 수 없습니다.',
            'provider' => 'gnuboard'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'gnuboard'
        ];
    }
}

/**
 * 알리고 SMS 사용
 */
function common_sms_send_aligo($to, $message, $options = []) {
    $api_key = getenv('ALIGO_API_KEY');
    $user_id = getenv('ALIGO_USER_ID');
    $sender = $options['from'] ?? getenv('ALIGO_SENDER');
    
    if (!$api_key || !$user_id || !$sender) {
        return [
            'success' => false,
            'message' => '알리고 설정이 완료되지 않았습니다.',
            'provider' => 'aligo'
        ];
    }
    
    try {
        return common_sms_repo_send_aligo($to, $message, $sender, $api_key, $user_id, $options);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'aligo'
        ];
    }
}

/**
 * 쿨에스엠에스 (NCP) 사용
 */
function common_sms_send_coolsms($to, $message, $options = []) {
    $api_key = getenv('COOLSMS_API_KEY');
    $api_secret = getenv('COOLSMS_API_SECRET');
    $sender = $options['from'] ?? getenv('COOLSMS_SENDER');
    
    if (!$api_key || !$api_secret || !$sender) {
        return [
            'success' => false,
            'message' => '쿨에스엠에스 설정이 완료되지 않았습니다.',
            'provider' => 'coolsms'
        ];
    }
    
    try {
        return common_sms_repo_send_coolsms($to, $message, $sender, $api_key, $api_secret, $options);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'coolsms'
        ];
    }
}

/**
 * Twilio 사용
 */
function common_sms_send_twilio($to, $message, $options = []) {
    $sid = getenv('TWILIO_SID');
    $token = getenv('TWILIO_TOKEN');
    $from = $options['from'] ?? getenv('TWILIO_FROM_NUMBER');
    
    if (!$sid || !$token || !$from) {
        return [
            'success' => false,
            'message' => 'Twilio 설정이 완료되지 않았습니다.',
            'provider' => 'twilio'
        ];
    }
    
    try {
        return common_sms_repo_send_twilio($to, $message, $from, $sid, $token);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'twilio'
        ];
    }
}

/**
 * MSA 분리 시 외부 Common Service API 호출
 */
function common_sms_send_via_api($to, $message, $options = []) {
    $service_url = getenv('COMMON_SERVICE_URL'); // 예: http://common-service:8080
    
    $payload = [
        'to' => $to,
        'message' => $message,
        'options' => $options
    ];
    
    try {
        $ch = curl_init($service_url . '/api/sms/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        return [
            'success' => false,
            'message' => 'SMS 서비스 호출 실패 (HTTP ' . $http_code . ')',
            'provider' => 'msa-api'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'msa-api'
        ];
    }
}

/**
 * SMS 발송 가능 여부 확인
 * 
 * @return array 설정 상태
 *   - available: bool 사용 가능 여부
 *   - provider: string 현재 설정된 제공자
 *   - message: string 상태 메시지
 */
function common_sms_check_status() {
    $provider = getenv('SMS_PROVIDER') ?: 'gnuboard';
    
    switch ($provider) {
        case 'aligo':
            $required = ['ALIGO_API_KEY', 'ALIGO_USER_ID', 'ALIGO_SENDER'];
            $missing = [];
            foreach ($required as $key) {
                if (!getenv($key)) $missing[] = $key;
            }
            
            return [
                'available' => empty($missing),
                'provider' => 'aligo',
                'message' => empty($missing) 
                    ? '알리고 설정이 완료되었습니다.' 
                    : '필수 설정 누락: ' . implode(', ', $missing)
            ];
            
        case 'coolsms':
        case 'ncloud':
            $required = ['COOLSMS_API_KEY', 'COOLSMS_API_SECRET', 'COOLSMS_SENDER'];
            $missing = [];
            foreach ($required as $key) {
                if (!getenv($key)) $missing[] = $key;
            }
            
            return [
                'available' => empty($missing),
                'provider' => 'coolsms',
                'message' => empty($missing) 
                    ? '쿨에스엠에스 설정이 완료되었습니다.' 
                    : '필수 설정 누락: ' . implode(', ', $missing)
            ];
            
        case 'twilio':
            $required = ['TWILIO_SID', 'TWILIO_TOKEN', 'TWILIO_FROM_NUMBER'];
            $missing = [];
            foreach ($required as $key) {
                if (!getenv($key)) $missing[] = $key;
            }
            
            return [
                'available' => empty($missing),
                'provider' => 'twilio',
                'message' => empty($missing) 
                    ? 'Twilio 설정이 완료되었습니다.' 
                    : '필수 설정 누락: ' . implode(', ', $missing)
            ];
            
        case 'gnuboard':
        default:
            $sms5_exists = is_dir(G5_PATH . '/plugin/sms5');
            return [
                'available' => $sms5_exists,
                'provider' => 'gnuboard',
                'message' => $sms5_exists
                    ? '그누보드 SMS 플러그인을 사용합니다.'
                    : '그누보드 SMS 플러그인을 찾을 수 없습니다.'
            ];
    }
}

/**
 * 전화번호 정규화
 * 
 * @param string $phone 전화번호
 * @return string 정규화된 전화번호 (01012345678)
 */
function common_sms_normalize_phone($phone) {
    // 숫자만 추출
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // 10자리 (구번호) -> 11자리 변환
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        $phone = '0' . substr($phone, 0, 2) . substr($phone, 2);
    }
    
    // 국제 번호 (+82) -> 국내 번호로 변환
    if (strlen($phone) === 11 && substr($phone, 0, 3) === '821') {
        $phone = '0' . substr($phone, 3);
    }
    if (strlen($phone) === 12 && substr($phone, 0, 4) === '8210') {
        $phone = '0' . substr($phone, 4);
    }
    
    return $phone;
}

/**
 * 인증번호 SMS 발송
 * 
 * @param string $to 받는 사람 전화번호
 * @param string $code 인증번호
 * @param int $expires_in 만료 시간 (초, 기본 300=5분)
 * @return array 발송 결과
 */
function common_sms_send_verification_code($to, $code, $expires_in = 300) {
    $minutes = ceil($expires_in / 60);
    $message = "[인증번호] {$code}\n{$minutes}분 내에 입력해주세요.";
    
    return common_sms_send($to, $message, ['type' => 'SMS']);
}

/**
 * 2FA 인증번호 SMS 발송
 * 
 * @param string $to 받는 사람 전화번호
 * @param string $code 인증번호
 * @return array 발송 결과
 */
function common_sms_send_2fa_code($to, $code) {
    $message = "[2차인증] 인증번호는 {$code} 입니다. 5분 내에 입력해주세요.";
    
    return common_sms_send($to, $message, ['type' => 'SMS']);
}

/**
 * 임시 비밀번호 SMS 발송
 * 
 * @param string $to 받는 사람 전화번호
 * @param string $temp_password 임시 비밀번호
 * @return array 발송 결과
 */
function common_sms_send_temp_password($to, $temp_password) {
    $message = "임시 비밀번호는 {$temp_password} 입니다.\n로그인 후 반드시 변경해주세요.";
    
    return common_sms_send($to, $message, ['type' => 'SMS']);
}
