# Gnuboard SvelteKit

> **그누보드를 위한 가장 쉬운 SvelteKit + App 프레임워크**
> 익숙한 구조로 시작해서, 웹과 앱까지 바로 확장합니다.

---

## ✨ 프로젝트 한 줄 소개

**Gnuboard SvelteKit**은 기존 그누보드 사용자가 **새로운 프레임워크를 다시 배우지 않고**,
SvelteKit 기반 웹과 모바일 앱을 바로 만들 수 있도록 돕는 **프론트엔드 확장 프레임워크**입니다.

> 👉 새로운 것을 강요하지 않고, 익숙한 것을 확장합니다.

---

## 🎯 왜 이 프로젝트인가

* 그누보드는 강력하지만 프론트엔드/앱 확장이 어렵습니다
* 클래스·추상화 위주의 API는 진입 장벽이 높습니다
* AI 기반 개발 환경에서는 **단순하고 명확한 구조**가 중요합니다

**Gnuboard SvelteKit은 이 문제를 해결합니다.**

---

## 🔑 핵심 특징

### 1. 그누보드 친화적 구조

* `/lib`, `/skin` 개념 그대로 유지
* `latest`, `board`, `member` 등 익숙한 스킨 구조

```txt
lib/gnuboard/skin/
 ├ latest/
 ├ board/
 └ member/
```

---

### 2. 파일 기반 · 함수형 API

* 클래스/DI 없음
* API는 단순 함수로 구성

```ts
getBoardList(bo_table, page)
login(id, password)
```

👉 AI 코드 생성·수정에 최적화

---

### 3. SvelteKit + 즉시 App 전환

* SvelteKit 기반 SSR/SPA
* JWT + HttpOnly Cookie 인증
* Capacitor 기본 내장

👉 **웹 → 앱 전환 즉시 가능**

---

### 4. 디자인은 고정, 변경은 값으로

* 테마/블록 에디터 제거
* Preset + Parameter 구조

```json
{
  "title": "OO구 기호 1번 홍길동",
  "primaryColor": "#2563eb"
}
```

👉 복잡도 최소화, 유지보수 극대화

---

## 🧱 디렉토리 구조 요약

```txt
src/
└ lib/
   ├ gnuboard/
   │  ├ skin/      # UI (렌더링 전용)
   │  ├ api/       # 파일 기반 API
   │  ├ service/   # 비즈니스 로직
   │  ├ hook/      # 확장 포인트
   │  ├ store/     # 상태 관리 (선택)
   │  ├ util/      # 헬퍼 함수
   │  ├ type/      # 타입 정의
   │  └ config/    # 설정
```

---

## 🔐 인증 구조

* JWT 기반 인증
* HttpOnly Cookie 사용
* Web/App 공통 인증 구조

---

## 📱 App 전략

* Capacitor 기본 포함
* Push / PG / 기기 연동은 확장 가능
* Core는 무료

---

## 💰 라이선스 & 수익 모델

### 무료 (Open)

* Core 프레임워크
* 기본 skin
* API 구조

### 유료 (Pro / Add-on)

* 완성형 템플릿 (선거, 쇼핑, 캠페인)
* PG 결제
* Push
* RBAC
* 정산 모듈

---

## 👥 이런 분께 추천합니다

* 그누보드 기반으로 외주/사이드 프로젝트를 하는 개발자
* 빠르게 웹 + 앱을 만들고 싶은 1인 개발자
* 선거·캠페인·관리 시스템을 반복 구축하는 팀

---

## 🧭 프로젝트 방향 한 문장

> **이 프로젝트는 새로운 프레임워크가 아니라,
> 그누보드의 다음 세대입니다.**

---

## 🚀 시작하기

```bash
npm install
npm run dev
```

> 자세한 사용법은 `/docs` 또는 예제 코드를 참고하세요.
