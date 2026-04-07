use axum::{extract::{State, Path}, response::IntoResponse, Json};

use crate::types::{AppState, Write};
use crate::response::ApiResponse;
use crate::timer::Timer;

pub async fn get(
    State(state): State<AppState>,
    Path(bo_table): Path<String>,
) -> impl IntoResponse {
    let timer = Timer::new();
    let table_name = format!("g5_write_{}", bo_table);
    
    let writes = sqlx::query_as::<_, Write>(
        format!("SELECT wr_id, wr_num, wr_reply, wr_parent, wr_is_comment, wr_comment, wr_comment_reply, ca_name, wr_option, wr_subject, wr_content, wr_link1, wr_link2, wr_link1_hit, wr_link2_hit, wr_hit, wr_good, wr_nogood, mb_id, wr_name, wr_password, wr_email, wr_homepage, wr_datetime, wr_ip, wr_1, wr_2, wr_3, wr_4, wr_5, wr_6, wr_7, wr_8, wr_9, wr_10 FROM {} WHERE wr_is_comment = 0 ORDER BY wr_num DESC LIMIT 100", table_name).as_str()
    )
    .fetch_all(&state.pool)
    .await;

    match writes {
        Ok(writes) => Json(ApiResponse::<Vec<Write>> {
            code: "00000".to_string(),
            data: Some(writes),
            msg: "".to_string(),
            time: timer.elapsed(),
        }).into_response(),
        Err(e) => Json(ApiResponse::<Vec<Write>> {
            code: "00001".to_string(),
            data: None,
            msg: format!("DB 오류: {}", e),
            time: timer.elapsed(),
        }).into_response()
    }
}

pub async fn post(
    State(_state): State<AppState>,
    Path(_bo_table): Path<String>,
) -> impl IntoResponse {
    let timer = Timer::new();
    Json(ApiResponse::<()> {
        code: "00001".to_string(),
        data: None,
        msg: "게시글 작성 구현 필요".to_string(),
        time: timer.elapsed(),
    })
}