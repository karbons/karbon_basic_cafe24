#!/bin/bash

# ===================================
# 환경 설정 스크립트
# .env 파일 생성 및 설정
# ===================================

set -e

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 스크립트 디렉토리
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 도움말 함수
show_help() {
    cat << EOF
${BLUE}========================================${NC}
${BLUE}  Karbon 환경 설정 스크립트${NC}
${BLUE}========================================${NC}

${GREEN}사용법:${NC}
  ./setup-env.sh [옵션]

${GREEN}옵션:${NC}
  --help              이 도움말 표시
  --interactive       대화형 모드 (기본값)
  --auto              자동 모드 (기본값 사용)

${GREEN}설명:${NC}
  이 스크립트는 .env 파일을 생성하고 필요한 설정을 진행합니다.
  - DEV_MODE: 개발 모드 선택 (local/remote)
  - FTP 설정: remote 모드 선택 시 FTP 정보 입력
  - 환경 파일 복사: 각 프로젝트 디렉토리에 .env 파일 복사

${GREEN}예시:${NC}
  ./setup-env.sh                # 대화형 모드 실행
  ./setup-env.sh --auto         # 자동 모드 실행

${BLUE}========================================${NC}
EOF
}

# 변수 초기화
INTERACTIVE_MODE=true
DEV_MODE=""
FTP_HOST=""
FTP_USER=""
FTP_PASS=""
FTP_PATH=""
JWT_ACCESS_TOKEN_KEY=""
JWT_REFRESH_TOKEN_KEY=""

# 랜덤 키 생성 함수
generate_random_key() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 32
    else
        # openssl이 없는 경우 대체 방법
        LC_ALL=C tr -dc 'a-zA-Z0-9' < /dev/urandom | fold -w 64 | head -n 1
    fi
}

# 명령행 인자 처리
while [[ $# -gt 0 ]]; do
    case $1 in
        --help)
            show_help
            exit 0
            ;;
        --auto)
            INTERACTIVE_MODE=false
            shift
            ;;
        --interactive)
            INTERACTIVE_MODE=true
            shift
            ;;
        *)
            echo -e "${RED}✗ 알 수 없는 옵션: $1${NC}"
            show_help
            exit 1
            ;;
    esac
done

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  Karbon 환경 설정 시작${NC}"
echo -e "${YELLOW}========================================${NC}"

# .env 파일 생성 (없으면)
if [ ! -f "$SCRIPT_DIR/.env" ]; then
    echo -e "${YELLOW}→ .env 파일 생성 중...${NC}"
    cp "$SCRIPT_DIR/.env.example" "$SCRIPT_DIR/.env"
    echo -e "${GREEN}✓ .env 파일 생성 완료${NC}"
else
    echo -e "${GREEN}✓ .env 파일이 이미 존재합니다${NC}"
fi

# 대화형 모드
if [ "$INTERACTIVE_MODE" = true ]; then
    echo ""
    echo -e "${BLUE}--- DEV_MODE 설정 ---${NC}"
    echo -e "${YELLOW}개발 모드를 선택하세요:${NC}"
    echo "  1) local  - 로컬 개발 환경"
    echo "  2) remote - 원격 서버 배포"
    read -p "선택 (1 또는 2) [기본값: 1]: " dev_mode_choice
    dev_mode_choice=${dev_mode_choice:-1}
    
    case $dev_mode_choice in
        1)
            DEV_MODE="local"
            echo -e "${GREEN}✓ DEV_MODE=local 선택됨${NC}"
            ;;
        2)
            DEV_MODE="remote"
            echo -e "${GREEN}✓ DEV_MODE=remote 선택됨${NC}"
            
            # FTP 설정 입력
            echo ""
            echo -e "${BLUE}--- FTP 설정 ---${NC}"
            read -p "FTP 호스트 [기본값: your-ftp-host.com]: " FTP_HOST
            FTP_HOST=${FTP_HOST:-your-ftp-host.com}
            
            read -p "FTP 사용자명 [기본값: your-ftp-username]: " FTP_USER
            FTP_USER=${FTP_USER:-your-ftp-username}
            
            read -sp "FTP 비밀번호 [기본값: your-ftp-password]: " FTP_PASS
            FTP_PASS=${FTP_PASS:-your-ftp-password}
            echo ""
            
            read -p "FTP 경로 [기본값: /public_html/karbon]: " FTP_PATH
            FTP_PATH=${FTP_PATH:-/public_html/karbon}
            
            echo -e "${GREEN}✓ FTP 설정 완료${NC}"
            ;;
        *)
            echo -e "${RED}✗ 잘못된 선택입니다. 기본값(local)을 사용합니다.${NC}"
            DEV_MODE="local"
            ;;
    esac
else
    # 자동 모드 - 기본값 사용
    DEV_MODE="local"
    FTP_HOST="your-ftp-host.com"
    FTP_USER="your-ftp-username"
    FTP_PASS="your-ftp-password"
    FTP_PATH="/public_html/karbon"
    echo -e "${GREEN}✓ 자동 모드: 기본값 사용${NC}"
fi

