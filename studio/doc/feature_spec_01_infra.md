# [Feature Spec] 01. 인프라 및 인스톨러 (Infra & Installer)

## 1. 도메인 개요 (Domain Overview)
인프라 및 인스톨러 모듈은 카본빌더 개발 환경의 토대를 마련하는 핵심 도메인입니다. 복잡한 로컬 개발 환경(Docker, DB, Web Server 등) 구축 과정을 자동화하고, 웹 GUI를 통해 누구나 쉽게 프로젝트를 시작하고 관리할 수 있도록 지원합니다. 또한, 다양한 운영 환경에 대응하는 배포 설정을 표준화하여 개발 생산성을 극대화합니다.

## 2. 화면 정의 (Page Definitions)

### 2.1 셋업 홈 (Setup Home)
- **목적**: 최초 방문 시 시스템 요구사항을 확인하고 설치를 시작하는 관문.
- **주요 기능**:
    - **시스템 체크리스트**: Docker 설치 여부, 포트 점유 상태(80, 443, 3306 등), PHP 버전 및 확장 모듈 설치 여부 확인.
    - **관리자 설정**: 인스톨러 및 초기 시스템 관리자 계정 정보(ID, Password, Email) 입력.
    - **설치 제어**: 원클릭 설치 실행 버튼 및 단계별 설치 프로세스 모니터링.
    - **진행 표시**: Docker 이미지 다운로드, 컨테이너 생성, 소스 코드 배포, DB 초기화 등 진행 상황 실시간 표시.

### 2.2 환경변수 관리 (Environment Variable Management)
- **목적**: `.env` 파일을 수동으로 편집하지 않고 UI 상에서 안전하고 직관적으로 관리.
- **주요 기능**:
    - **섹션별 분류**: 기본 앱 설정, 데이터베이스 연결 정보, JWT/OAuth 보안 키, 외부 서비스 API 키 등으로 그룹화.
    - **변수 편집**: Key-Value 쌍의 추가, 수정, 삭제 및 보안이 필요한 값에 대한 마스킹 처리.
    - **환경 전환**: 개발(Development), 테스트(Test), 운영(Production) 환경별 변수 세트 독립 관리 및 복사 기능.
    - **자동 동기화**: 저장 시 백엔드에서 `.env` 파일을 물리적으로 생성하고 관련 서비스에 반영.

### 2.3 컨테이너 대시보드 (Container Dashboard)
- **목적**: 프로젝트에 할당된 Docker 컨테이너의 상태를 모니터링하고 제어.
- **주요 기능**:
    - **컨테이너 목록**: Web (Nginx/Apache), API (PHP-FPM), Database (MySQL/PostgreSQL), Cache (Redis) 등 현재 구동 중인 서비스 리스트 출력.
    - **상태 제어**: 개별 또는 전체 컨테이너에 대한 Start, Stop, Restart, Rebuild 명령 실행.
    - **로그 모니터링**: 각 컨테이너의 실시간 실행 로그(Stdout/Stderr) 확인 창 제공.
    - **리소스 통계**: CPU 및 메모리 사용량을 간이 차트로 표시하여 부하 상태 확인.

### 2.4 배포 세트 관리 (Deployment Set Management)
- **목적**: 다양한 서버 환경 설정을 세트로 저장하여 재사용 및 공유.
- **주요 기능**:
    - **세트 프로필**: Cafe24 호스팅, AWS EC2, 로컬 Ubuntu, 윈도우 WSL2 등 환경별 최적화된 설정 프로필 관리.
    - **JSON 관리**: 배포 환경 정보를 JSON 형식으로 내보내기(Export) 및 가져오기(Import) 하여 팀원 간 공유.
    - **환경 적용**: 선택한 배포 세트에 맞춰 Docker Compose 설정 및 환경변수 템플릿을 즉시 변경 적용.

## 3. 핵심 UI 구성 요소 (Key Components)

### 3.1 시스템 체크리스트 (System Checklist)
- 각 항목별 통과(Success)/경고(Warning)/실패(Fail) 상태를 아이콘과 컬러로 표시.
- 실패 항목에 대한 구체적인 해결 가이드 팝업 연결.

### 3.2 환경변수 입력 폼 (Env Input Form)
- 동적 행 추가 방식의 Key-Value 입력 필드.
- 민감 정보 보호를 위한 '값 보기/숨기기' 토글 버튼.
- 대량의 변수를 한 번에 입력할 수 있는 Bulk Import 모달.

### 3.3 상태 카드 (Status Card)
- 컨테이너명, 상태(운영 중/중지됨/오류), 가동 시간(Uptime) 정보를 포함하는 카드 UI.
- 상태에 따른 배경색 또는 테두리 색상 변화 (녹색/회색/빨간색).

### 3.4 실시간 로그 위젯 (Log Streaming Widget)
- 터미널 느낌의 다크 테마 텍스트 영역.
- 최신 로그 자동 스크롤(Auto-scroll) 및 로그 검색/필터링 기능.

## 4. 사용자 인터랙션 (Interactions)

### 4.1 원클릭 설치 (One-click Install)
- 설치 시작 버튼 클릭 시 백엔드로 설치 요청 송신.
- 백엔드에서 `docker-compose up -d` 실행 및 소스 코드(그누보드 등) 다운로드 자동 수행.
- 완료 후 성공 메시지와 함께 대시보드로 자동 리다이렉트.

### 4.2 실시간 상태 동기화 (Real-time Sync)
- Polling 또는 WebSocket을 이용해 Docker 데몬으로부터 컨테이너 상태를 주기적으로 갱신.
- 사용자 조작 없이도 외부 요인에 의한 컨테이너 정지 등을 UI에 즉각 반영.

### 4.3 자동 .env 업데이트 로직 (Auto .env Update)
- UI에서 수정된 내용을 저장하면 백엔드에서 기존 `.env`를 `.env.bak`으로 백업 후 신규 파일 생성.
- 변경된 설정이 적용되기 위해 재시작이 필요한 서비스가 있을 경우 사용자에게 알림 및 재시작 제안.

## 5. 데이터 요구사항 (Data Requirements)

### 5.1 Config JSON (Deployment Sets)
- 각 배포 환경의 메타데이터를 정의하는 표준 스키마.
- `{"name": "Cafe24-PHP82", "version": "1.0", "services": [...], "env_template": {...}}`

### 5.2 Container Status Data
- Docker Engine API (`/containers/json`)로부터 응답받는 상태 데이터 객체.
- 컨테이너 ID, 이름, 이미지, 상태, 포트 맵핑 정보 포함.

### 5.3 System Requirements Spec
- 설치 전 체크해야 할 최소/권장 사양 정보가 담긴 정적 데이터 파일.
