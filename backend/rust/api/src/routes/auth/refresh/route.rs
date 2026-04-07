use axum::{extract::State, response::IntoResponse, Json};

use crate::types::AppState;
use crate::response::ApiResponse;
use crate::timer::Timer;

pub async fn post(State(_state): State<AppState>) -> impl IntoResponse {
    let timer = Timer::new();
    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({"message": "refresh endpoint"})),
        msg: "".to_string(),
        time: timer.elapsed(),
    })
}