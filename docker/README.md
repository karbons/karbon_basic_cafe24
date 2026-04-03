# GNU5 API Docker 환경

그누보드5 API를 위한 Docker 환경 설정입니다.

## 구성 요소

- **Nginx**: 웹서버 (포트 80, 443)
- **PHP-FPM**: PHP 8.1 with 필수 확장 모듈 (포트 9000)
- **MySQL**: 데이터베이스 (포트 3306)
- **Redis**: 캐시 서버 (포트 6379)
- **phpMyAdmin**: 데이터베이스 관리 도구 (포트 8080)

## 설치된 PHP 확장 모듈

### 필수 확장 모듈
- **GD**: 이미지 처리 (JPEG, PNG, GIF, WebP 지원)
- **MySQLi, PDO_MySQL**: MySQL 데이터베이스 연결
- **MBString**: 멀티바이트 문자열 처리
- **OpenSSL**: 암호화 및 SSL/TLS 지원
- **cURL**: HTTP 통신
- **ZIP**: 압축 파일 처리

### 성능 최적화
- **Redis**: 메모리 캐시
- **APCu**: 로컬 캐시
- **OPcache**: PHP 코드 캐싱

### 기타 확장 모듈
- **XML, XSL**: XML 문서 처리
- **Intl**: 국제화 및 유니코드 지원
- **Gettext**: 다국어 지원

## 설치 및 실행

### 1. 사전 요구사항

- Docker
- Docker Compose

### 2. 설치

```bash
# 설치 스크립트 실행
./install.sh
```

### 3. 컨테이너 관리

```bash
# 컨테이너 시작
./start.sh

# 컨테이너 중지
./stop.sh

# 컨테이너 재시작
./restart.sh

# 로그 확인
docker-compose logs -f
```

## 접속 정보

- **웹사이트**: http://localhost
- **API 엔드포인트**: http://localhost/api
- **phpMyAdmin**: http://localhost:8080
  - 사용자: gnuboard
  - 비밀번호: gnuboard123
- **MySQL**: localhost:3306
  - 데이터베이스: gnuboard
  - 사용자: gnuboard
  - 비밀번호: gnuboard123
- **Redis**: localhost:6379

## API 설정

nginx에서 `/api` 폴더에 대한 rewrite 설정이 포함되어 있습니다:

- 모든 API 요청은 `/api/index.php`로 라우팅됩니다
- CORS 설정이 포함되어 있습니다
- OPTIONS 요청에 대한 적절한 응답을 제공합니다

## 파일 구조

```
.
├── docker-compose.yml          # Docker Compose 설정
├── Dockerfile.php             # PHP 커스텀 이미지
├── nginx/
│   ├── nginx.conf             # Nginx 메인 설정
│   └── conf.d/
│       └── default.conf       # 가상 호스트 설정
├── php/
│   ├── php.ini               # PHP 설정
│   └── www.conf              # PHP-FPM 풀 설정
├── mysql/
│   └── init/
│       └── 01-init.sql       # MySQL 초기화 스크립트
├── logs/                     # 로그 디렉토리
│   ├── nginx/
│   ├── php/
│   ├── mysql/
│   └── redis/
├── install.sh                # 설치 스크립트
├── start.sh                  # 시작 스크립트
├── stop.sh                   # 중지 스크립트
├── restart.sh                # 재시작 스크립트
└── README.md                 # 이 파일
```

## 설정 변경

### Nginx 설정 변경
`nginx/conf.d/default.conf` 파일을 수정한 후 컨테이너를 재시작하세요.

### PHP 설정 변경
`php/php.ini` 파일을 수정한 후 컨테이너를 재빌드하세요:
```bash
docker-compose build php
docker-compose up -d php
```

### MySQL 설정 변경
`docker-compose.yml`의 mysql 서비스 섹션을 수정한 후 컨테이너를 재시작하세요.

## 문제 해결

### 컨테이너 로그 확인
```bash
# 모든 서비스 로그
docker-compose logs

# 특정 서비스 로그
docker-compose logs nginx
docker-compose logs php
docker-compose logs mysql
docker-compose logs redis
```

### 컨테이너 내부 접속
```bash
# PHP 컨테이너 접속
docker-compose exec php sh

# MySQL 컨테이너 접속
docker-compose exec mysql mysql -u gnuboard -p

# Redis 컨테이너 접속
docker-compose exec redis redis-cli
```

### PHP 확장 모듈 확인
```bash
# 설치된 확장 모듈 목록
docker-compose exec php php -m

# 특정 확장 모듈 확인
docker-compose exec php php -m | grep gd
```

## 성능 최적화

### OPcache 설정
PHP OPcache가 기본적으로 활성화되어 있어 PHP 코드 실행 성능이 향상됩니다.

### Redis 캐시
Redis를 사용하여 세션 및 데이터 캐싱이 가능합니다.

### APCu 캐시
로컬 메모리 캐시로 애플리케이션 성능을 향상시킵니다.

## 보안 고려사항

- 프로덕션 환경에서는 기본 비밀번호를 변경하세요
- SSL 인증서를 설정하세요
- 방화벽 설정을 확인하세요
- 정기적인 보안 업데이트를 수행하세요
- phpMyAdmin은 개발 환경에서만 사용하세요 