# .env 파일 업데이트
echo ""
echo -e "${YELLOW}→ .env 파일 업데이트 중...${NC}"

# DEV_MODE 업데이트
if grep -q "^DEV_MODE=" "$SCRIPT_DIR/.env"; then
    sed -i.bak "s/^DEV_MODE=.*/DEV_MODE=$DEV_MODE/" "$SCRIPT_DIR/.env"
else
    echo "DEV_MODE=$DEV_MODE" >> "$SCRIPT_DIR/.env"
fi

# FTP 설정 업데이트
if grep -q "^FTP_HOST=" "$SCRIPT_DIR/.env"; then
    sed -i.bak "s/^FTP_HOST=.*/FTP_HOST=$FTP_HOST/" "$SCRIPT_DIR/.env"
else
    echo "FTP_HOST=$FTP_HOST" >> "$SCRIPT_DIR/.env"
fi

if grep -q "^FTP_USER=" "$SCRIPT_DIR/.env"; then
    sed -i.bak "s/^FTP_USER=.*/FTP_USER=$FTP_USER/" "$SCRIPT_DIR/.env"
else
    echo "FTP_USER=$FTP_USER" >> "$SCRIPT_DIR/.env"
fi

if grep -q "^FTP_PASS=" "$SCRIPT_DIR/.env"; then
    sed -i.bak "s/^FTP_PASS=.*/FTP_PASS=$FTP_PASS/" "$SCRIPT_DIR/.env"
else
    echo "FTP_PASS=$FTP_PASS" >> "$SCRIPT_DIR/.env"
fi

if grep -q "^FTP_PATH=" "$SCRIPT_DIR/.env"; then
    sed -i.bak "s|^FTP_PATH=.*|FTP_PATH=$FTP_PATH|" "$SCRIPT_DIR/.env"
else
    echo "FTP_PATH=$FTP_PATH" >> "$SCRIPT_DIR/.env"
fi

# JWT 키 자동 생성 및 업데이트
echo -e "${YELLOW}→ JWT 보안 키 확인 중...${NC}"

# 현재 값 확인
CURRENT_ACCESS_KEY=$(grep "^JWT_ACCESS_TOKEN_KEY=" "$SCRIPT_DIR/.env" | cut -d'=' -f2)
CURRENT_REFRESH_KEY=$(grep "^JWT_REFRESH_TOKEN_KEY=" "$SCRIPT_DIR/.env" | cut -d'=' -f2)

# 기본값이거나 비어있으면 새로 생성
if [[ -z "$CURRENT_ACCESS_KEY" || "$CURRENT_ACCESS_KEY" == "your-access-token-key-change-in-production" ]]; then
    echo -e "${YELLOW}  - JWT_ACCESS_TOKEN_KEY 생성 중...${NC}"
    NEW_KEY=$(generate_random_key)
    sed -i.bak "s/^JWT_ACCESS_TOKEN_KEY=.*/JWT_ACCESS_TOKEN_KEY=$NEW_KEY/" "$SCRIPT_DIR/.env"
fi

if [[ -z "$CURRENT_REFRESH_KEY" || "$CURRENT_REFRESH_KEY" == "your-refresh-token-key-change-in-production" ]]; then
    echo -e "${YELLOW}  - JWT_REFRESH_TOKEN_KEY 생성 중...${NC}"
    NEW_KEY=$(generate_random_key)
    sed -i.bak "s/^JWT_REFRESH_TOKEN_KEY=.*/JWT_REFRESH_TOKEN_KEY=$NEW_KEY/" "$SCRIPT_DIR/.env"
fi

# 백업 파일 제거
rm -f "$SCRIPT_DIR/.env.bak"

echo -e "${GREEN}✓ .env 파일 업데이트 완료${NC}"

# 환경 파일 복사
echo ""
echo -e "${YELLOW}→ 환경 파일 복사 중...${NC}"

copy_env_file() {
    local target_dir=$1
    local target_name=$2
    
    if [ -d "$target_dir" ]; then
        cp "$SCRIPT_DIR/.env" "$target_dir/.env"
        echo -e "${GREEN}✓ $target_name/.env 복사 완료${NC}"
    else
        echo -e "${YELLOW}⚠ $target_name 디렉토리를 찾을 수 없습니다${NC}"
    fi
}

copy_env_file "$SCRIPT_DIR/backend" "backend"
copy_env_file "$SCRIPT_DIR/backend/gnuboard/api" "backend/gnuboard/api"
copy_env_file "$SCRIPT_DIR/frontend/app" "frontend/app"
copy_env_file "$SCRIPT_DIR/frontend/manager" "frontend/manager"

# 스크립트 실행 권한 설정
chmod +x "$SCRIPT_DIR/setup-env.sh"

echo ""
echo -e "${YELLOW}========================================${NC}"
echo -e "${GREEN}  환경 설정 완료!${NC}"
echo -e "${GREEN}  DEV_MODE: $DEV_MODE${NC}"
if [ "$DEV_MODE" = "remote" ]; then
    echo -e "${GREEN}  FTP_HOST: $FTP_HOST${NC}"
fi
echo -e "${YELLOW}========================================${NC}"
