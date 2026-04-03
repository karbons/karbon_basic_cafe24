# Store Caching Strategy (깜박임 방지 전략)

페이지 이동 시 Header의 사이트명, 메뉴, 로그인 상태가 깜박이는 문제를 방지하기 위한 localStorage 캐싱 전략입니다.

## 문제점

기존 방식에서는 다음과 같은 문제가 있었습니다:

1. 페이지 이동마다 `onMount`에서 API 호출
2. API 응답 전까지 기본값(빈 데이터) 표시
3. API 응답 후 데이터로 교체 → **깜박임 발생**

## 해결 방법

### 1. Store 초기화 시 localStorage에서 캐시 로드

```typescript
// src/lib/store/config.ts
import { browser } from '$app/environment';

function loadCachedConfig(): SiteConfig | null {
    if (!browser) return null;
    try {
        const cached = localStorage.getItem('app_config');
        if (cached) return JSON.parse(cached);
    } catch (e) {}
    return null;
}

// 캐시된 데이터로 초기화 → 즉시 표시
export const configStore = writable<SiteConfig | null>(loadCachedConfig());
```

### 2. 데이터 저장 시 localStorage에도 저장

```typescript
export function setConfig(config: SiteConfig) {
    configStore.set(config);
    if (browser) {
        localStorage.setItem('app_config', JSON.stringify(config));
    }
}
```

### 3. Layout에서 한 번만 API 호출

```typescript
// src/routes/+layout.svelte
onMount(async () => {
    const [member, config, menus] = await Promise.allSettled([
        getProfile(),
        getConfig(),
        apiGet("/menu"),
    ]);
    
    if (member.status === "fulfilled") setMember(member.value);
    if (config.status === "fulfilled") setConfig(config.value);
    if (menus.status === "fulfilled") setMenus(menus.value.menus);
});
```

## 적용된 Store 목록

| Store | 저장 키 | 설명 |
|-------|---------|------|
| `configStore` | `app_config` | 사이트 설정 (사이트명 등) |
| `memberStore` | `app_member` | 로그인된 회원 정보 |
| `menuStore` | `app_menus` | 메뉴 목록 |

## 데이터 흐름

```
페이지 로드
    ↓
localStorage에서 캐시 로드 (즉시)
    ↓
캐시된 데이터로 UI 렌더링 (깜박임 없음)
    ↓
백그라운드에서 API 호출
    ↓
새 데이터로 Store 업데이트 + localStorage 저장
    ↓
변경된 경우에만 UI 갱신
```

## 주의사항

1. **로그아웃 시 캐시 삭제**: `clearMember()` 호출 시 localStorage도 함께 삭제
2. **민감한 정보 주의**: localStorage는 클라이언트에 저장되므로 민감한 정보는 저장하지 않음
3. **캐시 무효화**: 필요 시 `localStorage.removeItem()` 또는 `clear*()` 함수 호출

## 파일 구조

```
src/lib/store/
├── config.ts    # 사이트 설정 (localStorage 캐싱)
├── member.ts    # 회원 정보 (localStorage 캐싱)
├── menu.ts      # 메뉴 목록 (localStorage 캐싱)
└── index.ts     # 모든 store export
```
