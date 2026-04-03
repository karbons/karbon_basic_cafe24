# PRD – Gnuboard API + SvelteKit 확장 플랫폼 (shadcn-svelte 적용)

## 1. 프로젝트 개요

### 프로젝트명

Gnuboard API + SvelteKit 확장 플랫폼

### 한 줄 정의

**그누보드 사용자가 가장 익숙한 구조로 사용하는, 최신 SvelteKit 기반 API·앱·템플릿 플랫폼**

### 핵심 목표

* 그누보드 사용자에게 **낯설지 않은 구조** 제공
* SvelteKit + JWT + Capacitor 기반 **즉시 앱 전환 가능한 프론트엔드** 제공
* 디자인 고민 없이 바로 판매 가능한 **표준 UI 시스템** 제공
* 무료 배포를 통한 빠른 확산 + 유료 기능을 통한 수익화

---

## 2. 프로젝트 배경 및 문제 인식

### 기존 문제

* 그누보드는 여전히 강력하지만

  * 최근 추가된 API 구조가 복잡
  * 프론트엔드/앱 확장이 어려움
  * 앱에 필요한 기능이 부족함

* 반면 최신 프레임워크는

  * 구조가 낯설어 기존 그누보드 사용자 진입장벽이 높음
  * 디자인과 기능구현에 과도한 시간이 소요됨

### 해결 전략

* **백엔드는 그누보드, 프론트엔드는 SvelteKit**
* **폴더 구조는 그누보드 감성 유지**
* **디자인은 shadcn-svelte로 표준화**

---

## 3. 전체 아키텍처 개요

```
[Gnuboard]
  └─ API (클래스 제거, 함수형)
       ↓
[SvelteKit]
  ├─ JWT (HttpOnly Cookie)
  ├─ shadcn-svelte UI
  └─ Capacitor (즉시 앱화)
```

---

## 4. 디렉토리 설계 (핵심 설계 포인트)

### 4-1. lib 디렉토리 전체 구조

```txt
src/lib/
├─ api/        # 그누보드 연동 함수형 API
├─ store/      # Svelte store
├─ hook/       # lifecycle / auth hook
├─ type/       # 공통 타입
├─ util/       # 공통 유틸
│
├─ ui/         # ⭐ shadcn-svelte UI 컴포넌트 (디자인 표준)
│  ├─ button.svelte
│  ├─ input.svelte
│  ├─ dialog.svelte
│  ├─ table.svelte
│  └─ form/
│
└─ skin/       # ⭐ 그누보드 사용자 친화 영역
   ├─ latest/
   ├─ board/
   ├─ member/
   └─ layout/
```

### 4-2. 설계 철학

* `lib/ui`

  * shadcn-svelte 기반
  * 디자인 표준, 수정 최소화
  * **"이쁘게 보이게 만드는 영역"**

* `lib/skin`

  * 그누보드 skin 개념 그대로 차용
  * UI 컴포넌트를 조합만 함
  * **디자인 고민 없이 기능에 집중**

---

## 5. shadcn-svelte 채택 이유

### 왜 shadcn-svelte인가?

* Tailwind 기반의 검증된 UI 품질
* headless + 로컬 컴포넌트 방식
* 테마 시스템 불필요
* AI 코드 생성과 궁합이 매우 좋음

### 역할 정의

| 영역        | 역할     |
| --------- | ------ |
| shadcn UI | 디자인 표준 |
| skin      | 기능 조합  |
| API       | 데이터 공급 |

> **디자인은 시스템이 책임지고, 개발자는 구조와 비즈니스에 집중**

---

## 6. 제공 기능 (무료 / 유료)

### 6-1. 무료 제공

* Gnuboard Simple API
* SvelteKit 프론트엔드 구조
* JWT 인증 (HttpOnly Cookie)
* shadcn-svelte 기본 UI 세트
* Capacitor 연동 (Web → App)

### 6-2. 유료 확장

* 영카트 + Svelte + Capacitor
* PG 결제 모듈 (PG사별)
* Push 알림
* RBAC (권한 관리)
* VOC / 민원 관리
* 입점몰 정산 시스템
* 약관 / 개인정보 자동 갱신

---

## 7. 주요 활용 시나리오

* 후보자 홈페이지 템플릿 판매
* 지자체 / 협회 / 단체 사이트
* 쇼핑몰 앱 패키지
* IoT / IPv6 기기 등록 플랫폼
* SaaS형 관리 시스템

---

## 8. 배포 전략

### 오픈소스 / 무료

* Gnuboard API + SvelteKit
* shadcn UI 포함 기본 템플릿

### 상업 모델

* 템플릿 패키지 판매
* 기능 단위 유료 모듈
* API Hub 형태의 완성형 상품

---

## 9. 이 프로젝트의 본질

> **이 프로젝트는 단순한 프론트엔드 프레임워크가 아니다.**

* 그누보드 생태계를
* 현대적인 SaaS / App 구조로 확장하고
* 개인·소규모 개발자가 **즉시 수익화 가능한 플랫폼**으로 만드는 것이 목적이다.

**“디자인 때문에 망하지 않게 해주는 구조”**


