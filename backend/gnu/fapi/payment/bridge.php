<?php
include_once __DIR__ . '/../_common.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
if (!$token) {
    die('Invalid access: Missing token');
}

// 토큰 복호화
$payload_json = decrypt($token);
if (!$payload_json) {
    die('Invalid access: Decryption failed');
}

$payload = json_decode($payload_json, true);
if (!$payload || (time() - $payload['timestamp'] > 3600)) {
    die('Invalid access: Expired or invalid payload');
}

$order = $payload['order'];
$mb_id = $payload['mb_id'];

// 그누보드 쇼핑몰 설정 로드
include_once G5_LIB_PATH.'/shop.lib.php';

// PG사 설정 확인 (예: KG이니시스)
$default = get_default();
$pg_type = $default['de_pg_service'];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 진행 중...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8f9fa; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <div class="loader" style="margin: 0 auto 20px;"></div>
        <p>결제창으로 이동 중입니다...</p>
    </div>

    <!-- PG사별 결제 폼 (예시: 이니시스) -->
    <form id="pay_form" method="post" action="">
        <?php
        // 실제 PG사별 파라미터 구성 로직이 여기에 들어갑니다.
        // 그누보드의 기존 shop/orderform.sub.php 로직을 참고하여 폼을 생성합니다.
        
        // 예시용 hidden fields
        foreach($order as $key => $val) {
            echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
        }
        ?>
    </form>

    <script>
        // PG사별 호출 스크립트
        window.onload = function() {
            // setTimeout(function() {
            //     document.getElementById('pay_form').submit();
            // }, 500);
            
            // 테스트를 위해 직접 닫기 시뮬레이션 버튼 (개발용)
            /*
            var btn = document.createElement('button');
            btn.innerHTML = "결제 완료 시뮬레이션 (앱복귀)";
            btn.onclick = function() {
                window.location.href = "com.gnuboard.karbon://payment-result?status=success&od_id=TEST_" + Date.now();
            };
            document.body.appendChild(btn);
            */
        };
    </script>
</body>
</html>
