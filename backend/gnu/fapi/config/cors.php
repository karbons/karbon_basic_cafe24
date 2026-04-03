<?php
/**
 * CORS 설정
 * .env 파일의 CORS_ALLOWED_ORIGINS 값을 쉼표로 구분하여 배열로 변환
 */
$allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173,http://localhost:3000';

return [
    'allowed_origins' => array_map('trim', explode(',', $allowedOrigins)),
    'allow_credentials' => getenv('CORS_ALLOW_CREDENTIALS') !== 'false', // 기본값: true
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
    'max_age' => (int)(getenv('CORS_MAX_AGE') ?: 86400),
];

