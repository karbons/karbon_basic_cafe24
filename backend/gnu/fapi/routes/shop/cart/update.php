<?php
// POST /api/shop/cart/update
function POST() {
    global $g5, $member;
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $it_id = isset($_POST['it_id']) ? trim($_POST['it_id']) : '';
    $ct_qty = isset($_POST['ct_qty']) ? (int)$_POST['ct_qty'] : 1;
    $io_id = isset($_POST['io_id']) ? trim($_POST['io_id']) : '';
    $io_type = isset($_POST['io_type']) ? (int)$_POST['io_type'] : 0;
    $io_value = isset($_POST['io_value']) ? trim($_POST['io_value']) : '';
    
    if (!$it_id) {
        json_return(null, 400, '00001', '상품 ID가 없습니다.');
    }
    
    $it = get_shop_item($it_id, true);
    if (!$it) {
        json_return(null, 404, '00001', '상품을 찾을 수 없습니다.');
    }
    
    // 장바구니 세션 ID
    $cart_id = get_session('ss_cart_id');
    if (!$cart_id) {
        $cart_id = (isset($_COOKIE['ss_cart_id']) && $_COOKIE['ss_cart_id']) ? $_COOKIE['ss_cart_id'] : '';
    }
    if (!$cart_id) {
        $cart_id = md5(uniqid(G5_SERVER_TIME, true));
        set_session('ss_cart_id', $cart_id);
        setcookie('ss_cart_id', $cart_id, G5_SERVER_TIME + 86400 * 30, '/');
    }
    
    // 옵션 가격 및 정보
    $io_price = 0;
    if ($io_id) {
        $io_price = (int)sqlx::query(" select io_price from {$g5['g5_shop_item_option_table']} 
                  where it_id = ? and io_id = ? and io_type = ? ")
            ->bind($it_id)
            ->bind($io_id)
            ->bind($io_type)
            ->fetch_scalar();
    }
    
    $ct_price = $it['it_price'] + $io_price;
    $ct_point = $it['it_point'];
    
    sqlx::transaction(function() use ($g5, $member, $it_id, $io_id, $io_type, $ct_qty, $it, $cart_id, $io_value, $ct_price, $ct_point) {
        // 기존 장바구니 확인 (동일 상품/옵션)
        $where = " where ct_status = '쇼핑' and it_id = ? and io_id = ? ";
        $params = [$it_id, $io_id];
        if ($member['mb_id']) {
            $where .= " and mb_id = ? ";
            $params[] = $member['mb_id'];
        } else {
            $where .= " and od_id = ? ";
            $params[] = $cart_id;
        }
        
        $query = sqlx::query(" select ct_id, ct_qty from {$g5['g5_shop_cart_table']} {$where} ");
        foreach ($params as $p) $query->bind($p);
        $row = $query->fetch_optional();
        
        if ($row && $row['ct_id']) {
            $new_qty = $row['ct_qty'] + $ct_qty;
            sqlx::query(" update {$g5['g5_shop_cart_table']} set ct_qty = ? where ct_id = ? ")
                ->bind($new_qty)
                ->bind($row['ct_id'])
                ->execute();
        } else {
            sqlx::query(" insert into {$g5['g5_shop_cart_table']} 
                      set od_id = ?,
                          mb_id = ?,
                          it_id = ?,
                          it_name = ?,
                          ct_option = ?,
                          io_id = ?,
                          io_type = ?,
                          ct_qty = ?,
                          ct_price = ?,
                          ct_point = ?,
                          ct_status = '쇼핑',
                          ct_time = ?,
                          ct_ip = ? ")
                ->bind($cart_id)
                ->bind($member['mb_id'])
                ->bind($it_id)
                ->bind($it['it_name'])
                ->bind($io_value)
                ->bind($io_id)
                ->bind($io_type)
                ->bind($ct_qty)
                ->bind($ct_price)
                ->bind($ct_point)
                ->bind(G5_TIME_YMDHIS)
                ->bind($_SERVER['REMOTE_ADDR'])
                ->execute();
        }
    });
    
    json_return(['cart_id' => $cart_id], 200, '00000', '장바구니에 담겼습니다.');
}
