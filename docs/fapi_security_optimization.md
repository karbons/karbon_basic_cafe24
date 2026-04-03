# FAPI 보안 기능 최적화 가이드

## 1. 개요

기존 FAPI에서 구현된 DB 바인딩 및 보안 강화 기능이 그누보드 자체 보안 기능과 중복되는 부분을 제거하고, 필요한 기능만 유지하는 방안을 제시합니다.

## 2. 그누보드 기본 보안 기능

### 2.1 자동 이스케이프 처리

**common.php에서 자동 적용:**
```php
// 모든 입력값에 자동으로 sql_escape_string 적용
$_POST    = array_map_deep(G5_ESCAPE_FUNCTION,  $_POST);
$_GET     = array_map_deep(G5_ESCAPE_FUNCTION,  $_GET);
$_COOKIE  = array_map_deep(G5_ESCAPE_FUNCTION,  $_COOKIE);
$_REQUEST = array_map_deep(G5_ESCAPE_FUNCTION,  $_REQUEST);
```

**sql_escape_string() 함수:**
```php
function sql_escape_string($str)
{
    if(defined('G5_ESCAPE_PATTERN') && defined('G5_ESCAPE_REPLACE')) {
        $pattern = G5_ESCAPE_PATTERN;
        $replace = G5_ESCAPE_REPLACE;
        if($pattern)
            $str = preg_replace($pattern, $replace, $str);
    }
    $str = call_user_func('addslashes', $str);
    return $str;
}
```

### 2.2 sql_query() 함수의 보안 기능

**lib/common.lib.php:**
```php
function sql_query($sql, $error=G5_DISPLAY_SQL_ERROR, $link=null)
{
    // Blind SQL Injection 취약점 해결
    $sql = trim($sql);
    
    // UNION 사용 차단
    $sql = preg_replace("#^select.*from.*[\s\(]+union[\s\)]+.*#i ", "select 1", $sql);
    
    // information_schema 접근 차단
    $sql = preg_replace("#^select.*from.*where.*`?information_schema`?.*#i", "select 1", $sql);
    
    // 쿼리 실행
    // ...
}
```

### 2.3 sql_real_escape_string() 함수

```php
function sql_real_escape_string($str, $link=null)
{
    global $g5;
    if(!$link)
        $link = $g5['connect_db'];
    
    if(function_exists('mysqli_connect') && G5_MYSQLI_USE) {
        return mysqli_real_escape_string($link, $str);
    }
    return mysql_real_escape_string($str, $link);
}
```

## 3. 기존 FAPI의 중복 보안 기능

### 3.1 문제점

**기존 코드 예시 (_common.php):**
```php
function set_refresh_token($token, $mb_id, $uuid, $agent)
{
    // 직접 SQL 쿼리 작성 - 그누보드의 자동 이스케이프가 이미 적용됨
    $sql = "delete from g5_member_rejwt where mb_id = '$mb_id' ";
    sql_query($sql);
    
    $sql = "insert into g5_member_rejwt 
            set mb_id = '$mb_id',
                uuid = '$uuid',
                agent = '$agent',
                refresh_token = '$token',
                reg_datetime = now()";
    return sql_query($sql);
}
```

**문제점:**
1. `$_POST`, `$_GET` 등은 이미 자동 이스케이프 처리됨
2. `sql_query()` 함수가 이미 UNION, information_schema 차단
3. 추가적인 이스케이프 처리가 중복될 수 있음
4. JSON 입력값(`file_get_contents('php://input')`)은 자동 이스케이프 대상이 아님

### 3.2 JSON 입력값의 보안 이슈

**현재 상황:**
```php
// JSON 입력은 자동 이스케이프 대상이 아님
$requestData = json_decode(file_get_contents('php://input'), true);
$mb_id = $requestData['mb_id']; // 이스케이프 처리 안됨!
```

**위험:**
- JSON으로 받은 데이터를 직접 SQL에 사용하면 SQL Injection 위험
- 그누보드의 자동 이스케이프가 적용되지 않음

## 4. 개선 방안

### 4.1 그누보드 보안 기능 활용

**원칙:**
1. 그누보드의 기존 보안 기능 최대한 활용
2. 중복 보안 검사 제거
3. JSON 입력값에 대해서만 추가 보안 처리

### 4.2 개선된 DB 헬퍼 클래스

