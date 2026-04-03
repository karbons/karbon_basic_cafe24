# FAPI 에러 응답 규칙

## 1. 개요

FAPI는 **모든 응답(성공 및 에러)을 JSON 형식으로 반환**합니다. 이는 프론트엔드에서 일관된 방식으로 응답을 처리하고, 특히 JWT 토큰 갱신 로직을 정상적으로 실행하기 위해 필수적입니다.

**⚠️ 중요:** 이 규칙은 PHP 레벨뿐만 아니라 **웹서버 레벨(Nginx/Apache)에서도 적용**되어야 합니다. 웹서버에서 발생하는 404, 403, 500 등의 에러도 JSON 형식으로 반환되어야 합니다.

## 2. 에러 응답이 JSON이어야 하는 이유

### 2.1 JWT 토큰 갱신 문제

**문제 상황:**
- HTTP 상태 코드만 반환하거나 HTML 에러 페이지를 반환하면, 프론트엔드에서 응답을 JSON으로 파싱할 수 없음
- 프론트엔드의 인터셉터나 에러 핸들러가 응답을 인식하지 못하여 토큰 갱신 로직이 실행되지 않음
- 사용자가 갑자기 로그아웃되거나 예기치 않은 에러 페이지를 보게 됨

**해결 방법:**
- 모든 에러 응답을 JSON 형식으로 반환
- 프론트엔드에서 응답의 `code` 필드를 확인하여 토큰 만료(`00004`) 등의 상황을 감지
- 토큰 갱신 로직을 자동으로 실행

### 2.2 일관된 에러 처리

**장점:**
- 프론트엔드에서 모든 응답을 동일한 방식으로 처리 가능
- 에러 메시지를 사용자에게 명확하게 전달 가능
- 디버깅 및 로깅이 용이함

## 3. 에러 응답 형식

### 3.1 표준 에러 응답 구조

```json
{
  "code": "에러코드",
  "msg": "에러 메시지",
  "data": null,
  "time": 0.1234
}
```

### 3.2 HTTP 상태 코드와 에러 코드 매핑

| HTTP 상태 코드 | 에러 코드 | 설명 | 사용 예시 |
|--------------|----------|------|----------|
| 200 | 00000 | 성공 | 정상 응답 |
| 200 | 00001 | 일반 에러 | 입력값 검증 실패 |
| 200 | 00002 | 인증 필요 | 로그인 필요 |
| 200 | 00003 | 권한 없음 | 접근 권한 없음 |
| 200 | 00004 | 토큰 만료 | Access Token 만료 (Refresh 가능) |
| 401 | 00002 | 인증 실패 | 로그인 실패 |
| 401 | 00004 | 토큰 만료 | Access Token 만료 |
| 403 | 00003 | 권한 없음 | 접근 권한 없음 |
| 404 | 00001 | 리소스 없음 | 페이지/리소스를 찾을 수 없음 |

### 3.3 에러 코드 상세

#### 00000: 성공
```json
{
  "code": "00000",
  "data": { ... },
  "msg": "성공 메시지 (선택)",
  "time": 0.1234
}
```

#### 00001: 일반 에러
```json
{
  "code": "00001",
  "data": null,
  "msg": "에러 메시지",
  "time": 0.1234
}
```
**사용 예시:**
- 입력값 검증 실패
- 리소스를 찾을 수 없음 (404)
- 일반적인 비즈니스 로직 에러

#### 00002: 인증 필요
```json
{
  "code": "00002",
  "data": null,
  "msg": "로그인이 필요합니다.",
  "time": 0.1234
}
```
**HTTP 상태 코드:** 401
**사용 예시:**
- 로그인하지 않은 사용자가 인증이 필요한 API 호출
- 로그인 실패

#### 00003: 권한 없음
```json
{
  "code": "00003",
  "data": null,
  "msg": "권한이 없습니다.",
  "time": 0.1234
}
```
**HTTP 상태 코드:** 403
**사용 예시:**
- 회원 레벨이 부족한 경우
- 게시판 읽기/쓰기 권한이 없는 경우
- 관리자 권한이 필요한 경우

#### 00004: 토큰 만료
```json
{
  "code": "00004",
  "data": null,
  "msg": "토큰이 만료되었습니다.",
  "time": 0.1234
}
```
**HTTP 상태 코드:** 401
**사용 예시:**
- Access Token이 만료된 경우
- 프론트엔드에서 이 코드를 받으면 자동으로 Refresh Token으로 갱신 시도

## 4. 구현 규칙

### 4.1 모든 에러는 JSON으로 반환

**❌ 잘못된 예시:**
```php
// 잘못된 방법: HTTP 상태 코드만 반환
if (!$member['mb_id']) {
    http_response_code(401);
    exit;
}

// 잘못된 방법: HTML 에러 페이지 반환
if (!$member['mb_id']) {
    header('HTTP/1.1 401 Unauthorized');
    include 'error_page.php';
    exit;
}
```

