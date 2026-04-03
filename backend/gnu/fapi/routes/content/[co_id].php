<?php
// GET /api/content/{co_id}
function GET($co_id)
{
    global $g5;

    // 내용관리 조회
    $content = sqlx::query("select * from {$g5['content_table']} where co_id = ?", [$co_id])
        ->fetch_optional();

    if (!$content || !$content['co_id']) {
        json_return(null, 404, '00001', '내용을 찾을 수 없습니다.');
    }

    json_return([
        'co_id' => $content['co_id'],
        'co_subject' => $content['co_subject'],
        'co_content' => $content['co_content'],
        'co_mobile_content' => $content['co_mobile_content'],
    ], 200, '00000');
}

