#!/bin/bash

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== GNU5 API Docker 컨테이너 시작 ===${NC}"

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

echo -e "${GREEN}컨테이너명 중복 없음. 시작을 진행합니다.${NC}"

# 컨테이너 시작
echo -e "${YELLOW}Docker 컨테이너를 시작합니다...${NC}"
docker-compose up -d

# 컨테이너 상태 확인
echo -e "${YELLOW}컨테이너 상태를 확인합니다...${NC}"
docker-compose ps

echo -e "${GREEN}=== 시작 완료 ===${NC}"
echo -e "${BLUE}웹사이트: http://localhost${NC}"
echo -e "${BLUE}API 엔드포인트: http://localhost/api${NC}" 