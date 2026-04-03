<?php
/**
 * 앱 버전 정보 조회 엔드포인트
 * 현재 앱 버전, 최소 버전, 업데이트 URL 정보를 반환합니다.
 */

function GET() {
    // 환경 변수에서 버전 정보 읽기
    $currentVersion = getenv('APP_VERSION') ?: '1.0.0';
    $minVersion = getenv('APP_MIN_VERSION') ?: '0.9.0';
    $iosStoreUrl = getenv('APP_STORE_URL_IOS') ?: 'https://apps.apple.com/app/karbon';
    $androidStoreUrl = getenv('APP_STORE_URL_ANDROID') ?: 'https://play.google.com/store/apps/details?id=com.karbon';
    
    // 응답 데이터 구성
    $data = [
        'current_version' => $currentVersion,
        'min_version' => $minVersion,
        'update_url' => [
            'ios' => $iosStoreUrl,
            'android' => $androidStoreUrl
        ]
    ];
    
    // JSON 응답 반환
    response_success($data, '앱 버전 정보 조회 성공');
}
