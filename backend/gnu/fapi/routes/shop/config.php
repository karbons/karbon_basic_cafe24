<?php
// GET /api/shop/config
function GET() {
    global $g5, $default;
    
    // 쇼핑몰 라이브러리 로드
    include_once G5_LIB_PATH.'/shop.lib.php';
    
    $res = [
        'de_root_index_use' => (int)$default['de_root_index_use'],
        'de_shop_mobile_use' => (int)$default['de_shop_mobile_use'],
        'de_type1_list_use' => (int)$default['de_type1_list_use'],
        'de_type2_list_use' => (int)$default['de_type2_list_use'],
        'de_type3_list_use' => (int)$default['de_type3_list_use'],
        'de_type4_list_use' => (int)$default['de_type4_list_use'],
        'de_type5_list_use' => (int)$default['de_type5_list_use'],
        'de_mobile_type1_list_use' => (int)$default['de_mobile_type1_list_use'],
        'de_mobile_type2_list_use' => (int)$default['de_mobile_type2_list_use'],
        'de_mobile_type3_list_use' => (int)$default['de_mobile_type3_list_use'],
        'de_mobile_type4_list_use' => (int)$default['de_mobile_type4_list_use'],
        'de_mobile_type5_list_use' => (int)$default['de_mobile_type5_list_use'],
        'de_member_reg_coupon_use' => (int)$default['de_member_reg_coupon_use'],
        'de_member_reg_coupon_price' => (int)$default['de_member_reg_coupon_price'],
        'de_member_reg_coupon_minimum' => (int)$default['de_member_reg_coupon_minimum'],
        'de_member_reg_coupon_term' => (int)$default['de_member_reg_coupon_term'],
    ];
    
    json_return($res, 200, '00000');
}
