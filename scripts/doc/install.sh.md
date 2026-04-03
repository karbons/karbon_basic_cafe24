# 개발 및 배포 환경 설정

## 백엔드(api) 설정
1. .env 파일 확인
2. /backend/v1 파일 생성
 1) https://github.com/gnuboard/gnuboard5 다운로드
 2) /backend/gnu -> /backend/v1/로 복사
 3) /backend/v1/config.php .env 참고하여 내용 수정
 4) /backend/v1/data 폴더 생성 권한 부여

4. 백엔드 배포 서버 폴더 구성
  -/v1 : api 서버(그누보드 + fapi)
  -/app : 앱 시연용 폴더(하이브리드앱 디바이스 설치 전 개발 과정 시연용)
  -/main : 프론트엔드 폴더(렌딩페이지 또는 웹 서비스)
  -/admin : 관리자 화면

5. 백엔드 개발환경 또는 배포 환경 구성
 .env DEV_MODE, DEPLOY_FRONT 값 확인 설정한 환경에 맞게 진행

 1) 로컬개발환경 DEV_MODE=local
  - .env 파일 확인
  - ./scripts/setup-local.sh 실행
  - /docker/docker-compose.yml 파일 확인
  - /docker/docker-compose up -d
 
 2) 배포환경(DEV_MODE=remote, DEPLOY_FRONT=hosting - cafe24 호스팅)
  - .env 파일 확인
  - 서버폴더 /v1/ 폴더에 로컬 /backend/v1/ 파일 복사
  - 서버폴더 /v1/data 폴더 생성 및 권한 부여
  - 서버폴더 /.htaccess 파일 생성
  - setup-hosting.sh 파일(mac) 실행
  - setup-hosting.ps1 파일(윈도우) 실행
 
 3) 배포환경(DEV_MODE=remote, DEPLOY_FRONT=myserver - 클라우드, idc 서버)
  - .env 파일 확인
  - setup-myserver.sh 파일(mac) 실행
  - setup-myserver.ps1 파일(윈도우) 실행
 
 4) 배포환경(DEV_MODE=remote, DEPLOY_FRONT=cloudflare - 클라우드플레어)
  - .env 파일 확인
  - setup-cloudflare.sh 파일(mac) 실행
  - setup-cloudflare.ps1 파일(윈도우) 실행
 
 5) 배포환경(DEV_MODE=remote, DEPLOY_FRONT=versel - 버셀)
  - .env 파일 확인
  - setup-versel.sh 파일(mac) 실행
  - setup-versel.ps1 파일(윈도우) 실행



## 프론트엔드 설정
### app
```
npm install
```

### admin
```
npm install
```

### web
```
npm install
```




