# [PRD] 인프라 및 인스톨러 (Infra & Installer)

## 1. 문서 개요
- **문서명**: 인프라 및 인스톨러 요구사항 명세서
- **버전**: v1.0
- **작성일**: 2026-02-12
- **상태**: Draft

## 2. 개요 (Overview)
본 모듈은 카본빌더 개발 환경의 기초를 다지고, 복잡한 서버 및 개발 환경 설정을 웹 GUI를 통해 원클릭에 가깝게 구현하는 것을 목적으로 합니다. Docker를 기반으로 개발 PC에 최적화된 환경을 자동 구성하며, 다양한 배포 환경(Cafe24, AWS 등)을 유연하게 관리할 수 있도록 지원합니다.

## 3. 핵심 기능 (Core Features)
### 3.1 GUI 인스톨러 (Web-based Setup)
- `/dev` 폴더 접근 시 웹 기반 인스톨러 인터페이스 제공.
- 시스템 요구사항 체크 (Docker 설치 여부, 포트 점유 등).
- 관리자(Admin) 계정 정보 설정 및 초기화.

### 3.2 환경변수 관리 (Environment Configuration)
- 웹 UI를 통해 DB 접속 정보, API 키 등 환경변수 입력.
- 입력된 정보를 바탕으로 `.env` 파일 자동 생성 및 관리.
- 환경별(Development, Production) 변수 세트 분리 관리.

### 3.3 도커 기반 환경 구축 (Docker Orchestration)
- 개발 PC에 최적화된 Docker 컨테이너 구성 (PHP 8.2, MySQL/PostgreSQL, Redis 등).
- 그누보드5 최신 버전 및 DB 개발 환경 셋트 자동 다운로드 및 설치.
- 컨테이너 상태 모니터링 및 재시작/정지 제어 GUI.

### 3.4 배포 세트 관리 (Deployment Set Management)
- 다양한 서버 환경(Cafe24 호스팅, AWS EC2, 로컬 윈도우/우분투 등)에 최적화된 설정 정보를 JSON 형태로 저장.
- 배포 환경 세트 공유 및 불러오기 기능을 통해 환경 구축 시간 단축.
- GitHub Actions 연동 설정 또는 수동 배포 스크립트 실행 선택 기능.

## 4. 사용자 시나리오 (User Scenarios)
1. **최초 환경 구축**: 사용자가 프로젝트의 `/dev` 경로로 접속하여 인스톨러를 실행합니다.
2. **정보 입력**: GUI를 통해 관리자 계정, 사용할 DB 종류, 외부 API 키 등을 입력합니다.
3. **자동 설치**: 인스톨러가 Docker Compose를 실행하여 필요한 컨테이너를 띄우고 그누보드 및 FAPI 환경을 소스 코드와 연결합니다.
4. **환경 최적화**: 사용자가 미리 정의된 'Cafe24 호스팅 세트'를 선택하여 실제 배포 환경과 동일한 설정을 적용합니다.

## 5. 기술적 요구사항 (Technical Requirements)
- **Frontend**: SvelteKit (관리 UI)
- **Backend**: PHP 8.2 (그누보드5 기반 엔진), Docker/Docker-Compose
- **Database**: MySQL 8.0+ 또는 PostgreSQL
- **Infrastructure**: Docker Desktop (Windows/Mac) 또는 Docker Engine (Linux)

## 6. 수용 기준 (Acceptance Criteria)
- 사용자가 `/dev` 접근 시 오류 없이 인스톨러 화면이 노출되는가?
- 웹 UI에서 저장한 설정값이 실제 `.env` 파일에 정확히 반영되는가?
- Docker를 통해 그누보드5와 API 서버가 독립적으로 정상 구동되는가?
- JSON 기반의 배포 환경 세트를 내보내고 다시 불러올 수 있는가?
