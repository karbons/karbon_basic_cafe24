<?php
// GET /v1/fapi/captcha/image
// GET /v1/fapi/captcha/image/{token}
function GET($token = null)
{
    require_once __DIR__ . '/../../shared/captcha.php';
    
    captcha_generate_image($token);
}
