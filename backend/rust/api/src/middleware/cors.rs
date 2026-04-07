use axum::{
    http::{HeaderValue, Method},
    response::Response,
};
use tower_http::cors::{Any, CorsLayer};

use crate::config::Config;
use std::sync::Arc;

pub fn create_cors_layer(config: &Arc<Config>) -> CorsLayer {
    let origins: Vec<HeaderValue> = config
        .cors_allowed_origins
        .iter()
        .filter_map(|origin| origin.parse().ok())
        .collect();

    CorsLayer::new()
        .allow_origin(origins)
        .allow_methods([
            Method::GET,
            Method::POST,
            Method::PUT,
            Method::DELETE,
            Method::OPTIONS,
        ])
        .allow_headers([
            "content-type".parse().unwrap(),
            "x-requested-with".parse().unwrap(),
            "authorization".parse().unwrap(),
        ])
        .allow_credentials(true)
        .max_age(std::time::Duration::from_secs(86400))
}