**✅ 올바른 예시:**
```php
// 올바른 방법: JSON으로 반환
if (!$member['mb_id']) {
    json_return(null, 401, '00002', '로그인이 필요합니다.');
}

// 또는 Response 클래스 사용
if (!$member['mb_id']) {
    Response::unauthorized('로그인이 필요합니다.');
}
```

### 4.2 토큰 만료 처리

**❌ 잘못된 예시:**
```php
// 잘못된 방법: HTTP 상태 코드만 반환
if ($tokeninfo->exp < time()) {
    http_response_code(203);
    exit;
}
```

**✅ 올바른 예시:**
```php
// 올바른 방법: JSON으로 반환하여 프론트엔드에서 처리 가능하도록
if ($tokeninfo->exp < time()) {
    json_return(null, 401, '00004', '토큰이 만료되었습니다.');
}
```

### 4.3 권한 체크

**❌ 잘못된 예시:**
```php
// 잘못된 방법: HTML 에러 페이지로 리다이렉트
if ($member['mb_level'] < $board['bo_read_level']) {
    header('Location: /error.php?code=403');
    exit;
}
```

**✅ 올바른 예시:**
```php
// 올바른 방법: JSON으로 반환
if ($member['mb_level'] < $board['bo_read_level']) {
    json_return(null, 403, '00003', '게시판을 읽을 권한이 없습니다.');
}

// 또는 Response 클래스 사용
if ($member['mb_level'] < $board['bo_read_level']) {
    Response::forbidden('게시판을 읽을 권한이 없습니다.');
}
```

## 5. 프론트엔드 처리 예시

### 5.1 토큰 만료 자동 갱신

```typescript
// api.ts
export async function apiRequest<T>(
    endpoint: string,
    options: RequestInit = {}
): Promise<ApiResponse<T>> {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    });
    
    const data: ApiResponse<T> = await response.json();
    
    // 토큰 만료 감지
    if (data.code === '00004') {
        // Refresh Token으로 갱신 시도
        try {
            await refreshToken();
            // 원래 요청 재시도
            return apiRequest<T>(endpoint, options);
        } catch (e) {
            // Refresh Token도 만료됨 - 로그인 페이지로 이동
            window.location.href = '/login';
            throw new Error('세션이 만료되었습니다. 다시 로그인해주세요.');
        }
    }
    
    if (data.code !== '00000') {
        throw new Error(data.msg || 'API 요청 실패');
    }
    
    return data;
}
```

### 5.2 에러 코드별 처리

```typescript
// errorHandler.ts
export function handleApiError(error: ApiResponse<any>) {
    switch (error.code) {
        case '00002':
            // 로그인 필요
            window.location.href = '/login';
            break;
        case '00003':
            // 권한 없음
            alert(error.msg || '권한이 없습니다.');
            break;
        case '00004':
            // 토큰 만료 - 자동 갱신 시도
            refreshToken();
            break;
        default:
            // 일반 에러
            alert(error.msg || '오류가 발생했습니다.');
    }
}
```

## 6. Response 클래스 사용법

### 6.1 기본 사용법

```php
<?php
// 성공 응답
Response::success(['id' => 123], '저장되었습니다.');

// 에러 응답
Response::error('입력값을 확인해주세요.', '00001');

// 인증 필요
Response::unauthorized('로그인이 필요합니다.');

// 권한 없음
Response::forbidden('권한이 없습니다.');

// 리소스 없음
Response::notFound('페이지를 찾을 수 없습니다.');
```

### 6.2 커스텀 HTTP 상태 코드

```php
<?php
// HTTP 401과 함께 JSON 반환
Response::json(null, '00002', '로그인이 필요합니다.', 401);

// HTTP 500과 함께 JSON 반환
Response::json(null, '00001', '서버 오류가 발생했습니다.', 500);
```

## 7. json_return 함수 사용법

### 7.1 기본 사용법

```php
<?php
// 성공 응답
json_return(['id' => 123], 200, '00000', '저장되었습니다.');

// 에러 응답
json_return(null, 200, '00001', '입력값을 확인해주세요.');

// 인증 필요
json_return(null, 401, '00002', '로그인이 필요합니다.');

// 권한 없음
json_return(null, 403, '00003', '권한이 없습니다.');

// 토큰 만료
json_return(null, 401, '00004', '토큰이 만료되었습니다.');
```

## 8. 체크리스트

개발 시 다음 사항을 확인하세요:

