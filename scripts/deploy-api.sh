#!/bin/bash

# ===================================
# API 배포 스크립트
# gnu5_api/gnuboard/api/ 폴더를 FTP 서버로 업로드
# ===================================

set -e  # 에러 발생 시 스크립트 중단

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 경로 설정
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
API_DIR="$PROJECT_ROOT/backend/gnuboard/api"

# 옵션 파싱
DRY_RUN=false
SHOW_HELP=false

for arg in "$@"; do
    case $arg in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --help)
            SHOW_HELP=true
            shift
            ;;
        *)
            ;;
    esac
done

# 도움말 표시
if [ "$SHOW_HELP" = true ]; then
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  API 배포 스크립트 사용법${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}사용법:${NC}"
    echo -e "  ./deploy-api.sh [옵션]"
    echo ""
    echo -e "${YELLOW}옵션:${NC}"
    echo -e "  --help      이 도움말을 표시합니다"
    echo -e "  --dry-run   실제 업로드 없이 업로드될 파일 목록만 표시합니다"
    echo ""
    echo -e "${YELLOW}설명:${NC}"
    echo -e "  gnu5_api/gnuboard/api/ 폴더를 FTP 서버로 업로드합니다."
    echo -e "  FTP 설정은 .env 파일에서 읽어옵니다."
    echo ""
    echo -e "${YELLOW}필요한 환경 변수 (.env):${NC}"
    echo -e "  FTP_HOST    FTP 서버 주소"
    echo -e "  FTP_USER    FTP 사용자명"
    echo -e "  FTP_PASS    FTP 비밀번호"
    echo -e "  FTP_PATH    FTP 업로드 경로"
    echo ""
    exit 0
fi

# .env 파일 로드
ENV_FILE="$PROJECT_ROOT/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}✗ .env 파일을 찾을 수 없습니다: $ENV_FILE${NC}"
    exit 1
fi

# .env 파일에서 FTP 설정 읽기
source "$ENV_FILE"

# FTP 설정 확인
if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASS" ] || [ -z "$FTP_PATH" ]; then
    echo -e "${RED}✗ FTP 설정이 완전하지 않습니다. .env 파일을 확인하세요.${NC}"
    echo -e "${YELLOW}필요한 변수: FTP_HOST, FTP_USER, FTP_PASS, FTP_PATH${NC}"
    exit 1
fi

# API 디렉토리 확인
if [ ! -d "$API_DIR" ]; then
    echo -e "${RED}✗ API 디렉토리를 찾을 수 없습니다: $API_DIR${NC}"
    exit 1
fi

# lftp 설치 확인
if ! command -v lftp &> /dev/null; then
    echo -e "${RED}✗ lftp가 설치되어 있지 않습니다.${NC}"
    echo -e "${YELLOW}설치 방법: brew install lftp${NC}"
    exit 1
fi

echo -e "${YELLOW}========================================${NC}"
if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}  API 배포 시뮬레이션 (Dry Run)${NC}"
else
    echo -e "${YELLOW}  API 배포 시작${NC}"
fi
echo -e "${YELLOW}========================================${NC}"

echo -e "${GREEN}✓ API 디렉토리: $API_DIR${NC}"
echo -e "${GREEN}✓ FTP 서버: $FTP_HOST${NC}"
echo -e "${GREEN}✓ FTP 경로: $FTP_PATH${NC}"

if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}→ 업로드될 파일 목록 확인 중...${NC}"
    
    # 파일 목록 표시
    cd "$API_DIR"
    echo -e "${BLUE}업로드될 파일:${NC}"
    find . -type f | while read file; do
        echo -e "  ${GREEN}→${NC} $file"
    done
    
    echo ""
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${BLUE}  Dry Run 완료 (실제 업로드 안 됨)${NC}"
    echo -e "${YELLOW}========================================${NC}"
else
    echo -e "${YELLOW}→ FTP 서버로 파일 업로드 중...${NC}"
    
    # lftp를 사용한 FTP 업로드
    lftp -c "
    set ftp:ssl-allow no;
    set net:timeout 10;
    set net:max-retries 3;
    set net:reconnect-interval-base 5;
    open -u $FTP_USER,$FTP_PASS $FTP_HOST;
    mirror --reverse --delete --verbose --exclude-glob .git/ --exclude-glob .DS_Store $API_DIR $FTP_PATH/api;
    bye;
    "
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ 파일 업로드 완료${NC}"
        echo ""
        echo -e "${YELLOW}========================================${NC}"
        echo -e "${GREEN}  API 배포 완료!${NC}"
        echo -e "${YELLOW}========================================${NC}"
    else
        echo -e "${RED}✗ 파일 업로드 실패${NC}"
        exit 1
    fi
fi
