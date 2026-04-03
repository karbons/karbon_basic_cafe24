<?php
// GET /v1/fapi/captcha/test - GD 확인용
function GET()
{
    header('Content-Type: text/plain');
    
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "GD Extension: " . (extension_loaded('gd') ? 'YES' : 'NO') . "\n";
    
    if (extension_loaded('gd')) {
        echo "GD Info:\n";
        print_r(gd_info());
    }
    
    echo "\nTemp Dir: " . sys_get_temp_dir() . "\n";
    echo "Session: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
}