**lib/Database.php:**
```php
<?php
class Database {
    /**
     * 그누보드의 sql_query 함수 활용
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
     * 그누보드의 sql_fetch_array 함수 활용
     */
    public static function fetchArray($sql) {
        return sql_fetch_array($sql);
    }
    
    /**
     * JSON 입력값을 안전하게 이스케이프 처리
     * 그누보드의 sql_escape_string 활용
     */
    public static function escape($value) {
        if (is_array($value)) {
            return array_map([self::class, 'escape'], $value);
        }
        
        // 그누보드의 이스케이프 함수 활용
        return sql_escape_string($value);
    }
    
    /**
     * JSON 입력값을 안전하게 처리하여 SQL에 사용
     */
    public static function safeValue($value) {
        if (is_numeric($value)) {
            return (int)$value; // 숫자는 타입 캐스팅만
        }
        
        return "'" . sql_escape_string($value) . "'";
    }
    
    /**
     * Prepared Statement 스타일의 쿼리 빌더 (선택사항)
     * 그누보드와 호환되도록 구현
     */
    public static function buildQuery($template, $params = []) {
        $sql = $template;
        
        foreach ($params as $key => $value) {
            $placeholder = ':' . $key;
            if (strpos($sql, $placeholder) !== false) {
                $sql = str_replace($placeholder, self::safeValue($value), $sql);
            }
        }
        
        return $sql;
    }
}
```

### 4.3 개선된 API 코드 예시

**기존 코드 (중복 보안 처리):**
```php
function set_refresh_token($token, $mb_id, $uuid, $agent)
{
    // 불필요한 추가 이스케이프 처리 (그누보드가 이미 처리)
    $mb_id = sql_real_escape_string($mb_id);
    $uuid = sql_real_escape_string($uuid);
    $agent = sql_real_escape_string($agent);
    $token = sql_real_escape_string($token);
    
    $sql = "delete from g5_member_rejwt where mb_id = '$mb_id' ";
    sql_query($sql);
    
    $sql = "insert into g5_member_rejwt 
            set mb_id = '$mb_id',
                uuid = '$uuid',
                agent = '$agent',
                refresh_token = '$token',
                reg_datetime = now()";
    return sql_query($sql);
}
```

**개선된 코드 (그누보드 보안 기능 활용):**
```php
function set_refresh_token($token, $mb_id, $uuid, $agent)
{
    // 그누보드의 sql_query가 이미 보안 처리하므로 추가 처리 불필요
    // 단, JSON 입력값인 경우에만 이스케이프 처리
    $mb_id = Database::escape($mb_id);
    $uuid = Database::escape($uuid);
    $agent = Database::escape($agent);
    $token = Database::escape($token);
    
    $sql = "delete from g5_member_rejwt where mb_id = '$mb_id' ";
    sql_query($sql);
    
    $sql = "insert into g5_member_rejwt 
            set mb_id = '$mb_id',
                uuid = '$uuid',
                agent = '$agent',
                refresh_token = '$token',
                reg_datetime = now()";
    return sql_query($sql);
}
```

### 4.4 JSON 입력값 처리 개선

**lib/Request.php:**
```php
<?php
class Request {
    /**
     * JSON 입력값을 안전하게 파싱하고 이스케이프 처리
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
     * 배열의 모든 값을 이스케이프 처리
     */
    private static function escapeArray($data) {
        if (!is_array($data)) {
            return sql_escape_string($data);
        }
        
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::escapeArray($value);
            } else {
                $result[$key] = sql_escape_string($value);
            }
        }
        
        return $result;
    }
    
    /**
     * GET/POST 값 가져오기 (그누보드는 이미 이스케이프 처리됨)
     */
    public static function get($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    public static function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
}
```

### 4.5 개선된 API 라우트 예시

**routes/auth/login.php:**
```php
<?php
function POST() {
    // JSON 입력값은 수동으로 이스케이프 처리 필요
    $data = Request::getJsonInput();
    
    // 그누보드 함수 사용 (이미 보안 처리됨)
    $mb = get_member($data['mb_id']);
    
    // 비밀번호 검증 (그누보드 함수 활용)
    if (!login_password_check($mb, $data['mb_password'], $mb['mb_password'])) {
        Response::error('로그인 실패', '00001');
    }
    
    // 토큰 생성 및 저장
    // ...
}
```

**routes/bbs/[bo_table]/write.php:**
```php
<?php
function POST($bo_table) {
    // JSON 입력값 처리
    $data = Request::getJsonInput();
    
    // 그누보드 함수 활용 (이미 보안 처리됨)
    $board = get_board_db($bo_table, true);
    
    // 게시글 작성 (그누보드 함수 활용)
    // 그누보드의 write_update.php 로직 활용 또는
    // 그누보드 함수들을 조합하여 사용
    // ...
}
```

## 5. 보안 체크리스트

### 5.1 제거해야 할 중복 보안 처리

- [ ] `$_POST`, `$_GET` 값에 대한 추가 `sql_real_escape_string()` 호출 제거
- [ ] `sql_query()` 호출 전 불필요한 UNION/information_schema 체크 제거
- [ ] 그누보드 함수(`get_member()`, `get_board_db()` 등) 사용 시 추가 이스케이프 제거

### 5.2 유지해야 할 보안 처리

