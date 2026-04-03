# 스크립트 문서

개발 및 배포 자동화 스크립트 사용법을 설명합니다.

## 1. 설치 스크립트

### install.sh

프로젝트 초기 설치를 담당하는 메인 스크립트입니다.

```bash
# 전체 설치 (백엔드 + 프론트엔드)
./scripts/install.sh --all

# 백엔드만 설치
./scripts/install.sh --backend

# 프론트엔드만 설치
./scripts/install.sh --frontend
```

**주요 기능:**
- 그누보드5 다운로드 및 설정
- `backend/gnu` → `backend/v1` 파일 복사
- 프론트엔드 의존성 설치
- Docker 개발환경 구성

## 2. 배포 스크립트

### setup-hosting.sh

Cafe24 호스팅 초기 설정을 담당합니다.

```bash
./scripts/setup-hosting.sh
```

**실행 내용:**
1. SFTP 연결 테스트
2. `backend/v1/` 파일 업로드
3. `data/` 폴더 생성 및 권한 설정
4. `.htaccess` 파일 업로드

### deploy-frontend.sh

프론트엔드를 빌드하고 서버로 배포합니다.

```bash
# 배포 전 .env 설정 확인
./scripts/deploy-frontend.sh
```

**실행 내용:**
1. `npm install` 실행
2. `npm run build` 실행
3. `rsync`로 서버에 파일 동기화

### deploy-api.sh

백엔드 API만 배포합니다.

```bash
./scripts/deploy-api.sh
```

**특징:**
- `data/` 폴더 제외
- `.env` 파일 제외 (보안)
- 증분 업데이트 지원

### deploy-main.sh / deploy-admin.sh / deploy-app.sh

각 프론트엔드 프로젝트 개별 배포 스크립트입니다.

```bash
./scripts/deploy-main.sh    # 메인 웹사이트
./scripts/deploy-admin.sh    # 관리자 페이지
./scripts/deploy-app.sh      # 앱 시연용
```

## 3. 개발 도구

### ftp-watch.sh

파일 변경 감시 및 실시간 업로드 스크립트입니다.

```bash
# 실행
./scripts/ftp-watch.sh

# 중지
# Ctrl+C
```

**필수 도구:**
- `fswatch` (파일 감시)
- `lftp` (업로드)

**설치:**
```bash
# macOS
brew install fswatch lftp

# Linux
sudo apt-get install fswatch lftp
```

**용도:**
- Cafe24 환경에서 로컬 개발 시
- 코드 변경 즉시 서버에 반영

### fix-hrefs.cjs

빌드 후 HTML 파일 경로 보정 유틸리티입니다.

```bash
node scripts/fix-hrefs.cjs
```

**용도:**
- 상대 경로 ↔ 절대 경로 변환
- CDN 주소로 경로 변경

## 4. 환경 변수

스크립트에서 사용되는 주요 환경 변수입니다.

### 배포 설정

| 변수 | 설명 | 예시 |
|------|------|------|
| `SFTP_HOST` | FTP 서버 주소 | `ftp.cafe24.com` |
| `SFTP_PORT` | FTP 포트 | `22` |
| `SFTP_USER` | FTP 사용자명 | `user123` |
| `SFTP_PASS` | FTP 비밀번호 | `********` |
| `SFTP_PATH` | FTP 경로 | `/www` |

### 개발 설정

| 변수 | 설명 | 기본값 |
|------|------|--------|
| `DEV_MODE` | 개발 모드 | `local` |
| `DEPLOY_FRONT` | 배포 환경 | `hosting` |

## 5. 윈도우 사용자

PowerShell 스크립트가 제공됩니다:

```powershell
# Windows PowerShell
.\scripts\setup-hosting.ps1
.\scripts\ftp-watch.ps1
```

## 6. 스크립트 수정

### SFTP 설정 변경

```bash
# .env 파일 편집
vi .env

# 변경 후 확인
cat .env | grep SFTP
```

### 배포 경로 변경

```bash
# deploy-frontend.sh 편집
vi scripts/deploy-frontend.sh

# REMOTE_PATH 수정
REMOTE_PATH="/your/custom/path"
```

## 7. 문제 해결

### lftp 연결 실패
```bash
# 연결 테스트
lftp -p 22 -u "user","pass" sftp://host
```

### 권한 오류
```bash
# 스크립트 실행 권한 부여
chmod +x scripts/*.sh
```

### Docker 컨테이너 오류
```bash
# 로그 확인
docker-compose logs -f

# 재시작
docker-compose restart
```

---

## 스크립트 목록

| 스크립트 | 용도 |
|---------|------|
| `install.sh` | 통합 설치 |
| `setup-hosting.sh` | Cafe24 설정 |
| `deploy-frontend.sh` | 프론트엔드 배포 |
| `deploy-api.sh` | API 배포 |
| `deploy-main.sh` | 메인 웹 배포 |
| `deploy-admin.sh` | 관리자 배포 |
| `deploy-app.sh` | 앱 배포 |
| `ftp-watch.sh` | 실시간 동기화 |
| `fix-hrefs.cjs` | 경로 보정 |

---

## 다음 단계

- [Cafe24 배포 가이드](deploy-cafe24.md)
- [클라우드 배포 가이드](deploy-cloud.md)
- [상세 설치 가이드](install-detailed.md)
