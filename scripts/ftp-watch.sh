#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

show_help() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  SFTP Watch Script 사용법${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}사용법:${NC}"
    echo -e "  $0 [옵션]"
    echo ""
    echo -e "${YELLOW}옵션:${NC}"
    echo -e "  --help    이 도움말 표시"
    echo -e "  --test    SFTP 연결 테스트"
    echo ""
    echo -e "${YELLOW}설명:${NC}"
    echo -e "  backend/v1/ 폴더의 변경사항을 감지하여"
    echo -e "  자동으로 SFTP 서버의 /v1 폴더에 동기화합니다."
    echo ""
    echo -e "${YELLOW}동기화 규칙:${NC}"
    echo -e "  - 일반 파일: 로컬 → 서버 (업로드)"
    echo -e "  - data/: 제외 (DB 설정)"
    echo -e "  - fapi/logs/: 제외 (서버 로그)"
    echo ""
    echo -e "${YELLOW}필수 요구사항:${NC}"
    echo -e "  - fswatch (brew install fswatch)"
    echo -e "  - lftp (brew install lftp)"
    echo ""
}

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

test_connection() {
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  SFTP 연결 테스트${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    lftp -p "${SFTP_PORT:-22}" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" -e "ls; bye" &> /dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ SFTP 연결 성공!${NC}"
        return 0
    else
        echo -e "${RED}✗ SFTP 연결 실패${NC}"
        return 1
    fi
}

upload_file() {
    local file_path="$1"
    local relative_path="${file_path#$WATCH_DIR/}"
    local remote_file="$SFTP_PATH/v1/$relative_path"
    local remote_dir=$(dirname "$remote_file")
    
    echo -e "${BLUE}↑ 업로드: ${NC}$relative_path"
    
    lftp -p "${SFTP_PORT:-22}" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
set net:timeout 30
mkdir -p "$remote_dir"
put "$file_path" -o "$remote_file"
bye
EOF
    
    [ $? -eq 0 ] && echo -e "${GREEN}✓ 완료${NC}" || echo -e "${RED}✗ 실패${NC}"
}

delete_file() {
    local file_path="$1"
    local relative_path="${file_path#$WATCH_DIR/}"
    local remote_file="$SFTP_PATH/v1/$relative_path"
    
    echo -e "${YELLOW}↓ 삭제: ${NC}$relative_path"
    
    lftp -p "${SFTP_PORT:-22}" -u "$SFTP_USER","$SFTP_PASS" sftp://"$SFTP_HOST" <<EOF 2>&1
rm "$remote_file"
bye
EOF
    
    [ $? -eq 0 ] && echo -e "${GREEN}✓ 완료${NC}" || echo -e "${YELLOW}⚠ 실패${NC}"
}

main() {
    if [ "${1:-}" = "--help" ]; then
        show_help
        exit 0
    fi
    
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
    
    load_env
    
    if [ -z "$SFTP_HOST" ] || [ -z "$SFTP_USER" ] || [ -z "$SFTP_PASS" ] || [ -z "$SFTP_PATH" ]; then
        echo -e "${RED}✗ .env 파일의 SFTP 설정을 불러오는데 실패했습니다${NC}"
        exit 1
    fi
    
    WATCH_DIR="$PROJECT_ROOT/backend/v1"
    
    if [ "${1:-}" = "--test" ]; then
        test_connection
        exit $?
    fi
    
    if ! command -v fswatch &> /dev/null; then
        echo -e "${RED}✗ fswatch가 설치되어 있지 않습니다${NC}"
        echo -e "${YELLOW}  설치: brew install fswatch${NC}"
        exit 1
    fi
    
    if ! command -v lftp &> /dev/null; then
        echo -e "${RED}✗ lftp가 설치되어 있지 않습니다${NC}"
        echo -e "${YELLOW}  설치: brew install lftp${NC}"
        exit 1
    fi
    
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  SFTP Watch 시작${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    if ! test_connection; then
        exit 1
    fi
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  파일 감시 시작${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo -e "${BLUE}감시 디렉토리: ${NC}$WATCH_DIR"
    echo -e "${BLUE}원격 경로: ${NC}$SFTP_PATH/v1"
    echo -e "${YELLOW}종료하려면 Ctrl+C를 누르세요${NC}"
    echo ""
    
    fswatch -0 -r \
        --exclude='\.git' \
        --exclude='\.DS_Store' \
        --exclude='node_modules' \
        --exclude='\.env' \
        --exclude='data/' \
        --exclude='fapi/logs/' \
        "$WATCH_DIR" | while read -d "" file; do
        
        if [ -f "$file" ]; then
            [[ "$file" =~ [[:cntrl:]] ]] && continue
            upload_file "$file"
        elif [ -d "$file" ]; then
            continue
        else
            delete_file "$file"
        fi
    done
}

main "$@"
