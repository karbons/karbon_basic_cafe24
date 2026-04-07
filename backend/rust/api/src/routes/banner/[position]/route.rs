use axum::{extract::{State, Path}, response::IntoResponse, Json};

use crate::types::AppState;
use crate::response::ApiResponse;

#[derive(sqlx::FromRow, serde::Serialize)]
pub struct Banner {
    pub bn_id: i32,
    pub bn_name: String,
    pub bn_image: String,
    pub bn_url: String,
    pub bn_position: String,
    pub bn_begin_datetime: Option<String>,
    pub bn_end_datetime: Option<String>,
    pub bn_order: i32,
    pub bn_use: i32,
}

pub async fn get(
    State(state): State<AppState>,
    Path(position): Path<String>,
) -> impl IntoResponse {
    let banners: Vec<Banner> = sqlx::query_as::<_, Banner>(
        "SELECT bn_id, bn_name, bn_image, bn_url, bn_position, bn_begin_datetime, bn_end_datetime, bn_order, bn_use 
         FROM g5_banner WHERE bn_position = ? AND bn_use = 1 
         AND (bn_begin_datetime IS NULL OR bn_begin_datetime <= NOW()) 
         AND (bn_end_datetime IS NULL OR bn_end_datetime >= NOW()) 
         ORDER BY bn_order"
    )
    .bind(&position)
    .fetch_all(&state.pool)
    .await.unwrap_or_default();

    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(banners),
        msg: "".to_string(),
        time: 0.0,
    }).into_response()
}