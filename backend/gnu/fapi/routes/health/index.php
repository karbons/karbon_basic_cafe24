<?php
/**
 * Health Check 엔드포인트
 * 서버 컨디션 정보를 JSON 형태로 반환
 */

function GET() {
    $status = 'ok';
    $checks = [];
    
    // 1. PHP 버전 정보
    $checks['php'] = [
        'version' => PHP_VERSION,
        'status' => 'ok'
    ];
    
    // 2. 메모리 사용량
    $memoryUsage = memory_get_usage(true);
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $memoryPercent = $memoryLimitBytes > 0 ? round(($memoryUsage / $memoryLimitBytes) * 100, 2) : 0;
    
    $checks['memory'] = [
        'used' => formatBytes($memoryUsage),
        'used_bytes' => $memoryUsage,
        'limit' => $memoryLimit,
        'limit_bytes' => $memoryLimitBytes,
        'percent' => $memoryPercent,
        'status' => $memoryPercent > 90 ? 'warning' : 'ok'
    ];
    
    // 3. 디스크 사용량 (루트 디렉토리)
    $diskTotal = disk_total_space('/');
    $diskFree = disk_free_space('/');
    $diskUsed = $diskTotal - $diskFree;
    $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
    
    $checks['disk'] = [
        'total' => formatBytes($diskTotal),
        'total_bytes' => $diskTotal,
        'free' => formatBytes($diskFree),
        'free_bytes' => $diskFree,
        'used' => formatBytes($diskUsed),
        'used_bytes' => $diskUsed,
        'percent' => $diskPercent,
        'status' => $diskPercent > 90 ? 'warning' : 'ok'
    ];
    
    // 4. 데이터베이스 연결 상태
    try {
        sqlx::query("SELECT 1")->fetch_scalar();
        $checks['database'] = [
            'status' => 'ok',
            'connected' => true,
            'host' => G5_MYSQL_HOST ?? 'unknown',
            'database' => G5_MYSQL_DB ?? 'unknown'
        ];
    } catch (Exception $e) {
        $status = 'error';
        $checks['database'] = [
            'status' => 'error',
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // 5. Redis 연결 상태 (선택적)
    if (extension_loaded('redis')) {
        try {
            $redis = new Redis();
            $redisConnected = @$redis->connect('127.0.0.1', 6379);
            if ($redisConnected) {
                $redis->ping();
                $checks['redis'] = [
                    'status' => 'ok',
                    'connected' => true
                ];
                $redis->close();
            } else {
                $checks['redis'] = [
                    'status' => 'warning',
                    'connected' => false,
                    'message' => 'Redis 서버에 연결할 수 없습니다.'
                ];
            }
        } catch (Exception $e) {
            $checks['redis'] = [
                'status' => 'warning',
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    } else {
        $checks['redis'] = [
            'status' => 'not_available',
            'connected' => false,
            'message' => 'Redis 확장이 설치되지 않았습니다.'
        ];
    }
    
    // 6. 서버 시간
    $checks['server_time'] = [
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
    
    // 7. 시스템 로드 (Linux/Unix만)
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $checks['load_average'] = [
            '1min' => round($load[0], 2),
            '5min' => round($load[1], 2),
            '15min' => round($load[2], 2),
            'status' => $load[0] > 2.0 ? 'warning' : 'ok'
        ];
    }
    
    // 8. PHP 설정 정보
    $checks['php_config'] = [
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_input_vars' => ini_get('max_input_vars')
    ];
    
    // 전체 상태 결정
    $overallStatus = 'ok';
    foreach ($checks as $check) {
        if (isset($check['status'])) {
            if ($check['status'] === 'error') {
                $overallStatus = 'error';
                break;
            } elseif ($check['status'] === 'warning' && $overallStatus === 'ok') {
                $overallStatus = 'warning';
            }
        }
    }
    
    // 응답 데이터 구성
    $data = [
        'status' => $overallStatus,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'checks' => $checks
    ];
    
    json_return($data, 200, '00000', '서버 상태 정상');
}

/**
 * 바이트를 읽기 쉬운 형식으로 변환
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * 메모리 제한 문자열을 바이트로 변환
 */
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}

