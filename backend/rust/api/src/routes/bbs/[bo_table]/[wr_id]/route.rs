use axum::{extract::{State, Path}, response::IntoResponse, Json};

use crate::types::AppState;
use crate::response::ApiResponse;
use crate::timer::Timer;

pub async fn get(
    State(_state): State<AppState>,
    Path((bo_table, wr_id)): Path<(String, String)>,
) -> impl IntoResponse {
    let timer = Timer::new();
    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({"bo_table": bo_table, "wr_id": wr_id, "message": "bbs detail"})),
        msg: "".to_string(),
        time: timer.elapsed(),
    })
}

pub async fn put(
    State(_state): State<AppState>,
    Path((bo_table, wr_id)): Path<(String, String)>,
) -> impl IntoResponse {
    let timer = Timer::new();
    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({"bo_table": bo_table, "wr_id": wr_id, "message": "bbs update"})),
        msg: "".to_string(),
        time: timer.elapsed(),
    })
}

pub async fn delete(
    State(_state): State<AppState>,
    Path((bo_table, wr_id)): Path<(String, String)>,
) -> impl IntoResponse {
    let timer = Timer::new();
    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({"bo_table": bo_table, "wr_id": wr_id, "message": "bbs delete"})),
        msg: "".to_string(),
        time: timer.elapsed(),
    })
}