# Cafe24 배포 가이드

Cafe24 PHP/MariaDB 호스팅에 프로젝트를 배포하는 방법을 설명합니다.

## 1. 사전 준비

### 1.1 SFTP 정보 설정

`.env` 파일에 SFTP 접속 정보를 입력:

```bash
DEV_MODE=remote
DEPLOY_FRONT=hosting

SFTP_HOST=your-id.cafe24.com
SFTP_PORT=22
SFTP_USER=your-id
SFTP_PASS=your-password
SFTP_PATH=/www
```

### 1.2 lftp 설치

```bash
# macOS
brew install lftp

# Linux
sudo apt-get install lftp
```

## 2. 백엔드 배포

### 2.1 초기 설정 스크립트 실행

```bash
./scripts/setup-hosting.sh
```

이 스크립트가 수행하는 작업:
1. `backend/v1/` 파일을 서버의 `/v1/` 폴더로 업로드
2. `/v1/data/` 폴더 생성 및 권한 설정 (707)
3. `.htaccess` 파일 업로드

### 2.2 수동 업로드 (대용량 파일)

```bash
lftp -p 22 -u "your-id","your-password" sftp://your-id.cafe24.com << EOF
mirror -R backend/v1 /www/v1
bye
EOF
```

## 3. 프론트엔드 배포

### 3.1 메인 웹사이트 배포

```bash
cd frontend/main

# 의존성 설치
npm install

# 빌드
npm run build
```

빌드된 파일을 FTP로 `/www/main/` 폴더에 업로드

### 3.2 앱 배포

```bash
cd frontend/app
npm install
npm run build
```

### 3.3 관리자 배포

```bash
cd frontend/admin
npm install
npm run build
```

## 4. 서버 설정

### 4.1 PHP 버전 설정

Cafe24 관리콘솔 → PHP 설정 → **8.1 이상**으로 설정

### 4.2 data 폴더 권한

SFTP 접속 후 실행:

```bash
chmod 707 /www/v1/data
```

### 4.3 .htaccess 확인

루트 `.htaccess` 파일이 다음을 포함하는지 확인:

```apache
RewriteEngine On

# /api 요청을 /v1/fapi로 전달
RewriteRule ^api/(.*)$ /v1/fapi/$1 [L]

# /v1 요청을 /v1/index.php로 전달
RewriteRule ^v1/(.*)$ /v1/index.php?_route=/$1 [L,QSA]
```

## 5. 주의사항

- **정적 파일**: Cafe24는 Node.js 실행 불가 → `adapter-static` 사용
- **경로**: `/www`가 웹 루트
- **세션**: PHP 세션 경로가 `data/session`인지 확인

## 6. 문제 해결

### 500 Internal Server Error
- `.htaccess` 문법 오류 확인
- PHP 버전 확인 (8.1+)
- 권한 확인 (data 폴더)

### API 404 Error
- `.htaccess` RewriteRule 확인
- `/v1/` 폴더 업로드 확인

### DB 연결 오류
- `config.php` DB 정보 확인
- Cafe24 MySQL 접속 정보 확인

---

## 다음 단계

- [클라우드 배포 가이드](deploy-cloud.md)
- [스크립트 문서](scripts.md)
- [상세 설치 가이드](install-detailed.md)
