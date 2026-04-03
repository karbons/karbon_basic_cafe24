#!/bin/bash

# 색상 정의
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== GNU5 API Docker 컨테이너 중지 ===${NC}"

# 컨테이너 중지
echo -e "${YELLOW}Docker 컨테이너를 중지합니다...${NC}"
docker-compose down

echo -e "${RED}=== 중지 완료 ===${NC}" 