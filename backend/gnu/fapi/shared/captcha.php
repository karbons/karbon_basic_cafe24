<?php
// 토큰 기반 CAPTCHA 스토리지
function _captcha_store_get($token) {
    $file = sys_get_temp_dir() . '/captcha_' . md5($token) . '.txt';
    if (file_exists($file)) {
        $data = file_get_contents($file);
        @unlink($file);
        return $data;
    }
    return null;
}

function _captcha_store_set($token, $code) {
    $file = sys_get_temp_dir() . '/captcha_' . md5($token) . '.txt';
    file_put_contents($file, $code, LOCK_EX);
}

function captcha_generate_image($token = null)
{
    while (ob_get_level()) ob_end_clean();
    
    if (!extension_loaded('gd')) {
        header('Content-Type: image/png');
        $img = imagecreate(150, 50);
        $bg = imagecolorallocate($img, 200, 200, 200);
        $text = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, 3, 10, 15, 'GD NOT INSTALLED', $text);
        imagepng($img);
        imagedestroy($img);
        exit;
    }
    
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $width = 150;
    $height = 60;
    $length = 5;

    $chars = '23456789';
    $captcha_code = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha_code .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    if ($token) {
        _captcha_store_set($token, strtolower($captcha_code));
    } else {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        $_SESSION['ss_captcha_key'] = strtolower($captcha_code);
    }

    $image = imagecreate($width, $height);
    $bg_color = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    $line_color = imagecolorallocate($image, 200, 200, 200);

    for ($i = 0; $i < 5; $i++) {
        imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $line_color);
    }

    $font_size = 5;
    $char_width = imagefontwidth($font_size);
    $total_text_width = $char_width * $length;
    $x = ($width - $total_text_width) / 2;
    $y = ($height - imagefontheight($font_size)) / 2;

    for ($i = 0; $i < $length; $i++) {
        $char = $captcha_code[$i];
        $offset_x = $i * $char_width;
        imagestring($image, $font_size, (int)($x + $offset_x + mt_rand(-2, 2)), (int)($y + mt_rand(-3, 3)), $char, $text_color);
    }

    imagepng($image);
    imagedestroy($image);
    exit;
}

function captcha_verify($input, $token = null)
{
    if ($token) {
        $stored = _captcha_store_get($token);
        return $stored !== null && strtolower(trim($input)) === $stored;
    } else {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        $stored = $_SESSION['ss_captcha_key'] ?? '';
        unset($_SESSION['ss_captcha_key']);
        return !empty($stored) && strtolower(trim($input)) === $stored;
    }
}

function captcha_generate_audio($code)
{
    $freq = [
        'a' => 1700, 'b' => 1600, 'c' => 1500, 'd' => 1400, 'e' => 1300,
        'f' => 1200, 'g' => 1100, 'h' => 1000, 'i' => 900, 'j' => 800,
        'k' => 750, 'l' => 700, 'm' => 650, 'n' => 600, 'o' => 550,
        'p' => 500, 'q' => 450, 'r' => 425, 's' => 400, 't' => 375,
        'u' => 350, 'v' => 325, 'w' => 300, 'x' => 275, 'y' => 250,
        'z' => 225, '0' => 2400, '1' => 2300, '2' => 2200, '3' => 2100,
        '4' => 2000, '5' => 1900, '6' => 1800, '7' => 1600, '8' => 1400, '9' => 1200
    ];
    
    $sample_rate = 22050;
    $duration = 0.2;
    $pause = 0.1;
    $samples = [];
    
    foreach (str_split(strtolower($code)) as $char) {
        $f = $freq[$char] ?? 1000;
        for ($i = 0; $i < $sample_rate * $duration; $i++) {
            $t = $i / $sample_rate;
            $val = sin(2 * M_PI * $f * $t) * 0.3;
            $samples[] = (int)($val * 32767);
        }
        for ($i = 0; $i < $sample_rate * $pause; $i++) {
            $samples[] = 0;
        }
    }
    
    $data = '';
    foreach ($samples as $sample) {
        $data .= pack('s', $sample);
    }
    
    $channels = 1;
    $bits = 16;
    $block = $channels * ($bits / 8);
    $data_size = strlen($data);
    
    $header = '';
    $header .= 'RIFF';
    $header .= pack('V', 36 + $data_size);
    $header .= 'WAVE';
    $header .= 'fmt ';
    $header .= pack('V', 16);
    $header .= pack('v', 1);
    $header .= pack('v', $channels);
    $header .= pack('V', $sample_rate);
    $header .= pack('V', $sample_rate * $block);
    $header .= pack('v', $block);
    $header .= pack('v', $bits);
    $header .= 'data';
    $header .= pack('V', $data_size);
    
    return $header . $data;
}
