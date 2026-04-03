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
    
    if ! command -v lftp &> /dev/null; then
        echo -e "${RED}✗ lftp가 설치되어 있지 않습니다${NC}"
        echo -e "${YELLOW}  설치: brew install lftp${NC}"
        return 1
    fi
    
    if [ ! -d "$PROJECT_ROOT/backend/v1" ]; then
        echo -e "${RED}✗ 로컬 백엔드 폴더가 없습니다: $PROJECT_ROOT/backend/v1${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓ 필수 설정 확인 완료${NC}"
    return 0
}

test_connection() {
    echo -e "${BLUE}→ SFTP 연결 테스트 중...${NC}"
    
    lftp -p "$SFTP_PORT" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
ls
bye
EOF
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ SFTP 연결 성공${NC}"
        return 0
    else
        echo -e "${RED}✗ SFTP 연결 실패${NC}"
        return 1
    fi
}

upload_backend() {
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  백엔드 파일 업로드${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    local remote_path="$SFTP_PATH/v1"
    
    echo -e "${BLUE}→ /backend/v1/ 파일 업로드 중...${NC}"
    echo -e "${BLUE}  원격 경로: $remote_path${NC}"
    echo ""
    
    lftp -p "$SFTP_PORT" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
set net:max-retries 3
set net:timeout 30
set xfer:log yes
mirror -R --verbose --parallel=3 "$PROJECT_ROOT/backend/v1" "$remote_path"
bye
EOF
    
    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ 백엔드 파일 업로드 완료${NC}"
        return 0
    else
        echo ""
        echo -e "${RED}✗ 백엔드 파일 업로드 실패${NC}"
        return 1
    fi
}

create_data_folder() {
    echo ""
    echo -e "${BLUE}→ /v1/data 폴더 생성 중...${NC}"
    
    lftp -p "$SFTP_PORT" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
mkdir -p "$SFTP_PATH/v1/data"
chmod 707 "$SFTP_PATH/v1/data"
bye
EOF
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ /v1/data 폴더 생성 완료${NC}"
    else
        echo -e "${YELLOW}  /v1/data 폴더 생성 경고 (이미 존재할 수 있음)${NC}"
    fi
}

create_htaccess() {
    echo ""
    echo -e "${BLUE}→ /.htaccess 파일 업로드 중...${NC}"
    
    if [ ! -f "$SCRIPT_DIR/.htaccess" ]; then
        echo -e "${YELLOW}  scripts/.htaccess 파일이 없습니다${NC}"
        return 1
    fi
    
    lftp -p "$SFTP_PORT" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
put "$SCRIPT_DIR/.htaccess" -o "$SFTP_PATH/.htaccess"
bye
EOF
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ /.htaccess 파일 업로드 완료${NC}"
    else
        echo -e "${RED}✗ /.htaccess 파일 업로드 실패${NC}"
    fi
}

main() {
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}  Cafe24 Hosting 배포 설정${NC}"
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
    
    if ! test_connection; then
        exit 1
    fi
    
    echo ""
    echo -e "${YELLOW}⚠ 이 작업은 다음을 수행합니다:${NC}"
    echo -e "  1. /backend/v1/ 파일을 /v1/ 폴더에 업로드"
    echo -e "  2. /v1/data 폴더 생성 및 권한 설정"
    echo -e "  3. scripts/.htaccess → /.htaccess 업로드"
    echo ""
    read -p "계속 진행하시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}취소되었습니다${NC}"
        exit 1
    fi
    
    if ! upload_backend; then
        exit 1
    fi
    
    create_data_folder
    create_htaccess
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  배포 설정 완료!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${YELLOW}다음 단계:${NC}"
    echo -e "  1. 서버에서 /v1/data 폴더 권한 확인: chmod 707 data"
    echo -e "  2. /v1/config.php 파일 설정 확인"
    echo -e "  3. 브라우저에서 접속 테스트"
    echo ""
}

main "$@"
