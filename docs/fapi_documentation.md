# FAPI 자동 문서화 가이드

## 1. 개요

FAPI는 파일 기반 라우팅 구조를 활용하여 API 문서를 자동으로 생성할 수 있습니다. Swagger/OpenAPI 형식의 문서를 생성하여 개발자들이 API를 쉽게 이해하고 테스트할 수 있도록 합니다.

## 2. 구현 방법

### 2.1 주석 기반 문서화 (PHPDoc 활용)

각 라우트 파일에 PHPDoc 주석을 추가하여 API 정보를 정의합니다.

**예시: routes/auth/login.php**
```php
<?php
/**
 * @api {POST} /api/auth/login 로그인
 * @apiName Login
 * @apiGroup Auth
 * 
 * @apiDescription 사용자 로그인 및 JWT 토큰 발급
 * 
 * @apiBody {String} mb_id 사용자 ID (필수)
 * @apiBody {String} mb_password 비밀번호 (필수)
 * 
 * @apiSuccess {String} code 에러 코드 (00000: 성공)
 * @apiSuccess {Object} data 응답 데이터
 * @apiSuccess {Object} data.mb 회원 정보
 * @apiSuccess {String} data.mb.mb_id 회원 ID
 * @apiSuccess {String} data.mb.mb_name 이름
 * @apiSuccess {String} data.mb.mb_nick 닉네임
 * @apiSuccess {Number} data.mb.mb_level 레벨
 * @apiSuccess {Number} data.mb.mb_point 포인트
 * @apiSuccess {String} msg 메시지
 * @apiSuccess {Number} time 실행 시간
 * 
 * @apiError {String} code 에러 코드 (00001: 실패)
 * @apiError {String} msg 에러 메시지
 * 
 * @apiExample {json} 요청 예시:
 * {
 *   "mb_id": "testuser",
 *   "mb_password": "password123"
 * }
 * 
 * @apiExample {json} 응답 예시:
 * {
 *   "code": "00000",
 *   "msg": "로그인 성공",
 *   "data": {
 *     "mb": {
 *       "mb_id": "testuser",
 *       "mb_name": "테스트",
 *       "mb_nick": "테스트닉",
 *       "mb_level": 1,
 *       "mb_point": 1000
 *     }
 *   },
 *   "time": 0.123
 * }
 */
function POST() {
    // 로그인 로직
}
```

### 2.2 메타데이터 기반 문서화 (권장)

파일 상단에 JSON 형식의 메타데이터를 추가하여 더 구조화된 문서화를 구현합니다.

**예시: routes/auth/login.php**
```php
<?php
/**
 * @fapi
 * {
 *   "method": "POST",
 *   "path": "/api/auth/login",
 *   "summary": "사용자 로그인",
 *   "description": "사용자 ID와 비밀번호로 로그인하고 JWT 토큰을 발급받습니다.",
 *   "tags": ["auth"],
 *   "security": false,
 *   "request": {
 *     "contentType": "application/json",
 *     "body": {
 *       "mb_id": {
 *         "type": "string",
 *         "required": true,
 *         "description": "사용자 ID"
 *       },
 *       "mb_password": {
 *         "type": "string",
 *         "required": true,
 *         "description": "비밀번호"
 *       }
 *     }
 *   },
 *   "responses": {
 *     "200": {
 *       "description": "로그인 성공",
 *       "body": {
 *         "code": "00000",
 *         "msg": "로그인 성공",
 *         "data": {
 *           "mb": {
 *             "mb_id": "string",
 *             "mb_name": "string",
 *             "mb_nick": "string",
 *             "mb_level": "number",
 *             "mb_point": "number"
 *           }
 *         }
 *       }
 *     },
 *     "401": {
 *       "description": "로그인 실패",
 *       "body": {
 *         "code": "00001",
 *         "msg": "가입된 회원아이디가 아니거나 비밀번호가 틀립니다."
 *       }
 *     }
 *   }
 * }
 */
function POST() {
    // 로그인 로직
}
```

### 2.3 자동 문서 생성기 구현

