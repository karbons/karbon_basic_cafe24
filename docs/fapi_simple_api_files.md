# FAPI 간단한 API 파일 작성 가이드

## 1. 개요

그누보드 사용자들이 쉽게 이해하고 사용할 수 있도록, **함수만 작성하면 자동으로 라우팅**되는 간단한 구조입니다. 클래스 래핑 없이 순수 함수만으로 API를 구현할 수 있습니다.

## 2. 핵심 원칙

### 2.1 함수만 작성하면 됨

- 클래스 불필요
- 복잡한 설정 불필요
- 파일 하나 = API 엔드포인트 하나
- 함수명 = HTTP 메서드 (GET, POST, PUT, DELETE)

### 2.2 그누보드 스타일 유지

- 그누보드의 함수형 프로그래밍 스타일 유지
- 기존 그누보드 함수 그대로 사용 가능
- 추가 학습 곡선 최소화

## 3. API 파일 작성 예시

### 3.1 가장 간단한 예시

**routes/auth/login.php:**
```php
<?php
// POST /api/auth/login
function POST() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $mb = get_member($data['mb_id']);
    
    if (!$mb || !login_password_check($mb, $data['mb_password'], $mb['mb_password'])) {
        json_return(null, 200, '00001', '로그인 실패');
    }
    
    // 토큰 생성
    $token = create_jwt_token($mb['mb_id']);
    
    json_return(['token' => $token], 200, '00000', '로그인 성공');
}
```

**이게 전부입니다!** 
- 함수 하나만 작성
- 그누보드 함수 그대로 사용
- 복잡한 클래스나 설정 없음

### 3.2 동적 경로 예시

**routes/bbs/[bo_table]/index.php:**
```php
<?php
// GET /api/bbs/{bo_table}
function GET($bo_table) {
    $board = get_board_db($bo_table, true);
    
    if (!$board) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }
    
    // 게시판 목록
    $list = get_board_list($bo_table);
    
    json_return(['board' => $board, 'list' => $list], 200, '00000');
}
```

**파라미터는 자동으로 전달됩니다!**
- `$bo_table`은 URL에서 자동 추출
- 함수 인자로 자동 전달

### 3.3 여러 파라미터 예시

**routes/bbs/[bo_table]/[wr_id].php:**
```php
<?php
// GET /api/bbs/{bo_table}/{wr_id}
function GET($bo_table, $wr_id) {
    $board = get_board_db($bo_table, true);
    $write = get_write($g5['write_prefix'] . $bo_table, $wr_id);
    
    if (!$write) {
        json_return(null, 404, '00001', '글을 찾을 수 없습니다.');
    }
    
    json_return(['write' => $write], 200, '00000');
}
```

**여러 파라미터도 자동 전달!**

### 3.4 POST 요청 예시

**routes/bbs/[bo_table]/write.php:**
```php
<?php
// POST /api/bbs/{bo_table}/write
function POST($bo_table) {
    global $member;
    
    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    
    // 게시판 확인
    $board = get_board_db($bo_table, true);
    if (!$board) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }
    
    // 입력값 받기
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 그누보드 함수 사용
    $wr_id = insert_write($bo_table, $data);
    
    json_return(['wr_id' => $wr_id], 200, '00000', '작성 완료');
}
```

**그누보드 함수 그대로 사용 가능!**

## 4. 라우터 구현 (간단 버전)

**api/index.php:**
```php
<?php
if (!defined("_GNUBOARD_")) exit;

include_once './_common.php';

// 미들웨어 (선택사항)
if (file_exists(__DIR__ . '/routes/_middleware.php')) {
    include_once __DIR__ . '/routes/_middleware.php';
}

// 경로 파싱
$method = $_SERVER['REQUEST_METHOD'];
$path = trim(str_replace('/api', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$pathParts = $path ? explode('/', $path) : [];

// 라우트 파일 찾기
$route = find_route_file(__DIR__ . '/routes', $pathParts, $method);

if ($route) {
    // 파일 include
    include_once $route['file'];
    
    // 함수 호출
    if (function_exists($method)) {
        call_user_func_array($method, $route['params']);
    } else {
        json_return(null, 404, '00001', '함수를 찾을 수 없습니다.');
    }
} else {
    json_return(null, 404, '00001', '페이지를 찾을 수 없습니다.');
}

/**
 * 라우트 파일 찾기 (재귀 함수)
 */
function find_route_file($dir, $pathParts, $method, $index = 0) {
    if (!is_dir($dir)) return null;
    
    $currentPart = $pathParts[$index] ?? null;
    $isLast = ($index + 1) >= count($pathParts);
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '_') === 0) continue;
        
        $filePath = $dir . '/' . $file;
        $routeName = str_replace('.php', '', $file);
        
        if (is_dir($filePath)) {
            // 디렉토리: 정확한 매칭 또는 [변수명] 형식
            if ($currentPart === $file || strpos($file, '[') === 0) {
                $result = find_route_file($filePath, $pathParts, $method, $index + 1);
                if ($result) {
                    // 동적 경로 파라미터 추가
                    if (strpos($file, '[') === 0) {
                        $result['params'] = array_merge([$currentPart], $result['params'] ?? []);
                    }
                    return $result;
                }
            }
        } else {
            // 파일: 마지막 경로 요소와 매칭
            if ($isLast) {
                if ($currentPart === $routeName || ($routeName === 'index' && empty($currentPart))) {
                    // HTTP 메서드 함수 존재 확인
                    if (has_method_function($filePath, $method)) {
                        return [
                            'file' => $filePath,
                            'params' => []
                        ];
                    }
                }
            }
        }
    }
    
    return null;
}

/**
 * HTTP 메서드 함수 존재 확인
 */
function has_method_function($filePath, $method) {
    $content = file_get_contents($filePath);
    return preg_match('/function\s+' . $method . '\s*\(/', $content);
}
```

