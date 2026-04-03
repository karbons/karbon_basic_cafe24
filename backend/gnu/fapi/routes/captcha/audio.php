<?php
// GET /v1/fapi/captcha/audio
// GET /v1/fapi/captcha/audio/{token}
function GET($token = null)
{
    while (ob_get_level()) ob_end_clean();
    
    require_once __DIR__ . '/../../shared/captcha.php';
    
    $code = '';
    if ($token) {
        $file = sys_get_temp_dir() . '/captcha_' . md5($token) . '.txt';
        if (file_exists($file)) {
            $code = file_get_contents($file);
        }
    }
    
    if (empty($code)) {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        $code = $_SESSION['ss_captcha_key'] ?? '';
    }
    
    if (empty($code)) {
        http_response_code(400);
        echo 'No captcha code';
        exit;
    }
    
    header('Content-Type: audio/mpeg');
    header('Cache-Control: no-cache');
    
    $mp3_dir = G5_PATH . '/plugin/kcaptcha/mp3/basic';
    
    foreach (str_split($code) as $char) {
        $mp3_file = $mp3_dir . '/' . $char . '.mp3';
        if (file_exists($mp3_file)) {
            echo file_get_contents($mp3_file);
        }
    }
    exit;
}
