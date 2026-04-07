use argon2::{Argon2, PasswordHash, PasswordVerifier};
use axum::{
    extract::State,
    http::StatusCode,
    Json,
};
use serde::{Deserialize, Serialize};
use std::time::Instant;

use crate::middleware::jwt::{generate_access_token, generate_refresh_token};
use crate::response::ApiResponse;
use crate::types::{AppState, Member};

#[derive(Debug, Deserialize)]
pub struct LoginRequest {
    pub mb_id: String,
    pub mb_password: String,
    pub fcm_token: Option<String>,
    pub device_model: Option<String>,
    pub os_version: Option<String>,
}

#[derive(Debug, Serialize)]
pub struct MemberInfo {
    pub mb_id: String,
    pub mb_name: String,
    pub mb_nick: String,
    pub mb_level: i8,
    pub mb_point: i32,
    pub mb_memo_cnt: i32,
    pub mb_scrap_cnt: i32,
}

#[derive(Debug, Serialize)]
pub struct LoginResponse {
    pub mb: MemberInfo,
    pub access_token: String,
    pub csrf_token: String,
    pub device_id: String,
}

#[derive(Debug, Deserialize)]
pub struct RegisterRequest {
    pub mb_id: String,
    pub mb_password: String,
    pub mb_name: String,
    pub mb_nick: String,
    pub mb_email: Option<String>,
}

pub async fn login(
    State(state): State<AppState>,
    Json(req): Json<LoginRequest>,
) -> Result<Json<ApiResponse<LoginResponse>>, StatusCode> {
    let start = Instant::now();

    let member: Option<Member> = sqlx::query_as::<_, Member>(
        "SELECT * FROM g5_member WHERE mb_id = ?"
    )
    .bind(&req.mb_id)
    .fetch_optional(&state.pool)
    .await
    .map_err(|_| StatusCode::INTERNAL_SERVER_ERROR)?;

    let member = match member {
        Some(m) => m,
        None => {
            return Ok(Json(ApiResponse {
                code: "00002".to_string(),
                data: None,
                msg: "가입된 회원아이디가 아니거나 비밀번호가 틀립니다.".to_string(),
                time: start.elapsed().as_secs_f64(),
            }));
        }
    };

    let parsed_hash = PasswordHash::new(&member.mb_password)
        .map_err(|_| StatusCode::INTERNAL_SERVER_ERROR)?;
    
    let argon2 = Argon2::default();
    if argon2.verify_password(req.mb_password.as_bytes(), &parsed_hash).is_err() {
        return Ok(Json(ApiResponse {
            code: "00002".to_string(),
            data: None,
            msg: "가입된 회원아이디가 아니거나 비밀번호가 틀립니다.".to_string(),
            time: start.elapsed().as_secs_f64(),
        }));
    }

    let access_token = generate_access_token(&member.mb_id, &state.config);
    let (refresh_token, device_id) = generate_refresh_token(&member.mb_id, &state.config);

    let csrf_token = uuid::Uuid::new_v4().to_string();

    let response = LoginResponse {
        mb: MemberInfo {
            mb_id: member.mb_id,
            mb_name: member.mb_name,
            mb_nick: member.mb_nick,
            mb_level: member.mb_level,
            mb_point: member.mb_point,
            mb_memo_cnt: 0,
            mb_scrap_cnt: 0,
        },
        access_token,
        csrf_token,
        device_id,
    };

    Ok(Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(response),
        msg: "로그인 성공".to_string(),
        time: start.elapsed().as_secs_f64(),
    }))
}

pub async fn logout(
    State(_state): State<AppState>,
) -> Result<Json<ApiResponse<serde_json::Value>>, StatusCode> {
    let start = Instant::now();

    Ok(Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({})),
        msg: "로그아웃 되었습니다.".to_string(),
        time: start.elapsed().as_secs_f64(),
    }))
}

pub async fn refresh(
    State(_state): State<AppState>,
) -> Result<Json<ApiResponse<serde_json::Value>>, StatusCode> {
    let start = Instant::now();

    let response = serde_json::json!({
        "access_token": "new_token_placeholder"
    });

    Ok(Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(response),
        msg: "토큰이 갱신되었습니다.".to_string(),
        time: start.elapsed().as_secs_f64(),
    }))
}

pub async fn register(
    State(state): State<AppState>,
    Json(req): Json<RegisterRequest>,
) -> Result<Json<ApiResponse<serde_json::Value>>, StatusCode> {
    let start = Instant::now();

    let existing: Option<(String,)> = sqlx::query_as("SELECT mb_id FROM g5_member WHERE mb_id = ?")
        .bind(&req.mb_id)
        .fetch_optional(&state.pool)
        .await
        .map_err(|_| StatusCode::INTERNAL_SERVER_ERROR)?;

    if existing.is_some() {
        return Ok(Json(ApiResponse {
            code: "00001".to_string(),
            data: None,
            msg: "이미 존재하는 회원아이디입니다.".to_string(),
            time: start.elapsed().as_secs_f64(),
        }));
    }

    use argon2::{password_hash::{rand_core::OsRng, SaltString}, PasswordHasher};
    
    let salt = SaltString::generate(&mut OsRng);
    let argon2 = Argon2::default();
    let password_hash = argon2
        .hash_password(req.mb_password.as_bytes(), &salt)
        .map_err(|_| StatusCode::INTERNAL_SERVER_ERROR)?
        .to_string();

    sqlx::query(
        "INSERT INTO g5_member (mb_id, mb_password, mb_name, mb_nick, mb_email, mb_level, mb_point, mb_datetime) VALUES (?, ?, ?, ?, ?, 2, 0, NOW())"
    )
    .bind(&req.mb_id)
    .bind(&password_hash)
    .bind(&req.mb_name)
    .bind(&req.mb_nick)
    .bind(&req.mb_email)
    .execute(&state.pool)
    .await
    .map_err(|_| StatusCode::INTERNAL_SERVER_ERROR)?;

    Ok(Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({})),
        msg: "회원가입이 완료되었습니다.".to_string(),
        time: start.elapsed().as_secs_f64(),
    }))
}
