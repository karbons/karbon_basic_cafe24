# Feature Spec 04: 디자인 및 컴포넌트 시스템 (Design & Component System)

## 1. 도메인 개요 (Domain Overview)
본 모듈은 디자인과 개발의 간극을 줄이고, 프로젝트의 시각적 일관성을 유지하며 개발 생산성을 극대화하는 것을 목적으로 합니다. 기획 단계의 스토리보드부터 실제 동작을 시뮬레이션하는 프로토타입, 그리고 코드와 직접 연결되는 디자인 시스템 및 외부 컴포넌트 샵 연동을 통해 전주기적인 UI/UX 관리를 지원합니다.

---

## 2. 화면 정의 (Page Definitions)

### 2.1 스토리보드 관리자 (Storyboard Manager)
- **목적**: 화면별 기획 의도와 사용자 흐름(Flow)을 시각적으로 관리합니다.
- **주요 기능**:
  - 화면 이미지 업로드 및 버전 관리.
  - 각 화면에 대한 기획 설명 및 요구사항 기술.
  - 화면 간 관계(Flow) 시각화 및 정렬.

### 2.2 프로토타입 뷰어 (Prototype Viewer)
- **목적**: 실제 서비스와 유사한 사용자 경험을 제공하여 디자인 컨펌 및 사용성 테스트를 수행합니다.
- **주요 기능**:
  - 스토리보드 이미지를 활용한 인터랙티브 시뮬레이션.
  - 핫스팟(Hotspot)을 통한 화면 전환 구현.
  - 고객 및 팀원 간 피드백(댓글) 수집 및 상태 관리.

### 2.3 디자인 시스템 정의 화면 (Design System Definition)
- **목적**: 프로젝트 전체에서 사용될 디자인 규칙과 컴포넌트 명세를 정의하고 관리합니다.
- **주요 기능**:
  - **가이드라인 관리**: 브랜드 컬러, 타이포그래피, 그리드 시스템 등 기초 규칙 정의.
  - **아토믹 컴포넌트 정의**: 버튼, 입력창 등 기초 단위(Atoms)부터 복합 단위(Organisms)까지 계층적 관리.
  - **디자인 토큰 관리**: 수치 정보를 변수화하여 코드로 즉시 내보내기 가능하도록 관리.

### 2.4 컴포넌트 샵 (Component Shop)
- **목적**: 검증된 외부 UI 리소스를 탐색하고 현재 프로젝트에 즉시 도입합니다.
- **주요 기능**:
  - 카본 전용 컴포넌트 샵 탐색 및 검색.
  - 컴포넌트 프리뷰 및 문서 확인.
  - 원클릭 임포트를 통한 프로젝트 내 자동 설치.

---

## 3. 핵심 UI 구성 요소 (Key Components)

### 3.1 이미지 렌더러 (Image Renderer)
- 고해상도 디자인 파일을 웹 환경에서 빠르고 선명하게 표시하는 엔진.
- 줌인/아웃 및 패닝 기능 지원.

### 3.2 핫스팟 도구 (Hotspot Tool)
- 이미지 위에 클릭 가능한 영역을 사각형 또는 원형으로 지정하는 도구.
- 클릭 시 이동할 대상 화면(Target Screen) 지정 인터페이스 포함.

### 3.3 디자인 토큰 목록 (Design Token List)
- 컬러 팔레트, 폰트 크기, 간격(Spacing) 등을 시각적 요소와 함께 리스트 형태로 표시.
- 각 토큰별 변수명(Variable Name) 및 CSS/JS 매핑 정보 노출.

### 3.4 컴포넌트 프리뷰 카드 (Component Preview Card)
- 컴포넌트의 시각적 모습과 함께 주요 Props, 상태 변환을 직접 조작해볼 수 있는 카드형 UI.
- 컴포넌트 샵에서 리스트 형태로 탐색할 때 사용.

---

## 4. 사용자 인터랙션 (Interactions)

### 4.1 핫스팟 매핑 (Hotspot Mapping)
- 디자이너가 이미지의 특정 영역을 드래그하여 선택하고, 연결할 다른 화면을 드롭다운에서 선택하면 흐름이 즉시 연결됨.

### 4.2 원클릭 컴포넌트 임포트 (One-click Import)
- 컴포넌트 샵에서 'Import' 버튼 클릭 시, 백엔드에서 필요한 라이브러리(npm 등)와 파일을 자동으로 내려받아 `/dev` 및 프로젝트 소스 경로에 배치.

### 4.3 디자인-코드 동기화 (Design-Code Sync)
- 디자인 시스템 정의 화면에서 토큰 값이 변경되면, 실시간으로 관련 CSS 변수 또는 Svelte 변수 파일이 업데이트되어 개발 환경에 반영됨.

---

## 5. 데이터 요구사항 (Data Requirements)

### 5.1 Storyboard Data
- `id`: 고유 식별자.
- `project_id`: 소속 프로젝트 ID.
- `image_url`: 화면 이미지 경로.
- `version`: 디자인 버전.
- `hotspots`: Array (x, y, width, height, target_id, type).

### 5.2 Design Tokens
- `category`: color, typography, spacing 등 카테고리.
- `token_name`: 변수 이름 (예: `primary-color`).
- `value`: 실제 값 (예: `#3490dc`).
- `description`: 용도 설명.

### 5.3 Component Metadata
- `component_id`: 고유 ID.
- `name`: 컴포넌트 명.
- `props`: Array (name, type, default, required).
- `variants`: Array (name, style_overrides).
- `dependencies`: 외부 라이브러리 의존성 목록.
