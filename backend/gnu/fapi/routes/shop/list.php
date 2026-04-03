<?php
// GET /api/shop/list
function GET() {
    global $g5;
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $ca_id = query('ca_id', '');
    $it_type = (int)query('it_type', 0);
    $stx = query('stx', '');
    $sort = query('sort', 'it_id desc');
    $page = (int)query('page', 1);
    $page_rows = (int)query('page_rows', 20);
    
    // Sort validation
    $allowed_sorts = ['it_id desc', 'it_price asc', 'it_price desc', 'it_name asc', 'it_hit desc'];
    if (!in_array($sort, $allowed_sorts)) {
        if (!preg_match("/^[a-zA-Z0-9_]+\s+(asc|desc)$/i", $sort)) {
             $sort = 'it_id desc';
        }
    }
    
    $where = " where it_use = '1' ";
    $params = [];

    if ($ca_id) {
        $where .= " and ca_id like ? ";
        $params[] = $ca_id . '%';
    }
    if ($it_type) {
        $where .= " and it_type{$it_type} = '1' ";
    }
    if ($stx) {
        $where .= " and (it_name like ? or it_id like ?) ";
        $params[] = '%' . $stx . '%';
        $params[] = '%' . $stx . '%';
    }
    
    // 전체 수
    $sql = " select count(*) as cnt from {$g5['g5_shop_item_table']} {$where} ";
    $q = sqlx::query($sql);
    foreach ($params as $param) {
        $q->bind($param);
    }
    $row = $q->fetch_one();
    $total_count = (int)$row['cnt'];
    $total_page = ceil($total_count / $page_rows);
    
    // 목록 조회
    $from_record = ($page - 1) * $page_rows;
    $sql = " select * from {$g5['g5_shop_item_table']} 
              {$where} 
              order by {$sort} 
              limit ?, ? ";
    
    $q = sqlx::query($sql);
    foreach ($params as $param) {
        $q->bind($param);
    }
    $q->bind($from_record);
    $q->bind($page_rows);
    
    $result = $q->fetch_all();
    
    $items = [];
    foreach ($result as $row) {
        $it_img_url = _get_it_imageurl($row['it_id']);
        $items[] = [
            'it_id' => $row['it_id'],
            'it_name' => $row['it_name'],
            'it_price' => (int)$row['it_price'],
            'it_cust_price' => (int)$row['it_cust_price'],
            'it_point' => (int)$row['it_point'],
            'it_img_url' => $it_img_url,
            'it_type1' => (int)$row['it_type1'],
            'it_type2' => (int)$row['it_type2'],
            'it_type3' => (int)$row['it_type3'],
            'it_type4' => (int)$row['it_type4'],
            'it_type5' => (int)$row['it_type5'],
            'it_hit' => (int)$row['it_hit'],
        ];
    }
    
    $category = null;
    if ($ca_id) {
        $category = sqlx::query(" select * from {$g5['g5_shop_category_table']} where ca_id = ? ")
            ->bind($ca_id)
            ->fetch_optional();
    }
    
    json_return([
        'items' => $items,
        'category' => $category,
        'total_count' => $total_count,
        'total_page' => $total_page,
        'page' => $page
    ], 200, '00000');
}

// 헬퍼 함수: 이미지 URL 가져오기
function _get_it_imageurl($it_id) {
    global $g5;
    for($i=1; $i<=10; $i++) {
        $file = G5_DATA_PATH.'/item/'.$it_id.'_l'.$i;
        if(file_exists($file)) {
            return G5_DATA_URL.'/item/'.$it_id.'_l'.$i;
        }
    }
    // DB 확인
    $row = sqlx::query(" select it_img1 from {$g5['g5_shop_item_table']} where it_id = ? ")
        ->bind($it_id)
        ->fetch_optional();
        
    if($row && $row['it_img1']) {
        return G5_DATA_URL.'/item/'.$row['it_img1'];
    }
    return '';
}
