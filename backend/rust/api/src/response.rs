//! Response utilities for the API
//!
//! Provides standardized response format matching PHP FAPI:
//! {code, data, msg, time}

use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use serde::Serialize;
use std::time::Instant;

/// Standard API response structure
#[derive(Debug, Serialize)]
pub struct ApiResponse<T> {
    pub code: String,
    pub data: Option<T>,
    pub msg: String,
    pub time: f64,
}

impl<T: Serialize> ApiResponse<T> {
    /// Create a new successful response
    pub fn success(data: T) -> Self {
        Self {
            code: "00000".to_string(),
            data: Some(data),
            msg: "".to_string(),
            time: 0.0,
        }
    }

    /// Create a new successful response with message
    pub fn success_with_msg(data: T, msg: &str) -> Self {
        Self {
            code: "00000".to_string(),
            data: Some(data),
            msg: msg.to_string(),
            time: 0.0,
        }
    }

    /// Set the response time
    pub fn with_time(mut self, start: Instant) -> Self {
        self.time = start.elapsed().as_secs_f64();
        self
    }
}

impl<T: Serialize> IntoResponse for ApiResponse<T> {
    fn into_response(self) -> Response {
        (StatusCode::OK, Json(self)).into_response()
    }
}

/// Create a successful response with data
pub fn api_success<T: Serialize>(data: T) -> ApiResponse<T> {
    ApiResponse::success(data)
}

/// Create a successful response with data and message
pub fn api_success_with_msg<T: Serialize>(data: T, msg: &str) -> ApiResponse<T> {
    ApiResponse::success_with_msg(data, msg)
}

/// Create an error response
pub fn api_error(msg: &str, code: &str) -> ApiResponse<serde_json::Value> {
    ApiResponse {
        code: code.to_string(),
        data: None,
        msg: msg.to_string(),
        time: 0.0,
    }
}

/// Create a "not found" error response
pub fn api_not_found(msg: &str) -> ApiResponse<serde_json::Value> {
    api_error(msg, "00001")
}

/// Create an "unauthorized" error response
pub fn api_unauthorized(msg: &str) -> ApiResponse<serde_json::Value> {
    api_error(msg, "00002")
}

/// Create a "forbidden" error response
pub fn api_forbidden(msg: &str) -> ApiResponse<serde_json::Value> {
    api_error(msg, "00003")
}

/// Create a "token expired" error response
pub fn api_token_expired(msg: &str) -> ApiResponse<serde_json::Value> {
    api_error(msg, "00004")
}
