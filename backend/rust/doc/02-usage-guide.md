# 사용 가이드

## 기본 명령어

### 빌드

```bash
# 전체 빌드
cargo build --workspace

# 특정 crate만 빌드
cargo build -p api

# debug 모드 (기본)
cargo build

# release 모드
cargo build --release
```

### 테스트

```bash
# 전체 테스트
cargo test --workspace

# 특정 crate 테스트
cargo test -p api

# 특정 테스트 함수만
cargo test -p api test_login
```

### 실행

```bash
# 특정 crate 실행
cargo run -p api

# release 모드로 실행
cargo run --release -p api
```

### 종속성 관리

```bash
# 새 의존성 추가 (workspace root)
cargo add axum --manifest-path ../Cargo.toml

# crate에만 추가
cargo add dotenvy -p api
```

## 환경 변수 설정

`.env` 파일은 `backend/rust/` 디렉토리에 위치:

```bash
DATABASE_URL=mariadb://user:password@host:3306/dbname
JWT_ACCESS_KEY=your-secret-key
JWT_REFRESH_KEY=your-refresh-key
JWT_ACCESS_MTIME=15
JWT_REFRESH_DATE=30
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000
SERVER_HOST=0.0.0.0
SERVER_PORT=8080
API_BASE_URL=http://localhost:8080
UPLOAD_STORAGE=local
AWS_S3_URL=
```

## 자주 사용하는 명령어

| 작업 | 명령어 |
|------|--------|
| 빌드 | `cargo build -p api` |
| 실행 | `cargo run -p api` |
| 테스트 | `cargo test -p api` |
| 의존성 확인 | `cargo tree -p api` |
| 빌드清理 | `cargo clean -p api` |
| lint 검사 | `cargo clippy -p api` |

## 팁

- `cargo check`: 컴파일만 확인 (빠름)
- `cargo build --release`: 프로덕션용 빌드
- `--no-default-features`: 기본 features 제외