**lib/Documentation.php:**
```php
<?php
class Documentation {
    private static $routes = [];
    
    /**
     * routes 폴더를 스캔하여 API 문서 생성
     */
    public static function generate($format = 'swagger') {
        self::scanRoutes(G5_API_PATH . '/routes');
        
        if ($format === 'swagger') {
            return self::generateSwagger();
        } elseif ($format === 'openapi') {
            return self::generateOpenAPI();
        } elseif ($format === 'markdown') {
            return self::generateMarkdown();
        }
        
        return self::generateSwagger();
    }
    
    /**
     * routes 폴더 스캔
     */
    private static function scanRoutes($dir, $prefix = '') {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (strpos($file, '_') === 0) continue; // Private 파일 제외
            
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $newPrefix = $prefix . '/' . $file;
                self::scanRoutes($path, $newPrefix);
            } else {
                self::parseRouteFile($path, $prefix);
            }
        }
    }
    
    /**
     * 라우트 파일 파싱
     */
    private static function parseRouteFile($filePath, $prefix) {
        $content = file_get_contents($filePath);
        
        // 파일 경로를 API 경로로 변환
        $apiPath = self::filePathToApiPath($filePath, $prefix);
        
        // HTTP 메서드 함수 찾기
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            if (preg_match('/function\s+' . $method . '\s*\(/', $content)) {
                // 메타데이터 추출
                $metadata = self::extractMetadata($content, $method);
                
                if ($metadata) {
                    self::$routes[] = [
                        'method' => $method,
                        'path' => $apiPath,
                        'metadata' => $metadata,
                        'file' => $filePath
                    ];
                }
            }
        }
    }
    
    /**
     * 파일 경로를 API 경로로 변환
     */
    private static function filePathToApiPath($filePath, $prefix) {
        $relativePath = str_replace(G5_API_PATH . '/routes', '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);
        
        // 동적 경로 변환: [bo_table] -> {bo_table}
        $relativePath = preg_replace('/\[([^\]]+)\]/', '{$1}', $relativePath);
        
        return '/api' . $prefix . $relativePath;
    }
    
    /**
     * 메타데이터 추출
     */
    private static function extractMetadata($content, $method) {
        // @fapi 주석에서 JSON 추출
        if (preg_match('/@fapi\s*\n\s*(\{.*?\})/s', $content, $matches)) {
            $json = $matches[1];
            $metadata = json_decode($json, true);
            
            if ($metadata && isset($metadata['method']) && $metadata['method'] === $method) {
                return $metadata;
            }
        }
        
        // PHPDoc에서 정보 추출 (대체 방법)
        return self::extractFromPhpDoc($content, $method);
    }
    
    /**
     * PHPDoc에서 정보 추출
     */
    private static function extractFromPhpDoc($content, $method) {
        if (preg_match('/\/\*\*(.*?)\*\//s', $content, $matches)) {
            $doc = $matches[1];
            
            $metadata = [
                'method' => $method,
                'summary' => '',
                'description' => '',
                'tags' => [],
                'security' => true,
                'request' => [],
                'responses' => []
            ];
            
            // @apiDescription 추출
            if (preg_match('/@apiDescription\s+(.+)/', $doc, $m)) {
                $metadata['description'] = trim($m[1]);
            }
            
            // @apiGroup 추출 (태그)
            if (preg_match('/@apiGroup\s+(.+)/', $doc, $m)) {
                $metadata['tags'] = [trim($m[1])];
            }
            
            // @apiBody 추출
            if (preg_match_all('/@apiBody\s+\{(\w+)\}\s+(\w+)\s+(.+)/', $doc, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $metadata['request']['body'][$match[2]] = [
                        'type' => $match[1],
                        'description' => trim($match[3])
                    ];
                }
            }
            
            return $metadata;
        }
        
        return null;
    }
    
    /**
     * Swagger JSON 생성
     */
    private static function generateSwagger() {
        $swagger = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => Config::get('app.name', 'FAPI'),
                'version' => Config::get('app.version', '1.0.0'),
                'description' => '그누보드5 FAPI 문서'
            ],
            'servers' => [
                [
                    'url' => Config::get('app.url', 'http://localhost'),
                    'description' => 'API 서버'
                ]
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'cookieAuth' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'fapi_access_token'
                    ]
                ]
            ]
        ];
        
        foreach (self::$routes as $route) {
            $path = $route['path'];
            $method = strtolower($route['method']);
            $meta = $route['metadata'];
            
            if (!isset($swagger['paths'][$path])) {
                $swagger['paths'][$path] = [];
            }
            
            $swagger['paths'][$path][$method] = [
                'summary' => $meta['summary'] ?? '',
                'description' => $meta['description'] ?? '',
                'tags' => $meta['tags'] ?? [],
                'security' => ($meta['security'] ?? true) ? [['cookieAuth' => []]] : [],
                'requestBody' => self::buildRequestBody($meta['request'] ?? []),
                'responses' => self::buildResponses($meta['responses'] ?? [])
            ];
        }
        
        return json_encode($swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Request Body 빌드
     */
    private static function buildRequestBody($request) {
        if (empty($request['body'])) {
            return null;
        }
        
        $properties = [];
        $required = [];
        
        foreach ($request['body'] as $key => $field) {
            $properties[$key] = [
                'type' => $field['type'] ?? 'string',
                'description' => $field['description'] ?? ''
            ];
            
            if ($field['required'] ?? false) {
                $required[] = $key;
            }
        }
        
        return [
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => $properties,
                        'required' => $required
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Responses 빌드
     */
    private static function buildResponses($responses) {
        $result = [];
        
        foreach ($responses as $statusCode => $response) {
            $result[$statusCode] = [
                'description' => $response['description'] ?? '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => self::buildSchema($response['body'] ?? [])
                        ]
                    ]
                ]
            ];
        }
        
        return $result;
    }
    
    /**
     * Schema 빌드 (재귀적)
     */
    private static function buildSchema($data) {
        $schema = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = [
                    'type' => 'object',
                    'properties' => self::buildSchema($value)
                ];
            } else {
                $schema[$key] = [
                    'type' => $value === 'string' ? 'string' : 
                             ($value === 'number' ? 'number' : 'string'),
                    'example' => $value
                ];
            }
        }
        
        return $schema;
    }
    
    /**
     * OpenAPI JSON 생성
     */
    private static function generateOpenAPI() {
        // Swagger와 동일 (OpenAPI 3.0)
        return self::generateSwagger();
    }
    
    /**
     * Markdown 문서 생성
     */
    private static function generateMarkdown() {
        $md = "# FAPI API 문서\n\n";
        $md .= "버전: " . Config::get('app.version', '1.0.0') . "\n\n";
        
        // 태그별로 그룹화
        $grouped = [];
        foreach (self::$routes as $route) {
            $tags = $route['metadata']['tags'] ?? ['기타'];
            foreach ($tags as $tag) {
                if (!isset($grouped[$tag])) {
                    $grouped[$tag] = [];
                }
                $grouped[$tag][] = $route;
            }
        }
        
        foreach ($grouped as $tag => $routes) {
            $md .= "## {$tag}\n\n";
            
            foreach ($routes as $route) {
                $meta = $route['metadata'];
                $md .= "### {$route['method']} {$route['path']}\n\n";
                $md .= "**요약:** " . ($meta['summary'] ?? '') . "\n\n";
                $md .= "**설명:** " . ($meta['description'] ?? '') . "\n\n";
                
                if (!empty($meta['request']['body'])) {
                    $md .= "**요청 파라미터:**\n\n";
                    $md .= "| 파라미터 | 타입 | 필수 | 설명 |\n";
                    $md .= "|---------|------|------|------|\n";
                    
                    foreach ($meta['request']['body'] as $key => $field) {
                        $required = ($field['required'] ?? false) ? '예' : '아니오';
                        $md .= "| {$key} | {$field['type']} | {$required} | {$field['description']} |\n";
                    }
                    $md .= "\n";
                }
                
                if (!empty($meta['responses'])) {
                    $md .= "**응답:**\n\n";
                    foreach ($meta['responses'] as $status => $response) {
                        $md .= "- **{$status}**: {$response['description']}\n";
                    }
                    $md .= "\n";
                }
                
                $md .= "---\n\n";
            }
        }
        
        return $md;
    }
}
```

