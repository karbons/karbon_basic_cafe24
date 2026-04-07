use axum::{extract::{State, Path}, response::IntoResponse, Json};

use crate::types::{AppState, Write};
use crate::response::ApiResponse;
use crate::timer::Timer;

pub async fn get(
    State(state): State<AppState>,
    Path(bo_table): Path<String>,
) -> impl IntoResponse {
    let timer = Timer::new();
    let writes: Vec<Write> = sqlx::query_as::<_, Write>(
        "SELECT * FROM g5_write_? ORDER BY wr_id DESC LIMIT 20"
    )
    .bind(&bo_table)
    .fetch_all(&state.pool)
    .await.unwrap_or_default();

    Json(ApiResponse {
        code: "00000".to_string(),
        data: Some(writes),
        msg: "".to_string(),
        time: timer.elapsed(),
    }).into_response()
}