<?php
// POST /api/shop/payment/prepare
function POST() {
    global $g5, $member;
    
    include_once G5_LIB_PATH.'/shop.lib.php';
    include_once G5_API_PATH.'/_common.php';
    
    // 데이터 검증 및 암호화
    $order_data = $_POST;
    
    // 필수 데이터 확인
    if (!isset($order_data['od_name']) || !isset($order_data['od_price'])) {
        json_return(null, 400, '00001', '필수 주문 정보가 누락되었습니다.');
    }
    
    // 주문 정보를 JSON으로 변환 후 암호화
    $payload = json_encode([
        'mb_id' => $member['mb_id'],
        'order' => $order_data,
        'timestamp' => time(),
        'nonce' => bin2hex(random_bytes(16))
    ]);
    
    $token = encrypt($payload);
    
    // 브리지 페이지 URL 구성
    $bridge_url = G5_API_URL . '/payment/bridge.php?token=' . urlencode($token);
    
    json_return([
        'token' => $token,
        'bridge_url' => $bridge_url
    ], 200, '00000');
}