### 2.4 문서 조회 API 엔드포인트

**routes/docs/swagger.php:**
```php
<?php
/**
 * @fapi
 * {
 *   "method": "GET",
 *   "path": "/api/docs/swagger",
 *   "summary": "Swagger 문서 조회",
 *   "description": "Swagger/OpenAPI 형식의 API 문서를 반환합니다.",
 *   "tags": ["docs"],
 *   "security": false,
 *   "responses": {
 *     "200": {
 *       "description": "Swagger JSON",
 *       "body": {}
 *     }
 *   }
 * }
 */
function GET() {
    require_once G5_API_PATH . '/lib/Documentation.php';
    
    $swagger = Documentation::generate('swagger');
    
    header('Content-Type: application/json');
    echo $swagger;
    exit;
}
```

**routes/docs/markdown.php:**
```php
<?php
/**
 * @fapi
 * {
 *   "method": "GET",
 *   "path": "/api/docs/markdown",
 *   "summary": "Markdown 문서 조회",
 *   "description": "Markdown 형식의 API 문서를 반환합니다.",
 *   "tags": ["docs"],
 *   "security": false,
 *   "responses": {
 *     "200": {
 *       "description": "Markdown 문서",
 *       "body": {}
 *     }
 *   }
 * }
 */
function GET() {
    require_once G5_API_PATH . '/lib/Documentation.php';
    
    $markdown = Documentation::generate('markdown');
    
    header('Content-Type: text/markdown');
    echo $markdown;
    exit;
}
```

## 3. 사용 방법

### 3.1 문서 생성

**터미널에서:**
```bash
php -r "require 'api/lib/Documentation.php'; echo Documentation::generate('swagger');" > swagger.json
```

