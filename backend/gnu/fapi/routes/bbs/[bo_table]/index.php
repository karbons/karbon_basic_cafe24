<?php
// GET /api/bbs/{bo_table}
function GET($bo_table)
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

    // 페이지 파라미터
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $page_rows = isset($_GET['page_rows']) ? (int) $_GET['page_rows'] : $board['bo_page_rows'];

    // 검색 파라미터
    $stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
    $sop = isset($_GET['sop']) ? trim($_GET['sop']) : 'and';
    $sca = isset($_GET['sca']) ? trim($_GET['sca']) : '';
    $sst = isset($_GET['sst']) ? trim($_GET['sst']) : '';
    $sod = isset($_GET['sod']) ? trim($_GET['sod']) : '';

    $write_table = $g5['write_prefix'] . $bo_table;

    // 공지글 처리
    $notice_array = explode(',', trim($board['bo_notice']));
    $arr_notice = [];
    foreach ($notice_array as $k => $v) {
        if (trim($v)) {
            $arr_notice[] = (int) trim($v);
        }
    }

    // 검색 조건
    $where = " where wr_is_comment = 0 ";
    $bind_params = [];

    if ($stx) {
        $sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : '';
        $where .= get_sql_search($sca, $sfl, $stx, $sop);
    }
    if ($sca) {
        $where .= " and ca_name = ? ";
        $bind_params[] = $sca;
    }

    // 정렬
    $order_by = " order by ";

    // 파라미터 정렬이 있으면 우선 적용
    if ($sst && in_array($sst, ['wr_datetime', 'wr_hit', 'wr_good', 'wr_nogood', 'wr_subject', 'wr_name'])) {
        $order_by .= $sst . " " . ($sod === 'asc' ? 'asc' : 'desc') . ", ";
    }
    // 없으면 게시판 설정 정렬 적용
    else if ($board['bo_sort_field']) {
        // bo_sort_field format ex: wr_datetime desc
        $order_by .= $board['bo_sort_field'] . ", ";
    }

    // 기본 정렬 (답글 정렬 포함)
    $reply_order = $board['bo_reply_order'] ? 'desc' : 'asc';
    $order_by .= "wr_num desc, wr_reply {$reply_order} ";

    // 전체 게시글 수
    $sql = "select count(*) as cnt from {$write_table} {$where}";
    $query = sqlx::query($sql);
    foreach ($bind_params as $param) {
        $query->bind($param);
    }
    $total_count = $query->fetch_scalar();

    // 공지글 수 제외
    if (!empty($arr_notice)) {
        $total_count -= count($arr_notice);
    }

    $total_page = ceil($total_count / $page_rows);

    // 게시글 목록 조회
    $from_record = ($page - 1) * $page_rows;
    $sql = "select * from {$write_table} {$where} {$order_by} limit ?, ?";
    
    $query = sqlx::query($sql);
    foreach ($bind_params as $param) {
        $query->bind($param);
    }
    $query->bind($from_record);
    $query->bind($page_rows);
    
    $result = $query->fetch_all();

    $list = [];
    $bo_new_time = $board['bo_new'] * 3600; // hours to seconds
    $bo_hot = (int) $board['bo_hot'];
    $thumb_width = (int) $board['bo_gallery_width'] ?: 200;
    $thumb_height = (int) $board['bo_gallery_height'] ?: 150;

    foreach ($result as $row) {
        // 공지글은 제외
        if (in_array($row['wr_id'], $arr_notice)) {
            continue;
        }

        // NEW 여부: 작성시간이 bo_new 시간 이내
        $is_new = false;
        if ($bo_new_time > 0) {
            $write_time = strtotime($row['wr_datetime']);
            $is_new = (G5_SERVER_TIME - $write_time) < $bo_new_time;
        }

        // HOT 여부: 조회수가 bo_hot 이상
        $is_hot = ($bo_hot > 0 && (int) $row['wr_hit'] >= $bo_hot);

        // 썸네일 생성
        $thumbnail = get_list_thumbnail($bo_table, $row['wr_id'], $thumb_width, $thumb_height, false, true, 'center');

        $list[] = [
            'wr_id' => $row['wr_id'],
            'wr_num' => $row['wr_num'],
            'wr_reply' => $row['wr_reply'],
            'wr_subject' => $row['wr_subject'],
            'wr_content' => $row['wr_content'],
            'wr_name' => $row['wr_name'],
            'wr_datetime' => $row['wr_datetime'],
            'wr_hit' => (int) $row['wr_hit'],
            'wr_good' => (int) $row['wr_good'],
            'wr_nogood' => (int) $row['wr_nogood'],
            'wr_comment' => (int) $row['wr_comment'],
            'ca_name' => $row['ca_name'],
            'wr_is_notice' => in_array($row['wr_id'], $arr_notice),
            'is_new' => $is_new,
            'is_hot' => $is_hot,
            'thumbnail' => $thumbnail['src'] ?: null,
            'thumbnail_ori' => $thumbnail['ori'] ?: null,
            'thumbnail_alt' => $thumbnail['alt'] ?: ''
        ];
    }

    // 공지글 추가
    $notice_list = [];
    if (!empty($arr_notice)) {
        foreach ($arr_notice as $notice_id) {
            $notice = sqlx::query("select * from {$write_table} where wr_id = ?")
                ->bind($notice_id)
                ->fetch_optional();
            if ($notice) {
                $notice_list[] = [
                    'wr_id' => $notice['wr_id'],
                    'wr_subject' => $notice['wr_subject'],
                    'wr_name' => $notice['wr_name'],
                    'wr_datetime' => $notice['wr_datetime'],
                    'wr_hit' => $notice['wr_hit'],
                    'wr_is_notice' => true
                ];
            }
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
            'bo_write_min' => (int) $board['bo_write_min'],
            'bo_write_max' => (int) $board['bo_write_max'],
            'bo_insert_content' => $board['bo_insert_content'],
            'bo_gallery_cols' => (int) $board['bo_gallery_cols'],
            'bo_gallery_width' => (int) $board['bo_gallery_width'],
            'bo_gallery_height' => (int) $board['bo_gallery_height'],
            'bo_reply_order' => (int) $board['bo_reply_order'],
            'bo_sort_field' => $board['bo_sort_field'],
            'bo_1_subj' => $board['bo_1_subj'],
            'bo_2_subj' => $board['bo_2_subj'],
            'bo_3_subj' => $board['bo_3_subj'],
            'bo_4_subj' => $board['bo_4_subj'],
            'bo_5_subj' => $board['bo_5_subj'],
            'bo_6_subj' => $board['bo_6_subj'],
            'bo_7_subj' => $board['bo_7_subj'],
            'bo_8_subj' => $board['bo_8_subj'],
            'bo_9_subj' => $board['bo_9_subj'],
            'bo_10_subj' => $board['bo_10_subj'],
            'bo_1' => $board['bo_1'],
            'bo_2' => $board['bo_2'],
            'bo_3' => $board['bo_3'],
            'bo_4' => $board['bo_4'],
            'bo_5' => $board['bo_5'],
            'bo_6' => $board['bo_6'],
            'bo_7' => $board['bo_7'],
            'bo_8' => $board['bo_8'],
            'bo_9' => $board['bo_9'],
            'bo_10' => $board['bo_10']
        ],
        'notice_list' => $notice_list,
        'list' => $list,
        'pagination' => [
            'current_page' => $page,
            'total_page' => $total_page,
            'total_count' => $total_count,
            'page_rows' => $page_rows
        ]
    ], 200, '00000');
}

