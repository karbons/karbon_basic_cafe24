#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

show_help() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  프로젝트 설치 스크립트${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}사용법:${NC}"
    echo -e "  $0 [옵션]"
    echo ""
    echo -e "${YELLOW}옵션:${NC}"
    echo -e "  --help          이 도움말 표시"
    echo -e "  --backend       백엔드만 설치"
    echo -e "  --frontend      프론트엔드만 설치 (app, admin, web)"
    echo -e "  --all           전체 설치 (기본값)"
    echo ""
    echo -e "${YELLOW}환경 변수 (.env):${NC}"
    echo -e "  DEV_MODE        - local 또는 remote"
    echo -e "  DEPLOY_FRONT    - hosting, myserver, cloudflare, versel"
    echo ""
}

check_requirements() {
    echo -e "${BLUE}→ 필수 도구 확인 중...${NC}"
    
    local missing=()
    
    if [ ! -f "$PROJECT_ROOT/.env" ]; then
        echo -e "${RED}✗ .env 파일이 없습니다${NC}"
        echo -e "${YELLOW}  .env.example을 복사하여 생성해주세요${NC}"
        missing+=("env")
    fi
    
    if [ ${#missing[@]} -gt 0 ]; then
        echo -e "${RED}✗ 필수要件 미충족: ${missing[*]}${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓ 필수 도구 확인 완료${NC}"
    return 0
}

load_env() {
    if [ -f "$PROJECT_ROOT/.env" ]; then
        set -a
        . "$PROJECT_ROOT/.env" 2>/dev/null
        set +a
    fi
    
    DEV_MODE="${DEV_MODE:-local}"
    DEPLOY_FRONT="${DEPLOY_FRONT:-local}"
}

setup_backend() {
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  백엔드 설치 시작${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    load_env
    
    echo -e "${BLUE}→ DEV_MODE: ${NC}$DEV_MODE"
    echo -e "${BLUE}→ DEPLOY_FRONT: ${NC}$DEPLOY_FRONT"
    echo ""
    
    if [ -f "$PROJECT_ROOT/backend/v1/index.php" ]; then
        echo -e "${GREEN}✓ 백엔드(v1)가 이미 존재합니다${NC}"
    else
        echo -e "${BLUE}→ 그누보드5 다운로드 중...${NC}"
        
        mkdir -p "$PROJECT_ROOT/backend"
        
        if command -v git &> /dev/null; then
            rm -rf "$PROJECT_ROOT/backend/v1"
            git clone https://github.com/gnuboard/gnuboard5.git "$PROJECT_ROOT/backend/v1" 2>&1
            if [ -f "$PROJECT_ROOT/backend/v1/index.php" ]; then
                echo -e "${GREEN}✓ 그누보드5 다운로드 완료${NC}"
                
                if [ -d "$PROJECT_ROOT/backend/gnu" ] && [ "$(ls -A "$PROJECT_ROOT/backend/gnu" 2>/dev/null)" ]; then
                    echo -e "${BLUE}→ /backend/gnu 파일 복사 중...${NC}"
                    cp -rf "$PROJECT_ROOT/backend/gnu/"* "$PROJECT_ROOT/backend/v1/"
                    cp -f "$PROJECT_ROOT/backend/gnu"/.htaccess "$PROJECT_ROOT/backend/v1/" 2>/dev/null || true
                    echo -e "${GREEN}✓ /backend/gnu → /backend/v1 복사 완료${NC}"
                fi
                
                # fapi routes에 hosting 파일 복사
                if [ -d "$PROJECT_ROOT/backend/v1/fapi/routes" ] && [ -d "$SCRIPT_DIR/hosting" ]; then
                    echo -e "${BLUE}→ hosting 파일 복사 중...${NC}"
                    cp -rf "$SCRIPT_DIR/hosting/"* "$PROJECT_ROOT/backend/v1/fapi/routes/"
                    cp -f "$SCRIPT_DIR/hosting"/.htaccess "$PROJECT_ROOT/backend/v1/fapi/routes/" 2>/dev/null || true
                    echo -e "${GREEN}✓ /scripts/hosting → /fapi/routes 복사 완료${NC}"
                fi
            else
                echo -e "${RED}✗ 그누보드5 다운로드 실패${NC}"
                return 1
            fi
        else
            echo -e "${RED}✗ git가 설치되어 있지 않습니다${NC}"
            echo -e "${YELLOW}  https://github.com/gnuboard/gnuboard5 에서 수동 다운로드 필요${NC}"
            return 1
        fi
    fi
    
    if [ -d "$PROJECT_ROOT/backend/v1/data" ]; then
        echo -e "${BLUE}→ data 폴더 권한 설정 중...${NC}"
        chmod 707 "$PROJECT_ROOT/backend/v1/data" 2>/dev/null || true
        echo -e "${GREEN}✓ data 폴더 권한 설정 완료${NC}"
    fi
    
    if [ "$DEV_MODE" = "local" ]; then
        echo ""
        echo -e "${BLUE}→ 로컬 개발환경 설정 중...${NC}"
        
        if [ -f "$SCRIPT_DIR/setup-local.sh" ]; then
            bash "$SCRIPT_DIR/setup-local.sh"
        else
            echo -e "${YELLOW}  setup-local.sh 파일이 없습니다${NC}"
        fi
        
        if [ -f "$PROJECT_ROOT/docker/docker-compose.yml" ]; then
            echo -e "${BLUE}→ Docker 컨테이너 시작 중...${NC}"
            (cd "$PROJECT_ROOT/docker" && docker-compose up -d) 2>&1 || echo -e "${YELLOW}  Docker 시작 실패 또는 미설치${NC}"
        fi
        
    elif [ "$DEV_MODE" = "remote" ]; then
        echo ""
        echo -e "${BLUE}→ 배포 환경 설정 중...${NC}"
        
        case "$DEPLOY_FRONT" in
            hosting)
                if [ -f "$SCRIPT_DIR/setup-hosting.sh" ]; then
                    bash "$SCRIPT_DIR/setup-hosting.sh"
                fi
                ;;
            myserver)
                if [ -f "$SCRIPT_DIR/setup-myserver.sh" ]; then
                    bash "$SCRIPT_DIR/setup-myserver.sh"
                fi
                ;;
            cloudflare)
                if [ -f "$SCRIPT_DIR/setup-cloudflare.sh" ]; then
                    bash "$SCRIPT_DIR/setup-cloudflare.sh"
                fi
                ;;
            versel)
                if [ -f "$SCRIPT_DIR/setup-versel.sh" ]; then
                    bash "$SCRIPT_DIR/setup-versel.sh"
                fi
                ;;
            *)
                echo -e "${YELLOW}  알 수 없는 DEPLOY_FRONT: $DEPLOY_FRONT${NC}"
                ;;
        esac
    fi
    
    echo ""
    echo -e "${GREEN}✓ 백엔드 설치 완료${NC}"
}