**API로 조회:**
```bash
# Swagger JSON
curl http://localhost/api/docs/swagger

# Markdown
curl http://localhost/api/docs/markdown
```

### 3.2 Swagger UI 연동

**public/swagger.html:**
```html
<!DOCTYPE html>
<html>
<head>
    <title>FAPI 문서</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "/api/docs/swagger",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ]
            });
        };
    </script>
</body>
</html>
```

## 4. 자동화된 문서 생성

### 4.1 빌드 스크립트

**scripts/generate-docs.php:**
```php
<?php
require_once __DIR__ . '/../api/lib/Documentation.php';
require_once __DIR__ . '/../api/lib/Config.php';

Config::load();

// Swagger JSON 생성
$swagger = Documentation::generate('swagger');
file_put_contents(__DIR__ . '/../docs/swagger.json', $swagger);

// Markdown 생성
$markdown = Documentation::generate('markdown');
file_put_contents(__DIR__ . '/../docs/api.md', $markdown);

echo "문서 생성 완료!\n";
echo "- docs/swagger.json\n";
echo "- docs/api.md\n";
```

### 4.2 Git Hook 설정

**.git/hooks/pre-commit:**
```bash
#!/bin/bash
php scripts/generate-docs.php
git add docs/swagger.json docs/api.md
```

## 5. 고급 기능

### 5.1 예제 자동 생성

각 라우트 파일에서 실제 사용 예시를 추출하여 문서에 포함:

```php
/**
 * @fapi
 * {
 *   "examples": [
 *     {
 *       "request": {
 *         "mb_id": "testuser",
 *         "mb_password": "password123"
 *       },
 *       "response": {
 *         "code": "00000",
 *         "msg": "로그인 성공"
 *       }
 *     }
 *   ]
 * }
 */
```

### 5.2 테스트 케이스 자동 생성

문서에서 테스트 케이스를 자동으로 생성:

```php
// 자동 생성된 테스트
test('POST /api/auth/login', function() {
    $response = $this->post('/api/auth/login', [
        'mb_id' => 'testuser',
        'mb_password' => 'password123'
    ]);
    
    $response->assertStatus(200);
    $response->assertJson(['code' => '00000']);
});
```

## 6. 구현 난이도 평가

### 6.1 난이도: 중간

**구현 가능한 이유:**
1. 파일 기반 라우팅 구조가 명확함
2. PHP의 Reflection API 활용 가능
3. 파일 스캔 및 파싱이 간단함
4. 주석 기반 메타데이터 추출 가능

**구현 시 고려사항:**
1. 주석 파싱 정확도
2. 동적 경로 처리
3. 타입 추론
4. 예제 자동 생성

### 6.2 단계별 구현 계획

**Phase 1: 기본 문서 생성 (1일)**
- 파일 스캔 및 라우트 추출
- 기본 Swagger JSON 생성
- 간단한 메타데이터 파싱

**Phase 2: 상세 정보 추가 (1일)**
- PHPDoc 주석 파싱
- 요청/응답 스키마 생성
- 태그 및 그룹화

**Phase 3: UI 연동 (0.5일)**
- Swagger UI 연동
- 문서 조회 API 엔드포인트

**Phase 4: 고급 기능 (1일)**
- 예제 자동 생성
- 테스트 케이스 생성
- 문서 자동 업데이트

## 7. 대안: 간단한 문서 생성기

복잡한 Swagger 대신 간단한 문서 생성기도 가능합니다:

**lib/SimpleDoc.php:**
```php
<?php
class SimpleDoc {
    public static function generate() {
        $routes = Router::getAllRoutes(); // 라우터에서 모든 라우트 가져오기
        
        $doc = "# FAPI API 문서\n\n";
        
        foreach ($routes as $route) {
            $doc .= "## {$route['method']} {$route['path']}\n\n";
            $doc .= "파일: `{$route['file']}`\n\n";
            $doc .= "---\n\n";
        }
        
        return $doc;
    }
}
```

이 방식은 구현이 매우 간단하며, 기본적인 API 목록을 빠르게 생성할 수 있습니다.

## 8. 결론

API 문서화 기능은 **구현 가능**하며, 파일 기반 라우팅 구조 덕분에 상대적으로 쉽게 구현할 수 있습니다. 

**권장 접근 방식:**
1. **1단계**: 간단한 문서 생성기로 시작 (라우트 목록만)
2. **2단계**: 주석 기반 메타데이터 추가
3. **3단계**: Swagger/OpenAPI 형식 지원
4. **4단계**: Swagger UI 연동

이렇게 단계적으로 구현하면 점진적으로 기능을 확장할 수 있습니다.

