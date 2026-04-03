# FAPI 간단한 파일 기반 라우터 구현

## 1. 개요

파일 기반 라우팅을 최대한 간단하게 구현한 버전입니다. 복잡한 클래스 구조 대신 간단한 함수들로 구성하여 유지보수가 쉽고 이해하기 쉽습니다.

## 2. 핵심 구조

### 2.1 최소한의 파일 구조

```
api/
├── index.php          # 진입점 (라우터 포함)
├── routes/            # 라우트 파일들
│   ├── auth/
│   │   └── login.php
│   └── bbs/
│       └── [bo_table]/
│           └── index.php
└── lib/               # 공통 함수
    ├── Response.php
    └── Auth.php
```

### 2.2 간단한 라우터 구현

**api/index.php:**
```php
<?php
if (!defined("_GNUBOARD_")) exit;

// 공통 함수 로드
require_once __DIR__ . '/lib/Response.php';
require_once __DIR__ . '/lib/Auth.php';

// 미들웨어 실행
if (file_exists(__DIR__ . '/routes/_middleware.php')) {
    require_once __DIR__ . '/routes/_middleware.php';
}

// 라우팅 실행
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path); // /api 제거
$path = trim($path, '/'); // 앞뒤 슬래시 제거

// 라우트 찾기 및 실행
$route = find_route($path, $method);
if ($route) {
    execute_route($route, $path);
} else {
    Response::notFound();
}

/**
 * 라우트 찾기
 */
function find_route($path, $method) {
    $routesDir = __DIR__ . '/routes';
    $pathParts = $path ? explode('/', $path) : [];
    
    return scan_route_file($routesDir, $pathParts, $method, '');
}

/**
 * 디렉토리 스캔하여 라우트 파일 찾기
 */
function scan_route_file($dir, $pathParts, $method, $currentPath) {
    if (!is_dir($dir)) {
        return null;
    }
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (strpos($file, '_') === 0) continue; // Private 파일 제외
        
        $filePath = $dir . '/' . $file;
        $newPath = $currentPath ? $currentPath . '/' . $file : $file;
        
        // 파일명에서 .php 제거
        $routeName = str_replace('.php', '', $file);
        
        if (is_dir($filePath)) {
            // 디렉토리인 경우 재귀적으로 스캔
            if (!empty($pathParts) && ($pathParts[0] === $file || strpos($file, '[') === 0)) {
                $remainingParts = array_slice($pathParts, 1);
                $result = scan_route_file($filePath, $remainingParts, $method, $newPath);
                if ($result) {
                    return $result;
                }
            }
        } else {
            // 파일인 경우
            if (empty($pathParts)) {
                // 경로가 끝났고 파일이 index.php인 경우
                if ($routeName === 'index') {
                    return check_method($filePath, $method, $newPath);
                }
            } else {
                // 경로 매칭 확인
                if ($pathParts[0] === $routeName || strpos($routeName, '[') === 0) {
                    $remainingParts = array_slice($pathParts, 1);
                    
                    if (empty($remainingParts)) {
                        // 마지막 경로 요소
                        return check_method($filePath, $method, $newPath);
                    } else {
                        // 아직 경로가 남아있음 (파일이 아닌 경우)
                        continue;
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
function check_method($filePath, $method, $routePath) {
    $content = file_get_contents($filePath);
    
    // 함수명 확인 (GET, POST, PUT, DELETE)
    if (preg_match('/function\s+' . $method . '\s*\(/', $content)) {
        return [
            'file' => $filePath,
            'path' => $routePath,
            'method' => $method
        ];
    }
    
    return null;
}

/**
 * 라우트 실행
 */
function execute_route($route, $requestPath) {
    $filePath = $route['file'];
    
    // 동적 경로 파라미터 추출
    $params = extract_params($route['path'], $requestPath);
    
    // 파일 include
    require_once $filePath;
    
    // 함수 호출
    $method = $route['method'];
    if (function_exists($method)) {
        call_user_func_array($method, $params);
    } else {
        Response::notFound();
    }
}

/**
 * 동적 경로 파라미터 추출
 */
function extract_params($routePath, $requestPath) {
    $routeParts = explode('/', trim($routePath, '/'));
    $requestParts = explode('/', trim($requestPath, '/'));
    
    $params = [];
    
    foreach ($routeParts as $index => $routePart) {
        // [변수명] 형식인 경우
        if (preg_match('/\[([^\]]+)\]/', $routePart, $matches)) {
            $paramName = $matches[1];
            $params[] = $requestParts[$index] ?? null;
        }
    }
    
    return $params;
}
```

## 3. 사용 예시

### 3.1 간단한 라우트

**routes/auth/login.php:**
```php
<?php
function POST() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 로그인 로직
    $member = get_member($data['mb_id']);
    
    if (!$member || !login_password_check($member, $data['mb_password'], $member['mb_password'])) {
        Response::error('로그인 실패', '00001');
    }
    
    // 토큰 생성 및 반환
    Response::success(['member' => $member], '로그인 성공');
}
```

**접근:** `POST /api/auth/login`

### 3.2 동적 경로 라우트

**routes/bbs/[bo_table]/index.php:**
```php
<?php
function GET($bo_table) {
    $board = get_board_db($bo_table, true);
    
    if (!$board) {
        Response::notFound('게시판을 찾을 수 없습니다.');
    }
    
    // 게시판 목록 조회
    $list = get_board_list($bo_table);
    
    Response::success(['board' => $board, 'list' => $list]);
}
```

**접근:** `GET /api/bbs/free` → `$bo_table = 'free'`

### 3.3 중첩 동적 경로

