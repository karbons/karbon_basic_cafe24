<?php
// GET /api/member/point
function GET() {
    global $g5, $member;
    
    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page_rows = 20;
    $from_record = ($page - 1) * $page_rows;
    
    // 포인트 내역 조회
    $rows = sqlx::query("select * from {$g5['point_table']} 
            where mb_id = ?
            order by po_id desc
            limit ?, ?")
        ->bind($member['mb_id'])
        ->bind((int)$from_record)
        ->bind((int)$page_rows)
        ->fetch_all();
    
    $points = [];
    foreach ($rows as $row) {
        $points[] = [
            'po_id' => $row['po_id'],
            'po_content' => $row['po_content'],
            'po_point' => $row['po_point'],
            'po_use_point' => $row['po_use_point'],
            'po_datetime' => $row['po_datetime'],
            'po_expired' => $row['po_expired'],
            'po_expire_date' => $row['po_expire_date']
        ];
    }
    
    // 전체 포인트
    $total_point = $member['mb_point'];
    
    json_return([
        'total_point' => $total_point,
        'points' => $points,
        'pagination' => [
            'current_page' => $page,
            'page_rows' => $page_rows
        ]
    ], 200, '00000');
}

