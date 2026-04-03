# 다국어 지원 적용 (i18n Implementation)

## TL;DR

> **Quick Summary**: 세 SvelteKit 앱(main, app, admin)에 svelte-i18n 기반 다국어 지원을 적용합니다. URL 구조를 `/{appType}/{lang}/{...}`로 변경하고, 언어 쿠키 저장, 301 리다이렉트, 번역 파일 생성, LanguageDropdown 컴포넌트를 구현합니다.
>
> **Deliverables**:
> - hooks.server.ts (쿠키 처리 + URL 리다이렉션)
> - svelte-i18n 설정 + 번역 파일 (ko.json, en.json)
> - LanguageDropdown 컴포넌트
> - 라우트 구조 변경 (`/{lang}/` prefix)
> - vitest 테스트 인프라
> - 301 리다이렉트 (old URL → new URL)
>
> **Estimated Effort**: Large (3 apps, 40+ tasks)
> **Parallel Execution**: YES - Wave-based approach
> **Critical Path**: main(i18n setup) → main(routes) → app/admin → final verification

---

## Context

### Original Request
`docs/다국어지원.md` 문서를 기준으로 다국어 지원 버전을 현재 프로젝트에 적용해주세요.

### Interview Summary

**Key Decisions**:
1. URL 구조 변경: `/{appType}/{...}` → `/{appType}/{lang}/{...}`
2. 세 앱 모두 적용: main, app, admin
3. 백엔드 전략: 전략 A (백엔드에서 번역된 문자열 응답)
4. 지원 언어: 한국어(ko) + 영어(en)
5. Svelte 5 호환성: 레거시 스토어 API 사용
6. URL 마이그레이션: 301 리다이렉트 포함
7. 테스트: vitest 설치
8. 기존 문자열: 추출하여 번역 파일 초기값으로 사용
9. 적용 순서: main에서 검증 후 app, admin 확장

**Research Findings**:
- 각 앱은 별도 SvelteKit 프로젝트 (main=/main, app=/app, admin=/admin base path)
- Svelte 5에서 svelte-i18n은 레거시 스토어 API 필요 (`import { _, locale }`)
- adapter-static 사용 중 (fallback: 'index.html')
- 테스트 인프라 미설치

### Metis Review

**Identified Gaps** (addressed):
1. Svelte 5 + svelte-i18n 호환성 → 레거시 API 사용으로 해결
2. URL 마이그레이션 → 301 리다이렉트 구현 계획
3. Static adapter 동적 라우팅 → prerender.entries: 'all' 설정
4. Capacitor deep links → appUrlOpen 핸들러 업데이트
5. SEO hreflang → main 앱에만 적용 (PRD 기준)

---

## Work Objectives

### Core Objective
세 SvelteKit 앱에 완전한 다국어 지원을 적용하여 사용자가 언어를 선택하고 전환할 수 있게 합니다.

### Concrete Deliverables
- `frontend/main/src/hooks.server.ts` - 언어 감지 + 쿠키 처리
- `frontend/main/src/lib/i18n/index.ts` - svelte-i18n 초기화
- `frontend/main/src/lib/i18n/ko.json` - 한국어 번역
- `frontend/main/src/lib/i18n/en.json` - 영어 번역
- `frontend/main/src/lib/components/LanguageDropdown.svelte` - 언어 선택 드롭다운
- `frontend/main/src/routes/(ko)/*` - 한국어 라우트 그룹
- `frontend/main/src/routes/(en)/*` - 영어 라우트 그룹
- `frontend/main/src/routes/+redirect.ts` - 301 리다이렉트
- `frontend/main/vitest.config.ts` - vitest 설정
- (동일 구조로 app, admin에 적용)

### Definition of Done
- [ ] `npm run build` 성공 (세 앱 모두)
- [ ] `/main/ko/about` 접속 시 한국어 UI 표시
- [ ] `/main/en/about` 접속 시 영어 UI 표시
- [ ] `/main/about` 접속 시 `/main/ko/about`로 301 리다이렉트
- [ ] 언어 변경 시 쿠키 저장 + URL 변경
- [ ] vitest 테스트 통과

### Must Have
- svelte-i18n 설치 및 초기화
- 언어 감지 체인 (URL > 쿠키 > 브라우저 > 기본값)
- 쿠키 저장 (1년, HttpOnly=false, SameSite=Lax)
- LanguageDropdown 컴포넌트
- 기존 URL 301 리다이렉트
- vitest 테스트 인프라

