# FAPI AI 개발 컨텍스트 문서

## 1. 개요

이 문서는 AI(ChatGPT, Cursor 등)가 FAPI를 이해하고 개발/튜닝할 수 있도록 필요한 모든 컨텍스트를 제공합니다.

## 2. 아키텍처 개요

### 2.1 핵심 원칙

- **함수형 프로그래밍**: 클래스 래핑 없이 순수 함수만 사용
- **파일 기반 라우팅**: 파일 경로 = API 경로
- **그누보드 호환**: 그누보드 함수 그대로 사용
- **모듈화**: 기능별로 독립적으로 설치/제거 가능

### 2.2 파일 구조

```
api/
├── index.php              # 라우터 (단일 파일, ~100줄)
├── _common.php            # 공통 함수
├── routes/                # API 라우트 파일들
│   ├── _middleware.php    # 전역 미들웨어 (Private)
│   ├── auth/
│   │   └── login.php      # POST /api/auth/login
│   ├── member/
│   │   └── profile.php    # GET /api/member/profile
│   └── bbs/
│       └── [bo_table]/    # 동적 경로
│           ├── index.php  # GET /api/bbs/{bo_table}
│           └── [wr_id].php # GET /api/bbs/{bo_table}/{wr_id}
├── lib/                   # 라이브러리
│   ├── Response.php       # 응답 처리
│   ├── Auth.php           # 인증 처리
│   └── jwt.php            # JWT 라이브러리
└── modules/               # 모듈 (선택적)
    ├── member/
    ├── bbs/
    └── shop/
```

## 3. 라우팅 시스템

### 3.1 기본 동작

1. **URL 파싱**: `/api/auth/login` → `['auth', 'login']`
2. **파일 찾기**: `routes/auth/login.php` 스캔
3. **함수 확인**: `function POST()` 존재 확인
4. **함수 호출**: `POST()` 실행

### 3.2 동적 경로

- `[bo_table]` → `$bo_table` 파라미터로 전달
- `[wr_id]` → `$wr_id` 파라미터로 전달
- 여러 파라미터는 순서대로 전달

**예시:**
```
GET /api/bbs/free/123
→ routes/bbs/[bo_table]/[wr_id].php
→ function GET($bo_table, $wr_id)
→ $bo_table = 'free', $wr_id = '123'
```

### 3.3 Private 파일

- `_` 접두사로 시작하는 파일은 라우팅에서 제외
- `_middleware.php`, `_helper.php` 등

## 4. API 파일 작성 규칙

### 4.1 기본 구조

```php
<?php
// routes/example.php
function GET() {
    // 로직
    json_return($data, 200, '00000', '성공');
}

function POST() {
    // 로직
    json_return($data, 200, '00000', '성공');
}
```

### 4.2 그누보드 함수 사용

```php
<?php
function GET($bo_table) {
    // 그누보드 함수 그대로 사용
    $board = get_board_db($bo_table, true);
    $member = get_member($mb_id);
    $write = get_write($write_table, $wr_id);
    
    json_return(['board' => $board], 200, '00000');
}
```

### 4.3 입력값 처리

```php
<?php
function POST() {
    // JSON 입력
    $data = json_decode(file_get_contents('php://input'), true);
    
    // GET/POST는 그누보드가 자동 이스케이프 처리
    $id = $_GET['id'];
    
    // JSON은 수동 이스케이프 필요
    $mb_id = sql_escape_string($data['mb_id']);
}
```

### 4.4 응답 형식

```php
<?php
// 성공
json_return($data, 200, '00000', '성공 메시지');

// 에러
json_return(null, 200, '00001', '에러 메시지');

// 로그인 필요
json_return(null, 401, '00002', '로그인이 필요합니다.');

// 권한 없음
json_return(null, 403, '00003', '권한이 없습니다.');
```

## 5. 보안 규칙

### 5.1 그누보드 보안 기능 활용

- `$_POST`, `$_GET`: 자동 이스케이프 처리됨 (추가 처리 불필요)
- `sql_query()`: UNION, information_schema 자동 차단
- JSON 입력값: 수동 이스케이프 필요 (`sql_escape_string()`)

### 5.2 인증

```php
<?php
function GET() {
    global $member;
    
    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    
    // 권한 체크
    if ($member['mb_level'] < 5) {
        json_return(null, 403, '00003', '권한이 없습니다.');
    }
}
```

### 5.3 HTTP Only Cookie

- JWT 토큰은 HTTP Only Cookie로 저장
- JavaScript 접근 불가 (XSS 방지)
- SameSite 속성으로 CSRF 방지

## 6. 데이터베이스

### 6.1 그누보드 함수 사용

```php
<?php
// 그누보드 함수 사용 (권장)
$member = get_member($mb_id);
$board = get_board_db($bo_table, true);
$write = get_write($write_table, $wr_id);

// 직접 쿼리 (필요시만)
$sql = "select * from table where id = '$id'";
$result = sql_query($sql);
$row = sql_fetch($result);
```

### 6.2 주의사항

- JSON 입력값은 수동 이스케이프 필요
- 숫자는 타입 캐스팅 권장: `(int)$id`
- 그누보드 함수 사용 시 추가 이스케이프 불필요

## 7. 에러 코드

### 7.1 표준 에러 코드

- `00000`: 성공
- `00001`: 일반 에러
- `00002`: 로그인 필요
- `00003`: 권한 없음
- `00004`: 권한 없음 (이전 페이지로)
- `00005`: 본인인증 필요
- `00006`: 성인인증 필요

## 8. 모듈 개발

### 8.1 모듈 구조