- [ ] 모든 에러 응답이 JSON 형식인가?
- [ ] HTTP 상태 코드만 반환하지 않는가?
- [ ] HTML 에러 페이지를 반환하지 않는가?
- [ ] `exit` 또는 `die` 전에 `json_return()` 또는 `Response::json()`을 호출하는가?
- [ ] 토큰 만료 시 `00004` 코드를 사용하는가?
- [ ] 프론트엔드에서 에러 코드를 확인하여 적절히 처리할 수 있는가?

## 9. 주의사항

### 9.1 절대 하지 말아야 할 것

1. **HTTP 상태 코드만 반환:**
   ```php
   // ❌ 잘못된 방법
   http_response_code(401);
   exit;
   ```

2. **HTML 에러 페이지 반환:**
   ```php
   // ❌ 잘못된 방법
   header('HTTP/1.1 401 Unauthorized');
   include 'error_page.php';
   exit;
   ```

3. **리다이렉트:**
   ```php
   // ❌ 잘못된 방법
   header('Location: /login.php');
   exit;
   ```

### 9.2 반드시 해야 할 것

1. **모든 에러를 JSON으로 반환:**
   ```php
   // ✅ 올바른 방법
   json_return(null, 401, '00002', '로그인이 필요합니다.');
   ```

2. **에러 코드 사용:**
   - `00002`: 인증 필요
   - `00003`: 권한 없음
   - `00004`: 토큰 만료

3. **명확한 에러 메시지 제공:**
   ```php
   // ✅ 올바른 방법
   json_return(null, 403, '00003', '게시판을 읽을 권한이 없습니다.');
   ```

## 10. 웹서버 레벨 에러 처리

### 10.1 Nginx 설정

Nginx에서 발생하는 에러(404, 403, 500 등)도 JSON으로 반환해야 합니다.

**nginx.conf:**
```nginx
# API 에러 페이지를 JSON으로 반환
error_page 404 = @api_json_error;
error_page 403 = @api_json_error;
error_page 500 = @api_json_error;

location @api_json_error {
    default_type application/json;
    add_header Content-Type application/json always;
    
    if ($status = 404) {
        return 404 '{"code":"00001","msg":"페이지를 찾을 수 없습니다.","data":null,"time":0}';
    }
    if ($status = 403) {
        return 403 '{"code":"00003","msg":"접근 권한이 없습니다.","data":null,"time":0}';
    }
    if ($status = 500) {
        return 500 '{"code":"00001","msg":"서버 오류가 발생했습니다.","data":null,"time":0}';
    }
    
    return 500 '{"code":"00001","msg":"서버 오류가 발생했습니다.","data":null,"time":0}';
}

# API 경로에서만 에러 페이지 적용
location ~ ^/api {
    error_page 404 = @api_json_error;
    error_page 403 = @api_json_error;
    error_page 500 = @api_json_error;
}
```

### 10.2 Apache 설정

Apache의 경우 `ErrorDocument`를 사용하여 에러를 PHP 파일로 리다이렉트할 수 있습니다.

**httpd-vhosts.conf:**
```apache
# API 에러 페이지를 JSON으로 반환
ErrorDocument 404 /api/error_handler.php
ErrorDocument 403 /api/error_handler.php
ErrorDocument 500 /api/error_handler.php
```

**api/error_handler.php:**
```php
<?php
$errorCode = $_SERVER['REDIRECT_STATUS'] ?? 500;
http_response_code($errorCode);
header('Content-Type: application/json');

$responses = [
    404 => ['code' => '00001', 'msg' => '페이지를 찾을 수 없습니다.'],
    403 => ['code' => '00003', 'msg' => '접근 권한이 없습니다.'],
    500 => ['code' => '00001', 'msg' => '서버 오류가 발생했습니다.'],
];

$response = $responses[$errorCode] ?? ['code' => '00001', 'msg' => '서버 오류가 발생했습니다.'];

echo json_encode([
    'code' => $response['code'],
    'msg' => $response['msg'],
    'data' => null,
    'time' => 0
], JSON_UNESCAPED_UNICODE);
exit;
```

### 10.3 PHP 에러 핸들러

PHP 레벨에서 발생하는 에러와 예외도 JSON으로 반환해야 합니다.

**api/index.php:**
```php
<?php
// PHP 에러 핸들러 설정
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'code' => '00001',
        'msg' => '서버 오류가 발생했습니다.',
        'data' => null,
        'time' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
}, E_ALL & ~E_NOTICE & ~E_WARNING);

// 예외 핸들러 설정
set_exception_handler(function($exception) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'code' => '00001',
        'msg' => '서버 오류가 발생했습니다.',
        'data' => null,
        'time' => 0
    ], JSON_UNESCAPED_UNICODE);
    exit;
});
```

## 11. 참고 자료

- [FAPI 개선 제안서](fapi_improvement_proposal.md)
- [FAPI 보안 쿠키](fapi_security_cookie.md)
- [FAPI 서버 설정](fapi_server_config.md)
- [Response 클래스 구현](../api/lib/Response.php)

