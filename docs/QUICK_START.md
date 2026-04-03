# 🚀 빠른 시작 가이드

5분 안에 Karbon 프로젝트를 시작해보세요!

## 📋 사전 요구사항

- **Node.js** 18+ (https://nodejs.org)
- **PHP** 8.1+ (이미 설치된 경우)
- **Git** (https://git-scm.com)

## ⚡ 3단계 빠른 시작

### 1단계: 프로젝트 다운로드

```bash
# ZIP 파일 다운로드 후 압축 해제
# 또는 git clone
git clone <repository-url> karbon-basic
cd karbon-basic
```

### 2단계: 환경 설정

```bash
# 환경 설정 스크립트 실행
./setup-env.sh

# 프롬프트에 따라 선택:
# - DEV_MODE: local (Docker) 또는 remote (FTP)
# - FTP 설정 (remote 선택 시)
```

### 3단계: 개발 서버 시작

**Docker 모드 (local):**
```bash
cd gnu5_api
./start.sh
```

**FTP 모드 (remote):**
```bash
# FTP watch 시작
./scripts/ftp-watch.sh
```

**프론트엔드 개발 서버:**
```bash
cd frontend
npm install
npm run dev
```

## 🎯 다음 단계

- [📖 상세 설치 가이드](./INSTALLATION.md)
- [💻 개발 환경 설정](./DEVELOPMENT.md)
- [📱 하이브리드앱 기능](./HYBRID_APP.md)

## ❓ 문제 해결

**Q: npm install이 실패합니다**  
A: Node.js 버전을 확인하세요 (18+ 필요)

**Q: Docker가 없습니다**  
A: DEV_MODE=remote로 설정하고 FTP 방식을 사용하세요

**Q: FTP 연결이 안됩니다**  
A: `./scripts/ftp-watch.sh --test`로 연결 테스트를 해보세요
