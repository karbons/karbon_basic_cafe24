//! Error types for the API
//!
//! Defines all API errors with corresponding HTTP status codes and error codes.
//! Error codes follow PHP FAPI convention:
//! - 00000: Success
//! - 00001: Not found / General error
//! - 00002: Unauthorized
//! - 00003: Forbidden
//! - 00004: Token expired

use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use serde::Serialize;
use std::time::Instant;

/// API error types
#[derive(Debug, thiserror::Error)]
pub enum ApiError {
    #[error("Not found: {0}")]
    NotFound(String),

    #[error("Unauthorized: {0}")]
    Unauthorized(String),

    #[error("Forbidden: {0}")]
    Forbidden(String),

    #[error("Token expired: {0}")]
    TokenExpired(String),

    #[error("Bad request: {0}")]
    BadRequest(String),

    #[error("Internal error: {0}")]
    Internal(String),
}

impl ApiError {
    /// Get the error code for this error
    pub fn code(&self) -> &'static str {
        match self {
            ApiError::NotFound(_) => "00001",
            ApiError::Unauthorized(_) => "00002",
            ApiError::Forbidden(_) => "00003",
            ApiError::TokenExpired(_) => "00004",
            ApiError::BadRequest(_) => "00001",
            ApiError::Internal(_) => "00001",
        }
    }

    /// Get the HTTP status code for this error
    pub fn status_code(&self) -> StatusCode {
        match self {
            ApiError::NotFound(_) => StatusCode::NOT_FOUND,
            ApiError::Unauthorized(_) => StatusCode::UNAUTHORIZED,
            ApiError::Forbidden(_) => StatusCode::FORBIDDEN,
            ApiError::TokenExpired(_) => StatusCode::UNAUTHORIZED,
            ApiError::BadRequest(_) => StatusCode::BAD_REQUEST,
            ApiError::Internal(_) => StatusCode::INTERNAL_SERVER_ERROR,
        }
    }

    /// Get the error message
    pub fn message(&self) -> String {
        self.to_string()
    }
}

impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let code = self.code();
        let msg = self.message();
        let status = self.status_code();

        let body = ErrorBody {
            code: code.to_string(),
            data: None::<serde_json::Value>,
            msg,
            time: 0.0,
        };

        (status, Json(body)).into_response()
    }
}

/// Error response body structure
#[derive(Debug, Serialize)]
struct ErrorBody {
    pub code: String,
    pub data: Option<serde_json::Value>,
    pub msg: String,
    pub time: f64,
}

/// Convert sqlx errors to ApiError
impl From<sqlx::Error> for ApiError {
    fn from(err: sqlx::Error) -> Self {
        match err {
            sqlx::Error::RowNotFound => ApiError::NotFound("Record not found".to_string()),
            _ => ApiError::Internal(format!("Database error: {}", err)),
        }
    }
}

/// Convert config errors to ApiError
impl From<config::ConfigError> for ApiError {
    fn from(err: config::ConfigError) -> Self {
        ApiError::Internal(format!("Configuration error: {}", err))
    }
}

/// Convert JWT errors to ApiError
impl From<jsonwebtoken::errors::Error> for ApiError {
    fn from(err: jsonwebtoken::errors::Error) -> Self {
        match err.kind() {
            jsonwebtoken::errors::ErrorKind::ExpiredSignature => {
                ApiError::TokenExpired("Token has expired".to_string())
            }
            _ => ApiError::Unauthorized(format!("Invalid token: {}", err)),
        }
    }
}
