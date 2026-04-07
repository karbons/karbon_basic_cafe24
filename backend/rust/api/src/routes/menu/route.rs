use axum::{extract::State, response::IntoResponse, Json};

use crate::types::{AppState, Menu};
use crate::response::ApiResponse;
use crate::timer::Timer;

pub async fn get(State(state): State<AppState>) -> impl IntoResponse {
    let timer = Timer::new();
    let menus = sqlx::query_as::<_, Menu>(
        "SELECT * FROM g5_menu WHERE me_use = 1 ORDER BY me_order"
    )
    .fetch_all(&state.pool)
    .await;

    match menus {
        Ok(menus) => Json(ApiResponse::<Vec<Menu>> {
            code: "00000".to_string(),
            data: Some(menus),
            msg: "".to_string(),
            time: timer.elapsed(),
        }).into_response(),
        Err(e) => Json(ApiResponse::<Vec<Menu>> {
            code: "00001".to_string(),
            data: None,
            msg: format!("DB 오류: {}", e),
            time: timer.elapsed(),
        }).into_response()
    }
}