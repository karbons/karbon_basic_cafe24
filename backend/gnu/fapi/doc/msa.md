# 📄 FAPI 기반 MSA 전환 설계 PRD

## 1. 문서 개요

### 1.1 목적

본 문서는 기존 그누보드 기반 ERP 시스템을 다음 목표로 재설계하기 위한 아키텍처 요구사항을 정의한다.

* 파일 기반(FAPI) 개발 생산성 유지
* 그누보드 개발자 친화적 구조 유지
* 복잡한 OOP/클래스 구조 배제
* 장기적으로 MSA 전환 가능 구조 확보
* Rust Axum 기반 백엔드로 점진적 마이그레이션 가능

즉,

> “지금은 단순하게, 미래에는 분리 가능하게”

를 핵심 설계 철학으로 한다.

---

## 2. 배경 및 문제 정의

### 2.1 기존 방식 한계 (그누보드 스타일)

* URL 수동 파싱
* 절차형 코드 혼재
* 비즈니스 로직 + DB 직접 호출 혼합
* 도메인 경계 없음
* 서비스 분리 불가능
* Rust/Axum 전환 어려움

결과:

* 확장성 낮음
* 협업 어려움
* 대규모 SaaS/ERP 구조로 성장 불가

---

## 3. 목표 (Goals)

### 3.1 기능 목표

* 파일 기반 라우팅 유지
* 함수 중심 개발 유지
* JWT httpOnly cookie 기반 인증 유지
* API/DB 논리적 분리
* 도메인 단위 코드 분리

### 3.2 비기능 목표

* 러닝커브 최소화 (그누보드 개발자 기준)
* 코드 단순성 유지
* MSA 전환 시 코드 변경 최소화
* Rust Axum 1:1 매핑 가능

---

## 4. 설계 원칙 (Architecture Principles)

### P1. File First

파일 구조 = 기능 구조

### P2. Function Only

클래스/OOP 의존 금지
모든 로직은 함수 기반

### P3. Domain Separation

도메인 단위 디렉토리 분리

### P4. Loose Coupling

라우트 → 서비스 → 레포지토리 단방향 호출

### P5. MSA Ready

폴더 단위 복사만으로 독립 서비스화 가능

---

## 5. 전체 아키텍처

---

### 5.1 디렉토리 구조

```
routes/                # API 엔트리 (개발자 작업 영역)
  bbs/
  member/
  auth/

domains/               # 도메인 비즈니스 로직 (내부 엔진)
  board/
    service.php
    repository.php
  member/
  auth/
  complaint/
  billing/

shared/                # 공통 모듈
  db.php
  middleware.php
  utils.php
```

---

## 6. 레이어 정의

### 6.1 Routes (Controller 역할)

책임:

* 요청 수신
* 파라미터 추출
* 서비스 호출
* 응답 반환

금지:

* SQL 실행 ❌
* 비즈니스 로직 ❌

예시:

```php
function GET($bo_table) {
    $posts = board_get_posts($bo_table);
    json_return($posts);
}
```

---

### 6.2 Service (비즈니스 로직)

책임:

* 업무 규칙 처리
* 트랜잭션 관리
* 여러 repository 조합

금지:

* 직접 SQL ❌

예시:

```php
function board_get_posts($bo_table) {
    return board_repo_find_all($bo_table);
}
```

---

### 6.3 Repository (DB 접근 전용)

책임:

* SQL 실행
* 데이터 CRUD

예시:

```php
function board_repo_find_all($bo_table) {
    return sqlx::query(...)->fetch_all();
}
```

---

## 7. 네이밍 규칙

### 7.1 함수 네임스페이스 방식

클래스 대신 prefix 사용

```
board_get_posts()
board_repo_find_all()

member_get_user()

auth_login()
```

### 이유

* 충돌 방지
* 가독성 향상
* Rust 전환 시 namespace 대응 가능

---

## 8. MSA 전환 전략

---

### 8.1 단계 1 — Modular Monolith (현재 단계)

* 단일 서버
* 도메인 폴더 분리
* 논리적 분리만 수행

---

### 8.2 단계 2 — DB 분리

도메인별 DB 분리

```
board DB
member DB
auth DB
```

sqlx:

```
sqlx::conn('board')
```

---

### 8.3 단계 3 — Gateway 도입

```
client
 ↓
gateway (auth / logging / rate limit)
 ↓
services
```

---

### 8.4 단계 4 — 서비스 분리 (MSA)

예:

```
domains/board → board-service
domains/auth → auth-service
```

폴더 복사 후 독립 배포

Routes는 HTTP 호출로 변경:

```php
http_call('board-service')
```

---

### 8.5 단계 5 — Rust Axum 전환

PHP:

```
board_get_posts()
```

Rust:

```
board::get_posts()
```

거의 동일 구조

핸들러 매핑:

```
function GET() → async fn get()
```

---

## 9. 개발 규칙 (필수)

### Rule 1

Route에서 SQL 금지

### Rule 2

Service에서 SQL 금지

### Rule 3

Repository만 DB 접근 가능

### Rule 4

도메인 간 직접 include 금지

→ HTTP/Event 방식 사용

### Rule 5

모든 함수 prefix 필수

---

## 10. 기대 효과

### 단기

* 코드 단순
* 그누보드 개발자 즉시 적응
* 생산성 유지

### 중기

* 기능별 유지보수 쉬움
* 테스트 쉬움
* 도메인 독립성 확보

### 장기

* MSA 자연스러운 분리
* Rust Axum 전환 비용 최소
* SaaS/ERP 대규모 확장 가능

---

## 11. 성공 기준 (Success Metrics)

* 신규 기능 개발 시 route 파일만 수정 가능
* 도메인 폴더 단독 실행 가능
* Rust 전환 시 rewrite < 30%
* 신규 개발자 온보딩 < 1일

---

# ✅ 최종 결론

본 설계는

> "파일 기반 개발 생산성" + "엔터프라이즈 확장성"을 동시에 만족하는
> FAPI 전용 MSA 준비 아키텍처

이며,

* 클래스 없이
* 함수 기반만으로
* 장기적으로 완전한 MSA 가능

하도록 설계되었다.

---
