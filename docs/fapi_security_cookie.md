# FAPI HTTP Only Cookie 보안 가이드

## 1. 개요

FAPI는 보안 강화를 위해 JWT 토큰을 HTTP Only Cookie로 저장하는 방식을 채택합니다. 이는 XSS(Cross-Site Scripting) 공격을 방지하고, CSRF(Cross-Site Request Forgery) 공격을 완화하는 효과적인 보안 조치입니다.

## 2. HTTP Only Cookie의 장점

### 2.1 XSS 공격 방지

**문제점 (기존 방식):**
```javascript
// localStorage에 토큰 저장 시
localStorage.setItem('token', accessToken);
// XSS 공격으로 토큰 탈취 가능
const stolenToken = localStorage.getItem('token');
```

**해결책 (HTTP Only Cookie):**
```php
// PHP에서 HTTP Only Cookie로 설정
setcookie('fapi_access_token', $token, [
    'httponly' => true  // JavaScript 접근 불가
]);
// JavaScript로는 접근 불가능
// document.cookie에서도 보이지 않음
```

### 2.2 자동 토큰 전송

- 브라우저가 자동으로 쿠키를 전송
- 프론트엔드에서 토큰 관리 불필요
- 개발 편의성 향상

### 2.3 CSRF 공격 완화

- SameSite 속성으로 CSRF 공격 방지
- Lax: GET 요청만 외부에서 가능
- Strict: 완전 차단
- None: 모든 요청 허용 (Secure 필수)

## 3. 구현 방법

### 3.1 Cookie 설정

**lib/Auth.php:**
```php
<?php
class Auth {
    private static $cookieName = 'fapi_access_token';
    private static $refreshCookieName = 'fapi_refresh_token';
    
    /**
     * Access Token을 HTTP Only Cookie로 설정
     */
    public static function setAccessTokenCookie($token, $expiresIn = 900) {
        $options = [
            'expires' => time() + $expiresIn,
            'path' => '/',
            'domain' => Config::get('app.cookie_domain', ''),
            'secure' => Config::get('app.https_only', true),
            'httponly' => true,
            'samesite' => Config::get('app.cookie_samesite', 'Lax')
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
    
    /**
     * HTTP Only Cookie에서 토큰 가져오기
     */
    private static function getTokenFromCookie() {
        return $_COOKIE[self::$cookieName] ?? null;
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
}
```

### 3.2 로그인 구현

