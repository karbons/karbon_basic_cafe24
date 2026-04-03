<?php
// GET /api/shop/order/{od_id}
function GET($od_id) {
    global $g5, $member;
    
    if (!$member['mb_id']) {
        json_return(null, 401, '00001', '로그인이 필요합니다.');
    }
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $od = sqlx::query(" select * from {$g5['g5_shop_order_table']} where od_id = ? and mb_id = ? ")
        ->bind($od_id)
        ->bind($member['mb_id'])
        ->fetch_optional();
    
    if (!$od) {
        json_return(null, 404, '00001', '주문 내역을 찾을 수 없습니다.');
    }
    
    // 주문 아이템 조회
    $items = [];
    $sql = " select * from {$g5['g5_shop_cart_table']} where od_id = ? order by ct_id asc ";
    $rows = sqlx::query($sql)->bind($od['od_id'])->fetch_all();
    foreach($rows as $row) {
        $items[] = [
            'it_id' => $row['it_id'],
            'it_name' => $row['it_name'],
            'ct_option' => $row['ct_option'],
            'ct_qty' => (int)$row['ct_qty'],
            'ct_price' => (int)$row['ct_price'],
            'ct_status' => $row['ct_status'],
        ];
    }
    
    $od['items'] = $items;
    
    json_return($od, 200, '00000');
}
