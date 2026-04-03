<?php
/**
 * CORS 처리 유틸리티
 */

function header_origin()
{
    $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:3000'));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (env('APP_ENV') === 'development' || in_array($origin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 86400");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