**routes/auth/login.php:**
```php
<?php
function POST() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 입력값 검증
    Validator::validate($data, [
        'mb_id' => 'required',
        'mb_password' => 'required'
    ]);
    
    // 로그인 검증
    $member = validateLogin($data['mb_id'], $data['mb_password']);
    
    // JWT 토큰 생성
    $jwt = new JWT("HS256");
    
    // Access Token 생성
    $issuedAt = time();
    $accessExpires = $issuedAt + (Config::get('jwt.access_mtime', 15) * 60);
    $accessPayload = [
        "mb_id" => $member['mb_id'],
        "iss" => Config::get('app.url'),
        "aud" => Config::get('jwt.audience'),
        "iat" => $issuedAt,
        "exp" => $accessExpires
    ];
    $accessToken = $jwt->encode($accessPayload, Config::get('jwt.access_token_key'), "HS256");
    
    // Refresh Token 생성
    $uuid = gen_uuid_v4();
    $refreshExpires = $issuedAt + (Config::get('jwt.refresh_date', 30) * 86400);
    $refreshPayload = [
        "id" => $uuid,
        "iat" => $issuedAt,
        "exp" => $refreshExpires
    ];
    $refreshToken = $jwt->encode($refreshPayload, Config::get('jwt.refresh_token_key'), "HS256");
    
    // Refresh Token DB 저장
    set_refresh_token($refreshToken, $member['mb_id'], $uuid, $_SERVER['HTTP_USER_AGENT']);
    
    // HTTP Only Cookie로 설정
    Auth::setAccessTokenCookie($accessToken, Config::get('jwt.access_mtime', 15) * 60);
    Auth::setRefreshTokenCookie($refreshToken, Config::get('jwt.refresh_date', 30) * 86400);
    
    // 응답에는 민감한 정보 제외 (토큰은 쿠키로만 전송)
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

### 3.3 로그아웃 구현

**routes/auth/logout.php:**
```php
<?php
function POST() {
    // 현재 회원 정보 가져오기
    $member = Auth::getMember();
    
    // Refresh Token DB에서 삭제
    if ($member['mb_id']) {
        delete_refresh_token($member['mb_id']);
    }
    
    // 쿠키 삭제
    Auth::clearCookies();
    
    Response::success(null, '로그아웃 성공');
}
```

### 3.4 토큰 갱신 구현

**routes/auth/refresh.php:**
```php
<?php
function POST() {
    $refreshToken = $_COOKIE[Auth::getRefreshCookieName()] ?? null;
    
    if (!$refreshToken) {
        Response::unauthorized('Refresh Token이 없습니다.');
    }
    
    try {
        $jwt = new JWT("HS256");
        $payload = $jwt->decode($refreshToken, Config::get('jwt.refresh_token_key'), ['HS256']);
        
        // Refresh Token이 유효한지 DB에서 확인
        $tokenData = get_refresh_token($payload->id);
        if (!$tokenData || $tokenData['mb_id'] !== $payload->mb_id) {
            Response::unauthorized('유효하지 않은 Refresh Token입니다.');
        }
        
        // 회원 정보 가져오기
        $member = get_member($tokenData['mb_id']);
        if (!$member) {
            Response::unauthorized('회원 정보를 찾을 수 없습니다.');
        }
        
        // 새 Access Token 생성
        $issuedAt = time();
        $accessExpires = $issuedAt + (Config::get('jwt.access_mtime', 15) * 60);
        $accessPayload = [
            "mb_id" => $member['mb_id'],
            "iss" => Config::get('app.url'),
            "aud" => Config::get('jwt.audience'),
            "iat" => $issuedAt,
            "exp" => $accessExpires
        ];
        $newAccessToken = $jwt->encode($accessPayload, Config::get('jwt.access_token_key'), "HS256");
        
        // 새 Access Token을 HTTP Only Cookie로 설정
        Auth::setAccessTokenCookie($newAccessToken, Config::get('jwt.access_mtime', 15) * 60);
        
        Response::success([
            'mb' => [
                'mb_id' => $member['mb_id'],
                'mb_name' => $member['mb_name'],
                'mb_nick' => $member['mb_nick'],
                'mb_level' => $member['mb_level'],
                'mb_point' => $member['mb_point']
            ]
        ], '토큰이 갱신되었습니다.');
        
    } catch (Exception $e) {
        // Refresh Token도 만료됨
        Auth::clearCookies();
        Response::unauthorized('Refresh Token이 만료되었습니다. 다시 로그인해주세요.');
    }
}
```

### 3.5 자동 토큰 갱신 (미들웨어)

**lib/Auth.php에 추가:**
```php
public static function getMember() {
    $token = self::getToken();
    
    if (!$token) {
        return self::getGuestMember();
    }
    
    try {
        $jwt = new JWT("HS256");
        $payload = $jwt->decode($token, Config::get('jwt.access_token_key'), ['HS256']);
        
        if ($payload->exp < time()) {
            // 토큰 만료 시 Refresh Token으로 자동 갱신 시도
            return self::refreshAccessToken();
        }
        
        return get_member($payload->mb_id);
    } catch (Exception $e) {
        // 토큰이 유효하지 않으면 Refresh Token으로 갱신 시도
        return self::refreshAccessToken();
    }
}

/**
 * Refresh Token으로 Access Token 자동 갱신
 */
