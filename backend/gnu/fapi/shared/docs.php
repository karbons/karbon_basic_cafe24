<?php
/**
 * API 문서 생성 함수
 */

/**
 * routes 폴더를 스캔하여 API 목록 생성
 */
function docs_generate() {
    $routesDir = __DIR__ . '/../routes';
    return _docs_scan_routes($routesDir);
}

/**
 * routes 폴더 스캔 (내부용)
 */
function _docs_scan_routes($dir, $prefix = '') {
    $routes = [];
    if (!is_dir($dir)) return $routes;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || strpos($file, '_') === 0) continue;
        
        $filePath = $dir . '/' . $file;
        
        if (is_dir($filePath)) {
            $newPrefix = $prefix ? $prefix . '/' . $file : $file;
            $routes = array_merge($routes, _docs_scan_routes($filePath, $newPrefix));
        } else {
            $routes = array_merge($routes, _docs_parse_route_file($filePath, $prefix));
        }
    }
    return $routes;
}

/**
 * 라우트 파일 파싱 (내부용)
 */
function _docs_parse_route_file($filePath, $prefix) {
    $routes = [];
    $content = file_get_contents($filePath);
    $apiPath = _docs_file_path_to_api_path($filePath);
    
    $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    
    foreach ($methods as $method) {
        if (preg_match('/function\s+' . $method . '\s*\(/', $content)) {
            $routes[] = [
                'method' => $method,
                'path' => $apiPath,
                'file' => str_replace(realpath(__DIR__ . '/../'), '', realpath($filePath))
            ];
        }
    }
    return $routes;
}

/**
 * 파일 경로를 API 경로로 변환 (내부용)
 */
function _docs_file_path_to_api_path($filePath) {
    $routesDir = realpath(__DIR__ . '/../routes');
    $realFilePath = realpath($filePath);
    $relativePath = str_replace($routesDir, '', $realFilePath);
    $relativePath = str_replace('.php', '', $relativePath);
    
    $parts = explode(DIRECTORY_SEPARATOR, trim($relativePath, DIRECTORY_SEPARATOR));
    $apiParts = [];
    
    foreach ($parts as $part) {
        if (preg_match('/^\[(.+)\]$/', $part, $matches)) {
            $apiParts[] = '{' . $matches[1] . '}';
        } else {
            if ($part !== 'index') {
                $apiParts[] = $part;
            }
        }
    }
    
    return '/api/' . implode('/', $apiParts);
}
