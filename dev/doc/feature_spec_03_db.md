# Feature Spec 03: 데이터베이스 및 모델링 (DB & Modeling)

## 1. 도메인 개요 (Domain Overview)
본 모듈은 '데이터 중심 개발(Data-Centric Development)'을 실현하기 위한 핵심 도구입니다. 개발자가 복잡한 SQL 쿼리 작성이나 별도의 DB 관리 도구 없이 웹 GUI 상에서 직관적으로 데이터베이스를 설계하고, 시각화된 ERD를 통해 구조를 관리하며, 개발에 즉시 사용 가능한 TypeScript 타입을 자동 생성하는 것을 목적으로 합니다. Supabase와 유사한 사용성을 제공하면서도 로컬 환경 및 다양한 DB(PostgreSQL, MySQL)와의 유연한 연동을 지원합니다.

---

## 2. 화면 정의 (Page Definitions)

### 2.1 테이블 빌더 (Table Builder)
- **목적**: 테이블 생성 및 컬럼 스키마 정의.
- **주요 기능**:
    - 테이블 추가/삭제/이름 변경.
    - 컬럼 추가: 이름, 데이터 타입(String, Number, JSON, Date 등), 기본값 설정.
    - 제약 조건 설정: Primary Key, Foreign Key, Not Null, Unique, Auto Increment.
    - 인덱스(Index) 관리.

### 2.2 데이터 브라우저 (Data Browser)
- **목적**: 테이블 내 실제 데이터를 조회하고 편집.
- **주요 기능**:
    - 스프레드시트 방식의 그리드 인터페이스.
    - 레코드 추가, 수정, 삭제 (CRUD).
    - 필터링, 정렬, 검색 기능.
    - 대용량 데이터 대응을 위한 페이징 및 무한 스크롤.

### 2.3 ERD 다이어그램 뷰 (ERD Diagram View)
- **목적**: 데이터베이스 구조를 시각적으로 파악하고 관계를 설정.
- **주요 기능**:
    - 테이블 간의 외래키(FK) 관계를 선(Edge)으로 표시.
    - 테이블 노드 배치 및 자동 정렬.
    - 다이어그램 내에서 직접 관계 설정 (Drag & Drop).
    - 미니맵 및 확대/축소 지원.

### 2.4 타입 정의 생성기 (Type Definition Generator)
- **목적**: DB 스키마를 기반으로 코드에서 사용할 타입을 생성.
- **주요 기능**:
    - DB 스키마 분석을 통한 TypeScript Interface/Type 자동 추출.
    - 스네이크 케이스(snake_case) → 카멜 케이스(camelCase) 변환 옵션.
    - 생성된 코드의 실시간 프리뷰.
    - 프로젝트 경로(`src/lib/types`)로의 원클릭 내보내기.

---

## 3. 핵심 UI 구성 요소 (Key Components)

- **스키마 그리드 (Schema Grid)**: 테이블의 컬럼 리스트를 한눈에 보고 편집할 수 있는 목록 UI.
- **속성 패널 (Properties Panel)**: 선택된 컬럼이나 테이블의 상세 설정(타입, 제약조건, 설명 등)을 편집하는 사이드바.
- **ERD 캔버스 (ERD Canvas)**: 테이블 노드와 관계선을 렌더링하고 인터랙션을 처리하는 영역.
- **코드 프리뷰어 (Code Previewer)**: 생성될 TypeScript 코드를 구문 강조(Syntax Highlighting)와 함께 보여주는 영역.
- **데이터 툴바 (Data Toolbar)**: 필터링, 정렬, 데이터 내보내기(CSV/JSON) 기능을 포함한 상단 제어 바.

---

## 4. 사용자 인터랙션 (Interactions)

- **드래그 앤 드롭 관계 설정**: 한 테이블의 컬럼을 드래그하여 다른 테이블의 PK로 연결하면 자동으로 Foreign Key 관계가 설정되고 다이어그램에 반영됩니다.
- **실시간 스키마 반영**: UI에서 변경된 사항은 '저장' 버튼 클릭 시 즉시 실제 DB 스키마(Migration 실행)에 반영됩니다.
- **캔버스 인터랙션**: 마우스 휠로 확대/축소, 빈 공간 드래그로 캔버스 이동, 테이블 노드 자유 배치.
- **원클릭 타입 내보내기**: 설정된 출력 경로로 타입 정의 파일을 즉시 생성 또는 업데이트.

---

## 5. 데이터 요구사항 (Data Requirements)

- **Schema Meta**:
    - 테이블명, 컬럼 정보(이름, 타입, 제약조건), 인덱스 정보.
    - 테이블 간 관계 정의(FK 매핑 정보).
- **Table Data**:
    - 데이터 브라우저에 표시될 실제 행(Rows) 데이터.
    - 정렬 및 필터링 상태 값.
- **ERD Coordinates**:
    - 다이어그램 내에서 각 테이블 노드의 X, Y 좌표 및 크기 정보.
    - 캔버스의 줌 배율 및 위치 정보.
- **Export Config**:
    - 타입 파일 생성 경로, 명명 규칙(Prefix/Suffix) 설정 값.
