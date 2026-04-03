<?php
// GET /api/docs
function GET() {
    require_once __DIR__ . '/../../shared/docs.php';
    
    $routes = docs_generate();
    
    usort($routes, function($a, $b) {
        $methodOrder = ['GET' => 1, 'POST' => 2, 'PUT' => 3, 'DELETE' => 4, 'PATCH' => 5];
        $aOrder = $methodOrder[$a['method']] ?? 99;
        $bOrder = $methodOrder[$b['method']] ?? 99;
        
        if ($aOrder === $bOrder) {
            return strcmp($a['path'], $b['path']);
        }
        return $aOrder - $bOrder;
    });
    
    json_return([
        'routes' => $routes,
        'total' => count($routes)
    ], 200, '00000');
}

