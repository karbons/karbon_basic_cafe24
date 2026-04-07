use serde::Deserialize;
use std::env;

#[derive(Debug, Deserialize)]
pub struct Config {
    pub database_url: String,
    pub jwt_access_key: String,
    pub jwt_refresh_key: String,
    pub jwt_access_mtime: i64,
    pub jwt_refresh_date: i64,
    pub cors_allowed_origins: Vec<String>,
    pub server_host: String,
    pub server_port: u16,
    pub api_base_url: String,
    pub upload_storage: String,
    pub aws_s3_url: String,
}

impl Config {
    pub fn new() -> Result<Self, env::VarError> {
        let env_local = std::path::Path::new(".env.local");
        let env_file = std::path::Path::new(".env");

        if env_local.exists() {
            dotenvy::from_filename(env_local).ok();
        } else if env_file.exists() {
            dotenvy::from_filename(env_file).ok();
        }

        let database_url = env::var("DATABASE_URL")?;
        let jwt_access_key = env::var("JWT_ACCESS_KEY")?;
        let jwt_refresh_key = env::var("JWT_REFRESH_KEY")?;
        let jwt_access_mtime: i64 = env::var("JWT_ACCESS_MTIME")
            .unwrap_or_else(|_| "15".to_string())
            .parse()
            .unwrap_or(15);
        let jwt_refresh_date: i64 = env::var("JWT_REFRESH_DATE")
            .unwrap_or_else(|_| "30".to_string())
            .parse()
            .unwrap_or(30);

        let cors_str = env::var("CORS_ALLOWED_ORIGINS")
            .unwrap_or_else(|_| "http://localhost:5173,http://localhost:3000".to_string());
        let cors_allowed_origins: Vec<String> =
            cors_str.split(',').map(|s| s.trim().to_string()).collect();

        let server_host = env::var("SERVER_HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
        let server_port: u16 = env::var("SERVER_PORT")
            .unwrap_or_else(|_| "8080".to_string())
            .parse()
            .unwrap_or(8080);
        let api_base_url =
            env::var("API_BASE_URL").unwrap_or_else(|_| "http://localhost:8080".to_string());
        let upload_storage = env::var("UPLOAD_STORAGE").unwrap_or_else(|_| "local".to_string());
        let aws_s3_url = env::var("AWS_S3_URL").unwrap_or_default();

        Ok(Config {
            database_url,
            jwt_access_key,
            jwt_refresh_key,
            jwt_access_mtime,
            jwt_refresh_date,
            cors_allowed_origins,
            server_host,
            server_port,
            api_base_url,
            upload_storage,
            aws_s3_url,
        })
    }
}
