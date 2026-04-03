# SAPI 환경 변수 문서

> **버전**: 1.0.0  
> **작성일**: 2026-02-25  
> **대상**: SAPI (GNU Board 5 기반 REST API) 백엔드

이 문서는 `backend/gnuboard/api/.env` 파일에서 사용되는 모든 환경 변수의 **용도, 사용 위치, 설정 방법**을 상세히 설명합니다.

---

## 📋 목차

1. [환경 변수 시스템 개요](#환경-변수-시스템-개요)
2. [애플리케이션 설정](#애플리케이션-설정)
3. [로깅 설정](#로깅-설정)
4. [데이터베이스 설정](#데이터베이스-설정)
5. [JWT 인증 설정](#jwt-인증-설정)
6. [CORS 설정](#cors-설정)
7. [Firebase 설정](#firebase-설정)
8. [파일 업로드 설정](#파일-업로드-설정)
9. [앱 버전 설정](#앱-버전-설정)
10. [이메일 설정](#이메일-설정)
11. [SMS 설정](#sms-설정)
12. [푸시 알림 설정](#푸시-알림-설정)
13. [환경 변수 로드 메커니즘](#환경-변수-로드-메커니즘)
14. [보안 권장사항](#보안-권장사항)

---

## 환경 변수 시스템 개요

### 아키텍처

```
.env 파일
    ↓
Config::load()  →  shared/config_class.php
    ↓
config/ 폴더의 PHP 파일들  →  $_ENV[] / getenv()
    ↓
각종 shared/*.php 유틸리티 함수
```

### 로드 우선순위

1. **환경 변수 (.env)** - 가장 높은 우선순위
2. **그누보드 상수 (G5_*)** - 레거시 호환성 (예: `G5_MYSQL_HOST`)
3. **기본값 (fallback)** - 코드에 하드코딩된 값

### 주요 설정 파일 위치

| 파일 | 역할 |
|------|------|
| `.env` | 환경 변수 정의 |
| `shared/config_class.php` | Config 클래스 - .env 로더 |
| `config/app.php` | 애플리케이션 설정 중앙화 |
| `config/jwt.php` | JWT 설정 중앙화 |
| `config/cors.php` | CORS 설정 중앙화 |
| `config/env.php` | 환경 변수 로드 (대체 로더) |

---

## 애플리케이션 설정

### APP_VERSION

```bash
APP_VERSION=0.0.1
```

**용도**: 현재 애플리케이션 버전  
**사용 위치**: 
- `routes/app/version.php` - 앱 버전 체크 API
- `routes/site.php` - 사이트 정보 API

**기본값**: `0.0.1` (코드 하드코딩)  
**설명**: 모바일 앱의 버전 체크 기능에서 사용됩니다.

---

### APP_ENV

```bash
APP_ENV=development
```

**용도**: 실행 환경 구분  
**사용 위치**: 
- `config/app.php` - 앱 설정 초기화
- `shared/sqlx.php` - 디버그 모드 결정

**가능한 값**:
- `development` - 개발 환경 (디버깅 활성화)
- `production` - 운영 환경 (성능 최적화)
- `testing` - 테스트 환경

**기본값**: `development`

---

### APP_DEBUG

```bash
APP_DEBUG=true
```

**용도**: 디버그 모드 활성화  
**사용 위치**: 
- `config/app.php` - 디버그 플래그 설정
- `shared/sqlx.php` - SQLX 디버그 모드 (`SQLX_DEBUG`가 없을 때 대체)

**가능한 값**:
- `true` - 디버그 정보 출력 (개발용)
- `false` - 디버그 정보 숨김 (운영용)

**⚠️ 주의**: 운영 환경에서는 반드시 `false`로 설정하세요.

**기본값**: `false`

---

### APP_DOMAIN / APP_URL

```bash
APP_DOMAIN=http://localhost
APP_URL=http://localhost  # 별칭
```

**용도**: 애플리케이션 기본 URL  
**사용 위치**: 
- `config/app.php` - 앱 URL 설정
- `shared/auth.php` - JWT 토큰 발급자(`iss`) 클레임 설정

**기본값**: 
- `.env` 없음: `http://localhost`
- 또는 그누보드 설정 `G5_URL` 상속

---

### APP_COOKIE_DOMAIN

```bash
APP_COOKIE_DOMAIN=.example.com
```

**용도**: 쿠키 도메인 설정  
**사용 위치**: 
- `config/app.php` - 쿠키 설정
- `shared/auth.php` - 인증 쿠키 설정

**기본값**: 빈 문자열 (현재 도메인)  
**설명**: 
- 서브도메인 간 쿠키 공유 시 설정 (예: `.example.com`)
- 빈 값일 경우 현재 요청 도메인에만 쿠키 설정

---

### APP_HTTPS_ONLY / HTTPS_ONLY

```bash
APP_HTTPS_ONLY=false
HTTPS_ONLY=false  # 별칭
```

**용도**: HTTPS 전용 모드  
**사용 위치**: 
- `config/app.php` - HTTPS 플래그
- `shared/auth.php` - 쿠키 secure 옵션 설정

**가능한 값**:
- `true` - HTTPS에서만 쿠키 전송
- `false` - HTTP에서도 쿠키 허용

**⚠️ 보안**: 운영 환경에서는 반드시 `true`로 설정하세요.

**기본값**: `false`

---

### APP_COOKIE_SAMESITE / COOKIE_SAMESITE

```bash
APP_COOKIE_SAMESITE=Lax
COOKIE_SAMESITE=Lax  # 별칭
```

**용도**: 쿠키 SameSite 속성  
**사용 위치**: 
- `config/app.php` - 쿠키 설정
- `shared/auth.php` - 인증 쿠키 설정

**가능한 값**:
| 값 | 설명 | 사용 시나리오 |
|----|------|--------------|
| `Strict` | 동일 사이트 요청만 쿠키 전송 | 최고 보안, 외부 링크 시 로그인 풀림 |
| `Lax` | GET 요청은 허용, POST는 차단 | 권장 기본값, 대부분의 경우 적합 |
| `None` | 모든 요청에 쿠키 전송 | **반드시 HTTPS_ONLY=true와 함께 사용** |

**⚠️ 주의**: 
- `None` 사용 시 `Secure` 플래그 필수 (HTTPS_ONLY=true)
- 크로스 도메인 환경에서만 `None` 사용

**기본값**: `Lax`

---

## 로깅 설정

### LOG_LEVEL

```bash
LOG_LEVEL=3
```

**용도**: 로그 레벨 설정  
**사용 위치**: `shared/logger.php` - 로그 필터링

**레벨 정의**:
| 레벨 | 값 | 설명 | 권장 환경 |
|------|----|----|----------|
| `LOG_LEVEL_NONE` | 0 | 로그 생성 안함 | production (최고 성능) |
| `LOG_LEVEL_ERROR` | 1 | 에러만 기록 | production (에러 모니터링) |
| `LOG_LEVEL_WARNING` | 2 | 에러 + 경고 기록 | production |
| `LOG_LEVEL_INFO` | 3 | 에러 + 경고 + 정보 기록 | development (기본값) |
| `LOG_LEVEL_DEBUG` | 4 | 모든 로그 기록 | 개발/디버깅 시 |

**기본값**: `3` (info 레벨)

---

### LOG_RETENTION_DAYS

```bash
LOG_RETENTION_DAYS=30
```

**용도**: 로그 파일 보관 기간  
**사용 위치**: `shared/logger.php` - `_cleanup_logs()` 함수

**동작**:
- 1% 확률로 로그 정리 실행 (성능 최적화)
- 설정된 일수 이상 된 로그 파일 자동 삭제
- `0` 설정 시 자동 삭제 비활성화

**기본값**: `30`일

---

## 데이터베이스 설정

### SQLX_DEBUG

```bash
SQLX_DEBUG=false
```

**용도**: SQL 쿼리 로깅 활성화  
**사용 위치**: `shared/sqlx.php` - 쿼리 디버그 모드

**동작**:
- `true`: 모든 SQL 쿼리 로그에 기록
- `false`: 에러만 기록

**⚠️ 성능**: 운영 환경에서는 반드시 `false`로 설정하세요.

**기본값**: `false` (또는 `APP_DEBUG` 값 상속)

---

### MySQL 연결 설정

```bash
MYSQL_HOST=mysql
MYSQL_DATABASE=gnuboard
MYSQL_USER=gnuboard
MYSQL_PASSWORD=gnuboard123
DB_PERSISTENT=false  # 추가 옵션
```

**용도**: 데이터베이스 연결 정보  
**사용 위치**: `shared/sqlx.php` - PDO 연결 풀 생성

**우선순위**:
1. 그누보드 상수 (`G5_MYSQL_HOST`, `G5_MYSQL_DB`, 등)
2. 환경 변수 (`MYSQL_HOST`, `MYSQL_DATABASE`, 등)
3. 기본값

**기본값**:
| 변수 | 기본값 | 설명 |
|------|--------|------|
| MYSQL_HOST | `localhost` | DB 서버 호스트 |
| MYSQL_DATABASE | `gnuboard` | 데이터베이스 이름 |
| MYSQL_USER | `root` | DB 사용자 |
| MYSQL_PASSWORD | (빈값) | DB 비밀번호 |
| DB_PERSISTENT | `false` | 지속 연결(Persistent Connection) |

**설명**: 
- 그누보드5와 동일한 데이터베이스 설정 권장
- `DB_PERSISTENT=true` 시 성능 향상 but 연결 수 제한 환경 주의

---

## JWT 인증 설정

### JWT_ACCESS_TOKEN_KEY

```bash
JWT_ACCESS_TOKEN_KEY=your-secret-access-token-key-change-in-production
```

**용도**: 액세스 토큰 서명/검증용 비밀 키  
**사용 위치**: 
- `config/jwt.php` - JWT 설정
- `shared/auth.php` - 토큰 생성/검증

**⚠️ 보안**: 
- 최소 32자 이상의 무작위 문자열 사용
- 운영 환경에서 반드시 변경 필요
- 버전 관리 시스템에 노출 금지

**기본값**: 그누보드 상수 `G5_JWT_ACCESS_TOKEN_KEY` 또는 빈 문자열

---

### JWT_REFRESH_TOKEN_KEY

```bash
JWT_REFRESH_TOKEN_KEY=your-secret-refresh-token-key-change-in-production
```

**용도**: 리프레시 토큰 서명/검증용 비밀 키  
**사용 위치**: 
- `config/jwt.php` - JWT 설정
- `shared/auth.php` - 리프레시 토큰 생성/검증

**⚠️ 보안**: 액세스 토큰 키와 반드시 다른 값 사용

**기본값**: 그누보드 상수 `G5_JWT_REFESH_TOKEN_KEY` 또는 빈 문자열

---

### JWT_CRYPT_KEY

```bash
JWT_CRYPT_KEY=your-secret-crypt-key-change-in-production
```

**용도**: 추가 암호화 작업용 키  
**사용 위치**: `config/jwt.php` - JWT 설정

**설명**: 현재 코드에서 직접 사용되지 않으나, 확장 암호화에 사용 가능

**기본값**: 그누보드 상수 `G5_JWT_CRYPT_KEY` 또는 빈 문자열

---

### JWT_AUDIENCE

```bash
JWT_AUDIENCE=localhost
```

**용도**: JWT 토큰 대상(audience) 클레임  
**사용 위치**: 
- `config/jwt.php` - JWT 설정
- `shared/auth.php` - 토큰 생성 시 `aud` 클레임 설정

**기본값**: 
- 그누보드 상수 `G5_JWT_AUDIENCE` 또는 
- `$_SERVER['HTTP_HOST']` 또는 
- `localhost`

---

### JWT_ACCESS_MTIME

```bash
JWT_ACCESS_MTIME=15
```

**용도**: 액세스 토큰 만료 시간 (분)  
**사용 위치**: 
- `config/jwt.php` - JWT 설정
- `shared/auth.php` - 토큰 생성 및 쿠키 만료 시간

**권장값**:
- 개발: 15분 ~ 60분
- 운영: 5분 ~ 15분 (보안 강화)

**기본값**: 그누보드 상수 `G5_JWT_ACCESS_MTIME` 또는 `15`분

---

### JWT_REFRESH_DATE

```bash
JWT_REFRESH_DATE=30
```

**용도**: 리프레시 토큰 만료 시간 (일)  
**사용 위치**: 
- `config/jwt.php` - JWT 설정
- `shared/auth.php` - 리프레시 토큰 생성

**권장값**:
- 일반: 7일 ~ 30일
- "Remember Me" 기능: 30일 ~ 90일

**기본값**: 그누보드 상수 `G5_JWT_RERESH_DATE` 또는 `30`일

---

## CORS 설정

### CORS_ALLOWED_ORIGINS

```bash
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
```

**용도**: 허용된 오리진(도메인) 목록  
**사용 위치**: `config/cors.php` - CORS 미들웨어

**형식**: 쉼표로 구분된 URL 목록

**기본값**: `http://localhost:5173,http://localhost:3000`

**설명**:
- 프론트엔드 개발 서버 주소 (Vite: 5173, 기타: 3000)
- 와일드카드 `*` 사용 가능하나 **인증 쿠키 사용 시 불가**
- 와일드카드 사용 시 모든 도메인에서 접근 가능 (보안 주의)

---

### CORS_ALLOW_CREDENTIALS

```bash
CORS_ALLOW_CREDENTIALS=true
```

**용도**: 인증 쿠키/헤더 허용  
**사용 위치**: `config/cors.php` - CORS 설정

**가능한 값**:
- `true` - 쿠키, 인증 헤더 전송 허용 (HTTP Only JWT 사용 시 필수)
- `false` - 인증 정보 전송 차단

**⚠️ 중요**: JWT HTTP Only 쿠키 사용 시 반드시 `true`로 설정

**기본값**: `true`

---

### CORS_MAX_AGE

```bash
CORS_MAX_AGE=86400
```

**용도**: CORS 프리플라이트 캐싱 시간 (초)  
**사용 위치**: `config/cors.php` - CORS 설정

**기본값**: `86400` (24시간)

---

## Firebase 설정

### FIREBASE_PROJECT_ID

```bash
FIREBASE_PROJECT_ID=your-project-id
```

**용도**: Firebase 프로젝트 ID  
**사용 위치**: `shared/firebase.php` - Firebase Admin SDK 초기화

**설명**: Firebase 콘솔에서 확인 가능

---

### FIREBASE_PRIVATE_KEY_ID

```bash
FIREBASE_PRIVATE_KEY_ID=your-private-key-id
```

**용도**: 서비스 계정 private key ID  
**사용 위치**: `shared/firebase.php` - Firebase 서비스 계정 설정

**기본값**: 빈 문자열

---

### FIREBASE_PRIVATE_KEY

```bash
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY_HERE\n-----END PRIVATE KEY-----\n"
```

**용도**: Firebase 서비스 계정 개인 키  
**사용 위치**: `shared/firebase.php` - Firebase Admin SDK 인증

**⚠️ 보안**: 
- **절대로 Git에 커밋하지 마세요**
- 따옴표 안에 `\n`으로 줄바꿈 표현
- Firebase Console > 프로젝트 설정 > 서비스 계정 > 비공개 키 생성

---

### FIREBASE_CLIENT_EMAIL

```bash
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com
```

**용도**: Firebase 서비스 계정 이메일  
**사용 위치**: `shared/firebase.php` - Firebase Admin SDK 인증

**형식**: `firebase-adminsdk-{랜덤}@{프로젝트}.iam.gserviceaccount.com`

---

### FIREBASE_CLIENT_ID

```bash
FIREBASE_CLIENT_ID=123456789
```

**용도**: Firebase OAuth 클라이언트 ID  
**사용 위치**: `shared/firebase.php` - Firebase 서비스 계정 설정

**기본값**: 빈 문자열

**설명**: 선택적 설정, 대부분의 경우 자동 설정됨

---

## 파일 업로드 설정

### UPLOAD_STORAGE

```bash
UPLOAD_STORAGE=local
```

**용도**: 파일 업로드 저장소 유형  
**사용 위치**: `shared/upload.php` - 업로드 핸들러

**가능한 값**:
| 값 | 설명 | 사용 시나리오 |
|----|----|-------------|
| `local` | 서버 로컬 파일 시스템 | 단일 서버, 소규모 |
| `s3` | AWS S3 (또는 호환 서비스) | 다중 서버, 대규모, CDN |

**기본값**: `local`

---

### AWS S3 설정 (UPLOAD_STORAGE=s3 시 필요)

```bash
AWS_REGION=ap-northeast-2
AWS_S3_BUCKET=your-bucket-name
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
```

**용도**: AWS S3 연결 정보  
**사용 위치**: `shared/upload.php` - S3 클라이언트 생성

| 변수 | 기본값 | 설명 |
|------|--------|------|
| AWS_REGION | `ap-northeast-2` | S3 리전 (서울) |
| AWS_S3_BUCKET | (필수) | S3 버킷 이름 |
| AWS_ACCESS_KEY_ID | (필수) | AWS IAM 액세스 키 |
| AWS_SECRET_ACCESS_KEY | (필수) | AWS IAM 시크릿 키 |

**⚠️ 보안**: AWS 키는 .env 파일에만 저장하고 Git에 커밋하지 마세요.

---

### UPLOAD_THUMBNAIL_WIDTH / UPLOAD_THUMBNAIL_HEIGHT

```bash
UPLOAD_THUMBNAIL_WIDTH=300
UPLOAD_THUMBNAIL_HEIGHT=300
```

**용도**: 이미지 업로드 시 자동 생성되는 썸네일 크기  
**사용 위치**: `shared/upload.php` - `_upload_handle_thumbnail()` 함수

**기본값**: `300` x `300` 픽셀

**설명**: 이미지 파일 업로드 시 자동으로 생성되는 썸네일의 최대 크기

---

## 앱 버전 설정

### APP_MIN_VERSION

```bash
APP_MIN_VERSION=0.9.0
```

**용도**: 지원하는 최소 앱 버전  
**사용 위치**: `routes/app/version.php` - 버전 체크 API

**기본값**: `0.9.0`

**설명**: 클라이언트 앱이 이 버전보다 낮으면 강제 업데이트 권장

---

### APP_STORE_URL_IOS

```bash
APP_STORE_URL_IOS=https://apps.apple.com/app/karbon
```

**용도**: iOS 앱 스토어 URL  
**사용 위치**: `routes/app/version.php` - 업데이트 링크 제공

**기본값**: `https://apps.apple.com/app/karbon`

---

### APP_STORE_URL_ANDROID

```bash
APP_STORE_URL_ANDROID=https://play.google.com/store/apps/details?id=com.karbon
```

**용도**: Android Play Store URL  
**사용 위치**: `routes/app/version.php` - 업데이트 링크 제공

**기본값**: `https://play.google.com/store/apps/details?id=com.karbon`

---

### PLAY_STORE_LINK / APP_STORE_LINK

```bash
PLAY_STORE_LINK=https://play.google.com/store/apps/details?id=com.karbon
APP_STORE_LINK=https://apps.apple.com/app/karbon
```

**용도**: 스토어 링크 (사이트 정보용)  
**사용 위치**: `routes/site.php` - 사이트 설정 API

**기본값**: 빈 문자열

**설명**: 웹사이트에서 앱 다운로드 버튼에 사용되는 링크

---

## 이메일 설정

> 이메일 발송 설정은 `domains/common/` 도메인에서 관리됩니다.
> MSA 분리 시 외부 API로 연동됩니다.

### EMAIL_PROVIDER

```bash
EMAIL_PROVIDER=gnuboard
```

**용도**: 이메일 발송 방식 선택  
**사용 위치**: `domains/common/service.php`

**가능한 값**:
| 값 | 설명 | 필요 설정 |
|----|------|----------|
| `gnuboard` | 그누보드 mailer.lib.php 사용 | 그누보드 메일 설정 |
| `smtp` | SMTP 직접 연결 (PHPMailer) | SMTP_* 변수 |
| `ses` | AWS SES 사용 | AWS_SES_* 변수 |

**기본값**: `gnuboard`

---

### SMTP 설정 (EMAIL_PROVIDER=smtp)

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME="Your Service Name"
```

**용도**: SMTP 서버 연결 설정  
**사용 위치**: `domains/common/email_repo.php` (PHPMailer)

| 변수 | 기본값 | 설명 |
|------|--------|------|
| SMTP_HOST | (필수) | SMTP 서버 주소 |
| SMTP_PORT | 587 | SMTP 포트 (587=tls, 465=ssl) |
| SMTP_USERNAME | (필수) | SMTP 로그인 ID |
| SMTP_PASSWORD | (필수) | SMTP 로그인 비밀번호 |
| SMTP_ENCRYPTION | tls | 암호화 방식 (tls, ssl, none) |
| SMTP_FROM_EMAIL | SMTP_USERNAME | 발신자 이메일 |
| SMTP_FROM_NAME | "관리자" | 발신자 이름 |

**⚠️ 보안**: Gmail 사용 시 "앱 비밀번호"를 사용해야 합니다.

---

### AWS SES 설정 (EMAIL_PROVIDER=ses)

```bash
AWS_SES_REGION=us-east-1
AWS_SES_ACCESS_KEY_ID=your-access-key
AWS_SES_SECRET_ACCESS_KEY=your-secret-key
```

**용도**: AWS SES 이메일 발송  
**사용 위치**: `domains/common/email_repo.php`

| 변수 | 기본값 | 설명 |
|------|--------|------|
| AWS_SES_REGION | us-east-1 | SES 리전 |
| AWS_SES_ACCESS_KEY_ID | (필수) | AWS Access Key |
| AWS_SES_SECRET_ACCESS_KEY | (필수) | AWS Secret Key |

**참고**: AWS SES는 샌드박스 모드에서 발신자/수신자 주소 사전 등록이 필요합니다.

---

### COMMON_SERVICE_URL (MSA 분리 시)

```bash
COMMON_SERVICE_URL=http://common-service:8080
```

**용도**: MSA 분리 시 이메일/SMS 서비스 URL  
**사용 위치**: `domains/common/service.php`, `domains/common/sms_service.php`

**설명**: 이 값이 설정되면 로컬에서 발송하지 않고 외부 서비스 API로 호출합니다.

---

## SMS 설정

> SMS 문자 발송 설정은 `domains/common/` 도메인에서 관리됩니다.
> MSA 분리 시 외부 API로 연동됩니다.

### SMS_PROVIDER

```bash
SMS_PROVIDER=gnuboard
```

**용도**: SMS 발송 방식 선택  
**사용 위치**: `domains/common/sms_service.php`

**가능한 값**:
| 값 | 설명 | 필요 설정 |
|----|------|----------|
| `gnuboard` | 그누보드 SMS 플러그인 사용 | SMS5 등 플러그인 필요 |
| `aligo` | 알리고 서비스 | ALIGO_* 변수 |
| `coolsms` | 쿨에스엠에스 (NCP) | COOLSMS_* 변수 |
| `twilio` | Twilio | TWILIO_* 변수 |

**기본값**: `gnuboard`

---

### 알리고 설정 (SMS_PROVIDER=aligo)

```bash
ALIGO_API_KEY=your-aligo-api-key
ALIGO_USER_ID=your-aligo-user-id
ALIGO_SENDER=01012345678
```

**용도**: 알리고 SMS 발송  
**사용 위치**: `domains/common/sms_repo.php`

| 변수 | 기본값 | 설명 |
|------|--------|------|
| ALIGO_API_KEY | (필수) | 알리고 API 키 |
| ALIGO_USER_ID | (필수) | 알리고 사용자 ID |
| ALIGO_SENDER | (필수) | 발신번호 (사전 등록 필요) |

**⚠️ 참고**: 알리고는 발신번호 사전 등록이 필요합니다. 개발 환경에서는 자동으로 테스트 모드로 동작합니다.

---

### 쿨에스엠에스 설정 (SMS_PROVIDER=coolsms)

```bash
COOLSMS_API_KEY=your-coolsms-api-key
COOLSMS_API_SECRET=your-coolsms-api-secret
COOLSMS_SENDER=01012345678
```

**용도**: 쿨에스엠에스 (NCP) SMS 발송  
**사용 위치**: `domains/common/sms_repo.php`

| 변수 | 기본값 | 설명 |
|------|--------|------|
| COOLSMS_API_KEY | (필수) | 쿨에스엠에스 API Key |
| COOLSMS_API_SECRET | (필수) | 쿨에스엠에스 API Secret |
| COOLSMS_SENDER | (필수) | 발신번호 |

---

### Twilio 설정 (SMS_PROVIDER=twilio)

```bash
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_FROM_NUMBER=+1234567890
```

**용도**: Twilio SMS 발송 (해외용)  
**사용 위치**: `domains/common/sms_repo.php`

| 변수 | 기본값 | 설명 |
|------|--------|------|
| TWILIO_SID | (필수) | Twilio Account SID |
| TWILIO_TOKEN | (필수) | Twilio Auth Token |
| TWILIO_FROM_NUMBER | (필수) | Twilio 발신번호 |

**참고**: 한국 서비스에는 알리고나 쿨에스엠에스 사용을 권장합니다.

---

## 푸시 알림 설정

### PUSH_PROVIDER

```bash
PUSH_PROVIDER=firebase
```

**용도**: 푸시 알림 방식 선택  
**사용 위치**: Firebase 설정과 연동

**가능한 값**:
| 값 | 설명 |
|----|------|
| `firebase` | Firebase Cloud Messaging (FCM) 사용 |
| `none` | 푸시 알림 사용 안함 |

**기본값**: `firebase`

**설명**: Firebase 설정은 상단 `FIREBASE_*` 변수와 공유합니다.

---

## 환경 변수 로드 메커니즘

### Config 클래스 로드 흐름

```php
// 1. .env 파일 파싱 (shared/config_class.php)
foreach (file('.env') as $line) {
    // KEY=VALUE 파싱
    putenv("$key=$value");    // 시스템 환경 변수로 설정
    $_ENV[$key] = $value;     // PHP 전역 변수로 설정
}

// 2. config/ 폴더의 PHP 파일 로드
$configData = require_once 'config/app.php';  // 배열 반환
self::$config['app'] = $configData;

// 3. 사용 시
g getenv('APP_ENV');          // 직접 접근
config('app.env');             // Config 클래스 사용 (권장)
env('APP_ENV', 'development'); // env() 헬퍼 함수 사용
```

### env() 헬퍼 함수

`config/env.php`에 정의된 편의 함수:

```php
env('APP_DEBUG', false);
```

**특징**:
- 불리언 문자열 자동 변환: `'true'` → `true`, `'false'` → `false`
- 널 문자열 변환: `'null'` → `null`
- 기본값 지정 가능

### config() 헬퍼 함수

`shared/config.php`에 정의된 권장 접근 방법:

```php
// 단일 값
config('app.env');

// 기본값 지정
config('jwt.access_mtime', 15);

// 중첩된 설정
config('app.cookie_samesite');
```

**장점**:
- 자동으로 Config::load() 호출
- 그누보드 상수와의 통합
- 일관된 기본값 처리

---

## 보안 권장사항

### 🔴 반드시 변경해야 할 값 (운영 배포 시)

| 변수 | 위험도 | 설명 |
|------|--------|------|
| `JWT_ACCESS_TOKEN_KEY` | 🔴 높음 | 토큰 위조 가능 |
| `JWT_REFRESH_TOKEN_KEY` | 🔴 높음 | 장기 세션 탈취 |
| `JWT_CRYPT_KEY` | 🟡 중간 | 추가 암호화 손상 |
| `MYSQL_PASSWORD` | 🔴 높음 | DB 유출 |
| `FIREBASE_PRIVATE_KEY` | 🔴 높음 | Firebase 계정 탈취 |
| `AWS_SECRET_ACCESS_KEY` | 🔴 높음 | AWS 계정 탈취 |
| `SMTP_PASSWORD` | 🔴 높음 | 이메일 계정 탈취 |
| `ALIGO_API_KEY` | 🟡 중간 | SMS 발송 남용 |
| `COOLSMS_API_SECRET` | 🟡 중간 | SMS 발송 남용 |
| `TWILIO_TOKEN` | 🟡 중간 | SMS 발송 남용 |

### 🟡 환경별 설정 차이

**development**:
```bash
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=4
SQLX_DEBUG=true
APP_HTTPS_ONLY=false
EMAIL_PROVIDER=gnuboard    # 개발 시 그누보드 사용
SMS_PROVIDER=gnuboard      # 개발 시 그누보드 사용 (또는 테스트 모드)
```

**production**:
```bash
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=0  # 또는 1
SQLX_DEBUG=false
APP_HTTPS_ONLY=true
APP_COOKIE_SAMESITE=Lax  # 또는 Strict
JWT_ACCESS_MTIME=5       # 짧은 토큰 수명

# 이메일/SMS 설정
EMAIL_PROVIDER=smtp      # 또는 ses
SMS_PROVIDER=aligo       # 또는 coolsms
SMTP_FROM_EMAIL=noreply@yourdomain.com
```

### 🟢 환경 변수 관리 모범 사례

1. **.env.example 커밋**: 실제 값 없이 템플릿만 버전 관리
2. **.env 파일 .gitignore**: 실제 설정 파일은 Git에 포함 금지
3. **길고 무작위한 키 사용**: `openssl rand -base64 32`
4. **정기적 키 교체**: JWT 키는 6개월~1년마다 교체 권장
5. **환경별 분리**: `.env.development`, `.env.production` 등 분리 사용

---

## 참고 자료

- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) - PHP DotEnv 라이브러리
- [JWT.io](https://jwt.io/) - JWT 디버깅 도구
- [OWASP CORS Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/CORS_Configuration_Cheat_Sheet.html)
- [Firebase Admin SDK 문서](https://firebase.google.com/docs/admin/setup)
- [MSA 설계 문서](./msa.md) - MSA 전환 설계
- [Common Domain README](../domains/common/README.md) - 이메일/SMS 사용 가이드

---

## 변경 이력

| 버전 | 날짜 | 변경 내용 |
|------|------|----------|
| 1.1.0 | 2026-02-25 | 이메일/SMS 설정 추가 (EMAIL_PROVIDER, SMS_PROVIDER, SMTP, 알리고, 쿨에스엠에스, Twilio) |
| 1.0.0 | 2026-02-25 | 초기 문서 작성 |