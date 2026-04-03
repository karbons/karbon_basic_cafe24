<?php
/**
 * Router - 파일 기반 라우팅 클래스
 * 
 * Sveltekit, Next.js 스타일의 파일 기반 라우팅을 제공합니다.
 * 동적 경로 [param] 형식을 지원합니다.
 */

class Router
{
    /** @var string 라우트 디렉토리 경로 */
    private string $routesDir;

    /** @var string HTTP 메서드 */
    private string $method;

    /** @var string 요청 URL */
    private string $url;

    /** @var array 동적 라우트 파라미터 */
    private array $params = [];

    /** @var int 최대 탐색 깊이 */
    private int $maxDepth = 10;

    /** @var array 라우트 캐시 (정적) */
    private static array $routeCache = [];

    /** @var array 함수 존재 캐시 (정적) */
    private static array $methodCache = [];

    /**
     * Router 생성자
     * 
     * @param string $routesDir 라우트 디렉토리 경로
     */
    public function __construct(string $routesDir)
    {
        $this->routesDir = realpath($routesDir) ?: $routesDir;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->url = $_SERVER['REQUEST_URI'];
    }

    /**
     * 라우팅 실행
     */
    public function dispatch(): void
    {
        $allowedPath = "/v1/fapi/";

        // 미들웨어 실행
        if (file_exists($this->routesDir . '/_middleware.php')) {
            require_once $this->routesDir . '/_middleware.php';
        }

        // 보안 체크: URL이 /fapi/로 시작하고 .. (상위 디렉토리)가 없는지 확인
        if (strpos($this->url, $allowedPath) !== 0 || strpos($this->url, "..") !== false) {
            if (function_exists('response_not_found')) {
                response_not_found('페이지를 찾을 수 없습니다.');
            } else {
                json_return(null, 404, '00001', '페이지를 찾을 수 없습니다.');
            }
            return;
        }

        // NULL 바이트 인젝션 방지
        if (strpos($this->url, "\0") !== false) {
            if (function_exists('response_error')) {
                response_error('잘못된 요청입니다.', '00002', 400);
            } else {
                json_return(null, 400, '00002', '잘못된 요청입니다.');
            }
            return;
        }

        // URL 파싱
        $url = str_replace($allowedPath, '', $this->url);
        $url = trim($url, '/');
        $url = parse_url($url, PHP_URL_PATH);
        $parts = $url ? explode('/', $url) : [];

        // 파라미터 sanitize
        $parts = array_map([$this, 'sanitizePathPart'], $parts);

        // 빈 값 필터링
        $parts = array_values(array_filter($parts, fn($p) => $p !== null && $p !== ''));

        // 라우트 찾기 (캐시 확인)
        $cacheKey = $this->method . ':' . implode('/', $parts);

        if (isset(self::$routeCache[$cacheKey])) {
            $route = self::$routeCache[$cacheKey];
        } else {
            $route = $this->findRoute($parts);
            if ($route) {
                self::$routeCache[$cacheKey] = $route;
            }
        }

        if (!$route) {
            if (function_exists('response_not_found')) {
                response_not_found('페이지를 찾을 수 없습니다.');
            } else {
                json_return(null, 404, '00001', '페이지를 찾을 수 없습니다.');
            }
            return;
        }

        // 보안: 차단 경로 및 확장자 검증
        if ($this->isBlockedPath($route['file'])) {
            if (function_exists('response_forbidden')) {
                response_forbidden('접근 권한이 없습니다.');
            } else {
                json_return(null, 403, '00003', '접근 권한이 없습니다.');
            }
            return;
        }

        // 라우트 실행
        $this->execute($route);
    }

    /**
     * 경로 부분 sanitize (보안)
     */
    private function sanitizePathPart(?string $part): ?string
    {
        if ($part === null || $part === '') {
            return null;
        }

        // 허용: 알파벳, 숫자, 하이픈, 언더스코어, 한글
        // UUID 지원을 위해 하이픈 허용
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-\x{AC00}-\x{D7AF}]/u', '', $part);

