use axum::{
    body::Body,
    extract::State,
    http::{Request, StatusCode},
    middleware::Next,
    response::Response,
};
use jsonwebtoken::{decode, DecodingKey, Validation, Algorithm};
use serde::{Deserialize, Serialize};
use std::sync::Arc;

use crate::config::Config;
use crate::types::AppState;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct JwtClaims {
    pub sub: String,
    pub exp: i64,
    pub iat: i64,
    pub aud: String,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct RefreshClaims {
    pub id: String,
    pub sub: String,
    pub exp: i64,
    pub iat: i64,
}

pub async fn jwt_middleware(
    State(state): State<AppState>,
    mut request: Request<Body>,
    next: Next,
) -> Result<Response, StatusCode> {
    let auth_header = request
        .headers()
        .get("authorization")
        .and_then(|header| header.to_str().ok());

    let token = if let Some(auth) = auth_header {
        if auth.starts_with("Bearer ") {
            auth.trim_start_matches("Bearer ").to_string()
        } else {
            return Err(StatusCode::UNAUTHORIZED);
        }
    } else {
        return Err(StatusCode::UNAUTHORIZED);
    };

    let validation = Validation::new(Algorithm::HS256);
    let decoding_key = DecodingKey::from_secret(state.config.jwt_access_key.as_bytes());

    match decode::<JwtClaims>(&token, &decoding_key, &validation) {
        Ok(token_data) => {
            request.extensions_mut().insert(token_data.claims);
            Ok(next.run(request).await)
        }
        Err(_) => Err(StatusCode::UNAUTHORIZED),
    }
}

pub async fn optional_jwt_middleware(
    State(state): State<AppState>,
    mut request: Request<Body>,
    next: Next,
) -> Response {
    let auth_header = request
        .headers()
        .get("authorization")
        .and_then(|header| header.to_str().ok());

    if let Some(auth) = auth_header {
        if auth.starts_with("Bearer ") {
            let token = auth.trim_start_matches("Bearer ").to_string();
            let validation = Validation::new(Algorithm::HS256);
            let decoding_key = DecodingKey::from_secret(state.config.jwt_access_key.as_bytes());

            if let Ok(token_data) = decode::<JwtClaims>(&token, &decoding_key, &validation) {
                request.extensions_mut().insert(token_data.claims);
            }
        }
    }

    next.run(request).await
}

pub fn generate_access_token(mb_id: &str, config: &Config) -> String {
    use jsonwebtoken::{encode, EncodingKey, Header};
    use chrono::{Utc, Duration};

    let now = Utc::now();
    let exp = now + Duration::minutes(config.jwt_access_mtime);

    let claims = JwtClaims {
        sub: mb_id.to_string(),
        exp: exp.timestamp(),
        iat: now.timestamp(),
        aud: config.api_base_url.clone(),
    };

    encode(
        &Header::new(Algorithm::HS256),
        &claims,
        &EncodingKey::from_secret(config.jwt_access_key.as_bytes()),
    )
    .unwrap()
}

pub fn generate_refresh_token(mb_id: &str, config: &Config) -> (String, String) {
    use jsonwebtoken::{encode, EncodingKey, Header};
    use chrono::{Utc, Duration};
    use uuid::Uuid;

    let uuid = Uuid::new_v4().to_string();
    let now = Utc::now();
    let exp = now + Duration::days(config.jwt_refresh_date);

    let claims = RefreshClaims {
        id: uuid.clone(),
        sub: mb_id.to_string(),
        exp: exp.timestamp(),
        iat: now.timestamp(),
    };

    let token = encode(
        &Header::new(Algorithm::HS256),
        &claims,
        &EncodingKey::from_secret(config.jwt_refresh_key.as_bytes()),
    )
    .unwrap();

    (token, uuid)
}