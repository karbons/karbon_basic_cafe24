<?php
// GET /api/shop/category
function GET() {
    global $g5;
    
    $sql = " select * from {$g5['g5_shop_category_table']} 
              where ca_use = '1' 
              order by ca_id ";
    $rows = sqlx::query($sql)->fetch_all();
    
    $categories = [];
    foreach ($rows as $row) {
        $len = strlen($row['ca_id']);
        if ($len == 2) {
            $categories[] = [
                'ca_id' => $row['ca_id'],
                'ca_name' => $row['ca_name'],
                'children' => []
            ];
        } else if ($len == 4) {
            $parent_id = substr($row['ca_id'], 0, 2);
            foreach ($categories as &$parent) {
                if ($parent['ca_id'] == $parent_id) {
                    $parent['children'][] = [
                        'ca_id' => $row['ca_id'],
                        'ca_name' => $row['ca_name'],
                        'children' => []
                    ];
                    break;
                }
            }
        }
    }
    
    json_return(['categories' => $categories], 200, '00000');
}
