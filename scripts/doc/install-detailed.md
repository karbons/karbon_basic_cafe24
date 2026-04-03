# 상세 설치 가이드

카본 빌더 Basic 프로젝트를 로컬 환경에 설치하고 실행하는 상세 과정을 설명합니다.

## 1. 사전 요구사항

| 도구 | 버전 | 설명 |
|------|------|------|
| Node.js | 18+ | 프론트엔드 빌드 |
| PHP | 8.0+ | 백엔드 실행 |
| MySQL/MariaDB | 5.7+ | 데이터베이스 |
| Git | 2.0+ | 소스 코드 관리 |
| Docker | 20+ (선택) | 로컬 개발 환경 |

## 2. 프로젝트 복제

```bash
git clone <repository-url> karbon-basic-cafe24
cd karbon-basic-cafe24
```

## 3. 환경 설정

### 3.1 .env 파일 생성

```bash
cp .env.example .env
```

### 3.2 주요 설정 항목

```bash
# 개발 모드
DEV_MODE=local

# 배포 환경 (hosting/myserver/cloudflare/versel)
DEPLOY_FRONT=hosting

# FTP/SFTP 배포 설정
SFTP_HOST=your-ftp.cafe24.com
SFTP_USER=your_id
SFTP_PASS=your_password
SFTP_PATH=/www
```

## 4. 그누보드5 다운로드

```bash
# 자동 설치 스크립트 실행
./scripts/install.sh --backend
```

또는 수동으로:

```bash
cd backend
git clone https://github.com/gnuboard/gnuboard5.git v1
```

## 5. 로컬 개발 환경 (Docker)

### 5.1 Docker로 개발환경 실행

```bash
cd docker
docker-compose up -d
```

### 5.2 접속 정보

- 웹 서버: http://localhost
- phpMyAdmin: http://localhost:8080
- MySQL: localhost:3306

### 5.3 데이터베이스 생성

phpMyAdmin 접속 후:
1. 새 데이터베이스 생성: `gnuboard5`
2. 문자집합: `utf8mb4_unicode_ci`

## 6. 그누보드5 설치

1. 브라우저에서 http://localhost/v1/install 접속
2. 설치 마법사 완료
3. 관리자 계정 설정

## 7. 프론트엔드 설정

### 7.1 의존성 설치

```bash
# 메인 웹사이트
cd frontend/main && npm install

# 앱
cd frontend/app && npm install

# 관리자
cd frontend/admin && npm install
```

### 7.2 환경 변수 설정

각 프론트엔드 폴더에 `.env` 파일 생성:

```bash
# frontend/main/.env
VITE_API_URL=http://localhost/v1/api
```

### 7.3 개발 서버 실행

```bash
# 메인 웹사이트
cd frontend/main && npm run dev

# 앱
cd frontend/app && npm run dev

# 관리자
cd frontend/admin && npm run dev
```

## 8. 문제 해결

### npm install 실패
```bash
rm -rf node_modules package-lock.json
npm install
```

### Docker 컨테이너 시작 실패
```bash
docker-compose down
docker-compose up -d --force-recreate
```

### data 폴더 권한 오류
```bash
chmod -R 707 backend/v1/data
```

### API 연결 오류
- `.env` 파일의 API URL 확인
- 백엔드 서버 실행 상태 확인

---

## 다음 단계

- [Cafe24 배포 가이드](deploy-cafe24.md)
- [클라우드 배포 가이드](deploy-cloud.md)
- [스크립트 문서](scripts.md)
