<?php
// GET /api/latest/{bo_table}
function GET($bo_table) {
    global $g5, $member;
    
    $board = get_board_db($bo_table, true);
    
    if (!$board || !$board['bo_table']) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }
    
    // 읽기 권한 체크
    if ($member['mb_level'] < $board['bo_read_level']) {
        json_return(null, 403, '00003', '게시판을 읽을 권한이 없습니다.');
    }
    
    $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
    $write_table = $g5['write_prefix'] . $bo_table;
    
    // 최신글 조회
    $sql = "select * from {$write_table} 
            where wr_is_comment = 0 
            order by wr_num desc 
            limit ?";
    $result = sqlx::query($sql)->bind($rows)->fetch_all();
    
    $list = [];
    foreach ($result as $row) {
        $list[] = [
            'wr_id' => $row['wr_id'],
            'wr_subject' => $row['wr_subject'],
            'wr_name' => $row['wr_name'],
            'wr_datetime' => $row['wr_datetime'],
            'wr_hit' => $row['wr_hit']
        ];
    }
    
    json_return([
        'bo_table' => $bo_table,
        'bo_subject' => $board['bo_subject'],
        'list' => $list
    ], 200, '00000');
}

