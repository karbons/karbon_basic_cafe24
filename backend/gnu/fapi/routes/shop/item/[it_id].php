<?php
// GET /api/shop/item/{it_id}
function GET($it_id) {
    global $g5, $default;
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $it = get_shop_item($it_id, true);
    
    if (!$it || !$it['it_id']) {
        json_return(null, 404, '00001', '상품을 찾을 수 없습니다.');
    }
    
    // 조회수 증가
    sqlx::query(" update {$g5['g5_shop_item_table']} set it_hit = it_hit + 1 where it_id = ? ")
        ->bind($it_id)
        ->execute();
    
    // 이미지 처리
    $images = [];
    for($i=1; $i<=10; $i++) {
        $img = $it['it_img'.$i];
        if($img) {
            $images[] = G5_DATA_URL.'/item/'.$img;
        }
    }
    
    // 옵션 처리
    $options = [];
    $sql = " select * from {$g5['g5_shop_item_option_table']} 
              where it_id = ? and io_use = '1' 
              order by io_no asc ";
    $rows = sqlx::query($sql)->bind($it_id)->fetch_all();
    foreach($rows as $row) {
        $options[] = [
            'io_id' => $row['io_id'],
            'io_type' => (int)$row['io_type'],
            'io_value' => $row['io_value'],
            'io_price' => (int)$row['io_price'],
            'io_stock_qty' => (int)$row['io_stock_qty'],
        ];
    }
    
    // 관련 상품
    $relations = [];
    $sql = " select b.* from {$g5['g5_shop_item_relation_table']} a 
              left join {$g5['g5_shop_item_table']} b on (a.it_id2=b.it_id)
              where a.it_id = ? and b.it_use = '1' ";
    $rows = sqlx::query($sql)->bind($it_id)->fetch_all();
    foreach($rows as $row) {
        $relations[] = [
            'it_id' => $row['it_id'],
            'it_name' => $row['it_name'],
            'it_price' => (int)$row['it_price'],
            'it_img_url' => G5_DATA_URL.'/item/'.$row['it_img1']
        ];
    }

    $res = [
        'item' => [
            'it_id' => $it['it_id'],
            'it_name' => $it['it_name'],
            'it_basic' => $it['it_basic'],
            'it_explan' => $it['it_explan'],
            'it_mobile_explan' => $it['it_mobile_explan'],
            'it_price' => (int)$it['it_price'],
            'it_cust_price' => (int)$it['it_cust_price'],
            'it_point' => (int)$it['it_point'],
            'it_stock_qty' => (int)$it['it_stock_qty'],
            'it_sc_type' => (int)$it['it_sc_type'],
            'it_sc_method' => (int)$it['it_sc_method'],
            'it_sc_price' => (int)$it['it_sc_price'],
            'it_sc_minimum' => (int)$it['it_sc_minimum'],
            'it_sc_qty' => (int)$it['it_sc_qty'],
            'images' => $images,
            'ca_id' => $it['ca_id'],
            'ca_id2' => $it['ca_id2'],
            'ca_id3' => $it['ca_id3'],
        ],
        'options' => $options,
        'relations' => $relations
    ];
    
    json_return($res, 200, '00000');
}
