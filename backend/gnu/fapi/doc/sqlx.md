# sqlx - Rust sqlx 스타일 PHP 데이터베이스 라이브러리

PHP에서 Rust sqlx와 동일한 문법을 사용하는 안전한 데이터베이스 라이브러리

## 특징

- ✅ **PDO Prepared Statements** - SQL Injection 원천 차단
- ✅ **Rust sqlx 동일 문법** - PHP → Rust 기계적 전환 가능
- ✅ **트랜잭션 API** - begin/commit/rollback/transaction()
- ✅ **마이그레이션** - DB 스키마 버전 관리
- ✅ **로깅 통합** - Logger 라이브러리와 연동

## 사용법

### 기본 쿼리

```php
require_once G5_API_PATH . '/lib/sqlx.php';

// 단일 조회 (없으면 Exception 발생)
$user = sqlx::query("SELECT * FROM g5_member WHERE mb_id = ?")
    ->bind($mb_id)
    ->fetch_one();

// 단일 조회 (없으면 null - 실무에서 더 자주 사용)
$user = sqlx::query("SELECT * FROM g5_member WHERE mb_id = ?")
    ->bind($mb_id)
    ->fetch_optional();

// 모든 행 조회
$posts = sqlx::query("SELECT * FROM g5_write_free WHERE mb_id = ? AND wr_hit > ?")
    ->bind($mb_id)
    ->bind(100)
    ->fetch_all();

// 단일 값 (COUNT, SUM 등)
$count = sqlx::query("SELECT COUNT(*) FROM g5_member")
    ->fetch_scalar();

// INSERT 후 ID 반환
$id = sqlx::query("INSERT INTO g5_write_free (wr_subject, wr_content) VALUES (?, ?)")
    ->bind($subject)
    ->bind($content)
    ->execute_insert_id();
```

### 이름 기반 바인딩

```php
$user = sqlx::query("SELECT * FROM g5_member WHERE mb_id = :id AND mb_level > :level")
    ->bind_named('id', $mb_id)
    ->bind_named('level', 5)
    ->fetch_optional();
```

### 다중 데이터베이스 연결

여러 DB에 동시 연결이 필요한 경우 (Read/Write 분리, 멀티테넌트 등):

```php
// 1. 추가 DB 풀 생성
sqlx::create_pool('analytics', [
    'host' => 'analytics-db.example.com',
    'database' => 'analytics',
    'user' => 'analytics_user',
    'password' => 'secret'
]);

sqlx::create_pool('read_replica', [
    'host' => 'db-read.example.com',
    'database' => 'gnuboard',
    'user' => 'readonly',
    'password' => 'secret'
]);

// 2. 특정 풀로 쿼리 실행
$stats = sqlx::query_with_pool('analytics', "SELECT * FROM user_stats")
    ->fetch_all();

$users = sqlx::query_with_pool('read_replica', "SELECT * FROM g5_member LIMIT 100")
    ->fetch_all();

// 3. 기본 풀 변경
sqlx::use_pool('read_replica');  // 이후 query()는 read_replica 사용

// 4. 풀 목록 확인
print_r(sqlx::pool_names());  // ['default', 'analytics', 'read_replica']
```

### 트랜잭션 (Transaction)

#### 트랜잭션이란?

**트랜잭션**은 여러 개의 DB 실행 작업을 **하나의 묶음**으로 처리하는 것입니다.

> 💡 **핵심 원칙**: "모두 성공하거나, 모두 실패하거나" (All or Nothing)

#### 왜 필요한가?

**예시: 계좌 이체**

A → B에게 10만원 이체 시:
1. A 잔액에서 10만원 차감
2. B 잔액에 10만원 추가

만약 1번은 성공했는데 2번에서 오류가 발생하면?
- ❌ 트랜잭션 없음: A돈은 빠졌는데 B는 못 받음 (돈 증발!)
- ✅ 트랜잭션 있음: 1번도 자동으로 취소됨 (원상복구)

#### 사용법

```php
// 방법 1: sqlx::transaction() - 권장! ⭐
// 에러 발생 시 자동 롤백
sqlx::transaction(function() use ($from_id, $to_id, $amount) {
    // 1. A 잔액 차감
    sqlx::query("UPDATE accounts SET balance = balance - ? WHERE id = ?")
        ->bind($amount)->bind($from_id)->execute();
    
    // 2. B 잔액 추가
    sqlx::query("UPDATE accounts SET balance = balance + ? WHERE id = ?")
        ->bind($amount)->bind($to_id)->execute();
    
    // 여기서 에러 발생하면 1, 2 모두 자동 취소됨!
});

// 방법 2: 명시적 제어 (세밀한 제어가 필요할 때)
sqlx::begin();  // 트랜잭션 시작
try {
    sqlx::query("UPDATE ...")->execute();
    sqlx::query("INSERT ...")->execute();
    sqlx::commit();  // 모두 성공 → 확정
} catch (Exception $e) {
    sqlx::rollback();  // 실패 → 모두 취소
}
```

#### 언제 사용하나?

| 상황 | 트랜잭션 필요 |
|------|--------------|
| 단순 조회 (SELECT) | ❌ |
| 단일 INSERT/UPDATE | ❌ (보통) |
| 여러 테이블 동시 수정 | ✅ |
| 결제/정산/이체 | ✅ 필수! |
| 게시글 + 첨부파일 함께 저장 | ✅ |

### 마이그레이션

```php
// migrations/ 디렉토리의 .sql 파일들을 순서대로 실행
$migrated = sqlx::migrate();
```

## Rust 전환 비교

| PHP | Rust |
|-----|------|
| `sqlx::query("...")->bind($v)->fetch_one()` | `sqlx::query("...").bind(&v).fetch_one(&pool).await?` |
| `sqlx::query("...")->fetch_all()` | `sqlx::query("...").fetch_all(&pool).await?` |
| `sqlx::begin()` | `pool.begin().await?` |
| `sqlx::commit()` | `tx.commit().await?` |

## 환경변수

| 변수 | 설명 | 기본값 |
|------|------|--------|
| `SQLX_DEBUG` | 쿼리 로깅 활성화 | `false` |
| `DB_PERSISTENT` | 영구 연결 사용 | `false` |
