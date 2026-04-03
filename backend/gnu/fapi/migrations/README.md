# fapi Migrations

마이그레이션 파일을 이 디렉토리에 배치합니다.

## 네이밍 규칙

```
YYYYMMDDHHIISS_description.sql
예: 20260322000000_expand_mb_id.sql
```

## 자동 실행

sqlx가 DB에 연결될 때 자동으로 마이그레이션을 체크하고 실행합니다.

```php
sqlx::query("SELECT * FROM ...")->fetch();
```

첫 연결 시:
1. `g5_migrations` 테이블 자동 생성
2. 대기 중인 마이그레이션 파일 체크
3. 미실행 마이그레이션 자동 실행

## 수동 실행

```bash
cd /v1/fapi/scripts
php migrate.php status
php migrate.php run
php migrate.php rollback
```

## 마이그레이션 파일

| 파일 | 설명 |
|------|------|
| `00000_create_migrations_table.sql` | 마이그레이션 관리 테이블 |
| `20260322000000_expand_mb_id.sql` | mb_id → VARCHAR(36) |

## PostgreSQL 전환

마이그레이션 파일에 PostgreSQL용 SQL이 포함되어 있습니다.
전환 시 마이그레이션 파일의 PostgreSQL 주석을 해제하고 실행하세요.
