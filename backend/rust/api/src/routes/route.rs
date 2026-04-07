use axum::{response::IntoResponse, Json};

use crate::response::ApiResponse;

pub async fn get() -> impl IntoResponse {
    let response = ApiResponse {
        code: "00000".to_string(),
        data: Some(serde_json::json!({"status": "ok", "message": "Rust API Running"})),
        msg: "".to_string(),
        time: 0.0,
    };
    
    Json(response)
}