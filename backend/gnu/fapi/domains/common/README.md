# Common Domain

공통 도메인 - 이메일/SMS 발송 등 공통 기능 제공

## 개요

이 도메인은 MSA(Microservices Architecture) 분리를 염두에 둔 공통 기능 도메인입니다. 향후 `common-service`로 분리되어 외부 API 연동을 담당하게 됩니다.

## 구조

```
domains/common/
├── service.php          # 이메일 서비스 레이어
├── sms_service.php      # SMS 서비스 레이어
├── email_repo.php       # 이메일 구현 레이어 (PHPMailer, AWS SES)
├── sms_repo.php         # SMS 구현 레이어 (Aligo, CoolSMS, Twilio)
└── README.md            # 이 문서
```

## 이메일 발송

### 설정 (.env)

```bash
# 이메일 제공 방식 선택
EMAIL_PROVIDER=gnuboard    # gnuboard | smtp | ses

# EMAIL_PROVIDER=smtp 사용 시
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME="Your Service"

# EMAIL_PROVIDER=ses 사용 시
AWS_SES_REGION=us-east-1
AWS_SES_ACCESS_KEY_ID=your-access-key
AWS_SES_SECRET_ACCESS_KEY=your-secret-key
```

### 사용 예시

```php
require_once __DIR__ . '/../domains/common/service.php';

// 기본 이메일 발송
$result = common_email_send(
    'user@example.com',
    '제목입니다',
    '<h1>HTML 내용</h1><p>본문입니다.</p>'
);

// 결과 확인
if ($result['success']) {
    echo "발송 성공: " . $result['message'];
} else {
    echo "발송 실패: " . $result['message'];
}

// 템플릿 이메일 발송
$result = common_email_send_template(
    'user@example.com',
    'welcome',  // 템플릿 이름
    [
        'name' => '홍길동',
        'site_name' => '카본빌더'
    ]
);
```

### MSA 분리 시

`COMMON_SERVICE_URL` 환경 변수가 설정되면 자동으로 외부 API 호출:

```bash
COMMON_SERVICE_URL=http://common-service:8080
```

## SMS 발송

### 설정 (.env)

```bash
# SMS 제공 방식 선택
SMS_PROVIDER=gnuboard    # gnuboard | aligo | coolsms | twilio

# SMS_PROVIDER=aligo 사용 시
ALIGO_API_KEY=your-aligo-api-key
ALIGO_USER_ID=your-aligo-user-id
ALIGO_SENDER=01012345678

# SMS_PROVIDER=coolsms 사용 시
COOLSMS_API_KEY=your-coolsms-api-key
COOLSMS_API_SECRET=your-coolsms-api-secret
COOLSMS_SENDER=01012345678

# SMS_PROVIDER=twilio 사용 시
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_FROM_NUMBER=+1234567890
```

### 사용 예시

```php
require_once __DIR__ . '/../domains/common/sms_service.php';

// 기본 SMS 발송
$result = common_sms_send(
    '01012345678',
    '안녕하세요. 인증번호는 123456입니다.'
);

// 인증번호 발송
$result = common_sms_send_verification_code(
    '01012345678',
    '123456',
    300  // 5분 유효
);

// 2FA 코드 발송
$result = common_sms_send_2fa_code(
    '01012345678',
    '789012'
);

// 임시 비밀번호 발송
$result = common_sms_send_temp_password(
    '01012345678',
    'temp1234'
);
```

## 상태 확인

```php
// 이메일 설정 상태
$email_status = common_email_check_status();
// {
//     "available": true,
//     "provider": "smtp",
//     "message": "SMTP 설정이 완료되었습니다."
// }

// SMS 설정 상태
$sms_status = common_sms_check_status();
// {
//     "available": true,
//     "provider": "aligo",
//     "message": "알리고 설정이 완료되었습니다."
// }
```

## 아키텍처

### 현재 (Monolith)

```
routes/*.php
    ↓
domains/common/service.php
    ↓
domains/common/email_repo.php (PHPMailer/AWS SES)
    ↓
SMTP/SES/그누보드 mailer
```

### MSA 분리 시

```
routes/*.php
    ↓
domains/common/service.php (HTTP Client)
    ↓ HTTP
common-service (별도 서버)
    ↓
SMTP/SES/Provider APIs
```

## 의존성 설치

### PHPMailer (SMTP 사용 시)

```bash
cd backend/gnuboard/api
composer require phpmailer/phpmailer
```

### AWS SES SDK

```bash
cd backend/gnuboard/api
composer require aws/aws-sdk-php
```

### CoolSMS SDK (선택)

```bash
cd backend/gnuboard/api
composer require coolsms/php-sdk
```

## 로깅

발송 이력은 선택적으로 DB에 저장됩니다 (g5_email_log, g5_sms_log 테이블):

```php
// 이메일 발송 이력 저장
common_email_repo_save_log([
    'to' => 'user@example.com',
    'subject' => '제목',
    'status' => true,
    'provider' => 'smtp',
    'message' => '발송 성공'
]);

// 이력 조회
$logs = common_email_repo_get_logs('user@example.com', 20, 0);
```

## 함수 네이밍 규칙

- `common_email_*` - 이메일 관련 함수
- `common_sms_*` - SMS 관련 함수
- `common_email_repo_*` - 이메일 구현 함수
- `common_sms_repo_*` - SMS 구현 함수

## 보안 고려사항

1. **API 키 관리**: 모든 API 키는 `.env` 파일에 저장, Git에 커밋 금지
2. **발신번호 인증**: Aligo, CoolSMS는 사전 등록된 발신번호만 사용 가능
3. **HTTPS**: 운영 환경에서는 반드시 HTTPS 사용
4. **개발 모드**: 개발 환경에서 자동으로 테스트 모드 사용 (알리고 `testmode_yn=Y`)

## 참고

- MSA 설계 문서: `/doc/msa.md`
- 환경 변수 문서: `/doc/environment-variables.md`
- 그누보드 메일 설정: 관리자 페이지 > 환경설정 > 기본환경설정 > 메일설정
