<?php
// GET /api/popup
function GET()
{
    global $g5, $member;

    $device = isset($_GET['device']) ? $_GET['device'] : 'pc'; // pc or mobile

    // 팝업 조회 (g5_new_win 테이블)
    // nw_device: 'both', 'pc', 'mobile'
    $device_val = ($device === 'mobile' ? 'mobile' : 'pc');
    $rows = sqlx::query("select * from {$g5['new_win_table']} 
            where nw_device in ('both', ?)
            and (nw_begin_time = '0000-00-00 00:00:00' or nw_begin_time <= ?)
            and (nw_end_time = '0000-00-00 00:00:00' or nw_end_time >= ?)
            order by nw_id desc")
        ->bind($device_val)
        ->bind(G5_TIME_YMDHIS)
        ->bind(G5_TIME_YMDHIS)
        ->fetch_all();

    $popups = [];
    foreach ($rows as $row) {
        $popups[] = [
            'nw_id' => $row['nw_id'],
            'nw_subject' => $row['nw_subject'],
            'nw_content' => $row['nw_content'],
            'nw_width' => $row['nw_width'],
            'nw_height' => $row['nw_height'],
            'nw_left' => $row['nw_left'],
            'nw_top' => $row['nw_top']
        ];
    }

    json_return(['popups' => $popups], 200, '00000');
}