### Must NOT Have (Guardrails)
- 백엔드 API 수정 (프론트엔드만)
- Crowdin/Lokalise 연동
- RTL 언어 지원
- URL 패스 번역 (paths는 영어만)
- 관리자용 번역 관리 UI

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: NO
- **Automated tests**: YES (TDD)
- **Framework**: vitest
- **Approach**: main 앱에서 POC 검증 후 app, admin 확장

### QA Policy
모든 태스크는 에이전트 실행 QA 시나리오를 포함합니다.
证据 저장 위치: `.sisyphus/evidence/`

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (main 앱 - Foundation):
├── T1: svelte-i18n + vitest 설치 (main)
├── T2: i18n 초기화 모듈 생성 (main)
├── T3: hooks.server.ts 생성 (쿠키 + 리다이렉션) (main)
├── T4: 번역 파일 생성 (ko.json, en.json) (main)
├── T5: LanguageDropdown 컴포넌트 (main)
└── T6: layout.ts에 i18n 초기화 (main)

Wave 2 (main 앱 - 라우트):
├── T7: 라우트 그룹 생성 (ko/en) (main)
├── T8: 기존 라우트를 라우트 그룹으로 이동 (main)
├── T9: +redirect.ts 생성 (301 리다이렉트) (main)
├── T10: 각 페이지에 번역 적용 (main)
└── T11: SEO hreflang 설정 (main)

Wave 3 (app/admin 확장):
├── T12: app 앱에 T1-T6 반복
├── T13: app 앱에 T7-T10 반복
├── T14: admin 앱에 T1-T6 반복
└── T15: admin 앱에 T7-T10 반복

Wave FINAL:
├── F1: Plan compliance audit (oracle)
├── F2: Build verification (세 앱 모두)
├── F3: QA 시나리오 실행
└── F4: Scope fidelity check
```

### Dependency Matrix

| Task | Blocked By | Blocks |
|------|-----------|--------|
| T1 (main: install) | - | T2, T6 |
| T2 (main: i18n init) | T1 | T3, T6 |
| T3 (main: hooks) | T2 | T7 |
| T4 (main: translations) | T1 | T5, T10 |
| T5 (main: dropdown) | T1 | T6 |
| T6 (main: layout) | T2, T5 | T7 |
| T7 (main: route groups) | T3, T6 | T8, T9 |
| T8 (main: move routes) | T7 | T10 |
| T9 (main: redirect) | T7 | T11 |
| T10 (main: apply translations) | T4, T8 | T11 |
| T11 (main: SEO) | T9, T10 | F1-F4 |
| T12-T15 (app/admin) | T11 (main 완료 후) | F1-F4 |

**Critical Path**: T1 → T2 → T3 → T7 → T8 → T10 → T11 → T12 → F1-F4

---

## TODOs

- [ ] 1. **main 앱: svelte-i18n + vitest 설치**

  **What to do**:
  - `cd frontend/main && npm install svelte-i18n`
  - `npm install -D vitest @vitest/ui`
  - package.json에 테스트 스크립트 추가: `"test": "vitest"`
  - vitest.config.ts 생성

  **Must NOT do**:
  - svelte-i18n runes 모듈 사용 금지 (레거시 스토어만)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 설치 및 설정은 간단한 npm 작업
  - **Skills**: []
    - No special skills needed for package installation

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with T2-T6)
  - **Blocks**: T2, T3, T4, T5, T6
  - **Blocked By**: None

  **References**:
  - `frontend/main/package.json:1-30` - 현재 dependencies 확인
  - `frontend/main/svelte.config.js:1-26` - vite.config 연동 확인
  - Official docs: `https://www.npmjs.com/package/svelte-i18n` - 설치 가이드
  - Official docs: `https://vitest.dev/guide/` - vitest 설정

  **Acceptance Criteria**:
  - [ ] `npm install svelte-i18n --prefix frontend/main` 성공
  - [ ] `npm install -D vitest @vitest/ui --prefix frontend/main` 성공
  - [ ] package.json에 `"test": "vitest"` 추가됨
  - [ ] `frontend/main/vitest.config.ts` 파일 생성됨

  **QA Scenarios**:

  \`\`\`
  Scenario: svelte-i18n 설치 확인
    Tool: Bash
    Preconditions: frontend/main 디렉토리
    Steps:
      1. npm install svelte-i18n
      2. grep "svelte-i18n" package.json
    Expected Result: "svelte-i18n"이 dependencies에 추가됨
    Failure Indicators: 설치 실패, peer dependency 오류
    Evidence: .sisyphus/evidence/task-1-install-success.log

  Scenario: vitest 설치 확인
    Tool: Bash
    Preconditions: svelte-i18n 설치 완료
    Steps:
      1. npm install -D vitest @vitest/ui
      2. npm test -- --version
    Expected Result: vitest 버전 출력 (예: 1.x.x)
    Failure Indicators: vitest 명령어 없음
    Evidence: .sisyphus/evidence/task-1-vitest-success.log
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add svelte-i18n and vitest`
  - Files: frontend/main/package.json, frontend/main/vitest.config.ts
  - Pre-commit: `cd frontend/main && npm test -- --run`