**routes/bbs/[bo_table]/[wr_id].php:**
```php
<?php
function GET($bo_table, $wr_id) {
    $board = get_board_db($bo_table, true);
    $write = get_write($g5['write_prefix'] . $bo_table, $wr_id);
    
    if (!$write) {
        Response::notFound('글을 찾을 수 없습니다.');
    }
    
    Response::success(['write' => $write]);
}
```

**접근:** `GET /api/bbs/free/123` → `$bo_table = 'free'`, `$wr_id = '123'`

## 4. 더 간단한 버전 (최소 구현)

**api/index.php (초간단 버전):**
```php
<?php
if (!defined("_GNUBOARD_")) exit;

require_once __DIR__ . '/lib/Response.php';

// 미들웨어
if (file_exists(__DIR__ . '/routes/_middleware.php')) {
    require_once __DIR__ . '/routes/_middleware.php';
}

// 경로 파싱
$method = $_SERVER['REQUEST_METHOD'];
$path = trim(str_replace('/api', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$pathParts = $path ? explode('/', $path) : [];

// 라우트 파일 찾기
$routeFile = find_route_file(__DIR__ . '/routes', $pathParts);

if ($routeFile && file_exists($routeFile)) {
    require_once $routeFile;
    
    // 함수 호출
    if (function_exists($method)) {
        // 동적 파라미터 전달
        $params = array_slice($pathParts, count(explode('/', str_replace(__DIR__ . '/routes/', '', $routeFile))) - 1);
        call_user_func_array($method, $params);
    } else {
        Response::notFound();
    }
} else {
    Response::notFound();
}

function find_route_file($dir, $pathParts, $index = 0) {
    if (!is_dir($dir)) return null;
    if ($index >= count($pathParts)) {
        // index.php 찾기
        $indexFile = $dir . '/index.php';
        return file_exists($indexFile) ? $indexFile : null;
    }
    
    $currentPart = $pathParts[$index];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '_') === 0) continue;
        
        $filePath = $dir . '/' . $file;
        
        // 정확한 매칭
        if ($file === $currentPart . '.php') {
            return $filePath;
        }
        
        // 동적 경로 [변수명].php
        if (preg_match('/^\[.+\]\.php$/', $file)) {
            return find_route_file($filePath, $pathParts, $index + 1) ?: $filePath;
        }
        
        // 디렉토리 매칭
        if (is_dir($filePath)) {
            if ($file === $currentPart || strpos($file, '[') === 0) {
                $result = find_route_file($filePath, $pathParts, $index + 1);
                if ($result) return $result;
            }
        }
    }
    
    return null;
}
```

## 5. 성능 최적화 버전

**캐싱을 활용한 버전:**
```php
<?php
if (!defined("_GNUBOARD_")) exit;

require_once __DIR__ . '/lib/Response.php';

// 라우트 캐시 (실제로는 파일 캐시 사용 권장)
static $routeCache = [];

// 미들웨어
if (file_exists(__DIR__ . '/routes/_middleware.php')) {
    require_once __DIR__ . '/routes/_middleware.php';
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(str_replace('/api', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

// 캐시 확인
$cacheKey = $method . ':' . $path;
if (isset($routeCache[$cacheKey])) {
    execute_route($routeCache[$cacheKey], $path);
    exit;
}

// 라우트 찾기
$route = find_route(__DIR__ . '/routes', explode('/', $path), $method);

if ($route) {
    $routeCache[$cacheKey] = $route; // 캐시 저장
    execute_route($route, $path);
} else {
    Response::notFound();
}

function find_route($dir, $parts, $method, $depth = 0) {
    if (!is_dir($dir)) return null;
    
    $currentPart = $parts[$depth] ?? null;
    $isLast = ($depth + 1) >= count($parts);
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '_') === 0) continue;
        
        $filePath = $dir . '/' . $file;
        $routeName = str_replace('.php', '', $file);
        
        if (is_dir($filePath)) {
            // 디렉토리 매칭
            if ($currentPart === $file || strpos($file, '[') === 0) {
                $result = find_route($filePath, $parts, $method, $depth + 1);
                if ($result) return $result;
            }
        } else {
            // 파일 매칭
            if ($isLast && ($currentPart === $routeName || $routeName === 'index')) {
                if (has_method($filePath, $method)) {
                    return ['file' => $filePath, 'depth' => $depth];
                }
            }
        }
    }
    
    return null;
}

function has_method($file, $method) {
    return preg_match('/function\s+' . $method . '\s*\(/', file_get_contents($file));
}

function execute_route($route, $path) {
    require_once $route['file'];
    $parts = explode('/', trim($path, '/'));
    $params = array_slice($parts, $route['depth'] + 1);
    
    $method = $_SERVER['REQUEST_METHOD'];
    if (function_exists($method)) {
        call_user_func_array($method, $params);
    }
}
```

## 6. 비교

### 6.1 코드 라인 수

- **복잡한 클래스 버전**: ~300줄
- **간단한 함수 버전**: ~150줄
- **초간단 버전**: ~80줄
- **최적화 버전**: ~100줄

### 6.2 추천

**개발 단계:**
- 초간단 버전 사용 (빠른 개발)

**프로덕션:**
- 간단한 함수 버전 + 캐싱 (성능과 가독성 균형)

## 7. 결론

파일 기반 라우팅은 생각보다 간단하게 구현할 수 있습니다. 핵심은:

1. **파일 스캔**: `scandir()`로 디렉토리 탐색
2. **경로 매칭**: 파일 경로와 URL 경로 비교
3. **함수 호출**: `function_exists()` + `call_user_func()`

복잡한 클래스 구조 없이도 충분히 동작하며, 유지보수도 쉽습니다.

