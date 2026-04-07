use axum::{
    extract::State,
    Json,
    response::IntoResponse,
};
use crate::types::AppState;
use crate::response::ApiResponse;
use crate::timer::Timer;

#[derive(serde::Deserialize)]
pub struct RegisterRequest {
    pub mb_id: String,
    pub mb_password: String,
    pub mb_name: String,
    pub mb_nick: Option<String>,
    pub mb_email: Option<String>,
}

pub async fn post(
    State(state): State<AppState>,
    Json(req): Json<RegisterRequest>,
) -> impl IntoResponse {
    let timer = Timer::new();
    let existing: Option<(String,)> = sqlx::query_as(
        "SELECT mb_id FROM g5_member WHERE mb_id = ?"
    )
    .bind(&req.mb_id)
    .fetch_optional(&state.pool)
    .await.ok().flatten();

    if existing.is_some() {
        return Json(ApiResponse::<serde_json::Value> {
            code: "00001".to_string(),
            data: None,
            msg: "이미 존재하는 회원아이디입니다.".to_string(),
            time: timer.elapsed(),
        }).into_response();
    }

    use argon2::{Argon2, password_hash::{rand_core::OsRng, SaltString}, PasswordHasher};
    let salt = SaltString::generate(&mut OsRng);
    let argon2 = Argon2::default();
    let password_hash = match argon2.hash_password(req.mb_password.as_bytes(), &salt) {
        Ok(h) => h.to_string(),
        Err(_) => {
            return Json(ApiResponse::<serde_json::Value> {
                code: "00001".to_string(),
                data: None,
                msg: "비밀번호 해시 생성 실패".to_string(),
                time: timer.elapsed(),
            }).into_response();
        }
    };

    let result = sqlx::query(
        "INSERT INTO g5_member (mb_id, mb_password, mb_name, mb_nick, mb_email, mb_level, mb_point, mb_datetime) VALUES (?, ?, ?, ?, ?, 2, 0, NOW())"
    )
    .bind(&req.mb_id)
    .bind(&password_hash)
    .bind(&req.mb_name)
    .bind(req.mb_nick.as_ref().unwrap_or(&req.mb_name))
    .bind(&req.mb_email)
    .execute(&state.pool)
    .await;

    match result {
        Ok(_) => Json(ApiResponse::<serde_json::Value> {
            code: "00000".to_string(),
            data: Some(serde_json::json!({})),
            msg: "회원가입이 완료되었습니다.".to_string(),
            time: timer.elapsed(),
        }).into_response(),
        Err(e) => Json(ApiResponse::<serde_json::Value> {
            code: "00001".to_string(),
            data: None,
            msg: format!("회원가입 실패: {}", e),
            time: timer.elapsed(),
        }).into_response(),
    }
}
