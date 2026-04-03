# FAPI 개선 제안서

## 1. 개요

현재 FAPI 구버전의 구조를 분석하고, SvelteKit 스타일의 파일 기반 라우팅과 모듈화된 구조로 개선하는 방안을 제안합니다.

**핵심 원칙:**
- **함수만 작성하면 됨**: 클래스 래핑 불필요
- **그누보드 함수 그대로 사용**: 기존 코드 스타일 유지
- **간단하고 직관적**: 파일 경로 = API 경로, 함수명 = HTTP 메서드
- **그누보드 사용자 친화적**: 함수형 프로그래밍에 익숙한 사용자를 위한 구조

**배포 및 확장성:**
- **컨텐츠몰 배포 용이**: `api/` 폴더 하나만 추가, 기존 파일 수정 없음
- **모듈화**: 기능별로 독립적으로 설치/제거 가능 (모듈, 플러그인, 테마, 빌더)
- **AI 개발 지원**: 상세한 AI 컨텍스트 문서 제공 (추가 개발 및 튜닝 용이)

**관련 문서:**
- 배포 가이드: `docs/fapi_deployment_guide.md`
- 모듈 시스템: `docs/fapi_module_system.md`
- AI 컨텍스트: `docs/fapi_ai_context.md`
- 프로젝트 구조: `docs/fapi_project_structure.md` (형상관리, 개발 환경, 배포)
- 서버 설정: `docs/fapi_server_config.md` (Nginx, Apache 설정)
- 에러 응답 규칙: `docs/fapi_error_response.md` (**중요**: 모든 에러는 JSON으로 반환)

## 2. 현재 구조의 문제점

### 2.1 파일 구조 문제

#### 문제점
- `index.php`에 라우팅 로직이 모두 집중되어 있음 (242줄)
- `_common.php`에 공통 함수와 설정이 혼재되어 있음
- 설정값이 코드에 하드코딩되어 있음
- 라우팅이 복잡한 URL 파싱 방식으로 구현됨

#### 영향
- 코드 가독성 저하
- 유지보수 어려움
- 보안 취약점 (설정 노출)
- 확장성 제한

### 2.2 라우팅 시스템 문제

#### 문제점
- URL 파싱 기반의 복잡한 라우팅 로직
- 함수명 규칙 강제 (대문자로 시작)
- 인자 전달 방식 제한 (1개만 가능)
- 파일과 함수의 매핑이 명확하지 않음

#### 영향
- 개발자가 직관적으로 이해하기 어려움
- RESTful API 스타일과 거리가 있음
- 파일 기반 라우팅의 장점을 활용하지 못함

### 2.3 보안 문제

#### 문제점
- JWT 키가 코드에 하드코딩됨
- CORS 설정이 코드에 직접 작성됨
- 환경별 설정 분리가 어려움

#### 영향
- Git에 민감한 정보 노출 위험
- 환경별 배포 시 설정 변경 필요
- 보안 취약점

## 3. 개선 제안

### 3.1 새로운 파일 구조

```
api/
├── .env.example              # 환경 변수 예시 파일
├── .env                      # 실제 환경 변수 (gitignore)
├── index.php                 # 진입점 (최소한의 코드만)
├── config/
│   ├── app.php              # 애플리케이션 설정
│   ├── cors.php             # CORS 설정
│   └── jwt.php              # JWT 설정
├── lib/
│   ├── Router.php           # 라우터 클래스
│   ├── Response.php         # 응답 처리 클래스
│   ├── Auth.php             # 인증 처리 클래스
│   ├── Validator.php        # 입력값 검증 클래스
│   ├── Logger.php           # 로깅 클래스
│   └── Utils.php            # 유틸리티 함수
├── routes/
│   ├── _middleware.php      # 전역 미들웨어 (private)
│   ├── auth/
│   │   ├── login.php        # POST /api/auth/login (public)
│   │   ├── logout.php       # POST /api/auth/logout (public)
│   │   ├── register.php     # POST /api/auth/register (public)
│   │   ├── refresh.php      # POST /api/auth/refresh (public)
│   │   └── _helper.php      # 인증 헬퍼 함수 (private)
│   ├── member/
│   │   ├── profile.php      # GET /api/member/profile (public)
│   │   ├── update.php       # PUT /api/member/update (public)
│   │   ├── leave.php        # DELETE /api/member/leave (public)
│   │   └── _validation.php  # 검증 함수 (private)
│   ├── bbs/
│   │   ├── [bo_table]/
│   │   │   ├── index.php    # GET /api/bbs/{bo_table} (public)
│   │   │   ├── [wr_id].php  # GET /api/bbs/{bo_table}/{wr_id} (public)
│   │   │   ├── write.php    # POST /api/bbs/{bo_table}/write (public)
│   │   │   ├── update.php   # PUT /api/bbs/{bo_table}/update (public)
│   │   │   ├── delete.php   # DELETE /api/bbs/{bo_table}/delete (public)
│   │   │   └── _board_helper.php  # 게시판 헬퍼 (private)
│   │   └── search.php       # GET /api/bbs/search (public)
│   ├── latest/
│   │   └── [bo_table].php   # GET /api/latest/{bo_table} (public)
│   ├── menu/
│   │   └── index.php        # GET /api/menu (public)
│   ├── banner/
│   │   └── [position].php   # GET /api/banner/{position} (public)
│   └── popup/
│       └── index.php        # GET /api/popup (public)
└── vendor/                  # 외부 라이브러리 (선택사항)
```

