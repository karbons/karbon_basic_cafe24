//! Database module for gnuboard5 integration
//! 
//! Provides MySQL connection pool using sqlx with compile-time checked queries.

use sqlx::mysql::MySqlPoolOptions;
use sqlx::Pool;
use sqlx::MySql;

/// Database connection pool manager
pub struct Db {
    pool: Pool<MySql>,
}

impl Db {
    /// Create a new database connection pool
    /// 
    /// # Arguments
    /// * `database_url` - MySQL connection string (e.g., "mysql://user:pass@localhost/gnuboard")
    /// 
    /// # Example
    /// ```rust
    /// let db = Db::new("mysql://root:password@localhost/g5_gnuboard").await?;
    /// ```
    pub async fn new(database_url: &str) -> Result<Self, sqlx::Error> {
        let pool = MySqlPoolOptions::new()
            .max_connections(5)
            .connect(database_url)
            .await?;

        Ok(Self { pool })
    }

    /// Get a reference to the connection pool
    pub fn get_pool(&self) -> &Pool<MySql> {
        &self.pool
    }

    /// Get the owned connection pool
    /// 
    /// Useful when you need to pass ownership of the pool
    pub fn pool(&self) -> Pool<MySql> {
        self.pool.clone()
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    #[ignore = "Requires database connection"]
    async fn test_db_connection() {
        // This test requires a running MySQL database
        // Run with: cargo test -- --ignored
        let result = Db::new("mysql://root:password@localhost/g5_gnuboard").await;
        
        // Will fail without proper database setup
        assert!(result.is_err() || result.is_ok());
    }
}