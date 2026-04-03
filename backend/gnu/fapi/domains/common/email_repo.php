<?php
/**
 * Common Domain - Email Repository
 * 
 * 이메일 발송 구현 레이어
 * - PHPMailer SMTP 발송
 * - AWS SES SDK 발송
 * - MSA 분리 시 HTTP API 호출
 * 
 * @author SAPI Team
 * @since 1.0.0
 */

/**
 * PHPMailer를 사용한 SMTP 발송
 * 
 * @param string $to 받는 사람
 * @param string $subject 제목
 * @param string $content 내용
 * @param array $options 추가 옵션
 * @return array 발송 결과
 */
function common_email_repo_send_phpmailer($to, $subject, $content, $options = []) {
    // PHPMailer가 설치되어 있는지 확인
    $composerAutoload = __DIR__ . '/../../../vendor/autoload.php';
    
    if (!file_exists($composerAutoload)) {
        return [
            'success' => false,
            'message' => 'PHPMailer가 설치되지 않았습니다. (composer require phpmailer/phpmailer)'
        ];
    }
    
    require_once $composerAutoload;
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'message' => 'PHPMailer 클래스를 찾을 수 없습니다.'
        ];
    }
    
    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = (int)(getenv('SMTP_PORT') ?: 587);
    $smtp_username = getenv('SMTP_USERNAME');
    $smtp_password = getenv('SMTP_PASSWORD');
    $smtp_encryption = getenv('SMTP_ENCRYPTION') ?: 'tls';
    
    $from_email = $options['from_email'] ?? getenv('SMTP_FROM_EMAIL') ?? $smtp_username;
    $from_name = $options['from_name'] ?? getenv('SMTP_FROM_NAME') ?? '관리자';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = $smtp_port;
        
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $content;
        $mail->CharSet = 'UTF-8';
        
        // 첨부파일 처리
        if (!empty($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                if (is_array($attachment)) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                } else {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => '이메일이 발송되었습니다.',
            'provider' => 'phpmailer'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $mail->ErrorInfo ?: $e->getMessage(),
            'provider' => 'phpmailer'
        ];
    }
}

/**
 * AWS SES SDK를 사용한 발송
 * 
 * @param string $to 받는 사람
 * @param string $subject 제목
 * @param string $content 내용
 * @param array $options 추가 옵션
 * @return array 발송 결과
 */
function common_email_repo_send_aws_ses($to, $subject, $content, $options = []) {
    // AWS SDK가 설치되어 있는지 확인
    $composerAutoload = __DIR__ . '/../../../vendor/autoload.php';
    
    if (!file_exists($composerAutoload)) {
        return [
            'success' => false,
            'message' => 'AWS SDK가 설치되지 않았습니다. (composer require aws/aws-sdk-php)'
        ];
    }
    
    require_once $composerAutoload;
    
    if (!class_exists('Aws\Ses\SesClient')) {
        return [
            'success' => false,
            'message' => 'AWS SES 클라이언트를 찾을 수 없습니다.'
        ];
    }
    
    $region = getenv('AWS_SES_REGION') ?: 'us-east-1';
    $access_key = getenv('AWS_SES_ACCESS_KEY_ID') ?: getenv('AWS_ACCESS_KEY_ID');
    $secret_key = getenv('AWS_SES_SECRET_ACCESS_KEY') ?: getenv('AWS_SECRET_ACCESS_KEY');
    
    $from_email = $options['from_email'] ?? getenv('SMTP_FROM_EMAIL') ?? 'noreply@example.com';
    $from_name = $options['from_name'] ?? getenv('SMTP_FROM_NAME') ?? '관리자';
    
    try {
        $client = new Aws\Ses\SesClient([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $access_key,
                'secret' => $secret_key
            ]
        ]);
        
        $result = $client->sendEmail([
            'Destination' => [
                'ToAddresses' => [$to]
            ],
            'Message' => [
                'Body' => [
                    'Html' => [
                        'Charset' => 'UTF-8',
                        'Data' => $content
                    ],
                    'Text' => [
                        'Charset' => 'UTF-8',
                        'Data' => strip_tags($content)
                    ]
                ],
                'Subject' => [
                    'Charset' => 'UTF-8',
                    'Data' => $subject
                ]
            ],
            'Source' => $from_name . ' <' . $from_email . '>'
        ]);
        
        return [
            'success' => true,
            'message' => '이메일이 발송되었습니다.',
            'provider' => 'aws_ses',
            'message_id' => $result['MessageId'] ?? null
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'provider' => 'aws_ses'
        ];
    }
}

/**
 * 이메일 발송 이력 저장
 * 
 * @param array $data 발송 데이터
 *   - to: 받는 사람
 *   - subject: 제목
 *   - content: 내용
 *   - status: 성공/실패 상태
 *   - provider: 사용된 제공자
 *   - message: 결과 메시지
 * @return bool 저장 성공 여부
 */
function common_email_repo_save_log($data) {
    // 발송 이력 DB 저장 (선택적)
    // 예: g5_email_log 테이블에 저장
    
    try {
        if (class_exists('sqlx')) {
            sqlx::query("INSERT INTO g5_email_log (to_email, subject, status, provider, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
                ->bind($data['to'])
                ->bind($data['subject'])
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
 * @param string $to 받는 사람 이메일 (선택)
 * @param int $limit 조회 개수
 * @param int $offset 시작 위치
 * @return array 발송 이력 목록
 */
function common_email_repo_get_logs($to = null, $limit = 20, $offset = 0) {
    try {
        if (!class_exists('sqlx')) {
            return [];
        }
        
        $sql = "SELECT * FROM g5_email_log";
        $params = [];
        
        if ($to) {
            $sql .= " WHERE to_email = ?";
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
