<?php
// GET /api/member/scrap
function GET()
{
    global $g5, $member;

    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }

    $rows = sqlx::query("select * from {$g5['scrap_table']} 
            where mb_id = ?
            order by ms_id desc")
        ->bind($member['mb_id'])
        ->fetch_all();
    
    $scraps = [];
    foreach ($rows as $row) {
        $scraps[] = [
            'ms_id' => $row['ms_id'],
            'bo_table' => $row['bo_table'],
            'wr_id' => $row['wr_id'],
            'ms_datetime' => $row['ms_datetime']
        ];
    }

    json_return(['scraps' => $scraps], 200, '00000');
}

// POST /api/member/scrap
function POST()
{
    global $g5, $member;

    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $bo_table = isset($data['bo_table']) ? trim($data['bo_table']) : '';
    $wr_id = isset($data['wr_id']) ? (int) $data['wr_id'] : 0;

    if (!$bo_table || !$wr_id) {
        json_return(null, 400, '00001', '게시판과 글 ID가 필요합니다.');
    }

    // 게시판 존재 확인
    $board = get_board_db($bo_table, true);
    if (!$board['bo_table']) {
        json_return(null, 404, '00001', '존재하지 않는 게시판입니다.');
    }

    // 글 존재 확인
    $write_table = $g5['write_prefix'] . $bo_table;
    $write = sqlx::query("SELECT wr_id FROM {$write_table} WHERE wr_id = ?")
        ->bind($wr_id)
        ->fetch_optional();
    if (!$write) {
        json_return(null, 404, '00001', '존재하지 않는 글입니다.');
    }

    // 중복 스크랩 체크
    $exists = sqlx::query("SELECT ms_id FROM {$g5['scrap_table']} 
                         WHERE mb_id = ?
                         AND bo_table = ?
                         AND wr_id = ?")
        ->bind($member['mb_id'])
        ->bind($bo_table)
        ->bind($wr_id)
        ->fetch_optional();
    if ($exists) {
        json_return(null, 400, '00001', '이미 스크랩한 글입니다.');
    }

    // 스크랩 등록 & 회원 스크랩 수 증가 (트랜잭션 처리)
    $ms_id = sqlx::transaction(function() use ($g5, $member, $bo_table, $wr_id) {
        $id = sqlx::query("INSERT INTO {$g5['scrap_table']} SET
                   mb_id = ?,
                   bo_table = ?,
                   wr_id = ?,
                   ms_datetime = ?")
            ->bind($member['mb_id'])
            ->bind($bo_table)
            ->bind($wr_id)
            ->bind(G5_TIME_YMDHIS)
            ->execute_insert_id();

        // 회원 스크랩 수 증가
        sqlx::query("UPDATE {$g5['member_table']} SET mb_scrap_cnt = mb_scrap_cnt + 1 
                   WHERE mb_id = ?")
            ->bind($member['mb_id'])
            ->execute();
        
        return $id;
    });

    json_return(['ms_id' => $ms_id], 200, '00000', '스크랩되었습니다.');
}

// DELETE /api/member/scrap?ms_id={ms_id} or ?bo_table={bo_table}&wr_id={wr_id}
function DELETE()
{
    global $g5, $member;

    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }

    $ms_id = isset($_GET['ms_id']) ? (int) $_GET['ms_id'] : 0;
    $bo_table = isset($_GET['bo_table']) ? trim($_GET['bo_table']) : '';
    $wr_id = isset($_GET['wr_id']) ? (int) $_GET['wr_id'] : 0;

    $where = "";
    $params = [];
    if ($ms_id) {
        $where = "ms_id = ?";
        $params[] = $ms_id;
    } elseif ($bo_table && $wr_id) {
        $where = "bo_table = ? AND wr_id = ?";
        $params[] = $bo_table;
        $params[] = $wr_id;
    } else {
        json_return(null, 400, '00001', '스크랩 ID 또는 게시판+글 ID가 필요합니다.');
    }

    // 스크랩 존재 확인
    $query = sqlx::query("SELECT ms_id FROM {$g5['scrap_table']} 
                         WHERE mb_id = ?
                         AND {$where}")
        ->bind($member['mb_id']);
    foreach ($params as $p) {
        $query->bind($p);
    }
    $scrap = $query->fetch_optional();

    if (!$scrap) {
        json_return(null, 404, '00001', '스크랩을 찾을 수 없습니다.');
    }

    // 스크랩 삭제 & 회원 스크랩 수 감소 (트랜잭션 처리)
    sqlx::transaction(function() use ($g5, $member, $where, $params) {
        $q = sqlx::query("DELETE FROM {$g5['scrap_table']} 
                   WHERE mb_id = ?
                   AND {$where}")
            ->bind($member['mb_id']);
        foreach ($params as $p) {
            $q->bind($p);
        }
        $q->execute();

        // 회원 스크랩 수 감소
        sqlx::query("UPDATE {$g5['member_table']} SET mb_scrap_cnt = mb_scrap_cnt - 1 
                   WHERE mb_id = ? AND mb_scrap_cnt > 0")
            ->bind($member['mb_id'])
            ->execute();
    });

    json_return(null, 200, '00000', '스크랩이 해제되었습니다.');
}