        // 빈 문자열이면 null 반환
        return $sanitized !== '' ? $sanitized : null;
    }

    /**
     * 라우트 찾기
     */
    private function findRoute(array $parts): ?array
    {
        // 동적 라우팅 우선 시도
        $route = $this->findDynamicRoute($this->routesDir, $parts, 0, []);
        if ($route) {
            return $route;
        }

        // 하위 호환성: 기존 방식 폴백
        return $this->findLegacyRoute($parts);
    }

    /**
     * 동적 라우트 찾기 (재귀)
     */
    private function findDynamicRoute(string $dir, array $pathParts, int $index, array $params): ?array
    {
        // 최대 깊이 제한 (보안)
        if ($index > $this->maxDepth) {
            return null;
        }

        if (!is_dir($dir)) {
            return null;
        }

        // 실제 경로 검증 (symlink 공격 방지)
        $realDir = realpath($dir);
        if ($realDir === false || strpos($realDir, $this->routesDir) !== 0) {
            return null;
        }

        $currentPart = $pathParts[$index] ?? null;
        $isLast = ($index + 1) >= count($pathParts);

        $files = scandir($dir);

        // 정적 경로를 동적 경로보다 먼저 처리
        usort($files, function ($a, $b) {
            $aIsDynamic = strpos($a, '[') === 0;
            $bIsDynamic = strpos($b, '[') === 0;
            if ($aIsDynamic && !$bIsDynamic)
                return 1;
            if (!$aIsDynamic && $bIsDynamic)
                return -1;
            return strcmp($a, $b);
        });

        foreach ($files as $file) {
            // 숨김 파일, ., .., _ 접두사 파일 무시
            if ($file === '.' || $file === '..' || strpos($file, '_') === 0 || strpos($file, '.') === 0) {
                continue;
            }

            $filePath = $dir . '/' . $file;
            $routeName = str_replace('.php', '', $file);

            if (is_dir($filePath)) {
                $result = $this->matchDirectory($filePath, $file, $currentPart, $pathParts, $index, $params, $isLast);
                if ($result) {
                    return $result;
                }
            } else {
                // .php 파일만 처리 (보안)
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }

                $result = $this->matchFile($filePath, $file, $routeName, $currentPart, $params, $isLast);
                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * 디렉토리 매칭
     */
    private function matchDirectory(string $filePath, string $file, ?string $currentPart, array $pathParts, int $index, array $params, bool $isLast): ?array
    {
        if ($currentPart !== $file && strpos($file, '[') !== 0) {
            return null;
        }

        $newParams = $params;
        if (strpos($file, '[') === 0) {
            // 동적 파라미터 sanitize
            $newParams[] = $this->sanitizePathPart($currentPart);
        }

        // 마지막 경로인 경우 index.php 확인
        if ($isLast) {
            $indexFile = $filePath . '/index.php';
            if (file_exists($indexFile) && $this->hasMethodFunction($indexFile)) {
                return ['file' => $indexFile, 'params' => $newParams];
            }
        }

        // 재귀 탐색
        return $this->findDynamicRoute($filePath, $pathParts, $index + 1, $newParams);
    }

    /**
     * 파일 매칭
     */
    private function matchFile(string $filePath, string $file, string $routeName, ?string $currentPart, array $params, bool $isLast): ?array
    {
        if (!$isLast) {
            return null;
        }

        // 정확한 파일명 매칭
        if ($currentPart === $routeName && $this->hasMethodFunction($filePath)) {
            return ['file' => $filePath, 'params' => $params];
        }

        // 동적 파라미터 파일 매칭
        if (strpos($file, '[') === 0 && strpos($file, ']') !== false) {
            $newParams = $params;
            $newParams[] = $this->sanitizePathPart($currentPart);
            if ($this->hasMethodFunction($filePath)) {
                return ['file' => $filePath, 'params' => $newParams];
            }
        }

        // index.php 매칭
        if (($currentPart === null || $currentPart === '') && $routeName === 'index') {
            if ($this->hasMethodFunction($filePath)) {
                return ['file' => $filePath, 'params' => $params];
            }
        }

        return null;
    }

    /**
     * 레거시 라우트 찾기 (하위 호환성)
     */
    private function findLegacyRoute(array $parts): ?array
    {
        if (empty($parts)) {
            $rootIndex = $this->routesDir . '/index.php';
            if (file_exists($rootIndex)) {
                return ['file' => $rootIndex, 'params' => []];
            }
            return null;
        }

        $dirPath = implode('/', $parts);

        // 1순위: 디렉토리/index.php
        $indexPath = $this->routesDir . '/' . $dirPath . '/index.php';
        if (file_exists($indexPath) && $this->hasMethodFunction($indexPath)) {
            return ['file' => $indexPath, 'params' => []];
        }

        // 2순위: 직접 파일명.php
        $directPath = $this->routesDir . '/' . $dirPath . '.php';
        if (file_exists($directPath) && $this->hasMethodFunction($directPath)) {
            return ['file' => $directPath, 'params' => []];
        }

        // 3순위: 역순 탐색 (최대 3단계)
        $maxDepth = min(count($parts), 3);
        for ($i = $maxDepth; $i > 0; $i--) {
            $fileName = $parts[$i - 1] . '.php';
            $subDirPath = implode('/', array_slice($parts, 0, $i - 1));
            $filePath = $this->routesDir . ($subDirPath ? '/' . $subDirPath : '') . '/' . $fileName;

            if (file_exists($filePath) && $this->hasMethodFunction($filePath)) {
                return ['file' => $filePath, 'params' => []];
            }
        }

        return null;
    }

    /**
     * HTTP 메서드 함수 존재 확인 (캐싱)
     */
    private function hasMethodFunction(string $filePath): bool
    {
        $cacheKey = $filePath . ':' . $this->method;

        if (isset(self::$methodCache[$cacheKey])) {
            return self::$methodCache[$cacheKey];
        }

        // OPcache 활성화 시 file_get_contents도 캐시됨
        $content = @file_get_contents($filePath);
        if ($content === false) {
            self::$methodCache[$cacheKey] = false;
            return false;
        }

        $result = preg_match('/function\s+' . $this->method . '\s*\(/', $content) === 1;
        self::$methodCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 차단된 경로인지 확인 (보안 강화)
     */
    private function isBlockedPath(string $filePath): bool
    {
        // 실제 경로 확인
        $realPath = realpath($filePath);
        if ($realPath === false) {
            return true;  // 존재하지 않는 파일은 차단
        }

        // routes 디렉토리 외부 접근 차단
        if (strpos($realPath, $this->routesDir) !== 0) {
            return true;
        }

        // .php 확장자 검증
        if (pathinfo($realPath, PATHINFO_EXTENSION) !== 'php') {
            return true;
        }

        // 차단 경로 패턴
        $blockedPatterns = [
            '/lib/',
            '/shared/',
            '/config/',
            '/_common',
            '/.git/',
            '/.env'
        ];

        foreach ($blockedPatterns as $pattern) {
            if (strpos($realPath, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 라우트 실행
     */
    private function execute(array $route): void
    {
        require_once $route['file'];

        if (!function_exists($this->method)) {
            if (function_exists('response_not_found')) {
                response_not_found('함수를 찾을 수 없습니다.');
            } else {
                json_return(null, 404, '00001', '함수를 찾을 수 없습니다.');
            }
            return;
        }

        if (!empty($route['params'])) {
            call_user_func_array($this->method, $route['params']);
        } else {
            call_user_func($this->method);
        }
    }

    /**
     * 캐시 클리어 (개발/테스트용)
     */
    public static function clearCache(): void
    {
        self::$routeCache = [];
        self::$methodCache = [];
    }
}
