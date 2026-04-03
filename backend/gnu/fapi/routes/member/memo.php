<?php
// GET /api/member/memo
function GET() {
    global $g5, $member;
    
    // 로그인 체크
    if (!$member['mb_id']) {
        json_return(null, 401, '00002', '로그인이 필요합니다.');
    }
    
    $type = isset($_GET['type']) ? $_GET['type'] : 'recv'; // recv or send
    
    if ($type === 'recv') {
        $rows = sqlx::query("select * from {$g5['memo_table']} 
                where me_recv_mb_id = ?
                and me_type = 'recv'
                order by me_id desc")
            ->bind($member['mb_id'])
            ->fetch_all();
    } else {
        $rows = sqlx::query("select * from {$g5['memo_table']} 
                where me_send_mb_id = ?
                and me_type = 'send'
                order by me_id desc")
            ->bind($member['mb_id'])
            ->fetch_all();
    }
    
    $memos = [];
    foreach ($rows as $row) {
        $memos[] = [
            'me_id' => $row['me_id'],
            'me_subject' => $row['me_subject'],
            'me_content' => $row['me_content'],
            'me_send_mb_id' => $row['me_send_mb_id'],
            'me_recv_mb_id' => $row['me_recv_mb_id'],
            'me_send_datetime' => $row['me_send_datetime'],
            'me_read_datetime' => $row['me_read_datetime']
        ];
    }
    
    json_return(['memos' => $memos], 200, '00000');
}

