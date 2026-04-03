<?php
if (!defined('_GNUBOARD_')) exit;

function GET() {
    $path = $_GET['path'] ?? '/';
    $app_scheme = Config::get('APP_SCHEME', 'karbon');
    $app_url = $app_scheme . '://' . ltrim($path, '/');
    $universal_url = G5_URL . $path;

    // Detect mobile
    $is_mobile = preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']);

    if (!$is_mobile) {
        header("Location: " . $universal_url);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karbon App으로 이동</title>
    <style>
        body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f9f9f9; }
        .container { text-align: center; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 80%; max-width: 400px; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        p { color: #666; margin-bottom: 2rem; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Karbon App으로 이동하시겠습니까?</h1>
        <p>앱을 설치하시면 더 빠르고 쾌적하게 이용하실 수 있습니다.</p>
        <a href="<?php echo htmlspecialchars($app_url); ?>" class="btn">앱에서 열기</a>
        <div style="margin-top: 20px;">
            <a href="<?php echo htmlspecialchars($universal_url); ?>" style="color: #999; font-size: 0.8rem;">웹으로 계속하기</a>
        </div>
    </div>
    <script>
        // Auto redirect attempt
        setTimeout(function() {
            window.location.href = "<?php echo $app_url; ?>";
        }, 500);
    </script>
</body>
</html>
<?php
}
