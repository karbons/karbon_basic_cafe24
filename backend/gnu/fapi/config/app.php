<?php
/**
 * 애플리케이션 설정
 * .env 파일의 값이 우선되며, 없을 경우 이 파일의 기본값 사용
 */
return [
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') === 'true',
    'url' => getenv('APP_URL') ?: (defined('G5_URL') ? G5_URL : 'http://localhost'),
    'cookie_domain' => getenv('APP_COOKIE_DOMAIN') ?: '',
    'https_only' => getenv('APP_HTTPS_ONLY') === 'true',
    'cookie_samesite' => getenv('APP_COOKIE_SAMESITE') ?: 'Lax', // Lax, Strict, None
];

