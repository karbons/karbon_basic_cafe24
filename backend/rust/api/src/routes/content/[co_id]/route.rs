use axum::{extract::{State, Path}, response::IntoResponse, Json};

use crate::types::AppState;
use crate::response::ApiResponse;
use crate::timer::Timer;

#[derive(sqlx::FromRow, serde::Serialize)]
pub struct Content {
    pub co_id: i32,
    pub co_subject: String,
    pub co_content: String,
    pub co_mobile_content: Option<String>,
    pub co_skin: Option<String>,
    pub co_mobile_skin: Option<String>,
    pub co_hit: i32,
    pub co_datetime: String,
    pub co_update_datetime: Option<String>,
}

pub async fn get(
    State(state): State<AppState>,
    Path(co_id): Path<String>,
) -> impl IntoResponse {
    let timer = Timer::new();
    let content: Option<Content> = sqlx::query_as::<_, Content>(
        "SELECT co_id, co_subject, co_content, co_mobile_content, co_skin, co_mobile_skin, co_hit, co_datetime, co_update_datetime 
         FROM g5_content WHERE co_id = ? OR co_name = ?"
    )
    .bind(&co_id)
    .bind(&co_id)
    .fetch_optional(&state.pool)
    .await.unwrap_or_default();

    match content {
        Some(c) => Json(ApiResponse {
            code: "00000".to_string(),
            data: Some(c),
            msg: "".to_string(),
            time: timer.elapsed(),
        }).into_response(),
        None => Json(ApiResponse::<Content> {
            code: "00001".to_string(),
            data: None,
            msg: "존재하지 않는 컨텐츠입니다.".to_string(),
            time: timer.elapsed(),
        }).into_response(),
    }
}