## 5. 기존 그누보드 코드와의 호환성

### 5.1 기존 함수 그대로 사용

```php
<?php
// routes/member/profile.php
function GET() {
    global $member;
    
    // 그누보드 함수 그대로 사용
    $profile = get_member($member['mb_id']);
    
    json_return(['profile' => $profile], 200, '00000');
}
```

### 5.2 기존 코드 재사용

```php
<?php
// routes/bbs/[bo_table]/list.php
function GET($bo_table) {
    // 기존 list.php의 로직 재사용
    include_once G5_BBS_PATH . '/list.php';
    
    // 또는 기존 함수 호출
    $list = get_board_list_data($bo_table);
    
    json_return(['list' => $list], 200, '00000');
}
```

## 6. 실제 사용 예시 비교

### 6.1 기존 방식 (복잡)

```php
<?php
class BoardController {
    private $request;
    private $response;
    
    public function __construct($request, $response) {
        $this->request = $request;
        $this->response = $response;
    }
    
    public function getList($bo_table) {
        // ...
    }
}
```

### 6.2 새로운 방식 (간단)

```php
<?php
// routes/bbs/[bo_table]/list.php
function GET($bo_table) {
    $list = get_board_list($bo_table);
    json_return(['list' => $list], 200, '00000');
}
```

**훨씬 간단합니다!**

## 7. 추가 기능 (선택사항)

### 7.1 헬퍼 함수 사용

**routes/bbs/[bo_table]/_helper.php (Private 파일):**
```php
<?php
// Private 파일이므로 외부 접근 불가
// 다른 파일에서 include하여 사용

function get_board_list_data($bo_table) {
    // 공통 로직
    return sql_fetch("select * from ...");
}
```

**routes/bbs/[bo_table]/list.php:**
```php
<?php
include_once __DIR__ . '/_helper.php';

function GET($bo_table) {
    $list = get_board_list_data($bo_table); // 헬퍼 함수 사용
    json_return(['list' => $list], 200, '00000');
}
```

### 7.2 미들웨어 사용

**routes/_middleware.php (Private 파일):**
```php
<?php
// 모든 요청에 실행되는 미들웨어

// CORS 처리
header_origin();

// JWT 인증 (선택적)
if (needs_auth()) {
    $member = get_jwt_member();
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
}
```

## 8. 개발자 경험

### 8.1 새 API 추가하기

1. **파일 생성**: `routes/새폴더/새파일.php`
2. **함수 작성**: `function GET()` 또는 `function POST()`
3. **끝!**

**예시:**
```php
<?php
// routes/shop/products.php 생성
function GET() {
    $products = get_products();
    json_return(['products' => $products], 200, '00000');
}
```

**접근:** `GET /api/shop/products`

### 8.2 디버깅

- 파일 경로 = API 경로
- 함수명 = HTTP 메서드
- 직관적이고 이해하기 쉬움

## 9. 기존 코드와의 차이점

### 9.1 기존 방식 (index.php)

```php
// api/member/login.php
function Index() {
    // 로직
}
```

**URL:** `/api/member/login/Index`

### 9.2 새로운 방식

```php
// routes/member/login.php
function POST() {
    // 로직
}
```

**URL:** `POST /api/member/login`

**개선점:**
- 함수명이 HTTP 메서드와 일치하여 직관적
- 파일 경로가 API 경로와 일치
- Index 함수 불필요

## 10. 결론

### 10.1 핵심 장점

1. **간단함**: 함수 하나만 작성하면 됨
2. **직관적**: 파일 경로 = API 경로
3. **호환성**: 그누보드 함수 그대로 사용
4. **학습 곡선 최소**: 클래스 불필요

### 10.2 사용자 친화적

- 그누보드 사용자들이 익숙한 함수형 프로그래밍
- 복잡한 클래스 구조 불필요
- 파일 하나에 함수 하나만 작성
- 그누보드 함수 그대로 사용 가능

이 구조로 그누보드 사용자들이 쉽게 API를 추가하고 관리할 수 있습니다!

