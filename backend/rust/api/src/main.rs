use axum::Router;
use axum_folder_router::folder_router;
use sqlx::mysql::MySqlPoolOptions;
use std::sync::Arc;
use tower_http::trace::TraceLayer;
use tracing::{error, info};

mod config;
mod db;
mod error;
mod handlers;
mod middleware;
mod response;
mod timer;
mod types;

use middleware::cors::create_cors_layer;
use types::AppState;

#[folder_router("./src/routes", AppState)]
struct ApiRouter();

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt()
        .with_env_filter(tracing_subscriber::EnvFilter::from_default_env())
        .init();

    info!("Starting Rust Axum API server...");

    let config = match config::Config::new() {
        Ok(cfg) => {
            info!("Configuration loaded successfully");
            Arc::new(cfg)
        }
        Err(e) => {
            error!("Failed to load configuration: {}", e);
            std::process::exit(1);
        }
    };

    let pool = match MySqlPoolOptions::new()
        .max_connections(5)
        .connect(&config.database_url)
        .await
    {
        Ok(pool) => {
            info!("Database connected successfully");
            pool
        }
        Err(e) => {
            error!("Failed to connect to database: {}", e);
            std::process::exit(1);
        }
    };

    let state = AppState { pool, config };

    let addr = format!(
        "{}:{}",
        state.config.server_host, state.config.server_port
    );

    let folder_router = ApiRouter::into_router();

    let app = Router::new()
        .merge(folder_router)
        .layer(create_cors_layer(&state.config))
        .layer(TraceLayer::new_for_http())
        .with_state(state);

    info!("Server listening on http://{}", addr);

    let listener = tokio::net::TcpListener::bind(&addr).await.unwrap();
    axum::serve(listener, app).await.unwrap();
}