#!/bin/bash

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== GNU5 API Docker 환경 설치 스크립트 ===${NC}"

# Docker 설치 확인
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker가 설치되어 있지 않습니다. Docker를 먼저 설치해주세요.${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose가 설치되어 있지 않습니다. Docker Compose를 먼저 설치해주세요.${NC}"
    exit 1
fi

echo -e "${GREEN}Docker 및 Docker Compose가 설치되어 있습니다.${NC}"

# .env 파일 로드
if [ -f "../.env" ]; then
    export $(cat ../.env | grep -v '^#' | xargs)
elif [ -f ".env" ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# PROJECT_NAME 설정 (기본값: gnu5)
PROJECT_NAME=${PROJECT_NAME:-gnu5}

echo -e "${BLUE}프로젝트명: ${PROJECT_NAME}${NC}"

# 컨테이너명 목록
CONTAINER_NAMES=(
    "${PROJECT_NAME}_nginx"
    "${PROJECT_NAME}_php"
    "${PROJECT_NAME}_mysql"
    "${PROJECT_NAME}_redis"
    "${PROJECT_NAME}_phpmyadmin"
)

# 기존 컨테이너 중복 체크
echo -e "${YELLOW}기존 컨테이너 확인 중...${NC}"
EXISTING_CONTAINERS=()
for container in "${CONTAINER_NAMES[@]}"; do
    if docker ps -a --format '{{.Names}}' | grep -q "^${container}$"; then
        EXISTING_CONTAINERS+=("$container")
    fi
done

# 중복된 컨테이너가 있을 경우 알림
if [ ${#EXISTING_CONTAINERS[@]} -gt 0 ]; then
    echo -e "${YELLOW}=== 알림: 이미 생성된 컨테이너가 발견되었습니다 ===${NC}"
    echo -e "${YELLOW}다음 컨테이너들이 이미 존재합니다:${NC}"
    for container in "${EXISTING_CONTAINERS[@]}"; do
        WORKING_DIR=$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project.working_dir"}}' "$container" 2>/dev/null)
        echo -e "${YELLOW}  - ${container} (위치: ${WORKING_DIR:-알 수 없음})${NC}"
    done
    echo ""
    echo -e "${YELLOW}기존 컨테이너가 현재 프로젝트와 충돌할 수 있습니다.${NC}"
    echo -e "${YELLOW}충돌 발생 시 .env 파일에서 PROJECT_NAME을 변경하거나 기존 컨테이너를 삭제하세요.${NC}"
    echo ""
fi

echo -e "${GREEN}컨테이너명 중복 없음. 설치를 진행합니다.${NC}"

# 필요한 디렉토리 생성
echo -e "${YELLOW}필요한 디렉토리를 생성합니다...${NC}"
mkdir -p nginx/ssl
mkdir -p logs/nginx
mkdir -p logs/php
mkdir -p logs/mysql
mkdir -p logs/redis

# 권한 설정
echo -e "${YELLOW}파일 권한을 설정합니다...${NC}"
chmod +x install.sh
chmod +x start.sh
chmod +x stop.sh
chmod +x restart.sh

# Docker 이미지 빌드 및 컨테이너 실행
echo -e "${YELLOW}Docker 이미지를 빌드하고 컨테이너를 실행합니다...${NC}"
docker-compose up -d --build

# 컨테이너 상태 확인
echo -e "${YELLOW}컨테이너 상태를 확인합니다...${NC}"
sleep 10
docker-compose ps

# PHP 확장 모듈 확인
echo -e "${YELLOW}PHP 확장 모듈을 확인합니다...${NC}"
docker-compose exec php php -m | grep -E "(gd|mysqli|pdo_mysql|mbstring|openssl|curl|zip|redis|apcu|xml|xsl|intl|gettext|opcache)"

echo -e "${GREEN}=== 설치 완료 ===${NC}"
echo -e "${BLUE}웹사이트: http://localhost${NC}"
echo -e "${BLUE}API 엔드포인트: http://localhost/api${NC}"
echo -e "${BLUE}phpMyAdmin: http://localhost:8080${NC}"
echo -e "${BLUE}MySQL: localhost:3306${NC}"
echo -e "${BLUE}Redis: localhost:6379${NC}"
echo ""
echo -e "${YELLOW}컨테이너 관리 명령어:${NC}"
echo -e "  시작: ./start.sh"
echo -e "  중지: ./stop.sh"
echo -e "  재시작: ./restart.sh"
echo -e "  로그 확인: docker-compose logs -f"
echo ""
echo -e "${YELLOW}설치된 PHP 확장 모듈:${NC}"
echo -e "  - GD (이미지 처리)"
echo -e "  - MySQLi, PDO_MySQL (데이터베이스)"
echo -e "  - MBString (멀티바이트 문자열)"
echo -e "  - OpenSSL (암호화)"
echo -e "  - cURL (HTTP 통신)"
echo -e "  - ZIP (압축)"
echo -e "  - Redis (캐시)"
echo -e "  - APCu (캐시)"
echo -e "  - XML, XSL (XML 처리)"
echo -e "  - Intl (국제화)"
echo -e "  - Gettext (다국어)"
echo -e "  - OPcache (성능 최적화)" 