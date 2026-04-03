<?php
// GET /api/bbs/search
function GET() {
    global $g5, $member;
    
    $stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
    $sop = isset($_GET['sop']) ? trim($_GET['sop']) : 'and';
    $bo_table = isset($_GET['bo_table']) ? trim($_GET['bo_table']) : '';
    
    if (empty($stx)) {
        json_return(null, 200, '00001', '검색어를 입력해주세요.');
    }
    
    // 검색할 게시판 목록
    $search_tables = [];
    if ($bo_table) {
        $board = sqlx::query("SELECT bo_table FROM {$g5['board_table']} WHERE bo_table = ?", [$bo_table])->fetch_optional();
        if ($board) {
            $search_tables[] = $bo_table;
        }
    } else {
        // 전체 게시판 검색
        $search_tables = sqlx::query("select bo_table from {$g5['board_table']} where bo_use_search = 1")
            ->fetch_all(PDO::FETCH_COLUMN);
    }
    
    $results = [];
    
    foreach ($search_tables as $table) {
        $board = sqlx::query("SELECT * FROM {$g5['board_table']} WHERE bo_table = ?", [$table])->fetch_optional();
        if (!$board) continue;
        
        // 읽기 권한 체크
        if ($member['mb_level'] < $board['bo_read_level']) {
            continue;
        }
        
        $write_table = $g5['write_prefix'] . $table;
        $sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : '';
        $sca = '';
        $where = "where wr_is_comment = 0 " . get_sql_search($sca, $sfl, $stx, $sop);
        
        $sql = "select * from {$write_table} {$where} order by wr_num desc limit 10";
        $rows = sqlx::query($sql)->fetch_all();
        
        foreach ($rows as $row) {
            $results[] = [
                'bo_table' => $table,
                'bo_subject' => $board['bo_subject'],
                'wr_id' => $row['wr_id'],
                'wr_subject' => $row['wr_subject'],
                'wr_name' => $row['wr_name'],
                'wr_datetime' => $row['wr_datetime'],
                'wr_hit' => $row['wr_hit']
            ];
        }
    }
    
    json_return(['results' => $results], 200, '00000');
}