### 3.2 파일 기반 라우팅 시스템

#### 3.2.1 SvelteKit 스타일 라우팅

**원칙:**
- **Public 파일**: 일반 파일명 사용 (자유로운 명명 가능)
- **Private 파일**: 언더스코어(`_`)로 시작하는 파일만 include 전용 (SvelteKit의 `_` 접두사 개념)
- 파일 경로 = API 경로
- HTTP 메서드 = 파일 내 함수명 (GET, POST, PUT, DELETE, PATCH)
- 동적 경로 = `[변수명]` 형식

**파일 접근 규칙:**
1. **Public 파일** (외부 접근 가능)
   - 일반 파일명: `login.php`, `index.php`, `write.php`, `Login.php` 등 모두 가능
   - `_` 접두사가 없는 모든 파일은 Public으로 간주
   - 라우터가 자동으로 매칭하여 외부에서 호출 가능

2. **Private 파일** (include 전용)
   - 언더스코어로 시작: `_helper.php`, `_middleware.php`, `_validation.php`
   - 라우터가 자동으로 무시하여 외부에서 직접 접근 불가
   - 다른 파일에서 `include` 또는 `require`로만 사용
   - **핵심**: `_` 접두사만으로 충분히 구분되므로 Public 파일은 자유롭게 명명 가능

**예시:**

```php
// routes/bbs/[bo_table]/index.php (Public - _ 접두사 없음)
// GET /api/bbs/free
function GET($bo_table) {
    // 게시판 목록 조회
    // _helper.php를 include하여 사용 가능
    include_once __DIR__ . '/_board_helper.php';
    $list = get_board_list($bo_table);
    Response::success($list);
}

// routes/bbs/[bo_table]/[wr_id].php (Public - _ 접두사 없음)
// GET /api/bbs/free/123
function GET($bo_table, $wr_id) {
    // 게시글 상세 조회
    include_once __DIR__ . '/_board_helper.php';
    $view = get_board_view($bo_table, $wr_id);
    Response::success($view);
}

// routes/bbs/[bo_table]/write.php (Public - _ 접두사 없음)
// POST /api/bbs/free/write
function POST($bo_table) {
    // 게시글 작성
    include_once __DIR__ . '/_board_helper.php';
    $data = json_decode(file_get_contents('php://input'), true);
    validate_write_data($data); // _board_helper.php의 함수
    $wr_id = create_post($bo_table, $data);
    Response::success(['wr_id' => $wr_id]);
}

// routes/bbs/[bo_table]/_board_helper.php (Private - _ 접두사로 시작)
// 외부에서 직접 접근 불가, include 전용
function get_board_list($bo_table) {
    // 게시판 목록 조회 로직
}

function get_board_view($bo_table, $wr_id) {
    // 게시글 상세 조회 로직
}

function validate_write_data($data) {
    // 작성 데이터 검증 로직
}

function create_post($bo_table, $data) {
    // 게시글 작성 로직
}
```

#### 3.2.2 라우터 구현

**간단한 라우터 구현 (api/index.php):**
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
$path = trim(str_replace('/api', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
$pathParts = $path ? explode('/', $path) : [];

// 라우트 찾기 및 실행
$route = find_route(__DIR__ . '/routes', $pathParts, $method);
if ($route) {
    execute_route($route, $pathParts);
} else {
    Response::notFound();
}

/**
 * 라우트 찾기 (간단한 재귀 함수)
 */
function find_route($dir, $pathParts, $method, $index = 0) {
    if (!is_dir($dir)) return null;
    
    $currentPart = $pathParts[$index] ?? null;
    $isLast = ($index + 1) >= count($pathParts);
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '_') === 0) continue;
        
        $filePath = $dir . '/' . $file;
        $routeName = str_replace('.php', '', $file);
        
        if (is_dir($filePath)) {
            // 디렉토리: 정확한 매칭 또는 동적 경로 [변수명]
            if ($currentPart === $file || strpos($file, '[') === 0) {
                $result = find_route($filePath, $pathParts, $method, $index + 1);
                if ($result) return $result;
            }
        } else {
            // 파일: 마지막 경로 요소와 매칭
            if ($isLast && ($currentPart === $routeName || $routeName === 'index')) {
                if (has_method($filePath, $method)) {
                    return ['file' => $filePath, 'depth' => $index];
                }
            }
        }
    }
    
    return null;
}

/**
 * HTTP 메서드 함수 존재 확인
 */
function has_method($filePath, $method) {
    return preg_match('/function\s+' . $method . '\s*\(/', file_get_contents($filePath));
}

/**
 * 라우트 실행
 */
function execute_route($route, $pathParts) {
    require_once $route['file'];
    
    // 동적 파라미터 추출 (depth 이후의 경로 요소들)
    $params = array_slice($pathParts, $route['depth'] + 1);
    
    // 함수 호출
    $method = $_SERVER['REQUEST_METHOD'];
    if (function_exists($method)) {
        call_user_func_array($method, $params);
    } else {
        Response::notFound();
    }
}
```

**특징:**
- **간단함**: 클래스 없이 함수만으로 구현 (~80줄)
- **명확함**: 재귀 함수로 직관적인 구조
- **효율적**: 필요한 파일만 include
- **유지보수 용이**: 복잡한 클래스 구조 없음

**상세 구현은 `docs/fapi_simple_router.md` 참고**

### 3.3 설정 파일 분리

#### 3.3.1 .env 파일 구조

**.env.example:**
```env
# 애플리케이션 설정
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# CORS 설정
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173
CORS_ALLOW_CREDENTIALS=true  # Cookie 전송을 위해 필수

