<?php
/**
 * Common Domain - SMS Repository
 * 
 * SMS 발송 구현 레이어
 * - 알리고 (Aligo) API
 * - 쿨에스엠에스 (CoolSMS/NCP) API
 * - Twilio API
 * - MSA 분리 시 HTTP API 호출
 * 
 * @author SAPI Team
 * @since 1.0.0
 */

/**
 * 알리고 SMS 발송
 * 
 * @param string $to 받는 사람 전화번호
 * @param string $message 메시지 내용
 * @param string $sender 발신번호
 * @param string $api_key API 키
 * @param string $user_id 사용자 ID
 * @param array $options 추가 옵션
 * @return array 발송 결과
 */
function common_sms_repo_send_aligo($to, $message, $sender, $api_key, $user_id, $options = []) {
    $url = 'https://apis.aligo.in/send/';
    
    $params = [
        'key' => $api_key,
        'user_id' => $user_id,
        'sender' => $sender,
        'receiver' => $to,
        'msg' => $message,
        'testmode_yn' => (getenv('APP_ENV') === 'development') ? 'Y' : 'N'
    ];
    
    // LMS/MMS인 경우 제목 추가
    if (!empty($options['subject'])) {
        $params['title'] = $options['subject'];
        $params['msg_type'] = 'LMS';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        
        if (isset($result['result_code']) && $result['result_code'] == 1) {
            return [
                'success' => true,
                'message' => 'SMS가 발송되었습니다.',
                'provider' => 'aligo',
                'msg_id' => $result['msg_id'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['message'] ?? '알리고 API 오류',
            'provider' => 'aligo'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'HTTP 오류: ' . $http_code,
        'provider' => 'aligo'
    ];
}

/**
 * 쿨에스엠에스 (NCP) 발송
 * 
 * @param string $to 받는 사람 전화번호
 * @param string $message 메시지 내용
 * @param string $sender 발신번호
 * @param string $api_key API 키
 * @param string $api_secret API 시크릿
 * @param array $options 추가 옵션
 * @return array 발송 결과
 */
function common_sms_repo_send_coolsms($to, $message, $sender, $api_key, $api_secret, $options = []) {
    $url = 'https://api.coolsms.co.kr/messages/v4/send';
    
    $timestamp = time();
    $salt = uniqid();
    $signature = hash_hmac('sha256', $timestamp . $salt, $api_secret);
    
    $headers = [
        'Authorization: HMAC-SHA256 apiKey=' . $api_key . ', date=' . $timestamp . ', salt=' . $salt . ', signature=' . $signature,
        'Content-Type: application/json'
    ];
    
    $params = [
        'message' => [
            'to' => $to,
            'from' => $sender,
            'text' => $message
        ]
    ];
    
    // LMS/MMS인 경우
    if (!empty($options['subject'])) {
        $params['message']['subject'] = $options['subject'];
        $params['message']['type'] = 'LMS';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        
        if (isset($result['messageId'])) {
            return [
                'success' => true,
                'message' => 'SMS가 발송되었습니다.',
                'provider' => 'coolsms',
                'message_id' => $result['messageId']
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? '쿨에스엠에스 API 오류',
            'provider' => 'coolsms'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'HTTP 오류: ' . $http_code,
        'provider' => 'coolsms'
    ];
}

/**
 * Twilio SMS 발송
 * 
 * @param string $to 받는 사람 전화번호
 * @param string $message 메시지 내용
 * @param string $from 발신번호
 * @param string $sid 계정 SID
 * @param string $token 인증 토큰
 * @return array 발송 결과
 */
function common_sms_repo_send_twilio($to, $message, $from, $sid, $token) {
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json';
    
    // 전화번호 국제 형식으로 변환
    if (substr($to, 0, 1) === '0') {
        $to = '+82' . substr($to, 1);
    }
    
    $params = [
        'To' => $to,
        'From' => $from,
        'Body' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 201) {
        $result = json_decode($response, true);
        
        if (isset($result['sid'])) {
            return [
                'success' => true,
                'message' => 'SMS가 발송되었습니다.',
                'provider' => 'twilio',
                'sid' => $result['sid']
            ];
        }
    }
    
    $result = json_decode($response, true);
    return [
        'success' => false,
        'message' => $result['message'] ?? 'Twilio API 오류',
        'provider' => 'twilio'
    ];
}

/**
 * SMS 발송 이력 저장
 * 
 * @param array $data 발송 데이터
 *   - to: 받는 사람
 *   - message: 메시지 내용
 *   - status: 성공/실패 상태
 *   - provider: 사용된 제공자
 *   - message: 결과 메시지
 * @return bool 저장 성공 여부
 */
function common_sms_repo_save_log($data) {
    // 발송 이력 DB 저장 (선택적)
    
    try {
        if (class_exists('sqlx')) {
            sqlx::query("INSERT INTO g5_sms_log (to_phone, message, status, provider, response_message, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
                ->bind($data['to'])
                ->bind($data['message'])
                ->bind($data['status'] ? 'success' : 'failed')
                ->bind($data['provider'])
                ->bind($data['message'])
                ->execute();
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 발송 이력 조회
 * 
 * @param string $to 받는 사람 전화번호 (선택)
 * @param int $limit 조회 개수
 * @param int $offset 시작 위치
 * @return array 발송 이력 목록
 */
function common_sms_repo_get_logs($to = null, $limit = 20, $offset = 0) {
    try {
        if (!class_exists('sqlx')) {
            return [];
        }
        
        $sql = "SELECT * FROM g5_sms_log";
        $params = [];
        
        if ($to) {
            $sql .= " WHERE to_phone = ?";
            $params[] = $to;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $query = sqlx::query($sql);
        foreach ($params as $param) {
            $query->bind($param);
        }
        
        return $query->fetch_all();
    } catch (Exception $e) {
        return [];
    }
}
