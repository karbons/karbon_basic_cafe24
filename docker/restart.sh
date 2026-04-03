#!/bin/bash

# 색상 정의
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== GNU5 API Docker 컨테이너 재시작 ===${NC}"

# 컨테이너 재시작
echo -e "${YELLOW}Docker 컨테이너를 재시작합니다...${NC}"
docker-compose restart

# 컨테이너 상태 확인
echo -e "${YELLOW}컨테이너 상태를 확인합니다...${NC}"
docker-compose ps

echo -e "${GREEN}=== 재시작 완료 ===${NC}"
echo -e "${BLUE}웹사이트: http://localhost${NC}"
echo -e "${BLUE}API 엔드포인트: http://localhost/api${NC}" 