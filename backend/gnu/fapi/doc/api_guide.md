# FAPI (File based API) 프레임워크 가이드

> 그누보드 기반 API를 현대적인 파일 기반 라우팅 시스템으로 재구성한 프레임워크입니다.
> Rust Axum 전환을 고려하여 설계되었습니다.

---

## 목차

1. [기존 그누보드 방식 vs FAPI 방식](#기존-그누보드-방식-vs-fapi-방식)
2. [파일 기반 라우팅](#파일-기반-라우팅)
3. [Request Extractors](#request-extractors)
4. [데이터베이스 (sqlx)](#데이터베이스-sqlx)
5. [함수 충돌 방지 규칙](#함수-충돌-방지-규칙)
6. [전체 예제](#전체-예제)

---

## 기존 그누보드 방식 vs FAPI 방식

### 라우팅 비교

| 항목 | 그누보드 방식 | FAPI 방식 |
|------|-------------|-----------|
| 라우트 정의 | URL 수동 파싱 | 파일 구조 = URL |
| 동적 파라미터 | `$_GET['bo_table']` | 함수 매개변수 `$bo_table` |
| HTTP 메서드 | `if ($_SERVER['REQUEST_METHOD'])` | 함수명 `GET()`, `POST()` |

```php
// ❌ 그누보드 방식
// bbs/board.php?bo_table=free&wr_id=123
$bo_table = $_GET['bo_table'];
$wr_id = $_GET['wr_id'];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 조회 로직
}

// ✅ FAPI 방식
// routes/bbs/[bo_table]/[wr_id].php → /api/bbs/free/123
function GET($bo_table, $wr_id) {
    // 조회 로직
}
```

---

### 데이터베이스 비교

| 항목 | 그누보드 방식 | FAPI 방식 |
|------|-------------|-----------|
| 쿼리 실행 | `sql_query()` | `sqlx::query()->execute()` |
| 단일 조회 | `sql_fetch()` | `sqlx::query()->fetch_one()` |
| 다중 조회 | `while(sql_fetch_array())` | `sqlx::query()->fetch_all()` |
| SQL Injection | `sql_real_escape_string()` | Prepared Statement (자동) |

```php
// ❌ 그누보드 방식 (SQL Injection 위험)
$mb_id = sql_real_escape_string($_GET['mb_id']);
$sql = "SELECT * FROM g5_member WHERE mb_id = '{$mb_id}'";
$member = sql_fetch($sql);

// ✅ FAPI 방식 (안전한 Prepared Statement)
$member = sqlx::query("SELECT * FROM g5_member WHERE mb_id = ?")
    ->bind($mb_id)
    ->fetch_optional();
```

---

## 파일 기반 라우팅

### 파일 구조 = URL 구조

```
routes/
├── index.php           → GET /api/
├── health.php          → GET /api/health
├── bbs/
│   ├── index.php       → GET /api/bbs
│   ├── search.php      → GET /api/bbs/search
│   └── [bo_table]/
│       ├── index.php   → GET /api/bbs/{bo_table}
│       └── [wr_id].php → GET /api/bbs/{bo_table}/{wr_id}
└── member/
    ├── update.php      → POST /api/member/update
    └── scrap.php       → GET /api/member/scrap
```

### 동적 라우트 규칙

| 파일명 | URL 예시 | 매개변수 |
|--------|----------|----------|
| `[bo_table].php` | `/api/bbs/free` | `$bo_table = "free"` |
| `[id].php` | `/api/member/123` | `$id = "123"` |
| `[bo_table]/[wr_id].php` | `/api/bbs/free/456` | `$bo_table, $wr_id` |

### HTTP 메서드 함수

```php
// routes/bbs/[bo_table]/index.php

function GET($bo_table) {
    // GET /api/bbs/{bo_table} 요청 처리
    json_return(['board' => $bo_table]);
}

function POST($bo_table) {
    // POST /api/bbs/{bo_table} 요청 처리
    json_return(['created' => true], 201);
}

function PUT($bo_table) {
    // PUT /api/bbs/{bo_table} 요청 처리
}

function DELETE($bo_table) {
    // DELETE /api/bbs/{bo_table} 요청 처리
}
```

---

## Request Extractors

Rust Axum 스타일의 요청 데이터 추출 함수들입니다.

### query() - URL 쿼리 파라미터

```php
// URL: /api/bbs/free?page=2&limit=10

// 그누보드 방식
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// FAPI 방식
$page = query('page', 1);       // 2
$limit = query('limit', 20);    // 10
$all = query();                 // ['page' => '2', 'limit' => '10']
```

### json_body() - JSON 요청 본문

```php
// 요청: POST /api/bbs/free
// Body: {"title": "제목", "content": "내용"}

// 그누보드 방식
$input = json_decode(file_get_contents('php://input'), true);
$title = $input['title'] ?? '';

// FAPI 방식
$title = json_body('title');           // "제목"
$content = json_body('content');       // "내용"
$body = json_body();                   // 전체 배열
```

### require_json_body() - 필수 필드 검증

```php
// 그누보드 방식
$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['title'])) {
    json_return(null, 400, '00002', 'title은 필수입니다.');
}

// FAPI 방식 (없으면 자동 400 에러)
$body = require_json_body(['title', 'content']);
// title 또는 content 없으면: 400 "필수 항목이 누락되었습니다: title, content"
```

### form_body() - Form 데이터

```php
// 그누보드 방식
$email = $_POST['email'] ?? '';

// FAPI 방식
$email = form_body('email');
$all = form_body();
```

### headers() - HTTP 헤더

```php
// 그누보드 방식
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// FAPI 방식
$auth = headers('authorization');
$contentType = headers('content-type');
$all = headers();
```

---

## 데이터베이스 (sqlx)

### 기본 쿼리

```php
// 단일 조회 (없으면 빈 배열)
$post = sqlx::query("SELECT * FROM g5_write_free WHERE wr_id = ?")
    ->bind($wr_id)
    ->fetch_optional();

// 단일 조회 (없으면 예외)
$post = sqlx::query("SELECT * FROM g5_write_free WHERE wr_id = ?")
    ->bind($wr_id)
    ->fetch_one();

// 다중 조회
$posts = sqlx::query("SELECT * FROM g5_write_free LIMIT ?")
    ->bind($limit)
    ->fetch_all();

// INSERT/UPDATE/DELETE
sqlx::query("INSERT INTO g5_write_free (wr_subject) VALUES (?)")
    ->bind($title)
    ->execute();
```

### 트랜잭션

```php
sqlx::begin();
try {
    sqlx::query("INSERT INTO ...")->execute();
    sqlx::query("UPDATE ...")->execute();
    sqlx::commit();
} catch (Exception $e) {
    sqlx::rollback();
}
```

---

## 함수 충돌 방지 규칙

### 규칙

| 함수 종류 | 네이밍 규칙 | 예시 |
|-----------|------------|------|
| 핸들러 (라우터 호출) | HTTP 메서드 대문자 | `GET()`, `POST()` |
| 파일 내부 헬퍼 | `_` 접두사 | `_get_posts()`, `_format_row()` |
| 전역 유틸리티 | 접두사 없음 | `json_return()`, `query()` |

### 예시

```php
// routes/bbs/[bo_table]/index.php

// ✅ 핸들러 - 라우터가 호출
function GET($bo_table) {
    $posts = _get_posts($bo_table);  // 내부 함수 호출
    json_return($posts);              // 전역 함수 호출
}

// ✅ 내부 함수 - 이 파일에서만 사용
function _get_posts($bo_table) {
    return sqlx::query("SELECT * FROM g5_write_{$bo_table}")
        ->fetch_all();
}

// ✅ 내부 함수 - 다른 파일의 _format_row와 충돌 안 함
function _format_row($row) {
    return [
        'id' => $row['wr_id'],
        'title' => $row['wr_subject']
    ];
}
```

---

## 전체 예제

### 게시글 CRUD

```php
// routes/bbs/[bo_table]/[wr_id].php

/**
 * 게시글 상세 조회
 * GET /api/bbs/{bo_table}/{wr_id}
 */
function GET($bo_table, $wr_id) {
    $post = _get_post($bo_table, $wr_id);
    
    if (!$post) {
        json_return(null, 404, '00001', '게시글을 찾을 수 없습니다.');
    }
    
    json_return($post);
}

/**
 * 게시글 수정
 * PUT /api/bbs/{bo_table}/{wr_id}
 */
function PUT($bo_table, $wr_id) {
    $body = require_json_body(['title', 'content']);
    
    sqlx::query("UPDATE g5_write_{$bo_table} SET wr_subject = ?, wr_content = ? WHERE wr_id = ?")
        ->bind($body['title'])
        ->bind($body['content'])
        ->bind($wr_id)
        ->execute();
    
    json_return(['updated' => true]);
}

/**
 * 게시글 삭제
 * DELETE /api/bbs/{bo_table}/{wr_id}
 */
function DELETE($bo_table, $wr_id) {
    sqlx::query("DELETE FROM g5_write_{$bo_table} WHERE wr_id = ?")
        ->bind($wr_id)
        ->execute();
    
    json_return(['deleted' => true]);
}

// ============================================================
// 내부 함수 (파일 스코프)
// ============================================================

function _get_post($bo_table, $wr_id) {
    return sqlx::query("SELECT * FROM g5_write_{$bo_table} WHERE wr_id = ?")
        ->bind($wr_id)
        ->fetch_optional();
}
```

---

## Rust Axum 전환 참고

PHP와 Rust 매핑:

| PHP | Rust Axum |
|-----|-----------|
| `function GET($id)` | `pub async fn get(Path(id): Path<i32>)` |
| `query('page', 1)` | `Query(params): Query<Params>` |
| `json_body()` | `Json(body): Json<Body>` |
| `headers('auth')` | `TypedHeader(auth): TypedHeader<Authorization>` |
| `sqlx::query()->fetch_all()` | `sqlx::query_as!().fetch_all()` |
| `json_return($data)` | `Ok(Json(data))` |