---

- [ ] 2. **main 앱: i18n 초기화 모듈 생성**

  **What to do**:
  - `src/lib/i18n/index.ts` 생성
  - svelte-i18n 설정 (레거시 모드, lazy loading)
  - supportedLocales 정의 ['ko', 'en']
  - initLocale 함수 생성 (URL → 쿠키 → 브라우저 → 기본값)

  **Must NOT do**:
  - Svelte 5 runes 사용 금지 (`$state()` 대신 `writable`)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 설정 파일 생성, 단순한 구조
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with T1, T3-T6)
  - **Blocks**: T3, T6
  - **Blocked By**: T1

  **References**:
  - `docs/다국어지원.md:78-111` - hooks.server.ts 예제 코드
  - `docs/다국어지원.md:259-284` - 번역 파일 구조
  - svelte-i18n docs: `https://github.com/kaisermann/svelte-i18n/blob/main/docs/04.api.md`

  **Acceptance Criteria**:
  - [ ] `src/lib/i18n/index.ts` 생성됨
  - [ ] `supportedLocales` = ['ko', 'en']
  - [ ] `initLocale()` 함수 exported
  - [ ] lazy loading 설정됨

  **QA Scenarios**:

  \`\`\`
  Scenario: i18n 모듈 생성 확인
    Tool: Bash
    Preconditions: T1 완료
    Steps:
      1. cat src/lib/i18n/index.ts
      2. grep "supportedLocales" src/lib/i18n/index.ts
      3. grep "initLocale" src/lib/i18n/index.ts
    Expected Result: 파일 존재, 두 함수/변수 exports 확인
    Evidence: .sisyphus/evidence/task-2-i18n-module.ts

  Scenario: svelte-i18n 레거시 API 사용 확인
    Tool: Bash
    Preconditions: index.ts 생성됨
    Steps:
      1. grep "import.*svelte-i18n" src/lib/i18n/index.ts
      2. grep "register" src/lib/i18n/index.ts
    Expected Result: `import { register, init, getLocaleFromNavigator } from 'svelte-i18n'`
    Failure Indicators: `$` 접두사 사용 (Svelte 5 runes)
    Evidence: .sisyphus/evidence/task-2-legacy-api.ts
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add i18n initialization module`
  - Files: frontend/main/src/lib/i18n/index.ts

---

- [ ] 3. **main 앱: hooks.server.ts 생성 (쿠키 + URL 리다이렉션)**

  **What to do**:
  - `src/hooks.server.ts` 생성
  - URL에서 언어 감지: `/{appType}/{lang}/...` 패턴
  - 유효하지 않은 언어 → 쿠키/기본값으로 리다이렉트
  - 쿠키 설정: lang, 1년, HttpOnly=false, SameSite=Lax
  - 301 리다이렉트 구현

  **Must NOT do**:
  - non-{appType} 경로에 적용 금지 (/api, /static 등)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: hooks.server.ts는 표준 SvelteKit 패턴
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with T1, T2, T4-T6)
  - **Blocks**: T7
  - **Blocked By**: T2

  **References**:
  - `docs/다국어지원.md:78-111` - hooks.server.ts 예제 코드
  - `frontend/main/svelte.config.js:13-15` - base path 확인 (/main)

  **Acceptance Criteria**:
  - [ ] `src/hooks.server.ts` 생성됨
  - [ ] `/main/xx/about` (xx는 유효하지 않은 언어) → `/main/ko/about` 302 리다이렉트
  - [ ] `/main/about` (언어 없음) → 쿠키 확인 후 리다이렉트
  - [ ] 쿠키 set: maxAge=31536000, sameSite='lax', secure=true

  **QA Scenarios**:

  \`\`\`
  Scenario: 유효하지 않은 언어 리다이렉트
    Tool: Bash
    Preconditions: hooks.server.ts 생성됨
    Steps:
      1. curl -I http://localhost:5173/main/xx/about 2>/dev/null | head -5
    Expected Result: HTTP 302, Location: /main/ko/about
    Failure Indicators: 200 응답, 잘못된 URL로 유지
    Evidence: .sisyphus/evidence/task-3-invalid-lang-redirect.log

  Scenario: 쿠키 설정 확인
    Tool: Bash
    Preconditions: 유효한 언어 URL 접속
    Steps:
      1. curl -I http://localhost:5173/main/ko/about 2>/dev/null | grep -i set-cookie
    Expected Result: Set-Cookie: lang=ko; Path=/; Max-Age=31536000; SameSite=Lax
    Evidence: .sisyphus/evidence/task-3-cookie-set.log
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add hooks.server.ts for i18n`
  - Files: frontend/main/src/hooks.server.ts

