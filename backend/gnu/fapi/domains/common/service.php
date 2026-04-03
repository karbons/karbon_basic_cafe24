<?php
/**
 * Common Domain - Email Service
 * 
 * 이메일 발송 서비스 레이어
 * - 비즈니스 로직 처리
 * - 발송 방식 선택 (그누보드/SMTP/AWS SES)
 * - MSA 분리 시 외부 API 연동 담당
 * 
 * @author SAPI Team
 * @since 1.0.0
 */

/**
 * 이메일 발송 (공용 인터페이스)
 * 
 * @param string $to 받는 사람 이메일
 * @param string $subject 제목
 * @param string $content 내용 (HTML 가능)
 * @param array $options 추가 옵션
 *   - from_name: 발신자 이름
 *   - from_email: 발신자 이메일
 *   - attachments: 첨부파일 배열
 * @return array 발송 결과
 *   - success: bool 성공 여부
 *   - message: string 결과 메시지
 *   - provider: string 사용된 제공자
 */
function common_email_send($to, $subject, $content, $options = []) {
    $provider = getenv('EMAIL_PROVIDER') ?: 'gnuboard';
    
    // MSA 분리 시 외부 API 호출
    if (getenv('COMMON_SERVICE_URL')) {
        return common_email_send_via_api($to, $subject, $content, $options);
    }
    
    switch ($provider) {
        case 'smtp':
        case 'ses':
            return common_email_send_smtp($to, $subject, $content, $options);
            
        case 'gnuboard':
        default:
            return common_email_send_gnuboard($to, $subject, $content, $options);
    }
}

/**
 * 그누보드 mailer 사용
 */
