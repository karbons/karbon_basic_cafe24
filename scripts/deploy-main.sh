#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

load_env() {
    if [ -f "$PROJECT_ROOT/.env" ]; then
        set -a
        . "$PROJECT_ROOT/.env" 2>/dev/null
        set +a
    fi
    
    SFTP_HOST="${SFTP_HOST:-}"
    SFTP_PORT="${SFTP_PORT:-22}"
    SFTP_USER="${SFTP_USER:-}"
    SFTP_PASS="${SFTP_PASS:-}"
    SFTP_PATH="${SFTP_PATH:-}"
}

check_requirements() {
    echo -e "${BLUE}→ 필수 설정 확인 중...${NC}"
    
    if [ -z "$SFTP_HOST" ] || [ -z "$SFTP_USER" ] || [ -z "$SFTP_PASS" ] || [ -z "$SFTP_PATH" ]; then
        echo -e "${RED}✗ .env 파일에 SFTP 설정이 incomplete합니다${NC}"
        echo -e "${YELLOW}  SFTP_HOST, SFTP_USER, SFTP_PASS, SFTP_PATH를 확인해주세요${NC}"
        return 1
    fi
    
    if [ ! -d "$PROJECT_ROOT/frontend/main" ]; then
        echo -e "${RED}✗ 프론트엔드 폴더가 없습니다: $PROJECT_ROOT/frontend/main${NC}"
        return 1
    fi
    
    if ! command -v lftp &> /dev/null; then
        echo -e "${RED}✗ lftp가 설치되어 있지 않습니다${NC}"
        echo -e "${YELLOW}  설치: brew install lftp${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓ 필수 설정 확인 완료${NC}"
    return 0
}

build_frontend() {
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  SvelteKit 빌드${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    local main_dir="$PROJECT_ROOT/frontend/main"
    
    if [ ! -f "$main_dir/package.json" ]; then
        echo -e "${RED}✗ package.json이 없습니다${NC}"
        return 1
    fi
    
    echo -e "${BLUE}→ npm install 실행 중...${NC}"
    (cd "$main_dir" && npm install) 2>&1
    
    echo -e "${BLUE}→ npm run build 실행 중...${NC}"
    (cd "$main_dir" && npm run build) 2>&1
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ 빌드 완료${NC}"
        return 0
    else
        echo -e "${RED}✗ 빌드 실패${NC}"
        return 1
    fi
}

upload_build() {
    echo ""
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  빌드 파일 업로드${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    local build_dir="$PROJECT_ROOT/frontend/main/build"
    local remote_path="$SFTP_PATH/main"
    
    if [ ! -d "$build_dir" ]; then
        echo -e "${RED}✗ 빌드 폴더가 없습니다: $build_dir${NC}"
        return 1
    fi
    
    echo -e "${BLUE}→ /frontend/main/build 파일 업로드 중...${NC}"
    echo -e "${BLUE}  원격 경로: $remote_path${NC}"
    echo ""
    
    lftp -p "$SFTP_PORT" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
set net:max-retries 3
set net:timeout 30
mirror -R --verbose --parallel=3 "$build_dir" "$remote_path"
bye
EOF
    
    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ 빌드 파일 업로드 완료${NC}"
        return 0
    else
        echo ""
        echo -e "${RED}✗ 빌드 파일 업로드 실패${NC}"
        return 1
    fi
}

main() {
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}  Main (랜딩페이지) 배포${NC}"
    echo -e "${CYAN}========================================${NC}"
    echo ""
    
    load_env
    
    if ! check_requirements; then
        exit 1
    fi
    
    echo ""
    echo -e "${BLUE}→ 배포 설정:${NC}"
    echo -e "  호스트: ${GREEN}$SFTP_HOST${NC}"
    echo -e "  포트: ${GREEN}$SFTP_PORT${NC}"
    echo -e "  사용자: ${GREEN}$SFTP_USER${NC}"
    echo -e "  원격 경로: ${GREEN}$SFTP_PATH${NC}"
    echo ""
    
    echo -e "${YELLOW}⚠ 이 작업은 다음을 수행합니다:${NC}"
    echo -e "  1. /frontend/main SvelteKit 빌드"
    echo -e "  2. 빌드 파일을 /main 폴더에 업로드"
    echo ""
    read -p "계속 진행하시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}취소되었습니다${NC}"
        exit 1
    fi
    
    if ! build_frontend; then
        exit 1
    fi
    
    if ! upload_build; then
        exit 1
    fi
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  배포 완료!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
}

main "$@"