---

- [ ] 4. **main 앱: 번역 파일 생성 (ko.json, en.json)**

  **What to do**:
  - `src/lib/i18n/ko.json` 생성
  - `src/lib/i18n/en.json` 생성
  - 기존 페이지에서 문자열 추출하여 초기값 설정
  - 키 구조: `{도메인}.{기능}.{메시지명}`

  **Must NOT do**:
  - 값에 HTML 포함 금지
  - 키에 특수문자 사용 금지

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: JSON 파일 생성, 기존 문자열 추출
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with T1-T3, T5, T6)
  - **Blocks**: T10
  - **Blocked By**: T1

  **References**:
  - `docs/다국어지원.md:259-284` - 번역 파일 구조 예시
  - `frontend/main/src/routes/+page.svelte` - 추출할 문자열 소스
  - `docs/다국어지원.md:187-201` - 키 규칙

  **Acceptance Criteria**:
  - [ ] `src/lib/i18n/ko.json` 생성됨 (auth, common, errors 섹션)
  - [ ] `src/lib/i18n/en.json` 생성됨 (동일 구조, 영어 번역)
  - [ ] 모든 기존 페이지 문자열이 번역 파일에 존재

  **QA Scenarios**:

  \`\`\`
  Scenario: 번역 파일 구조 확인
    Tool: Bash
    Preconditions: 번역 파일 생성됨
    Steps:
      1. cat src/lib/i18n/ko.json | jq 'keys'
      2. cat src/lib/i18n/en.json | jq 'keys'
    Expected Result: 두 파일 모두 동일한 키 구조
    Failure Indicators: 키 불일치, 유효하지 않은 JSON
    Evidence: .sisyphus/evidence/task-4-translations-structure.json

  Scenario: JSON 유효성 검사
    Tool: Bash
    Preconditions: 번역 파일 존재
    Steps:
      1. node -e "JSON.parse(require('fs').readFileSync('src/lib/i18n/ko.json'))"
      2. node -e "JSON.parse(require('fs').readFileSync('src/lib/i18n/en.json'))"
    Expected Result: 에러 없음 (유효한 JSON)
    Failure Indicators: JSON parse error
    Evidence: .sisyphus/evidence/task-4-json-valid.json
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add translation files (ko, en)`
  - Files: frontend/main/src/lib/i18n/ko.json, frontend/main/src/lib/i18n/en.json

---

