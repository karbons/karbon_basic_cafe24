# 💻 개발 환경 설정

Karbon 프로젝트의 개발 환경을 설정하는 방법을 안내합니다.

## 🎯 개발 모드 선택

Karbon은 두 가지 개발 모드를 지원합니다:

| 모드 | 설명 | 사용场景 |
|------|------|----------|
| **local** | Docker 로컬 개발 | 로컬에서 완전한 환경 구축 |
| **remote** | FTP 원격 개발 | 실제 서버에 직접 업로드하며 개발 |

---

## 🐳 Local 모드 (Docker)

### 사전 요구사항

- Docker Desktop 설치
- Docker Compose 설치

### 시작하기

```bash
cd gnu5_api

# 컨테이너 시작
./start.sh

# 또는
./restart.sh
```

### 서비스 구성

| 서비스 | 포트 | 설명 |
|--------|------|------|
| Nginx | 80, 443 | 웹서버 |
| PHP-FPM | 9000 | PHP 처리 |
| MySQL | 3306 | 데이터베이스 |
| Redis | 6379 | 캐시 |
| phpMyAdmin | 8080 | DB 관리 도구 |

### 접속 정보

- **웹사이트**: http://localhost
- **API**: http://localhost/api
- **phpMyAdmin**: http://localhost:8080

---

## 🌐 Remote 모드 (FTP)

### 사전 요구사항

- FTP 접속 정보
- fswatch 설치 (Mac): `brew install fswatch`
- lftp 설치 (Mac): `brew install lftp`

### 설정

```bash
# .env 파일에서 DEV_MODE=remote 설정
./setup-env.sh
# → 2) remote 선택
# → FTP 정보 입력
```

### FTP Watch 시작

```bash
# 파일 변경 감시 및 자동 업로드
./scripts/ftp-watch.sh

# FTP 연결 테스트
./scripts/ftp-watch.sh --test
```

### 수동 배포

```bash
# API 폴더 수동 업로드
./scripts/deploy-api.sh

# Dry-run (업로드 목록만 확인)
./scripts/deploy-api.sh --dry-run
```

---

## 🔄 개발 워크플로우

### 일반적인 개발 흐름

1. **코드 수정** (로컬 IDE)
2. **자동 업로드** (FTP watch가 감지하여 업로드)
3. **브라우저 확인** (실제 서버에서 동작 확인)

### 프론트엔드 개발

```bash
cd frontend

# 개발 서버
npm run dev

# API 프록시 설정 (vite.config.js)
# → 로컬: http://localhost/api
# → 원격: https://your-domain/api
```

---

## 🛠️ 유용한 스크립트

| 스크립트 | 설명 |
|----------|------|
| `setup-env.sh` | 환경 설정 |
| `ftp-watch.sh` | FTP 파일 감시 |
| `deploy-api.sh` | API 수동 배포 |
| `deploy-frontend.sh` | 프론트엔드 배포 |

---

## 📝 팁과 노하우

### 대용량 파일 처리
- 100MB 이상 파일은 수동 업로드 권장
- FTP watch는 자동으로 경고 표시

### 네트워크 문제
- FTP watch는 자동 재연결 시도 (3회)
- 연결 실패 시 로그 확인

### 성능 최적화
- Docker 모드: 로컬 개발 시 더 빠름
- FTP 모드: 실제 환경과 동일

---

## 🚀 다음 단계

- [🚀 배포 가이드](./DEPLOYMENT.md)
- [📱 하이브리드앱 기능](./HYBRID_APP.md)
