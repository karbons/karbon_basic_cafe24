# 마이크로서비스 분리 가이드

## 분리 이유

현재 api crate에 모든 기능이 통합되어 있습니다. 서비스가 성장함에 따라:
- 빌드 시간 증가
- 배포 단위 큼
- 장애 영향 범위 넓음
- 팀 협업 어려움

## 제안 분리 구조

```
backend/rust/
├── Cargo.toml              # workspace root
├── .env
├── api/                    # API Gateway (routes만)
│   ├── Cargo.toml
│   └── src/main.rs
├── auth/                   # 인증 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
├── bbs/                    # 게시판 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
├── member/                 # 회원 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
├── latest/                 # 최신글 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
├── banner/                 # 배너 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
├── menu/                   # 메뉴 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
├── popup/                  # 팝업 서비스
│   ├── Cargo.toml
│   └── src/lib.rs
└── content/                # 컨텐츠 서비스
    ├── Cargo.toml
    └── src/lib.rs
```

## 서비스별 Cargo.toml 예시

### auth/Cargo.toml

```toml
[package]
name = "auth"
version = "0.1.0"
edition = "2021"

[dependencies]
axum = "0.8"
sqlx = { version = "0.8", features = ["mysql"] }
jsonwebtoken = "9"
argon2 = "0.5"
# workspace 의존성 참조
```

### auth/src/lib.rs

```rust
use axum::{routing::post, Router};
use serde::{Deserialize, Serialize};

pub fn router() -> Router {
    Router::new()
        .route("/login", post(login))
        .route("/register", post(register))
}

async fn login() -> impl IntoResponse { ... }
async fn register() -> impl IntoResponse { ... }
```

## 분리 단계

### 1단계:crate 생성

```bash
# auth crate 생성
mkdir -p auth/src
touch auth/Cargo.toml auth/src/lib.rs
```

### 2단계: workspace members 추가

```toml
# Cargo.toml
[workspace]
members = ["api", "auth", "bbs", "member"]
```

### 3단계: 의존성 정의

각 서비스의 `Cargo.toml`에 필요한 의존성 추가

### 4단계: 기능 이동

기존 `api/src/handlers/`에서 각 서비스의 `lib.rs`로 이동

### 5단계: API Gateway 연결

```rust
// api/src/main.rs
use axum::Router;

mod auth;
mod bbs;
mod member;

let app = Router::new()
    .nest("/auth", auth::router())
    .nest("/bbs", bbs::router())
    .nest("/member", member::router());
```

## 테스트 전략

```bash
# 각 서비스 개별 테스트
cargo test -p auth
cargo test -p bbs

# 통합 테스트
cargo test --workspace
```

## 배포 고려사항

### Docker

```dockerfile
# auth 서비스만 배포
FROM rust:1.75
COPY auth/target/release/auth /usr/local/bin/
CMD ["auth"]
```

### Kubernetes

각 서비스를 별도 pod으로 배포:
- auth pod
- bbs pod
- member pod

## 통신 방식

### 동기 통신 (HTTP)
- API Gateway가 각 서비스 호출
- 각 서비스가 독립적인 포트 실행

### 비동기 통신 (Event)
- 메시지 큐 (Redis, Kafka)
- 이벤트 기반 loosely coupled