- [ ] 5. **main 앱: LanguageDropdown 컴포넌트 생성**

  **What to do**:
  - `src/lib/components/LanguageDropdown.svelte` 생성
  - 현재 언어 표시
  - 언어 선택 시 쿠키 업데이트 + URL 변경
  - Svelte 5 레거시 스토어 사용 (`$_`, `$_locale`)

  **Must NOT do**:
  - Svelte 5 runes 사용 금지
  - page reload 없이 SPA 전환 금지 (lazy load 때문)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: UI 컴포넌트 생성, 스타일링 필요
  - **Skills**: [`svelte`]
    - Svelte 5 컴포넌트 패턴
  - **Skills Evaluated but Omitted**:
    - `tailwindcss`: 기본 스타일만 사용 (추가 설치 불필요)

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with T1-T4, T6)
  - **Blocks**: T6
  - **Blocked By**: T4

  **References**:
  - `docs/다국어지원.md:125-144` - Svelte 컴포넌트 예시
  - `frontend/main/src/routes/+layout.svelte` - 레이아웃 구조 확인

  **Acceptance Criteria**:
  - [ ] `src/lib/components/LanguageDropdown.svelte` 생성됨
  - [ ] 한국어/영어 선택 옵션 존재
  - [ ] 선택 시 `window.location.href`로 페이지 reload

  **QA Scenarios**:

  \`\`\`
  Scenario: LanguageDropdown 렌더링
    Tool: Playwright
    Preconditions: main 앱 실행 중
    Steps:
      1. Navigate to http://localhost:5173/main/ko/
      2. Find select element with id or class for language dropdown
      3. Count options
    Expected Result: 2 options (한국어, English)
    Evidence: .sisyphus/evidence/task-5-dropdown-render.png

  Scenario: 언어 변경 시 URL 변경
    Tool: Playwright
    Preconditions: LanguageDropdown 존재
    Steps:
      1. Navigate to http://localhost:5173/main/ko/about
      2. Change language to English
      3. Check current URL
    Expected Result: URL changed to /main/en/about
    Failure Indicators: URL unchanged, no reload
    Evidence: .sisyphus/evidence/task-5-lang-change.png
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add LanguageDropdown component`
  - Files: frontend/main/src/lib/components/LanguageDropdown.svelte

---

- [ ] 6. **main 앱: layout.ts에 i18n 초기화 추가**

  **What to do**:
  - `src/routes/+layout.ts` 수정
  - i18n 초기화 (`initLocale()`) 호출
  - locale 파라미터를 로드에서 추출하여 전달

  **Must NOT do**:
  - prerender 설정 오버라이드 금지 (기존 설정 유지)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 설정 파일 수정
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with T1-T5)
  - **Blocks**: T7
  - **Blocked By**: T2, T5

  **References**:
  - `frontend/main/src/routes/+layout.ts` - 현재 파일 확인
  - `docs/다국어지원.md:78-111` - 초기화 패턴

  **Acceptance Criteria**:
  - [ ] `+layout.ts`에서 i18n 초기화 호출
  - [ ] locale 파라미터를 page data로 전달

  **QA Scenarios**:

  \`\`\`
  Scenario: layout.ts 수정 확인
    Tool: Bash
    Preconditions: T2, T5 완료
    Steps:
      1. cat src/routes/+layout.ts
      2. grep "initLocale\|i18n" src/routes/+layout.ts
    Expected Result: i18n 초기화 코드 존재
    Evidence: .sisyphus/evidence/task-6-layout-init.ts
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): integrate i18n in layout`
  - Files: frontend/main/src/routes/+layout.ts

---

- [ ] 7. **main 앱: 라우트 그룹 생성 (ko/en)**

  **What to do**:
  - `src/routes/(ko)/` 디렉토리 생성
  - `src/routes/(en)/` 디렉토리 생성
  - 각 그룹에 `+layout.ts` 생성 (locale 설정)

  **Must NOT do**:
  - 기존 `src/routes/` 파일 삭제 금지 (T8에서 처리)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 디렉토리 구조 생성
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: T8, T9
  - **Blocked By**: T3, T6

  **References**:
  - `docs/다국어지원.md:31-45` - URL 구조
  - SvelteKit docs: route groups pattern

  **Acceptance Criteria**:
  - [ ] `src/routes/(ko)/` 디렉토리 존재
  - [ ] `src/routes/(en)/` 디렉토리 존재
  - [ ] 각 그룹에 `+layout.ts` 존재

  **QA Scenarios**:

  \`\`\`
  Scenario: 라우트 그룹 디렉토리 확인
    Tool: Bash
    Preconditions: T3, T6 완료
    Steps:
      1. ls -la src/routes/ | grep -E '\(ko\)|\(en\)'
    Expected Result: (ko) (en) 두 디렉토리 존재
    Evidence: .sisyphus/evidence/task-7-route-groups-ls.txt
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): create route groups (ko/en)`
  - Files: frontend/main/src/routes/(ko), frontend/main/src/routes/(en)

---

