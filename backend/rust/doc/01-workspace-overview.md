# Rust Workspace 개요

## 구조 설명

Rust Workspace는 여러 crate를 하나의 빌드 유닛으로 관리하는 패턴입니다.

```
backend/rust/
├── Cargo.toml              # Workspace root (공유 의존성 정의)
├── .env                    # 환경 변수 (DB, JWT 등)
├── api/                    # REST API 서버 crate
│   ├── Cargo.toml
│   └── src/
│       ├── main.rs         # 엔트리 포인트
│       ├── routes/         # axum-folder-router 기반 라우트
│       ├── handlers/       # 핸들러 로직
│       ├── middleware/     # JWT, CORS 등
│       ├── config.rs       # 설정 로딩
│       ├── types.rs        # DB 타입 정의
│       └── response.rs     # API 응답 포맷
└── doc/                    # 문서
```

## 현재 설정

### Workspace Root (Cargo.toml)

```toml
[workspace]
members = ["api"]
resolver = "2"

[workspace.dependencies]
axum = "0.8"
axum-folder-router = "0.4"
tokio = { version = "1", features = ["full"] }
sqlx = { version = "0.8", features = ["mysql", "runtime-tokio-native-tls", "chrono", "time"] }
serde = { version = "1.0", features = ["derive"] }
jsonwebtoken = "9"
# ... 기타 공통 의존성
```

### crate (api/Cargo.toml)

```toml
[package]
name = "api"
version = "0.1.0"
edition = "2021"

[dependencies]
axum = { workspace = true }           # workspace 의존성 참조
tokio = { workspace = true }
sqlx = { workspace = true }
# ...

[[bin]]
name = "api"
path = "src/main.rs"
```

## 장점

1. **의존성 공유**: 중복 없이 공통 crate만 정의
2. **독립 빌드**: 각 crate 별도 빌드/테스트 가능
3. **확장성**: 마이크로서비스 분리 용이
4. **관리**: 버전统一的