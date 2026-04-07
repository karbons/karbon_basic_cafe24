use axum::{extract::State, response::IntoResponse, Json};

use crate::types::AppState;
use crate::response::ApiResponse;

#[derive(sqlx::FromRow, serde::Serialize)]
pub struct Popup {
    pub pp_id: i32,
    pub pp_subject: String,
    pub pp_content: String,
    pub pp_width: i32,
    pub pp_height: i32,
    pub pp_left: i32,
    pub pp_top: i32,
    pub pp_begin_datetime: Option<String>,
    pub pp_end_datetime: Option<String>,
    pub pp_use: i32,
}

pub async fn get(State(state): State<AppState>) -> impl IntoResponse {
    let popups: Vec<Popup> = sqlx::query_as::<_, Popup>(
        "SELECT pp_id, pp_subject, pp_content, pp_width, pp_height, pp_left, pp_top, pp_begin_datetime, pp_end_datetime, pp_use 
         FROM g5_popup WHERE pp_use = 1 
         AND (pp_begin_datetime IS NULL OR pp_begin_datetime <= NOW()) 
         AND (pp_end_datetime IS NULL OR pp_end_datetime >= NOW())"
    )
    .fetch_all(&state.pool)
    .await.unwrap_or_default();

    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(popups),
        msg: "".to_string(),
        time: 0.0,
    }).into_response()
}