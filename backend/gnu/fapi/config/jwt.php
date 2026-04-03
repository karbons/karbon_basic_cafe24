<?php
/**
 * JWT 설정
 * .env 파일의 값이 우선되며, 없을 경우 그누보드 상수 또는 기본값 사용
 */
return [
    'access_token_key' => getenv('JWT_ACCESS_TOKEN_KEY') ?: (defined('G5_JWT_ACCESS_TOKEN_KEY') ? G5_JWT_ACCESS_TOKEN_KEY : ''),
    'refresh_token_key' => getenv('JWT_REFRESH_TOKEN_KEY') ?: (defined('G5_JWT_REFESH_TOKEN_KEY') ? G5_JWT_REFESH_TOKEN_KEY : ''),
    'crypt_key' => getenv('JWT_CRYPT_KEY') ?: (defined('G5_JWT_CRYPT_KEY') ? G5_JWT_CRYPT_KEY : ''),
    'audience' => getenv('JWT_AUDIENCE') ?: (defined('G5_JWT_AUDIENCE') ? G5_JWT_AUDIENCE : ($_SERVER['HTTP_HOST'] ?? 'localhost')),
    'access_mtime' => (int) (getenv('JWT_ACCESS_MTIME') ?: (defined('G5_JWT_ACCESS_MTIME') ? G5_JWT_ACCESS_MTIME : 15)), // 분
    'refresh_date' => (int) (getenv('JWT_REFRESH_DATE') ?: (defined('G5_JWT_RERESH_DATE') ? G5_JWT_RERESH_DATE : 30)), // 일
];

