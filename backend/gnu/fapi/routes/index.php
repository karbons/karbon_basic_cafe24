<?php
/**
 * API 루트 엔드포인트
 * /api/ 경로에 대한 기본 응답
 */

function GET() {
    $data = [
        'name' => 'SAPI (Simple API)',
        'version' => '1.0.0',
        'description' => '그누보드5 Simple API',
        'endpoints' => [
            'health' => '/api/health',
            'auth' => [
                'login' => '/api/auth/login',
                'logout' => '/api/auth/logout',
                'register' => '/api/auth/register',
                'refresh' => '/api/auth/refresh'
            ],
            'bbs' => [
                'list' => '/api/bbs/{bo_table}',
                'view' => '/api/bbs/{bo_table}/{wr_id}',
                'write' => '/api/bbs/{bo_table}/write',
                'update' => '/api/bbs/{bo_table}/update',
                'delete' => '/api/bbs/{bo_table}/delete',
                'search' => '/api/bbs/search'
            ],
            'member' => [
                'profile' => '/api/member/profile',
                'update' => '/api/member/update',
                'leave' => '/api/member/leave',
                'memo' => '/api/member/memo',
                'scrap' => '/api/member/scrap',
                'point' => '/api/member/point'
            ],
            'latest' => '/api/latest/{bo_table}',
            'menu' => '/api/menu',
            'banner' => '/api/banner/{position}',
            'popup' => '/api/popup',
            'content' => '/api/content/{co_id}',
            'docs' => '/api/docs'
        ],
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s')
    ];
    
    json_return($data, 200, '00000', 'SAPI 정보');
}