setup_frontend() {
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  프론트엔드 설치 시작${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""
    
    install_frontend_app() {
        local dir="$PROJECT_ROOT/frontend/app"
        if [ -d "$dir" ] && [ -f "$dir/package.json" ]; then
            echo -e "${BLUE}→ app 설치 중...${NC}"
            (cd "$dir" && npm install) 2>&1
            echo -e "${GREEN}✓ app 설치 완료${NC}"
        else
            echo -e "${YELLOW}  app 폴더가 없습니다 - 건너뜀${NC}"
        fi
    }
    
    install_frontend_admin() {
        local dir="$PROJECT_ROOT/frontend/admin"
        if [ -d "$dir" ] && [ -f "$dir/package.json" ]; then
            echo -e "${BLUE}→ admin 설치 중...${NC}"
            (cd "$dir" && npm install) 2>&1
            echo -e "${GREEN}✓ admin 설치 완료${NC}"
        else
            echo -e "${YELLOW}  admin 폴더가 없습니다 - 건너뜀${NC}"
        fi
    }
    
    install_frontend_web() {
        local dir="$PROJECT_ROOT/frontend/web"
        if [ -d "$dir" ] && [ -f "$dir/package.json" ]; then
            echo -e "${BLUE}→ web 설치 중...${NC}"
            (cd "$dir" && npm install) 2>&1
            echo -e "${GREEN}✓ web 설치 완료${NC}"
        else
            echo -e "${YELLOW}  web 폴더가 없습니다 - 건너뜀${NC}"
        fi
    }
    
    install_frontend_app
    install_frontend_admin
    install_frontend_web
    
    echo ""
    echo -e "${GREEN}✓ 프론트엔드 설치 완료${NC}"
}

main() {
    local backend_only=false
    local frontend_only=false
    local all=true
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --help)
                show_help
                exit 0
                ;;
            --backend)
                backend_only=true
                all=false
                ;;
            --frontend)
                frontend_only=true
                all=false
                ;;
            --all)
                all=true
                ;;
            *)
                echo -e "${RED}알 수 없는 옵션: $1${NC}"
                show_help
                exit 1
                ;;
        esac
        shift
    done
    
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}  프로젝트 설치${NC}"
    echo -e "${CYAN}========================================${NC}"
    echo ""
    
    if ! check_requirements; then
        exit 1
    fi
    
    if $backend_only || $all; then
        setup_backend
    fi
    
    if $frontend_only || $all; then
        setup_frontend
    fi
    
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  설치 완료!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${YELLOW}다음 단계:${NC}"
    echo -e "  - 백엔드: $PROJECT_ROOT/backend/v1/config.php 확인"
    echo -e "  - 프론트엔드: 각 폴더에서 npm run dev 실행"
    echo ""
}

main "$@"
