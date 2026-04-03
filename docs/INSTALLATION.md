# 📦 상세 설치 가이드

Karbon 보일러플레이트의 완전한 설치 방법을 안내합니다.

## 🎯 설치 흐름

1. 그누보드5 설치
2. API 폴더 업로드
3. 환경 설정
4. 프론트엔드 설치

---

## 1️⃣ 그누보드5 설치

### PHP 웹호스팅 (Cafe24 등) 사용 시

1. **그누보드5 다운로드**
   - https://sir.kr 에서 최신 버전 다운로드

2. **FTP 업로드**
   ```bash
   # 파일을 웹호스팅에 업로드
   # 예: /public_html/gnuboard/ 경로에 업로드
   ```

3. **설치 진행**
   - 브라우저에서 `http://your-domain/gnuboard/` 접속
   - 설치 마법사 따라 진행
   - DB 정보 입력 (호스팅에서 제공)

### Docker 로컬 개발 시

```bash
cd gnu5_api
./install.sh
./start.sh
```

---

## 2️⃣ API 폴더 업로드

### FTP 방식 (원격 서버)

```bash
# API 배포 스크립트 사용
./scripts/deploy-api.sh

# 또는 FTP watch 모드로 자동 업로드
./scripts/ftp-watch.sh
```

### 수동 업로드

1. `gnu5_api/gnuboard/api/` 폴더를 압축
2. FTP로 서버의 그누보드 폴더에 업로드
3. 압축 해제

---

## 3️⃣ 환경 설정

### .env 파일 생성

```bash
# 설정 스크립트 실행 (대화형)
./setup-env.sh

# 또는 자동 모드
./setup-env.sh --auto
```

### 필수 환경 변수

```env
# 개발 모드
DEV_MODE=remote  # local 또는 remote

# FTP 설정 (remote 모드 사용 시)
FTP_HOST=your-ftp-host.com
FTP_USER=your-username
FTP_PASS=your-password
FTP_PATH=/public_html/gnuboard

# 앱 버전
APP_VERSION=1.0.0
APP_MIN_VERSION=0.9.0
```

---

## 4️⃣ 프론트엔드 설치

```bash
cd frontend

# 의존성 설치
npm install

# 개발 서버 시작
npm run dev
```

브라우저에서 `http://localhost:5173` 접속

---

## 🔧 설치 확인

### API 테스트
```bash
curl http://your-domain/api/app/version
```

### 프론트엔드 빌드 테스트
```bash
cd frontend
npm run build
```

---

## 📝 다음 단계

- [💻 개발 환경 설정](./DEVELOPMENT.md)
- [🚀 배포 가이드](./DEPLOYMENT.md)
- [📱 하이브리드앱 기능](./HYBRID_APP.md)