```
modules/example/
├── module.json            # 모듈 메타데이터
├── install.php            # 설치 스크립트
├── uninstall.php          # 제거 스크립트
├── routes/                # 라우트 파일들
│   └── example.php
└── lib/                   # 라이브러리
    └── ExampleHelper.php
```

### 8.2 모듈 작성 예시

**modules/example/routes/example.php:**
```php
<?php
function GET() {
    json_return(['message' => 'Hello'], 200, '00000');
}
```

**modules/example/install.php:**
```php
<?php
function install_example_module() {
    // 라우트 파일 복사
    copy(__DIR__ . '/routes/example.php', 
         dirname(dirname(__DIR__)) . '/routes/example.php');
    
    echo "모듈 설치 완료!\n";
}
```

## 9. 플러그인 개발

### 9.1 플러그인 구조

```
plugins/example/
├── plugin.php             # 플러그인 메타데이터
├── routes/                # 라우트 파일들
│   └── example.php
└── lib/                   # 라이브러리
    └── ExampleLib.php
```

### 9.2 플러그인 작성 예시

**plugins/example/plugin.php:**
```php
<?php
return [
    'name' => 'Example Plugin',
    'version' => '1.0.0',
    'install' => function() {
        copy_routes('example');
        echo "플러그인 설치 완료!\n";
    },
    'uninstall' => function() {
        remove_routes('example');
        echo "플러그인 제거 완료!\n";
    }
];
```

## 10. 테마 개발

### 10.1 SvelteKit 테마

**themes/sveltekit/src/routes/+page.svelte:**
```svelte
<script>
  import { onMount } from 'svelte';
  
  let posts = [];
  
  onMount(async () => {
    const res = await fetch('/api/bbs/free');
    const data = await res.json();
    posts = data.data.list;
  });
</script>

{#each posts as post}
  <div>{post.wr_subject}</div>
{/each}
```

### 10.2 API 호출 패턴

```typescript
// lib/api.ts
export async function apiRequest(url: string, options: RequestInit = {}) {
    const response = await fetch(`/api${url}`, {
        ...options,
        credentials: 'include', // Cookie 전송
        headers: {
            'Content-Type': 'application/json',
            ...options.headers,
        },
    });
    
    return response.json();
}
```

## 11. 개발 가이드

### 11.1 새 API 추가하기

1. **파일 생성**: `routes/새폴더/새파일.php`
2. **함수 작성**: `function GET()` 또는 `function POST()`
3. **로직 구현**: 그누보드 함수 사용
4. **응답 반환**: `json_return()`

**예시:**
```php
<?php
// routes/shop/products.php
function GET() {
    $products = get_products(); // 그누보드 함수
    json_return(['products' => $products], 200, '00000');
}
```

### 11.2 디버깅

- 파일 경로 = API 경로
- 함수명 = HTTP 메서드
- `json_return()`으로 응답 확인

### 11.3 테스트

```bash
# GET 요청
curl http://localhost/api/auth/login

# POST 요청
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"mb_id":"test","mb_password":"test"}'
```

## 12. 자주 사용하는 패턴

### 12.1 로그인 체크

```php
<?php
function POST() {
    global $member;
    
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    
    // 로직
}
```

### 12.2 권한 체크

```php
<?php
function POST($bo_table) {
    global $member;
    
    $board = get_board_db($bo_table, true);
    
    if ($member['mb_level'] < $board['bo_write_level']) {
        json_return(null, 403, '00003', '권한이 없습니다.');
    }
    
    // 로직
}
```

### 12.3 입력값 검증

```php
<?php
function POST() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['mb_id'])) {
        json_return(null, 200, '00001', '아이디를 입력해주세요.');
    }
    
    // 로직
}
```

## 13. AI 프롬프트 예시

### 13.1 새 API 추가 요청

```
FAPI에 새로운 API를 추가해주세요.

요구사항:
- 경로: GET /api/shop/products
- 기능: 상품 목록 조회
- 그누보드 함수 사용: get_products()
- 응답: 상품 배열 반환

파일 위치: routes/shop/products.php
```

### 13.2 기존 API 수정 요청

```
FAPI의 로그인 API를 수정해주세요.

현재: routes/auth/login.php
요구사항:
- Refresh Token도 함께 반환
- HTTP Only Cookie 설정 추가
- 응답 형식 유지
```

### 13.3 모듈 개발 요청

```
FAPI에 새로운 모듈을 만들어주세요.

모듈명: Shop Module
기능:
- 상품 목록 조회
- 상품 상세 조회
- 장바구니 추가

파일 구조:
- modules/shop/routes/products.php
- modules/shop/routes/cart.php
- modules/shop/install.php
```

## 14. 참고 자료

### 14.1 그누보드 함수 목록

- `get_member($mb_id)`: 회원 정보 조회
- `get_board_db($bo_table, $is_cache)`: 게시판 정보 조회
- `get_write($write_table, $wr_id)`: 게시글 조회
- `sql_query($sql)`: SQL 쿼리 실행
- `sql_fetch($sql)`: 단일 행 조회
- `sql_fetch_array($result)`: 배열로 조회

### 14.2 FAPI 함수 목록

- `json_return($data, $http_code, $error_code, $msg)`: JSON 응답
- `get_jwt_member()`: JWT에서 회원 정보 추출
- `set_refresh_token()`: Refresh Token 저장
- `get_refresh_token()`: Refresh Token 조회

## 15. 결론

이 문서를 AI에게 제공하면:
1. FAPI 구조를 이해할 수 있음
2. 새 API를 추가할 수 있음
3. 기존 API를 수정할 수 있음
4. 모듈/플러그인을 개발할 수 있음
5. 그누보드 함수를 올바르게 사용할 수 있음

**AI 프롬프트에 이 문서를 포함시키면 효과적인 개발이 가능합니다!**

