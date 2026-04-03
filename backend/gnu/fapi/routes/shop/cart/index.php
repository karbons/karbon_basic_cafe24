<?php
// GET /api/shop/cart
function GET() {
    global $g5, $member;
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $cart_id = get_session('ss_cart_id');
    if (!$cart_id) {
        $cart_id = (isset($_COOKIE['ss_cart_id']) && $_COOKIE['ss_cart_id']) ? $_COOKIE['ss_cart_id'] : '';
    }
    
    if (!$cart_id && !$member['mb_id']) {
        json_return(['list' => [], 'total_price' => 0, 'total_qty' => 0], 200, '00000');
    }
    
    $where = " where ct_status = '쇼핑' ";
    $params = [];
    if ($member['mb_id']) {
        $where .= " and mb_id = ? ";
        $params[] = $member['mb_id'];
    } else {
        $where .= " and od_id = ? ";
        $params[] = $cart_id;
    }
    
    $sql = " select a.*, b.it_name, b.it_cust_price from {$g5['g5_shop_cart_table']} a 
              left join {$g5['g5_shop_item_table']} b on (a.it_id=b.it_id)
              {$where} 
              order by ct_id desc ";
    
    $query = sqlx::query($sql);
    foreach ($params as $param) {
        $query->bind($param);
    }
    $rows = $query->fetch_all();
    
    $list = [];
    $total_price = 0;
    $total_qty = 0;
    
    foreach ($rows as $row) {
        $it_img_url = G5_DATA_URL.'/item/'.$row['it_id'].'_s1';
        $list[] = [
            'ct_id' => (int)$row['ct_id'],
            'it_id' => $row['it_id'],
            'it_name' => $row['it_name'],
            'ct_option' => $row['ct_option'],
            'ct_qty' => (int)$row['ct_qty'],
            'ct_price' => (int)$row['ct_price'],
            'ct_point' => (int)$row['ct_point'],
            'ct_status' => $row['ct_status'],
            'it_img_url' => $it_img_url
        ];
        $total_price += ($row['ct_price'] * $row['ct_qty']);
        $total_qty += $row['ct_qty'];
    }
    
    json_return([
        'list' => $list,
        'total_price' => $total_price,
        'total_qty' => $total_qty
    ], 200, '00000');
}
