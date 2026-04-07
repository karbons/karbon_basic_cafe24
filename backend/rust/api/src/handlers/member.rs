use axum::{
    extract::State,
    http::StatusCode,
    Json,
};
use sqlx::MySqlPool;
use std::time::Instant;

use crate::middleware::jwt::JwtClaims;
use crate::response::ApiResponse;
use crate::types::Member;

pub async fn profile(
    State(pool): State<MySqlPool>,
    claims: JwtClaims,
) -> Result<Json<ApiResponse<Member>>, StatusCode> {
    let start = Instant::now();

    let member: Option<Member> = sqlx::query_as::<_, Member>(
        "SELECT * FROM g5_member WHERE mb_id = ?"
    )
    .bind(&claims.sub)
    .fetch_optional(&pool)
    .await
    .map_err(|_| StatusCode::INTERNAL_SERVER_ERROR)?;

    match member {
        Some(m) => Ok(Json(ApiResponse {
            code: "00000".to_string(),
            data: Some(m),
            msg: "".to_string(),
            time: start.elapsed().as_secs_f64(),
        })),
        None => Ok(Json(ApiResponse {
            code: "00001".to_string(),
            data: None,
            msg: "회원 정보를 찾을 수 없습니다.".to_string(),
            time: start.elapsed().as_secs_f64(),
        })),
    }
}