private static function refreshAccessToken() {
    $refreshToken = $_COOKIE[self::$refreshCookieName] ?? null;
    
    if (!$refreshToken) {
        return self::getGuestMember();
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
    return self::getGuestMember();
}
```

## 4. CORS 설정

### 4.1 필수 설정

**config/cors.php:**
```php
<?php
return [
    'allowed_origins' => explode(',', Config::get('cors.allowed_origins', '')),
    'allow_credentials' => true, // Cookie 전송을 위해 필수
    'allowed_headers' => ['Content-Type', 'X-Requested-With'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'max_age' => 86400
];
```

**routes/_middleware.php:**
```php
<?php
function cors() {
    // 허용된 Origin 목록 가져오기 (.env에서 쉼표로 구분된 목록)
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
        // 예: 요청 Origin이 http://localhost:3000이면 Access-Control-Allow-Origin: http://localhost:3000
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true"); // Cookie 전송을 위해 필수
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 86400");
    }
    // 허용되지 않은 Origin이면 CORS 헤더를 설정하지 않음 (브라우저가 요청 차단)
    
    // OPTIONS 요청 (Preflight) 처리
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

cors();
logRequest();
```

**동작 예시:**
```env
# .env 파일
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,https://app.example.com,https://admin.example.com
```

```php
// 시나리오 1: 허용된 Origin 요청
// 요청 헤더: Origin: http://localhost:3000
// → 허용 목록에 있음
// → 응답 헤더: Access-Control-Allow-Origin: http://localhost:3000 ✅

// 시나리오 2: 다른 허용된 Origin 요청
// 요청 헤더: Origin: https://app.example.com
// → 허용 목록에 있음
// → 응답 헤더: Access-Control-Allow-Origin: https://app.example.com ✅

// 시나리오 3: 허용되지 않은 Origin 요청
// 요청 헤더: Origin: https://evil.com
// → 허용 목록에 없음
// → CORS 헤더 미설정 → 브라우저가 요청 차단 ❌
```

## 5. 환경 변수 설정

**.env:**
```env
# Cookie 설정
APP_COOKIE_DOMAIN=localhost  # 프로덕션에서는 실제 도메인
APP_HTTPS_ONLY=true  # 프로덕션에서는 true로 설정
APP_COOKIE_SAMESITE=Lax  # Lax, Strict, None 중 선택

# CORS 설정
# 여러 도메인을 쉼표로 구분하여 설정
# 요청한 Origin이 이 목록에 있으면 해당 Origin을 Access-Control-Allow-Origin에 반환
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,https://app.example.com,https://admin.example.com
CORS_ALLOW_CREDENTIALS=true  # Cookie 전송을 위해 필수
```

## 6. 프론트엔드 연동

### 6.1 SvelteKit 예시

**lib/api.ts:**
```typescript
// Cookie는 자동으로 전송되므로 별도 설정 불필요
export async function apiRequest(url: string, options: RequestInit = {}) {
    const response = await fetch(`/api${url}`, {
        ...options,
        credentials: 'include', // Cookie 전송을 위해 필수
        headers: {
            'Content-Type': 'application/json',
            ...options.headers,
        },
    });
    
    return response.json();
}

// 로그인
export async function login(mbId: string, password: string) {
    return apiRequest('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ mb_id: mbId, mb_password: password }),
    });
}

// 로그아웃
export async function logout() {
    return apiRequest('/auth/logout', {
        method: 'POST',
    });
}

// API 호출 (토큰은 자동으로 쿠키에서 전송됨)
export async function getBoardList(boTable: string) {
    return apiRequest(`/bbs/${boTable}`);
}
```

### 6.2 주의사항

1. **credentials: 'include' 필수**
   - 모든 fetch 요청에 `credentials: 'include'` 설정 필요
   - Cookie 전송을 위해 필수

2. **CORS 설정**
   - `Access-Control-Allow-Credentials: true` 필수
   - `Access-Control-Allow-Origin`에 와일드카드(`*`) 사용 불가
   - 정확한 Origin 지정 필요

3. **SameSite 설정**
   - `Lax`: GET 요청만 외부에서 가능 (권장)
   - `Strict`: 완전 차단 (보안 최우선)
   - `None`: 모든 요청 허용 (Secure 필수)

## 7. 보안 고려사항

### 7.1 XSS 공격 방지

- ✅ HTTP Only Cookie로 JavaScript 접근 불가
- ✅ 토큰이 응답 본문에 포함되지 않음
- ✅ 프론트엔드에서 토큰 관리 불필요

### 7.2 CSRF 공격 방지

- ✅ SameSite 속성으로 CSRF 공격 완화
- ✅ 추가 보호: CSRF Token 검증 (선택사항)

### 7.3 토큰 탈취 방지

- ✅ HTTPS에서만 전송 (Secure 속성)
- ✅ 짧은 Access Token 유효기간 (15분)
- ✅ Refresh Token은 DB에 저장하여 무효화 가능

## 8. 마이그레이션 가이드

### 8.1 기존 Authorization 헤더 방식에서 전환

1. **하위 호환성 유지**
   - Cookie 우선, Header는 백업으로 사용
   - 기존 클라이언트도 동작 가능

2. **점진적 전환**
   - 새 클라이언트는 Cookie 방식 사용
   - 기존 클라이언트는 Header 방식 계속 사용
   - 일정 기간 후 Header 방식 제거

### 8.2 테스트 체크리스트

- [ ] 로그인 시 Cookie 설정 확인
- [ ] API 호출 시 Cookie 자동 전송 확인
- [ ] 로그아웃 시 Cookie 삭제 확인
- [ ] 토큰 만료 시 자동 갱신 확인
- [ ] CORS 설정 확인
- [ ] HTTPS 환경에서 Secure 속성 확인
- [ ] XSS 공격 시도 시 토큰 접근 불가 확인

## 9. 참고 자료

- [MDN: HTTP Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies)
- [OWASP: Cross-Site Scripting (XSS)](https://owasp.org/www-community/attacks/xss/)
- [OWASP: Cross-Site Request Forgery (CSRF)](https://owasp.org/www-community/attacks/csrf)
- [SameSite Cookie 설명](https://web.dev/samesite-cookies-explained/)

