<?php
// GET /api/boards
function GET()
{
    global $g5;

    // 게시판 목록 조회
    $rows = sqlx::query("SELECT bo_table, bo_subject, bo_read_level 
            FROM {$g5['board_table']} 
            WHERE bo_read_level <= 1 
            ORDER BY bo_order, bo_table")
        ->fetch_all();

    $boards = [];
    foreach ($rows as $row) {
        $boards[] = [
            'bo_table' => $row['bo_table'],
            'bo_subject' => $row['bo_subject']
        ];
    }

    json_return(['boards' => $boards], 200, '00000');
}