- [ ] 8. **main 앱: 기존 라우트를 라우트 그룹으로 이동**

  **What to do**:
  - `src/routes/`의 파일들을 `src/routes/(ko)/`로 이동
  - 파일 내용을 복사하여 `src/routes/(en)/` 생성 (영어 버전)
  - 기존 파일은 T9의 리다이렉트로 처리
  - 각 페이지에 `$_('key')` 번역 적용

  **Must NOT do**:
  - 원본 파일 삭제 (리다이렉트 소스로 필요)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 파일 이동 및 복제
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: T10
  - **Blocked By**: T7

  **References**:
  - `frontend/main/src/routes/+page.svelte` - 이동할 페이지
  - `frontend/main/src/routes/about/` - 하위 라우트
  - T4에서 생성한 번역 파일

  **Acceptance Criteria**:
  - [ ] 기존 페이지가 `(ko)/`와 `(en)/`에 복제됨
  - [ ] 모든 하드코딩된 문자열이 `$_('key')`로 변경됨
  - [ ] 빌드 성공 (`npm run build`)

  **QA Scenarios**:

  \`\`\`
  Scenario: 라우트 이동 확인
    Tool: Bash
    Preconditions: T7 완료
    Steps:
      1. ls src/routes/\(ko\)/ | head -10
      2. ls src/routes/\(en\)/ | head -10
    Expected Result: 두 디렉토리에 동일한 파일 구조
    Evidence: .sisyphus/evidence/task-8-routes-moved.txt

  Scenario: 번역 함수 사용 확인
    Tool: Bash
    Preconditions: 파일 이동 완료
    Steps:
      1. grep -r "\\$_(" src/routes/\(ko\)/ | head -5
    Expected Result: $_() 함수 호출 존재
    Failure Indicators: 하드코딩된 문자열 그대로
    Evidence: .sisyphus/evidence/task-8-translation-usage.txt
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): move routes to language groups with translations`
  - Files: frontend/main/src/routes/(ko)/*, frontend/main/src/routes/(en)/*

---

- [ ] 9. **main 앱: +redirect.ts 생성 (301 리다이렉트)**

  **What to do**:
  - `src/routes/+redirect.ts` 생성
  - `/main/{path}` → `/main/ko/{path}` 301 리다이렉트
  - SvelteKit의 `redirect` 사용

  **Must NOT do**:
  - `/main/ko/`나 `/main/en/` 경로에 리다이렉트 적용 금지

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 리다이렉트 설정
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: T11
  - **Blocked By**: T7

  **References**:
  - SvelteKit docs: `+redirect.js`
  - `docs/다국어지원.md:78-111` - 리다이렉트 패턴

  **Acceptance Criteria**:
  - [ ] `/main/about` → `/main/ko/about` 301 리다이렉트
  - [ ] `/main/ko/about` → 정상 작동 (리다이렉트 없음)

  **QA Scenarios**:

  \`\`\`
  Scenario: 301 리다이렉트 확인
    Tool: Bash
    Preconditions: 리다이렉트 파일 생성됨
    Steps:
      1. curl -I http://localhost:5173/main/about 2>/dev/null | head -3
    Expected Result: HTTP 301, Location: /main/ko/about
    Evidence: .sisyphus/evidence/task-9-redirect-301.log
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add 301 redirect for old URLs`
  - Files: frontend/main/src/routes/+redirect.ts

---

- [ ] 10. **main 앱: 각 페이지에 번역 적용 (T4의 번역 파일 사용)**

  **What to do**:
  - T4의 번역 파일 키를 기반으로 모든 페이지 업데이트
  - 각 컴포넌트에서 `$_('key')` 사용
  - 존재하지 않는 키는 fallback (현재 텍스트 유지)

  **Must NOT do**:
  - 키 누락 시 경고/에러 발생 금지 (fallback만)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 문자열 치환
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: T11
  - **Blocked By**: T4, T8

  **References**:
  - T4의 번역 파일: `src/lib/i18n/ko.json`, `src/lib/i18n/en.json`
  - 기존 페이지: `src/routes/(ko)/+page.svelte`

  **Acceptance Criteria**:
  - [ ] 모든 페이지 문자열이 번역 함수 사용
  - [ ] ko.json과 en.json의 키 일치

  **QA Scenarios**:

  \`\`\`
  Scenario: 번역 적용 완료 확인
    Tool: Bash
    Preconditions: T4, T8 완료
    Steps:
      1. grep -c "\\$_(" src/routes/\(ko\)/**/*.svelte
      2. grep -c "\\$_(" src/routes/\(en\)/**/*.svelte
    Expected Result: 두 디렉토리 모두 $_() 사용
    Evidence: .sisyphus/evidence/task-10-all-translated.txt
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): apply translations to all pages`
  - Files: frontend/main/src/routes/(ko)/*, frontend/main/src/routes/(en)/*

---

- [ ] 11. **main 앱: SEO hreflang 설정**

  **What to do**:
  - `src/routes/(ko)/+layout.ts`에 hreflang 설정
  - `src/routes/(en)/+layout.ts`에 hreflang 설정
  - sitemap.xml에 언어별 URL 추가

  **Must NOT do**:
  - admin, app 앱에는 SEO 적용 안함 (main만)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 메타 태그 설정
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: T12, T13, T14, T15
  - **Blocked By**: T9, T10

  **References**:
  - `docs/다국어지원.md:299` - SEO는 main만 적용
  - `frontend/app/src/lib/config/seo.ts` - SEO 패턴 참고

  **Acceptance Criteria**:
  - [ ] `<link rel="alternate" hreflang="ko" href="...">` 태그 존재
  - [ ] `<link rel="alternate" hreflang="en" href="...">` 태그 존재
  - [ ] sitemap.xml에 두 언어 URL 포함

  **QA Scenarios**:

  \`\`\`
  Scenario: hreflang 태그 확인
    Tool: Playwright
    Preconditions: main 앱 빌드 완료
    Steps:
      1. Navigate to http://localhost:5173/main/ko/about
      2. Get page HTML
      3. grep hreflang
    Expected Result: hreflang="ko"와 hreflang="en" 태그 존재
    Evidence: .sisyphus/evidence/task-11-hreflang.html
  \`\`\`

  **Commit**: YES
  - Message: `feat(main): add SEO hreflang tags`
  - Files: frontend/main/src/routes/(ko)/+layout.ts, frontend/main/src/routes/(en)/+layout.ts

---

- [ ] 12. **app 앱: T1-T6 반복 (svelte-i18n + vitest 설치 및 설정)**

  **What to do**:
  - T1-T6과 동일한 작업
  - app 앱 (`frontend/app/`)에 적용
  - app의 라우트 구조 반영 (`(auth)/`, `/bbs/`, `/member/` 등)

  **Must NOT do**:
  - main의 번역 파일 복사 금지 (별도 생성)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 반복 작업
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 3
  - **Blocks**: T13
  - **Blocked By**: T11 (main 완료 후)

  **References**:
  - T1-T6의 모든 레퍼런스
  - `frontend/app/src/routes/` - app 라우트 구조

  **Acceptance Criteria**:
  - [ ] `frontend/app/package.json`에 svelte-i18n, vitest 추가
  - [ ] `frontend/app/src/hooks.server.ts` 생성
  - [ ] `frontend/app/src/lib/i18n/` 생성
  - [ ] LanguageDropdown 컴포넌트 생성

  **QA Scenarios**:
  (T1-T6과 동일한 QA 시나리오, app 경로로 변경)

  **Commit**: YES
  - Message: `feat(app): add i18n infrastructure`
  - Files: frontend/app/package.json, frontend/app/src/hooks.server.ts, frontend/app/src/lib/i18n/*

---

- [ ] 13. **app 앱: T7-T10 반복 (라우트 + 번역 적용)**

  **What to do**:
  - T7-T10과 동일한 작업
  - app 앱의 라우트 구조 반영
  - `/app/(ko)/`, `/app/(en)/` 형태로 생성

  **Must NOT do**:
  - Capacitor 관련 파일 수정 금지

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 반복 작업
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 3
  - **Blocks**: T14, T15
  - **Blocked By**: T12

  **References**:
  - T7-T10의 모든 레퍼런스
  - `frontend/app/src/routes/` - app 라우트 구조

  **Acceptance Criteria**:
  - [ ] `/app/ko/bbs/list` → 한국어 UI
  - [ ] `/app/en/bbs/list` → 영어 UI
  - [ ] `/app/bbs/list` → `/app/ko/bbs/list` 301 리다이렉트

  **QA Scenarios**:
  (T7-T10과 동일한 QA 시나리오, app 경로로 변경)

  **Commit**: YES
  - Message: `feat(app): apply i18n to routes`
  - Files: frontend/app/src/routes/(ko)/*, frontend/app/src/routes/(en)/*, frontend/app/src/routes/+redirect.ts

---

- [ ] 14. **admin 앱: T1-T6 반복 (svelte-i18n + vitest 설치 및 설정)**

  **What to do**:
  - T1-T6과 동일한 작업
  - admin 앱 (`frontend/admin/`)에 적용

  **Must NOT do**:
  - admin 앱은 SEO 적용 안함

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 반복 작업
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES (with T12)
  - **Parallel Group**: Wave 3
  - **Blocks**: T15
  - **Blocked By**: T11

  **References**:
  - T1-T6의 모든 레퍼런스
  - `frontend/admin/src/routes/` - admin 라우트 구조

  **Acceptance Criteria**:
  - [ ] `frontend/admin/package.json`에 svelte-i18n, vitest 추가
  - [ ] `frontend/admin/src/hooks.server.ts` 생성
  - [ ] `frontend/admin/src/lib/i18n/` 생성
  - [ ] LanguageDropdown 컴포넌트 생성

  **QA Scenarios**:
  (T1-T6과 동일한 QA 시나리오, admin 경로로 변경)

  **Commit**: YES
  - Message: `feat(admin): add i18n infrastructure`
  - Files: frontend/admin/package.json, frontend/admin/src/hooks.server.ts, frontend/admin/src/lib/i18n/*

---

- [ ] 15. **admin 앱: T7-T10 반복 (라우트 + 번역 적용)**

  **What to do**:
  - T7-T10과 동일한 작업
  - admin 앱의 라우트 구조 반영
  - `/admin/(ko)/`, `/admin/(en)/` 형태로 생성

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: 반복 작업
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 3
  - **Blocks**: F1-F4
  - **Blocked By**: T14

  **References**:
  - T7-T10의 모든 레퍼런스
  - `frontend/admin/src/routes/` - admin 라우트 구조

  **Acceptance Criteria**:
  - [ ] `/admin/ko/` → 한국어 UI
  - [ ] `/admin/en/` → 영어 UI
  - [ ] `/admin/` → `/admin/ko/` 301 리다이렉트

  **QA Scenarios**:
  (T7-T10과 동일한 QA 시나리오, admin 경로로 변경)

  **Commit**: YES
  - Message: `feat(admin): apply i18n to routes`
  - Files: frontend/admin/src/routes/(ko)/*, frontend/admin/src/routes/(en)/*, frontend/admin/src/routes/+redirect.ts

---

## Final Verification Wave

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. Verify each "Must Have" is implemented in code. Check each "Must NOT Have" is absent. Verify evidence files exist in .sisyphus/evidence/.
  Output: `Must Have [15/15] | Must NOT Have [8/8] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Build Verification** — `unspecified-high`
  Run `npm run build` for all three apps (main, app, admin). Verify no build errors. Check that language routes are correctly generated.
  Output: `main build [PASS/FAIL] | app build [PASS/FAIL] | admin build [PASS/FAIL] | VERDICT`

