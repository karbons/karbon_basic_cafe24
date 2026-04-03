#!/bin/bash

# ===================================
# Manager 배포 스크립트
# https://karbon.kr/manager 로 배포
# ===================================

set -e  # 에러 발생 시 스크립트 중단

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 경로 설정
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
MANAGER_DIR="$PROJECT_ROOT/manager"

# 서버 설정
REMOTE_HOST="karbon"
REMOTE_PATH="/srv/docker/apache-php/sites/default/manager"

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  Manager 배포 시작${NC}"
echo -e "${YELLOW}========================================${NC}"

# 1. manager 디렉토리로 이동
cd "$MANAGER_DIR"
echo -e "${GREEN}✓ manager 디렉토리로 이동: $MANAGER_DIR${NC}"

# 2. 의존성 설치
echo -e "${YELLOW}→ npm install 실행 중...${NC}"
npm install
echo -e "${GREEN}✓ 의존성 설치 완료${NC}"

# 3. 빌드
echo -e "${YELLOW}→ npm run build 실행 중...${NC}"
npm run build
echo -e "${GREEN}✓ 빌드 완료${NC}"

# 4. 서버에 manager 디렉토리 생성 (없으면)
echo -e "${YELLOW}→ 서버에 manager 디렉토리 확인 중...${NC}"
ssh -p 22000 "$REMOTE_HOST" "mkdir -p $REMOTE_PATH"
echo -e "${GREEN}✓ 디렉토리 확인 완료${NC}"

# 5. 서버로 배포 (rsync)
echo -e "${YELLOW}→ 서버로 파일 전송 중...${NC}"
rsync -avz --delete \
    --exclude='.htaccess' \
    -e "ssh -p 22000" \
    "$MANAGER_DIR/build/" \
    "$REMOTE_HOST:$REMOTE_PATH/"
echo -e "${GREEN}✓ 파일 전송 완료${NC}"

echo -e "${YELLOW}========================================${NC}"
echo -e "${GREEN}  Manager 배포 완료!${NC}"
echo -e "${GREEN}  URL: https://karbon.kr/manager${NC}"
echo -e "${YELLOW}========================================${NC}"
