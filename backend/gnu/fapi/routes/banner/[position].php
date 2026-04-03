<?php
// GET /api/banner/{position}
function GET($position) {
    global $g5;
    
    $device = isset($_GET['device']) ? $_GET['device'] : 'pc'; // pc or mobile
    
    $bn_device = ($device === 'mobile' ? 'mobile' : 'pc');
    // 배너 조회 (그누보드5는 쇼핑몰 배너 테이블 사용)
    $banners_data = sqlx::query("select * from {$g5['g5_shop_banner_table']} 
            where bn_position = ?
            and bn_use = 1
            and bn_device in ('both', ?)
            and (? between bn_begin_time and bn_end_time or bn_begin_time = '0000-00-00 00:00:00')
            order by bn_order asc, bn_id desc")
        ->bind($position)
        ->bind($bn_device)
        ->bind(G5_TIME_YMDHIS)
        ->fetch_all();
    
    $banners = [];
    foreach ($banners_data as $row) {
        $banners[] = [
            'bn_id' => $row['bn_id'],
            'bn_alt' => $row['bn_alt'],
            'bn_url' => $row['bn_url'],
            'bn_target' => $row['bn_new_win'] ? '_blank' : '_self',
            'bn_image' => G5_DATA_PATH . '/banner/' . $row['bn_image'],
            'bn_bgcolor' => $row['bn_bgcolor']
        ];
    }
    
    json_return(['banners' => $banners], 200, '00000');
}