# Cookie 설정
APP_COOKIE_DOMAIN=localhost
APP_HTTPS_ONLY=true  # 프로덕션에서는 true로 설정
APP_COOKIE_SAMESITE=Lax  # Lax, Strict, None 중 선택

# JWT 설정
JWT_ACCESS_TOKEN_KEY=your-access-token-key-here
JWT_REFRESH_TOKEN_KEY=your-refresh-token-key-here
JWT_CRYPT_KEY=your-crypt-key-here
JWT_AUDIENCE=your-site-url
JWT_ACCESS_MTIME=15
JWT_REFRESH_DATE=30

# 데이터베이스 설정 (그누보드 기본 사용)
# DB_HOST=localhost
# DB_NAME=gnuboard5
# DB_USER=root
# DB_PASS=

# API 설정
API_RATE_LIMIT=100
API_RATE_LIMIT_WINDOW=60

# 로깅 설정
LOG_LEVEL=debug
LOG_FILE=api.log
```

#### 3.3.2 설정 로더

**lib/Config.php:**
```php
<?php
class Config {
    private static $config = [];
    
    public static function load() {
        // .env 파일 로드
        $envFile = G5_API_PATH . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // 주석 무시
                
                list($key, $value) = explode('=', $line, 2);
                self::$config[trim($key)] = trim($value);
            }
        }
        
        // config 폴더의 PHP 파일들 로드
        $configDir = G5_API_PATH . '/config';
        if (is_dir($configDir)) {
            $files = glob($configDir . '/*.php');
            foreach ($files as $file) {
                $name = basename($file, '.php');
                self::$config[$name] = require $file;
            }
        }
        
        // CORS 설정 처리: 쉼표로 구분된 Origin 목록을 배열로 변환
        if (isset(self::$config['CORS_ALLOWED_ORIGINS'])) {
            self::$config['cors'] = [
                'allowed_origins' => array_map('trim', explode(',', self::$config['CORS_ALLOWED_ORIGINS'])),
                'allow_credentials' => self::$config['CORS_ALLOW_CREDENTIALS'] ?? true
            ];
        }
    }
    
    public static function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}
```

### 3.4 보안 강화: HTTP Only Cookie

#### 3.4.1 HTTP Only Cookie 적용

**보안 개선 사항:**
- JWT 토큰을 HTTP Only Cookie로 저장하여 XSS 공격 방지
- SameSite 속성으로 CSRF 공격 방지
- Secure 속성으로 HTTPS에서만 전송

**lib/Auth.php (개선된 버전):**
```php
<?php
class Auth {
    private static $cookieName = 'fapi_access_token';
    private static $refreshCookieName = 'fapi_refresh_token';
    
    /**
     * HTTP Only Cookie에서 토큰 가져오기
     */
    private static function getTokenFromCookie() {
        if (isset($_COOKIE[self::$cookieName])) {
            return $_COOKIE[self::$cookieName];
        }
        return null;
    }
    
