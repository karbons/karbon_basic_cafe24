<?php
/**
 * API 엔트리 포인트
 * 
 * 모든 API 요청은 이 파일을 통해 처리됩니다.
 * Next.js 스타일의 파일 기반 라우팅을 지원합니다.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 부트스트랩 (초기화)
require_once __DIR__ . '/bootstrap.php';

// 라우팅 실행
$router = new Router(__DIR__ . '/routes');
$router->dispatch();