- [ ] JSON 입력값(`file_get_contents('php://input')`)에 대한 이스케이프 처리
- [ ] 외부 API에서 받은 데이터에 대한 검증
- [ ] 파일 업로드 시 확장자/크기 검증
- [ ] 권한 체크 (그누보드 함수 활용)

### 5.3 추가해야 할 보안 처리

- [ ] 입력값 타입 검증 (Validator 클래스)
- [ ] 파일명 검증 (경로 순회 방지)
- [ ] XSS 방지 (그누보드의 `get_text()`, `clean_xss_tags()` 활용)

## 6. 그누보드 보안 함수 활용 가이드

### 6.1 입력값 처리

**그누보드 함수 활용:**
```php
// XSS 방지
$clean = get_text($input);
$clean = clean_xss_tags($input);

// SQL Injection 방지 (자동 처리됨)
// $_POST, $_GET은 이미 처리됨
// JSON 입력값만 수동 처리 필요

// HTML 태그 제거
$clean = strip_tags($input);
```

### 6.2 DB 쿼리

**그누보드 함수 활용:**
```php
// 그누보드 함수 사용 (보안 처리 포함)
$member = get_member($mb_id);
$board = get_board_db($bo_table, true);
$write = get_write($write_table, $wr_id);

// 직접 쿼리 필요 시
$sql = "select * from table where id = '$id'"; // $id는 이미 이스케이프됨
$result = sql_query($sql); // UNION, information_schema 차단 포함
$row = sql_fetch($result);
```

### 6.3 파일 처리

**그누보드 함수 활용:**
```php
// 파일 업로드
$file = get_file($bo_table, $wr_id);

// 파일 검증
if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
    Response::error('잘못된 파일입니다.', '00001');
}
```

## 7. 마이그레이션 가이드

### 7.1 단계별 개선

**Phase 1: 중복 보안 처리 제거 (0.5일)**
1. `$_POST`, `$_GET` 값의 추가 이스케이프 제거
2. 그누보드 함수 사용 시 추가 보안 처리 제거
3. `sql_query()` 전 불필요한 검사 제거

**Phase 2: JSON 입력값 처리 개선 (0.5일)**
1. `Request::getJsonInput()` 클래스 구현
2. 모든 JSON 입력값에 적용
3. 테스트 및 검증

**Phase 3: 그누보드 함수 활용 강화 (1일)**
1. 직접 쿼리 대신 그누보드 함수 사용
2. 보안 검증은 그누보드 함수에 위임
3. 코드 간소화 및 성능 개선

## 8. 개선 효과

### 8.1 코드 간소화

**Before:**
```php
$mb_id = sql_real_escape_string($requestData['mb_id']);
$mb_password = sql_real_escape_string($requestData['mb_password']);
$mb = get_member($mb_id); // 이미 이스케이프 처리됨
```

**After:**
```php
$data = Request::getJsonInput(); // JSON만 처리
$mb = get_member($data['mb_id']); // 그누보드 함수 활용
```

### 8.2 성능 개선

- 중복 이스케이프 처리 제거로 성능 향상
- 그누보드 최적화된 함수 활용

### 8.3 유지보수성 향상

- 그누보드 표준 함수 사용으로 일관성 유지
- 보안 로직이 한 곳에 집중되어 관리 용이

## 9. 주의사항

### 9.1 JSON 입력값은 반드시 처리

```php
// ❌ 위험
$data = json_decode(file_get_contents('php://input'), true);
$sql = "select * from table where id = '{$data['id']}'"; // SQL Injection 위험!

// ✅ 안전
$data = Request::getJsonInput(); // 이스케이프 처리됨
$sql = "select * from table where id = '{$data['id']}'"; // 안전
```

### 9.2 그누보드 함수 활용 우선

```php
// ❌ 직접 쿼리 (불필요)
$sql = "select * from g5_member where mb_id = '$mb_id'";
$member = sql_fetch($sql);

// ✅ 그누보드 함수 활용
$member = get_member($mb_id); // 보안 처리 포함, 최적화됨
```

### 9.3 숫자 값은 타입 캐스팅

```php
// ❌ 문자열로 처리
$id = sql_escape_string($id);
$sql = "select * from table where id = '$id'";

// ✅ 숫자는 타입 캐스팅
$id = (int)$id;
$sql = "select * from table where id = $id"; // 따옴표 불필요
```

## 10. 결론

그누보드의 기존 보안 기능을 최대한 활용하고, 중복 보안 처리를 제거하여:

1. **코드 간소화**: 불필요한 보안 처리 제거
2. **성능 향상**: 중복 처리 제거로 성능 개선
3. **유지보수성 향상**: 그누보드 표준 함수 사용
4. **보안 강화**: JSON 입력값에 대한 적절한 처리 추가

이를 통해 더 간결하고 안전한 코드를 유지할 수 있습니다.

