#!/bin/bash

# ===================================
# ZIP 패키지 생성 스크립트
# karbon-basic-v{version}.zip 생성
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

# 함수: 도움말 출력
show_help() {
    cat << EOF
${BLUE}사용법:${NC}
  $0 [옵션]

${BLUE}옵션:${NC}
  --help              이 도움말을 표시합니다
  --version           버전 정보를 표시합니다
  --output DIR        ZIP 파일 저장 경로 (기본값: 현재 디렉토리)

${BLUE}예시:${NC}
  $0                                    # 기본 설정으로 패키지 생성
  $0 --output /tmp                      # /tmp에 패키지 생성
  $0 --help                             # 도움말 표시

${BLUE}생성되는 파일:${NC}
  karbon-basic-v{version}.zip

${BLUE}포함되는 항목:${NC}
  - 소스 코드 (frontend, manager, gnu5_api, docs)
  - 설정 파일 (.env.example, setup-env.sh, setup-env.ps1)
  - 스크립트 (scripts/)
  - README.md

${BLUE}제외되는 항목:${NC}
  - node_modules
  - .git
  - build 디렉토리
  - .DS_Store
  - 로그 파일 (*.log)
  - vendor 디렉토리
  - .env 파일
  - .sisyphus 디렉토리

EOF
}

# 함수: 버전 정보 출력
show_version() {
    if [ -f "$PROJECT_ROOT/frontend/package.json" ]; then
        VERSION=$(grep '"version"' "$PROJECT_ROOT/frontend/package.json" | head -1 | sed 's/.*"version": "\([^"]*\)".*/\1/')
        echo "karbon-basic version: $VERSION"
    else
        echo "버전 정보를 찾을 수 없습니다"
        exit 1
    fi
}

# 함수: 버전 추출
get_version() {
    if [ -f "$PROJECT_ROOT/frontend/package.json" ]; then
        grep '"version"' "$PROJECT_ROOT/frontend/package.json" | head -1 | sed 's/.*"version": "\([^"]*\)".*/\1/'
    else
        echo "0.0.1"
    fi
}

# 함수: 제외 패턴 생성
create_exclude_patterns() {
    cat << EOF
node_modules
.git
build
.DS_Store
*.log
vendor
.env
.sisyphus
.svelte-kit
.env.local
dist
.next
out
EOF
}

# 옵션 파싱
OUTPUT_DIR="."

while [[ $# -gt 0 ]]; do
    case $1 in
        --help)
            show_help
            exit 0
            ;;
        --version)
            show_version
            exit 0
            ;;
        --output)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}✗ 알 수 없는 옵션: $1${NC}"
            show_help
            exit 1
            ;;
    esac
done

# 출력 디렉토리 확인
if [ ! -d "$OUTPUT_DIR" ]; then
    echo -e "${RED}✗ 출력 디렉토리가 존재하지 않습니다: $OUTPUT_DIR${NC}"
    exit 1
fi

# 버전 추출
VERSION=$(get_version)
PACKAGE_NAME="karbon-basic-v${VERSION}.zip"
PACKAGE_PATH="$OUTPUT_DIR/$PACKAGE_NAME"

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  ZIP 패키지 생성 시작${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# 기존 파일 확인
if [ -f "$PACKAGE_PATH" ]; then
    echo -e "${YELLOW}→ 기존 파일 제거 중: $PACKAGE_NAME${NC}"
    rm -f "$PACKAGE_PATH"
fi

# 임시 디렉토리 생성
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

echo -e "${YELLOW}→ 임시 디렉토리 생성: $TEMP_DIR${NC}"

# 프로젝트 파일 복사
echo -e "${YELLOW}→ 프로젝트 파일 복사 중...${NC}"

# 제외 패턴 파일 생성
EXCLUDE_FILE="$TEMP_DIR/exclude.txt"
create_exclude_patterns > "$EXCLUDE_FILE"

# rsync를 사용하여 파일 복사 (제외 패턴 적용)
rsync -av \
    --exclude-from="$EXCLUDE_FILE" \
    "$PROJECT_ROOT/" \
    "$TEMP_DIR/karbon-basic/" \
    > /dev/null 2>&1

echo -e "${GREEN}✓ 파일 복사 완료${NC}"

# 패키지 생성
echo -e "${YELLOW}→ ZIP 파일 생성 중: $PACKAGE_NAME${NC}"

cd "$TEMP_DIR"
zip -r -q "$PACKAGE_PATH" karbon-basic/

echo -e "${GREEN}✓ ZIP 파일 생성 완료${NC}"

# 패키지 정보 출력
echo ""
echo -e "${YELLOW}→ 패키지 정보:${NC}"
echo -e "  ${BLUE}파일명:${NC} $PACKAGE_NAME"
echo -e "  ${BLUE}경로:${NC} $PACKAGE_PATH"
echo -e "  ${BLUE}크기:${NC} $(du -h "$PACKAGE_PATH" | cut -f1)"

# 패키지 내용 목록
echo ""
echo -e "${YELLOW}→ 패키지 내용 목록:${NC}"
unzip -l "$PACKAGE_PATH" | head -30

if [ $(unzip -l "$PACKAGE_PATH" | wc -l) -gt 32 ]; then
    echo "  ... (더 많은 파일들)"
fi

echo ""
echo -e "${YELLOW}========================================${NC}"
echo -e "${GREEN}  ZIP 패키지 생성 완료!${NC}"
echo -e "${YELLOW}========================================${NC}"
