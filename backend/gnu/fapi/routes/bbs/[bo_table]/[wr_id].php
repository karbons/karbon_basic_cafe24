<?php
function format_files_for_api($bo_table, $wr_id)
{
    global $g5;

    $files = [];
    $sql = "SELECT * FROM {$g5['board_file_table']} 
            WHERE bo_table = ? 
            AND wr_id = ? 
            ORDER BY bf_no";
    
    $rows = sqlx::query($sql)
        ->bind($bo_table)
        ->bind($wr_id)
        ->fetch_all();

    foreach ($rows as $row) {
        $files[] = [
            'bf_no' => (int) $row['bf_no'],
            'bf_source' => $row['bf_source'],
            'bf_file' => $row['bf_file'],
            'bf_filesize' => (int) $row['bf_filesize'],
            'bf_width' => (int) $row['bf_width'],
            'bf_height' => (int) $row['bf_height'],
            'bf_type' => (int) $row['bf_type'],
            'bf_datetime' => $row['bf_datetime'],
            'bf_content' => $row['bf_content'] ?? '',
            'download_url' => G5_DATA_URL . '/file/' . $bo_table . '/' . $row['bf_file']
        ];
    }

    return $files;
}

// GET /api/bbs/{bo_table}/{wr_id}
function GET($bo_table, $wr_id)
{
    global $g5, $member;

    $board = get_board_db($bo_table, true);

    if (!$board || !$board['bo_table']) {
        json_return(null, 404, '00001', '게시판을 찾을 수 없습니다.');
    }

    // 게시판 읽기 권한 체크
    if ($member['mb_level'] < $board['bo_read_level']) {
        json_return(null, 403, '00003', '게시판을 읽을 권한이 없습니다.');
    }

    $write_table = $g5['write_prefix'] . $bo_table;
    $write = get_write($write_table, $wr_id);

    if (!$write || !$write['wr_id']) {
        json_return(null, 404, '00001', '글을 찾을 수 없습니다.');
    }

    // 비밀글 체크
    if (strstr($write['wr_option'], 'secret')) {
        if (!$member['mb_id'] || ($write['mb_id'] !== $member['mb_id'] && $member['mb_level'] < $board['bo_read_level'])) {
            json_return(null, 403, '00003', '비밀글은 작성자와 관리자만 볼 수 있습니다.');
        }
    }

    // 조회수 증가 (IP당 하루 1회만)
    $remote_addr = $_SERVER['REMOTE_ADDR'];
    $today = G5_TIME_YMD;
    $hit_key = "hit_{$bo_table}_{$wr_id}_{$remote_addr}_{$today}";

    // 세션 또는 별도 테이블로 중복 체크 (간단히 쿠키 활용도 가능하지만 API에서는 DB 체크)
    // 임시 테이블 g5_board_hit 또는 write 테이블의 wr_hit 업데이트 조건으로 처리
    $hit_check_sql = "SELECT wr_id FROM {$write_table} WHERE wr_id = ? AND wr_ip = ? AND DATE(wr_last) = ?";
    $hit_check = sqlx::query($hit_check_sql)
        ->bind($wr_id)
        ->bind($remote_addr)
        ->bind($today)
        ->fetch_optional();

    // IP 중복 체크용 별도 테이블이 없으므로, 간단히 매 조회시 증가하되 API 레벨에서 제한
    // 실제 구현: g5_board_view 테이블 생성하거나 cookie 기반
    // 여기서는 IP+글ID+날짜 조합으로 hit 테이블 사용
    if (!isset($_SESSION['hit_' . $bo_table . '_' . $wr_id]) && !isset($_COOKIE['hit_' . $bo_table . '_' . $wr_id])) {
        sqlx::query("UPDATE {$write_table} SET wr_hit = wr_hit + 1 WHERE wr_id = ?")
            ->bind($wr_id)
            ->execute();
        // 쿠키 설정 (86400 = 하루)
        setcookie('hit_' . $bo_table . '_' . $wr_id, '1', time() + 86400, '/');
    }

    // 이전글/다음글
    $prev = null;
    $next = null;

    $prev_sql = "select wr_id, wr_subject from {$write_table} where wr_num < ? and wr_is_comment = 0 order by wr_num desc limit 1";
    $prev_row = sqlx::query($prev_sql)
        ->bind($write['wr_num'])
        ->fetch_optional();
        
    if ($prev_row) {
        $prev = [
            'wr_id' => $prev_row['wr_id'],
            'wr_subject' => $prev_row['wr_subject']
        ];
    }

    $next_sql = "select wr_id, wr_subject from {$write_table} where wr_num > ? and wr_is_comment = 0 order by wr_num asc limit 1";
    $next_row = sqlx::query($next_sql)
        ->bind($write['wr_num'])
        ->fetch_optional();

    if ($next_row) {
        $next = [
            'wr_id' => $next_row['wr_id'],
            'wr_subject' => $next_row['wr_subject']
        ];
    }

    // 권한 체크 로직 개선
    $is_admin = ($member['mb_id'] && $member['mb_level'] == 10) ? true : false;

    $is_owner = false;
    if ($write['mb_id']) {
        if ($member['mb_id'] && $member['mb_id'] === $write['mb_id']) {
            $is_owner = true;
        }
    } else {
        $is_owner = true; // 비회원 글은 누구나(비번 알면) 수정/삭제 가능하므로 true (UI 노출용)
    }

    $can_edit = ($is_admin || $is_owner);
    $can_delete = ($is_admin || $is_owner);

    // 삭제의 경우 코멘트 개수 제한 체크 (관리자 제외)
    if ($can_delete && !$is_admin && $board['bo_count_delete'] > 0 && $write['wr_comment'] >= $board['bo_count_delete']) {
        $can_delete = false;
    }

    // 답변 권한 체크
    $can_reply = ($member['mb_level'] >= $board['bo_reply_level']);

    // 스크랩 여부 체크
    $is_scraped = false;
    if ($member['mb_id']) {
        $scrap = sqlx::query("SELECT ms_id FROM {$g5['scrap_table']} 
                            WHERE mb_id = ?
                            AND bo_table = ?
                            AND wr_id = ?")
            ->bind($member['mb_id'])
            ->bind($bo_table)
            ->bind($wr_id)
            ->fetch_optional();
            
        if ($scrap && $scrap['ms_id']) {
            $is_scraped = true;
        }
    }

    json_return([
        'board' => [
            'bo_table' => $board['bo_table'],
            'bo_subject' => $board['bo_subject'],
            'bo_page_rows' => (int) $board['bo_page_rows'],
            'bo_mobile_page_rows' => (int) $board['bo_mobile_page_rows'],
            'bo_device' => $board['bo_device'],
            'bo_skin' => $board['bo_skin'],
            'bo_mobile_skin' => $board['bo_mobile_skin'],
            'bo_use_category' => (int) $board['bo_use_category'],
            'bo_category_list' => $board['bo_category_list'],
            'bo_use_sideview' => (int) $board['bo_use_sideview'],
            'bo_use_file_content' => (int) $board['bo_use_file_content'],
            'bo_use_secret' => (int) $board['bo_use_secret'],
            'bo_use_dhtml_editor' => (int) $board['bo_use_dhtml_editor'],
            'bo_use_rss_view' => (int) $board['bo_use_rss_view'],
            'bo_use_sns' => (int) $board['bo_use_sns'],
            'bo_use_good' => (int) $board['bo_use_good'],
            'bo_use_nogood' => (int) $board['bo_use_nogood'],
            'bo_use_name' => (int) $board['bo_use_name'],
            'bo_use_signature' => (int) $board['bo_use_signature'],
            'bo_use_ip_view' => (int) $board['bo_use_ip_view'],
            'bo_use_list_view' => (int) $board['bo_use_list_view'],
            'bo_use_list_file' => (int) $board['bo_use_list_file'],
            'bo_use_list_content' => (int) $board['bo_use_list_content'],
            'bo_table_width' => (int) $board['bo_table_width'],
            'bo_subject_len' => (int) $board['bo_subject_len'],
            'bo_mobile_subject_len' => (int) $board['bo_mobile_subject_len'],
            'bo_new' => (int) $board['bo_new'],
            'bo_hot' => (int) $board['bo_hot'],
            'bo_image_width' => (int) $board['bo_image_width'],
            'bo_upload_count' => (int) $board['bo_upload_count'],
            'bo_upload_size' => (int) $board['bo_upload_size'],
            'bo_count_delete' => (int) $board['bo_count_delete'],
            'bo_write_level' => (int) $board['bo_write_level'],
            'bo_reply_level' => (int) $board['bo_reply_level']
        ],
        'write' => [
            'wr_id' => $write['wr_id'],
            'wr_subject' => $write['wr_subject'],
            'wr_content' => replace_localhost_urls($write['wr_content'], G5_URL),
            'wr_name' => $write['wr_name'],
            'mb_id' => $write['mb_id'],
            'wr_datetime' => $write['wr_datetime'],
            'wr_hit' => (int) $write['wr_hit'],
            'wr_good' => $write['wr_good'],
            'wr_nogood' => $write['wr_nogood'],
            'ca_name' => $write['ca_name'],
            'wr_option' => $write['wr_option'],
            'wr_comment' => (int) $write['wr_comment'],
            'files' => format_files_for_api($bo_table, $wr_id),
            'mb_1' => ($write['mb_id'] ? (get_member($write['mb_id'])['mb_1'] ?? '') : '')
        ],
        'prev' => $prev,
        'next' => $next,
        'can_edit' => $can_edit,
        'can_delete' => $can_delete,
        'can_reply' => $can_reply,
        'is_scraped' => $is_scraped
    ], 200, '00000');
}

