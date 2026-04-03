<?php
// GET /api/shop/order/list
function GET() {
    global $g5, $member;
    
    if (!$member['mb_id']) {
        json_return(null, 401, '00001', '로그인이 필요합니다.');
    }
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page_rows = 10;
    $from_record = ($page - 1) * $page_rows;
    
    $sql_common = " from {$g5['g5_shop_order_table']} where mb_id = ? ";
    
    $total_count = (int)sqlx::query(" select count(*) as cnt {$sql_common} ")
        ->bind($member['mb_id'])
        ->fetch_scalar();
    $total_page = ceil($total_count / $page_rows);
    
    $sql = " select * {$sql_common} order by od_id desc limit ?, ? ";
    $rows = sqlx::query($sql)
        ->bind($member['mb_id'])
        ->bind($from_record)
        ->bind($page_rows)
        ->fetch_all();
    
    $list = [];
    foreach ($rows as $row) {
        $list[] = [
            'od_id' => $row['od_id'],
            'od_name' => $row['od_name'],
            'od_cart_price' => (int)$row['od_cart_price'],
            'od_receipt_price' => (int)$row['od_receipt_price'],
            'od_status' => $row['od_status'],
            'od_time' => $row['od_time'],
        ];
    }
    
    json_return([
        'list' => $list,
        'total_count' => $total_count,
        'total_page' => $total_page,
        'page' => $page
    ], 200, '00000');
}