function common_email_send_gnuboard($to, $subject, $content, $options = []) {
    global $config;
    
    if (!file_exists(G5_LIB_PATH . '/mailer.lib.php')) {
        return [
            'success' => false,
            'message' => '그누보드 mailer 라이브러리를 찾을 수 없습니다.',
            'provider' => 'gnuboard'
        ];
    }
    
    include_once G5_LIB_PATH . '/mailer.lib.php';
    
    $from_name = $options['from_name'] ?? $config['cf_title'] ?? '관리자';
    $from_email = $options['from_email'] ?? $config['cf_admin_email'] ?? '';
    
    try {
        // mailer($fname, $fmail, $to, $subject, $content, $type=0, $file="", $cc="", $bcc="")
        mailer($from_name, $from_email, $to, $subject, $content, 1);
        
        return [
            'success' => true,
            'message' => '이메일이 발송되었습니다.',
            'provider' => 'gnuboard',
            'to' => $to
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
 * SMTP/AWS SES 사용
 */
function common_email_send_smtp($to, $subject, $content, $options = []) {
    // SMTP 설정 로드
    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = (int)(getenv('SMTP_PORT') ?: 587);
    $smtp_username = getenv('SMTP_USERNAME');
    $smtp_password = getenv('SMTP_PASSWORD');
    $smtp_encryption = getenv('SMTP_ENCRYPTION') ?: 'tls';
    
    if (!$smtp_host || !$smtp_username || !$smtp_password) {
        return [
            'success' => false,
            'message' => 'SMTP 설정이 완료되지 않았습니다.',
            'provider' => 'smtp'
        ];
    }
    
    // AWS SES 사용 여부 확인
    if (getenv('EMAIL_PROVIDER') === 'ses' || getenv('AWS_SES_ACCESS_KEY_ID')) {
        return common_email_send_aws_ses($to, $subject, $content, $options);
    }
    
    // PHPMailer 사용 (composer로 설치 필요: composer require phpmailer/phpmailer)
    try {
        return common_email_repo_send_phpmailer($to, $subject, $content, $options);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'smtp'
        ];
    }
}

/**
 * AWS SES 사용
 */
function common_email_send_aws_ses($to, $subject, $content, $options = []) {
    // AWS SES SDK 사용 (composer로 설치 필요: composer require aws/aws-sdk-php)
    try {
        return common_email_repo_send_aws_ses($to, $subject, $content, $options);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'ses'
        ];
    }
}

/**
 * MSA 분리 시 외부 Common Service API 호출
 */
function common_email_send_via_api($to, $subject, $content, $options = []) {
    $service_url = getenv('COMMON_SERVICE_URL'); // 예: http://common-service:8080
    
    $payload = [
        'to' => $to,
        'subject' => $subject,
        'content' => $content,
        'options' => $options
    ];
    
    try {
        $ch = curl_init($service_url . '/api/email/send');
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
            'message' => '이메일 서비스 호출 실패 (HTTP ' . $http_code . ')',
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
 * 이메일 발송 가능 여부 확인
 * 
 * @return array 설정 상태
 *   - available: bool 사용 가능 여부
 *   - provider: string 현재 설정된 제공자
 *   - message: string 상태 메시지
 */
function common_email_check_status() {
    $provider = getenv('EMAIL_PROVIDER') ?: 'gnuboard';
    
    switch ($provider) {
        case 'smtp':
            $required = ['SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD'];
            $missing = [];
            foreach ($required as $key) {
                if (!getenv($key)) $missing[] = $key;
            }
            
            return [
                'available' => empty($missing),
                'provider' => 'smtp',
                'message' => empty($missing) 
                    ? 'SMTP 설정이 완료되었습니다.' 
                    : '필수 설정 누락: ' . implode(', ', $missing)
            ];
            
        case 'ses':
            $required = ['AWS_SES_ACCESS_KEY_ID', 'AWS_SES_SECRET_ACCESS_KEY'];
            $missing = [];
            foreach ($required as $key) {
                if (!getenv($key)) $missing[] = $key;
            }
            
            return [
                'available' => empty($missing),
                'provider' => 'ses',
                'message' => empty($missing) 
                    ? 'AWS SES 설정이 완료되었습니다.' 
                    : '필수 설정 누락: ' . implode(', ', $missing)
            ];
            
        case 'gnuboard':
        default:
            return [
                'available' => file_exists(G5_LIB_PATH . '/mailer.lib.php'),
                'provider' => 'gnuboard',
                'message' => file_exists(G5_LIB_PATH . '/mailer.lib.php')
                    ? '그누보드 mailer를 사용합니다.'
                    : '그누보드 mailer 라이브러리를 찾을 수 없습니다.'
            ];
    }
}

/**
 * 템플릿 이메일 발송
 * 
 * @param string $to 받는 사람
 * @param string $template 템플릿 이름
 * @param array $variables 템플릿 변수
 * @return array 발송 결과
 */
function common_email_send_template($to, $template, $variables = []) {
    $templates = [
        'welcome' => [
            'subject' => '{$site_name}에 오신 것을 환영합니다!',
            'content' => '<h1>환영합니다, {$name}님!</h1><p>{$site_name}에 가입해 주셔서 감사합니다.</p>'
        ],
        'password_reset' => [
            'subject' => '[{$site_name}] 비밀번호 재설정',
            'content' => '<h1>비밀번호 재설정</h1><p>임시 비밀번호: <strong>{$temp_password}</strong></p><p>로그인 후 반드시 비밀번호를 변경해주세요.</p>'
        ],
        'verification' => [
            'subject' => '[{$site_name}] 인증번호',
            'content' => '<h1>인증번호</h1><p>인증번호: <strong>{$code}</strong></p><p>5분 내에 입력해주세요.</p>'
        ]
    ];
    
    if (!isset($templates[$template])) {
        return [
            'success' => false,
            'message' => '템플릿을 찾을 수 없습니다: ' . $template
        ];
    }
    
    $tpl = $templates[$template];
    
    // 변수 치환
    $subject = $tpl['subject'];
    $content = $tpl['content'];
    
    foreach ($variables as $key => $value) {
        $subject = str_replace('{$' . $key . '}', $value, $subject);
        $content = str_replace('{$' . $key . '}', $value, $content);
    }
    
    return common_email_send($to, $subject, $content);
}
