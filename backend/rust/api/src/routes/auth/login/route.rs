use axum::{
    extract::State,
    http::StatusCode,
    Json,
    response::IntoResponse,
};
use crate::types::{AppState, Member};
use crate::response::ApiResponse;
use crate::middleware::jwt::generate_access_token;

#[derive(serde::Deserialize)]
pub struct LoginRequest {
    pub mb_id: String,
    pub mb_password: String,
}

#[derive(serde::Serialize)]
pub struct MemberInfo {
    pub mb_id: String,
    pub mb_name: String,
    pub mb_nick: String,
    pub mb_level: i8,
    pub mb_point: i32,
    pub mb_memo_cnt: i32,
    pub mb_scrap_cnt: i32,
}

#[derive(serde::Serialize)]
pub struct LoginResponse {
    pub mb: MemberInfo,
    pub access_token: String,
    pub csrf_token: String,
    pub device_id: String,
}

pub async fn post(
    State(state): State<AppState>,
    Json(req): Json<LoginRequest>,
) -> impl IntoResponse {
    let member: Option<Member> = sqlx::query_as::<_, Member>(
        "SELECT * FROM g5_member WHERE mb_id = ?"
    )
    .bind(&req.mb_id)
    .fetch_optional(&state.pool)
    .await.ok().flatten();

    let member = match member {
        Some(m) => m,
        None => {
            return Json(ApiResponse::<LoginResponse> {
                code: "00002".to_string(),
                data: None,
                msg: "가입된 회원아이디가 아니거나 비밀번호가 틀립니다.".to_string(),
                time: 0.0,
            }).into_response();
        }
    };

    use argon2::{Argon2, PasswordHash, PasswordVerifier};
    let parsed_hash = PasswordHash::new(&member.mb_password).ok();
    let valid = parsed_hash.and_then(|h| Argon2::default().verify_password(req.mb_password.as_bytes(), &h).ok()).is_some();

    if !valid {
        return Json(ApiResponse::<LoginResponse> {
            code: "00002".to_string(),
            data: None,
            msg: "가입된 회원아이디가 아니거나 비밀번호가 틀립니다.".to_string(),
            time: 0.0,
        }).into_response();
    }

    let access_token = generate_access_token(&member.mb_id, &state.config);
    let device_id = uuid::Uuid::new_v4().to_string();
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

    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(response),
        msg: "로그인 성공".to_string(),
        time: 0.0,
    }).into_response()
}