    /**
     * Authorization 헤더에서 토큰 가져오기 (하위 호환성)
     */
    private static function getTokenFromHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        return null;
    }
    
    /**
     * 토큰 가져오기 (Cookie 우선, Header는 하위 호환)
     */
    private static function getToken() {
        // HTTP Only Cookie에서 먼저 시도
        $token = self::getTokenFromCookie();
        
        // 없으면 Authorization 헤더에서 시도 (하위 호환성)
        if (!$token) {
            $token = self::getTokenFromHeader();
        }
        
        return $token;
    }
    
    /**
     * Access Token을 HTTP Only Cookie로 설정
     */
    public static function setAccessTokenCookie($token, $expiresIn = 900) {
        $options = [
            'expires' => time() + $expiresIn,
            'path' => '/',
            'domain' => Config::get('app.cookie_domain', ''),
            'secure' => Config::get('app.https_only', true), // HTTPS에서만 전송
            'httponly' => true, // JavaScript 접근 불가
            'samesite' => Config::get('app.cookie_samesite', 'Lax') // CSRF 방지
        ];
        
        setcookie(self::$cookieName, $token, $options);
    }
    
    /**
     * Refresh Token을 HTTP Only Cookie로 설정
     */
    public static function setRefreshTokenCookie($token, $expiresIn = 2592000) {
        $options = [
            'expires' => time() + $expiresIn,
            'path' => '/',
            'domain' => Config::get('app.cookie_domain', ''),
            'secure' => Config::get('app.https_only', true),
            'httponly' => true,
            'samesite' => Config::get('app.cookie_samesite', 'Lax')
        ];
        
        setcookie(self::$refreshCookieName, $token, $options);
    }
    
    /**
     * 쿠키 삭제 (로그아웃)
     */
    public static function clearCookies() {
        $options = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => Config::get('app.cookie_domain', ''),
            'secure' => Config::get('app.https_only', true),
            'httponly' => true,
            'samesite' => Config::get('app.cookie_samesite', 'Lax')
        ];
        
        setcookie(self::$cookieName, '', $options);
        setcookie(self::$refreshCookieName, '', $options);
    }
    
    public static function getMember() {
        $token = self::getToken();
        
        if (!$token) {
            return self::getGuestMember();
        }
        
        try {
            $jwt = new JWT("HS256");
            $payload = $jwt->decode($token, Config::get('jwt.access_token_key'), ['HS256']);
            
            if ($payload->exp < time()) {
                // 토큰 만료 시 Refresh Token으로 갱신 시도
                return self::refreshAccessToken();
            }
            
            return get_member($payload->mb_id);
        } catch (Exception $e) {
            return self::getGuestMember();
        }
    }
    
    /**
     * Refresh Token으로 Access Token 갱신
     */
    private static function refreshAccessToken() {
        $refreshToken = $_COOKIE[self::$refreshCookieName] ?? null;
        
        if (!$refreshToken) {
            http_response_code(203);
            exit;
        }
        
        try {
            $jwt = new JWT("HS256");
            $payload = $jwt->decode($refreshToken, Config::get('jwt.refresh_token_key'), ['HS256']);
            
            // Refresh Token이 유효하면 새 Access Token 발급
            $member = get_member($payload->mb_id);
            if ($member) {
                $newAccessToken = self::generateAccessToken($member);
                self::setAccessTokenCookie($newAccessToken, Config::get('jwt.access_mtime', 15) * 60);
                
                return $member;
            }
        } catch (Exception $e) {
            // Refresh Token도 만료됨
        }
        
        // Refresh Token도 유효하지 않으면 쿠키 삭제
        self::clearCookies();
        http_response_code(203);
        exit;
    }
    
    /**
     * Access Token 생성
     */
    private static function generateAccessToken($member) {
        $jwt = new JWT("HS256");
        $issuedAt = time();
        $expirationTime = $issuedAt + (Config::get('jwt.access_mtime', 15) * 60);
        
        $payload = [
            "mb_id" => $member['mb_id'],
            "iss" => Config::get('app.url'),
            "aud" => Config::get('jwt.audience'),
            "iat" => $issuedAt,
            "exp" => $expirationTime
        ];
        
        return $jwt->encode($payload, Config::get('jwt.access_token_key'), "HS256");
    }
    
    private static function getGuestMember() {
        return [
            'mb_id' => '',
            'mb_level' => 1,
            'mb_name' => '',
            'mb_point' => 0,
            // ... 기타 필드
        ];
    }
    
    public static function requireAuth() {
        $member = self::getMember();
        if (!$member['mb_id']) {
            Response::unauthorized();
        }
        return $member;
    }
    
    public static function requireLevel($level) {
        $member = self::requireAuth();
        if ($member['mb_level'] < $level) {
            Response::forbidden();
        }
        return $member;
    }
}
```

#### 3.4.2 로그인 응답 개선

**routes/auth/login.php:**
```php
<?php
function POST() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 로그인 검증 로직
    $member = validateLogin($data['mb_id'], $data['mb_password']);
    
    // Access Token 생성
    $accessToken = Auth::generateAccessToken($member);
    $refreshToken = Auth::generateRefreshToken($member);
    
    // HTTP Only Cookie로 설정
    Auth::setAccessTokenCookie($accessToken, Config::get('jwt.access_mtime', 15) * 60);
    Auth::setRefreshTokenCookie($refreshToken, Config::get('jwt.refresh_date', 30) * 86400);
    
    // 응답에는 민감한 정보 제외
    Response::success([
        'mb' => [
            'mb_id' => $member['mb_id'],
            'mb_name' => $member['mb_name'],
            'mb_nick' => $member['mb_nick'],
            'mb_level' => $member['mb_level'],
            'mb_point' => $member['mb_point']
        ]
    ], '로그인 성공');
}
```

#### 3.4.3 로그아웃 응답 개선

**routes/auth/logout.php:**
```php
<?php
function POST() {
    // 쿠키 삭제
    Auth::clearCookies();
    
    // Refresh Token DB에서도 삭제
    $member = Auth::getMember();
    if ($member['mb_id']) {
        delete_refresh_token($member['mb_id']);
    }
    
    Response::success(null, '로그아웃 성공');
}
```

#### 3.4.4 CORS 설정 개선

**config/cors.php:**
```php
<?php
return [
    // .env에서 쉼표로 구분된 도메인 목록을 배열로 변환
    'allowed_origins' => array_map('trim', explode(',', Config::get('cors.allowed_origins', ''))),
    'allow_credentials' => true, // Cookie 전송을 위해 필수
    'allowed_headers' => ['Content-Type', 'X-Requested-With'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'max_age' => 86400
];
```

**routes/_middleware.php (개선):**
```php
<?php
function cors() {
    // 허용된 Origin 목록 가져오기
    $allowedOrigins = Config::get('cors.allowed_origins', []);
    
    // 요청한 Origin 가져오기
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // 개발 환경이거나 Origin이 허용 목록에 있는지 확인
    $isAllowed = false;
    
    if (Config::get('app.env') === 'development') {
        // 개발 환경: 모든 Origin 허용 또는 특정 Origin 허용
        if (empty($allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }
    } else {
        // 프로덕션 환경: 허용 목록에 있는 Origin만 허용
        if (!empty($origin) && in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }
    }
    
    // 허용된 Origin이면 CORS 헤더 설정
    if ($isAllowed) {
        // 요청한 Origin을 그대로 반환 (여러 도메인 중 해당하는 도메인 반환)
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true"); // Cookie 전송을 위해 필수
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 86400");
    }
    // 허용되지 않은 Origin이면 CORS 헤더를 설정하지 않음 (브라우저가 차단)
    
    // OPTIONS 요청 (Preflight) 처리
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

cors();
logRequest();
```

**동작 방식:**
1. `.env` 파일에서 `CORS_ALLOWED_ORIGINS`를 쉼표로 구분하여 설정
2. 요청 시 `HTTP_ORIGIN` 헤더에서 Origin 추출
3. 허용 목록에 해당 Origin이 있는지 확인
4. 있으면 해당 Origin을 `Access-Control-Allow-Origin`에 설정
5. 없으면 CORS 헤더를 설정하지 않아 브라우저가 요청 차단

**예시:**
```env
# .env 파일
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,https://app.example.com,https://admin.example.com
```

```php
// 요청: Origin: http://localhost:3000
// → 허용 목록에 있음 → Access-Control-Allow-Origin: http://localhost:3000

// 요청: Origin: https://app.example.com
// → 허용 목록에 있음 → Access-Control-Allow-Origin: https://app.example.com

// 요청: Origin: https://evil.com
// → 허용 목록에 없음 → CORS 헤더 미설정 → 브라우저가 차단
```

### 3.5 공통 함수 분리

#### 3.5.1 응답 처리 클래스

**lib/Response.php:**
```php
<?php
class Response {
    private static $beginTime;
    
    public static function init() {
        self::$beginTime = microtime(true);
    }
    
    public static function json($data, $code = '00000', $msg = '', $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = [
            'code' => $code,
            'data' => $data,
            'time' => round(microtime(true) - self::$beginTime, 4)
        ];
        
        if ($msg) {
            $response['msg'] = $msg;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function success($data, $msg = '') {
        self::json($data, '00000', $msg);
    }
    
    public static function error($msg, $code = '00001', $httpCode = 200) {
        self::json(null, $code, $msg, $httpCode);
    }
    
    public static function unauthorized($msg = '로그인이 필요합니다.') {
        self::json(null, '00002', $msg, 401);
    }
    
    public static function forbidden($msg = '권한이 없습니다.') {
        self::json(null, '00003', $msg, 403);
    }
    
    public static function notFound($msg = '페이지를 찾을 수 없습니다.') {
        self::json(null, '00001', $msg, 404);
    }
    
    /**
     * 토큰 만료 응답
     * 프론트엔드에서 이 코드를 받으면 자동으로 Refresh Token으로 갱신 시도
     */
    public static function tokenExpired($msg = '토큰이 만료되었습니다.') {
        self::json(null, '00004', $msg, 401);
    }
}
```

**⚠️ 중요: 모든 에러 응답은 JSON 형식으로 반환해야 합니다.**

HTTP 상태 코드만 반환하거나 HTML 에러 페이지를 반환하면, 프론트엔드에서 JWT 토큰 갱신 로직이 실행되지 않을 수 있습니다. 자세한 내용은 `docs/fapi_error_response.md`를 참고하세요.

**에러 코드:**
- `00000`: 성공
- `00001`: 일반 에러
- `00002`: 인증 필요 (401)
- `00003`: 권한 없음 (403)
- `00004`: 토큰 만료 (401) - 프론트엔드에서 자동 갱신 시도
```

#### 3.4.2 인증 처리 클래스

**lib/Auth.php:**
```php
<?php
class Auth {
    public static function getMember() {
        $token = self::getToken();
        
        if (!$token) {
            return self::getGuestMember();
        }
        
        try {
            $jwt = new JWT("HS256");
            $payload = $jwt->decode($token, Config::get('jwt.access_token_key'), ['HS256']);
            
            if ($payload->exp < time()) {
                http_response_code(203);
                exit;
            }
            
            return get_member($payload->mb_id);
        } catch (Exception $e) {
            return self::getGuestMember();
        }
    }
    
    private static function getToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        return null;
    }
    
    private static function getGuestMember() {
        return [
            'mb_id' => '',
            'mb_level' => 1,
            'mb_name' => '',
            'mb_point' => 0,
            // ... 기타 필드
        ];
    }
    
    public static function requireAuth() {
        $member = self::getMember();
        if (!$member['mb_id']) {
            Response::unauthorized();
        }
        return $member;
    }
    
    public static function requireLevel($level) {
        $member = self::requireAuth();
        if ($member['mb_level'] < $level) {
            Response::forbidden();
        }
        return $member;
    }
}
```

#### 3.4.3 보안 최적화: 그누보드 보안 기능 활용

#### 3.4.3.1 보안 기능 중복 제거

**문제점:**
- 기존 FAPI에서 DB 바인딩 및 보안 강화 기능이 그누보드 자체 보안 기능과 중복
- `$_POST`, `$_GET`은 이미 그누보드에서 자동 이스케이프 처리됨
- `sql_query()` 함수가 이미 UNION, information_schema 차단 포함
- JSON 입력값만 추가 보안 처리 필요

**개선 방안:**
- 그누보드의 기존 보안 기능 최대한 활용
- 중복 보안 검사 제거
- JSON 입력값에 대해서만 추가 보안 처리

**lib/Request.php:**
```php
<?php
class Request {
    /**
     * JSON 입력값을 안전하게 파싱하고 이스케이프 처리
     * 그누보드의 자동 이스케이프는 $_POST, $_GET에만 적용되므로
     * JSON 입력값은 수동으로 처리 필요
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('잘못된 JSON 형식입니다.', '00001');
        }
        
        // JSON 입력값은 자동 이스케이프 대상이 아니므로 수동 처리
        return self::escapeArray($data);
    }
    
    /**
     * 배열의 모든 값을 이스케이프 처리 (그누보드 함수 활용)
     */
    private static function escapeArray($data) {
        if (!is_array($data)) {
            // 그누보드의 sql_escape_string 함수 활용
            return sql_escape_string($data);
        }
        
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::escapeArray($value);
            } else {
                // 그누보드 함수 활용 (중복 보안 처리 제거)
                $result[$key] = sql_escape_string($value);
            }
        }
        
        return $result;
    }
    
    /**
     * GET/POST 값 가져오기 (그누보드는 이미 이스케이프 처리됨)
     * 추가 이스케이프 불필요
     */
    public static function get($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    public static function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
}
```

**lib/Database.php:**
```php
<?php
class Database {
    /**
     * 그누보드의 sql_query 함수 직접 활용
     * 추가 보안 처리는 불필요 (그누보드가 이미 처리)
     */
    public static function query($sql) {
        return sql_query($sql);
    }
    
    /**
     * 그누보드의 sql_fetch 함수 활용
     */
    public static function fetch($sql) {
        return sql_fetch($sql);
    }
    
    /**
     * JSON 입력값을 안전하게 처리하여 SQL에 사용
     * 숫자는 타입 캐스팅, 문자열은 그누보드 함수로 이스케이프
     */
    public static function safeValue($value) {
        if (is_numeric($value)) {
            return (int)$value; // 숫자는 타입 캐스팅만
        }
        
        // 그누보드 함수 활용 (중복 처리 제거)
        return "'" . sql_escape_string($value) . "'";
    }
}
```

**사용 예시:**
```php
// ❌ 기존 방식 (중복 보안 처리)
$mb_id = sql_real_escape_string($requestData['mb_id']); // 불필요
$mb = get_member($mb_id); // 이미 이스케이프 처리됨

// ✅ 개선된 방식 (그누보드 함수 활용)
$data = Request::getJsonInput(); // JSON만 처리
$mb = get_member($data['mb_id']); // 그누보드 함수 활용
```

**보안 체크리스트:**
- ✅ `$_POST`, `$_GET` 값: 그누보드가 자동 처리 (추가 처리 불필요)
- ✅ JSON 입력값: `Request::getJsonInput()` 사용 (수동 처리 필요)
- ✅ 그누보드 함수 사용: 추가 이스케이프 불필요
- ✅ 직접 쿼리: 그누보드의 `sql_query()` 사용 (보안 포함)

### 3.4.4 입력값 검증 클래스

**lib/Validator.php:**
```php
<?php
class Validator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $rule);
            
            foreach ($ruleArray as $r) {
                if ($r === 'required' && empty($value)) {
                    $errors[$field][] = "{$field}는 필수입니다.";
                } elseif ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "{$field}는 유효한 이메일이 아닙니다.";
                } elseif (strpos($r, 'min:') === 0) {
                    $min = (int)substr($r, 4);
                    if (strlen($value) < $min) {
                        $errors[$field][] = "{$field}는 최소 {$min}자 이상이어야 합니다.";
                    }
                }
                // ... 기타 검증 규칙
            }
        }
        
        if (!empty($errors)) {
            Response::error('입력값 검증 실패', '00001');
        }
        
        return true;
    }
}
```

### 3.5 미들웨어 시스템

#### 3.5.1 전역 미들웨어

**routes/_middleware.php (Private 파일):**
```php
<?php
// 모든 요청에 대해 실행되는 미들웨어
// 언더스코어로 시작하므로 외부에서 직접 접근 불가

// CORS 처리
function cors() {
    // 허용된 Origin 목록 가져오기
    $allowedOrigins = Config::get('cors.allowed_origins', []);
    
    // 요청한 Origin 가져오기
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // 개발 환경이거나 Origin이 허용 목록에 있는지 확인
    $isAllowed = false;
    
    if (Config::get('app.env') === 'development') {
        // 개발 환경: 모든 Origin 허용 또는 특정 Origin 허용
        if (empty($allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }
    } else {
        // 프로덕션 환경: 허용 목록에 있는 Origin만 허용
        if (!empty($origin) && in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }
    }
    
    // 허용된 Origin이면 CORS 헤더 설정
    if ($isAllowed) {
        // 요청한 Origin을 그대로 반환 (여러 도메인 중 해당하는 도메인 반환)
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 86400");
    }
    // 허용되지 않은 Origin이면 CORS 헤더를 설정하지 않음 (브라우저가 차단)
    
    // OPTIONS 요청 (Preflight) 처리
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// 요청 로깅
function logRequest() {
    Logger::info('Request', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'path' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
}

// 실행
cors();
logRequest();
```

**주의사항:**
- `_middleware.php`는 언더스코어로 시작하므로 외부에서 `/api/_middleware`로 접근 불가
- 라우터가 자동으로 로드하여 실행

#### 3.5.2 라우트별 미들웨어

**routes/bbs/[bo_table]/write.php (Public 파일 - _ 접두사 없음):**
```php
<?php
// Public 파일이므로 외부에서 POST /api/bbs/{bo_table}/write 로 접근 가능
// 파일명이 자유롭게 사용 가능 (write.php, Write.php 모두 가능)

// Private 헬퍼 파일 include
include_once __DIR__ . '/_board_helper.php';

// 인증 미들웨어
$member = Auth::requireAuth();

function POST($bo_table) {
    global $member;
    
    // Private 헬퍼 함수 사용
    $board = get_board_db($bo_table);
    
    // 권한 체크 미들웨어
    if ($member['mb_level'] < $board['bo_write_level']) {
        Response::forbidden('글을 쓸 권한이 없습니다.');
    }
    
    // 입력값 검증 (Private 헬퍼 함수)
    $data = json_decode(file_get_contents('php://input'), true);
    validate_write_data($data); // _board_helper.php의 함수
    
    // 게시글 작성 로직 (Private 헬퍼 함수)
    $wr_id = create_post($bo_table, $data);
    
    Response::success(['wr_id' => $wr_id], '게시글이 작성되었습니다.');
}
```

**routes/bbs/[bo_table]/_board_helper.php (Private 파일):**
```php
<?php
// Private 파일이므로 외부에서 직접 접근 불가
// Write.php에서만 include하여 사용

function get_board_db($bo_table) {
    // 게시판 정보 조회 로직
    return get_board_db($bo_table, true);
}

function validate_write_data($data) {
    // 입력값 검증 로직
    Validator::validate($data, [
        'subject' => 'required|min:2',
        'content' => 'required|min:10'
    ]);
}

function create_post($bo_table, $data) {
    // 게시글 작성 로직
    // ...
    return $wr_id;
}
```

**파일 접근 예시:**
- ✅ `POST /api/bbs/free/write` → `write.php` 실행 (Public - _ 접두사 없음)
- ❌ `GET /api/bbs/free/_board_helper` → 404 에러 (Private 파일은 접근 불가 - _ 접두사)
- ✅ `write.php` 내부에서 `include_once '_board_helper.php'` → 정상 작동
- ✅ 파일명 자유롭게 사용 가능: `write.php`, `Write.php`, `writePost.php` 모두 Public으로 인식

### 3.6 개선된 index.php

**index.php:**
```php
<?php
// 그누보드 공통 파일 로드
include_once '../common.php';

// FAPI 초기화
define('G5_API_PATH', __DIR__);
define('G5_API_URL', G5_URL . '/api');

// 오토로더
spl_autoload_register(function ($class) {
    $file = G5_API_PATH . '/lib/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 설정 로드
Config::load();

// 응답 초기화
Response::init();

// 전역 미들웨어 실행
if (file_exists(G5_API_PATH . '/routes/_middleware.php')) {
    require_once G5_API_PATH . '/routes/_middleware.php';
}

// 라우터 실행
$router = new Router();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path); // /api 제거

$router->dispatch($method, $path);
```

## 4. 마이그레이션 계획

### 4.1 단계별 마이그레이션

#### Phase 1: 기반 구조 구축 (1일)
1. 새로운 폴더 구조 생성
2. Config, Response, Auth 클래스 구현
3. 기본 Router 구현
4. .env 파일 설정

#### Phase 2: 핵심 기능 마이그레이션 (2일)
1. 회원 인증 API 마이그레이션
2. 게시판 기본 API 마이그레이션
3. 파일 기반 라우팅 테스트

#### Phase 3: 부가 기능 마이그레이션 (1일)
1. 최신글, 메뉴, 배너 API 추가
2. 미들웨어 시스템 구축
3. 에러 처리 개선

#### Phase 4: 테스트 및 문서화 (1일)
1. API 테스트
2. 문서 작성
3. 성능 최적화

### 4.2 호환성 유지

기존 API와의 호환성을 유지하기 위해:
- 기존 URL 형식도 지원하는 옵션 제공
- 점진적 마이그레이션 가능하도록 구조 설계

## 5. 기대 효과

### 5.1 개발자 경험 개선

1. **직관적인 파일 구조**: 파일 경로 = API 경로
2. **명확한 코드 분리**: 각 기능별로 파일 분리
3. **쉬운 확장**: 새 파일 추가만으로 API 추가
4. **표준화된 응답**: 일관된 응답 형식
5. **명확한 접근 제어**: 파일명만으로 Public/Private 구분
   - 언더스코어(`_`) 시작 = Private (include 전용)
   - 그 외 모든 파일 = Public (외부 접근 가능)
   - 파일명 자유롭게 사용 가능 (`login.php`, `Login.php` 모두 가능)
6. **SvelteKit 패턴 활용**: `_` 접두사만으로 충분히 구분 가능하여 간단하고 직관적

### 5.2 보안 강화

1. **설정 파일 분리**: 민감한 정보 Git 제외
2. **환경별 설정**: 개발/운영 환경 분리
3. **입력값 검증**: 자동화된 검증 시스템
   - 그누보드 보안 기능 최대한 활용
   - JSON 입력값에 대해서만 추가 보안 처리
   - 중복 보안 검사 제거로 성능 향상
4. **로깅 시스템**: API 호출 추적
5. **파일 접근 제어**: 파일명 규칙으로 자동 차단
6. **보안 최적화**: 
   - 그누보드 기존 보안 기능 활용
   - 중복 보안 처리 제거
   - JSON 입력값 안전 처리 (상세 내용은 `docs/fapi_security_optimization.md` 참고)
   - Private 파일(`_` 시작)은 라우터가 자동으로 무시
   - Public 파일(`_` 접두사 없음)은 모두 외부 접근 가능
   - 파일명 자유롭게 사용 가능하여 개발 편의성 향상
   - 헬퍼 함수나 내부 로직이 외부에 노출되지 않음

### 5.3 유지보수성 향상

1. **모듈화**: 각 기능이 독립적으로 관리 가능
2. **테스트 용이**: 단위 테스트 작성 가능
3. **문서화**: 파일 구조 자체가 문서
4. **확장성**: 플러그인 시스템 구축 가능

## 6. 참고 사항

### 6.1 SvelteKit + Go 패턴 참고

#### SvelteKit 패턴
- 파일 기반 라우팅: `routes/` 폴더의 파일 구조가 URL 구조를 결정
- 동적 라우팅: `[변수명]` 형식으로 동적 경로 처리
- Private 파일: `_` 접두사로 시작하는 파일은 라우팅에서 제외
- Public 파일: `_` 접두사가 없는 모든 파일은 라우팅에 포함

#### FAPI 적용
- **Public 파일**: `_` 접두사가 없는 모든 파일 (`login.php`, `index.php`, `write.php` 등 자유롭게 명명 가능)
- **Private 파일**: 언더스코어로 시작 (`_helper.php`, `_middleware.php`)
- **동적 경로**: `[변수명].php` 형식
- **미들웨어**: `_middleware.php`로 전역 처리
- **헬퍼 함수**: `_helper.php`로 공통 로직 분리
- **핵심**: `_` 접두사만으로 충분히 구분되므로 Public 파일은 자유롭게 명명 가능

### 6.2 추가 개선 사항

1. **API 버전 관리**: `/api/v1/`, `/api/v2/` 형식 지원
2. **Rate Limiting**: 요청 제한 기능 추가
3. **캐싱 시스템**: Redis 등을 활용한 캐싱
4. **API 문서 자동 생성**: Swagger/OpenAPI 자동 생성
   - 파일 기반 라우팅 구조를 활용한 자동 문서화
   - 주석 기반 메타데이터 추출 (`@fapi` 주석)
   - Swagger UI 연동 가능
   - 구현 난이도: 중간 (상세 내용은 `docs/fapi_documentation.md` 참고)
5. **테스트 프레임워크**: PHPUnit 통합

## 7. 파일 명명 규칙 요약

### 7.1 Public 파일 (외부 접근 가능)

**규칙:**
- 파일명이 **언더스코어(`_`)로 시작하지 않으면** 모두 Public
- 파일명은 자유롭게 사용 가능 (`login.php`, `Login.php`, `writePost.php` 등)
- HTTP 메서드 함수 (`GET`, `POST`, `PUT`, `DELETE`)를 포함해야 함

**예시:**
```
login.php      → POST /api/auth/login
index.php      → GET /api/bbs/{bo_table}
write.php      → POST /api/bbs/{bo_table}/write
Write.php      → POST /api/bbs/{bo_table}/write (대문자도 가능)
[wr_id].php    → GET /api/bbs/{bo_table}/{wr_id} (동적 경로)
```

### 7.2 Private 파일 (include 전용)

**규칙:**
- 파일명이 **언더스코어(`_`)로 시작**해야 함
- 라우터가 자동으로 무시하여 외부 접근 불가

**예시:**
```
_helper.php        → include 전용 헬퍼 함수
_middleware.php    → 전역 미들웨어
_validation.php    → 검증 함수
_board_helper.php  → 게시판 관련 헬퍼
```

### 7.3 파일 구조 예시

```
routes/
├── auth/
│   ├── Login.php        ✅ Public: POST /api/auth/login
│   ├── Logout.php       ✅ Public: POST /api/auth/logout
│   └── _helper.php      ❌ Private: include 전용
├── bbs/
│   └── [bo_table]/
│       ├── Index.php           ✅ Public: GET /api/bbs/{bo_table}
│       ├── [wr_id].php         ✅ Public: GET /api/bbs/{bo_table}/{wr_id}
│       ├── Write.php           ✅ Public: POST /api/bbs/{bo_table}/write
│       ├── Update.php           ✅ Public: PUT /api/bbs/{bo_table}/update
│       ├── Delete.php          ✅ Public: DELETE /api/bbs/{bo_table}/delete
│       └── _board_helper.php   ❌ Private: include 전용
└── _middleware.php      ❌ Private: 전역 미들웨어
```

## 8. 결론

이 개선안을 통해 FAPI는 다음과 같은 이점을 얻을 수 있습니다:

1. **개발자 친화적**: 
   - SvelteKit처럼 직관적인 파일 기반 라우팅
   - `_` 접두사만으로 명확한 접근 제어
   - 파일명만으로 Public/Private 구분 가능
   - Public 파일은 자유롭게 명명 가능하여 개발 편의성 향상

2. **보안 강화**: 
   - 설정 파일 분리 및 환경 변수 관리
   - 파일명 규칙으로 자동 접근 제어
   - Private 파일이 외부에 노출되지 않음
   - HTTP Only Cookie로 XSS 공격 방지
   - SameSite 속성으로 CSRF 공격 방지
   - 자동 토큰 갱신으로 사용자 경험 향상

3. **유지보수성 향상**: 
   - 모듈화된 구조로 코드 관리 용이
   - 헬퍼 함수를 Private 파일로 분리하여 재사용성 향상
   - 명확한 파일 구조로 코드 탐색 용이

4. **확장성**: 
   - 새로운 기능 추가가 쉬움
   - 파일 추가만으로 API 확장 가능
   - 검증된 패턴 적용으로 일관성 유지

이 구조는 그누보드5의 레거시 코드와도 잘 통합되면서, Go와 SvelteKit의 검증된 패턴을 적용하여 최신 웹 개발 트렌드를 따를 수 있도록 설계되었습니다.