- [ ] F3. **QA 시나리오 실행** — `unspecified-high` (+ `playwright` skill)
  Start from clean state. Execute EVERY QA scenario from EVERY task. Test cross-app integration. Save to `.sisyphus/evidence/final-qa/`.
  Output: `Scenarios [N/N pass] | Integration [N/N] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task: verify 1:1 - everything in spec was built, nothing beyond spec was built. Detect cross-app contamination.
  Output: `Tasks [15/15 compliant] | Contamination [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

- 각 Wave 완료 후 개별 커밋
- Wave 1: `feat(i18n): setup i18n infrastructure for main`
- Wave 2: `feat(i18n): apply translations and routes for main`
- Wave 3: `feat(i18n): extend i18n to app and admin`

---

## Success Criteria

### Verification Commands
```bash
# main 앱 빌드
cd frontend/main && npm run build
# Expected: build/ 디렉토리에 언어별 페이지 생성

# app 앱 빌드
cd frontend/app && npm run build
# Expected: build/ 디렉토리에 언어별 페이지 생성

# admin 앱 빌드
cd frontend/admin && npm run build
# Expected: build/ 디렉토리에 언어별 페이지 생성

# vitest 테스트
cd frontend/main && npm test -- --run
# Expected: All tests pass
```

### Final Checklist
- [ ] 모든 Must Have 항목 구현됨
- [ ] 모든 Must NOT Have 항목 없음
- [ ] 세 앱 모두 빌드 성공
- [ ] vitest 테스트 통과
- [ ] 301 리다이렉트 동작 확인
- [ ] LanguageDropdown 동작 확인
