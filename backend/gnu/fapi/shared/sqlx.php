<?php
/**
 * sqlx - Rust sqlx 스타일 데이터베이스 라이브러리
 * 
 * PDO Prepared Statements를 사용하여 SQL Injection 원천 차단
 * Rust sqlx와 동일한 문법으로 PHP → Rust 기계적 전환 가능
 * 
 * 사용법:
 *   // 조회
 *   $user = sqlx::query("SELECT * FROM users WHERE id = ?")
 *       ->bind($id)
 *       ->fetch_optional();
 *   
 *   // 트랜잭션
 *   sqlx::transaction(function() {
 *       sqlx::query("UPDATE ...")->execute();
 *   });
 * 
 * 환경변수 (.env):
 *   LOG_LEVEL: 0~4 (0=없음, 4=debug 포함)
 *   SQLX_DEBUG: true/false (쿼리 로깅)
 */

// Logger 라이브러리 로드
if (file_exists(__DIR__ . '/logger.php')) {
    require_once __DIR__ . '/logger.php';
}

/**
 * sqlx 클래스 - Rust sqlx 동일 문법
 */
class sqlx
{
    /** @var array<string, PDO> Named PDO connection pools */
    private static array $pools = [];

    /** @var string Current pool name */
    private static string $currentPool = 'default';

    /** @var bool 디버그 모드 */
    private static bool $debug = false;

    /** @var string|null 마이그레이션 실행 여부 */
    private static ?string $migrated = null;

    /** @var string SQL 쿼리 */
    private string $sql;

    /** @var array 위치 기반 바인딩 값 */
    private array $bindings = [];

    /** @var array 이름 기반 바인딩 값 */
    private array $namedBindings = [];

    /** @var float 쿼리 시작 시간 */
    private float $startTime;

    /** @var string 이 쿼리에서 사용할 pool 이름 */
    private string $poolName;

    /**
     * Private constructor - factory 패턴 사용
     */
    private function __construct(string $sql, string $poolName = 'default')
    {
        $this->sql = $sql;
        $this->startTime = microtime(true);
        $this->poolName = $poolName;
    }

    // =========================================================================
    // Connection Pool Management (Multi-DB Support)
    // =========================================================================

    /**
     * 연결 풀 생성/등록
     * Rust: Pool::connect("postgres://...")
     * 
     * @param string $name 풀 이름 (default, read, write, analytics 등)
     * @param array $config DB 설정 ['host', 'database', 'user', 'password', 'charset']
     * @return PDO
     */
    public static function create_pool(string $name, array $config): PDO
    {
        $host = $config['host'] ?? 'localhost';
        $db = $config['database'] ?? '';
        $user = $config['user'] ?? 'root';
        $pass = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $port = $config['port'] ?? 3306;

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => $config['persistent'] ?? false,
        ];

        try {
            self::$pools[$name] = new PDO($dsn, $user, $pass, $options);
            self::$debug = (bool) (getenv('SQLX_DEBUG') ?: (getenv('APP_DEBUG') ?: false));

            if (self::$debug && function_exists('log_debug')) {
                log_debug("sqlx: Pool '{$name}' created", ['host' => $host, 'database' => $db]);
            }
        } catch (PDOException $e) {
            if (function_exists('log_error')) {
                log_error("sqlx: Pool '{$name}' connection failed", ['error' => $e->getMessage()]);
            }
            throw $e;
        }

        return self::$pools[$name];
    }

    /**
     * 기본(default) PDO 연결 초기화
     * data/dbconfig.php의 그누보드 DB 설정을 사용
     */
    public static function connect(): PDO
    {
        if (isset(self::$pools['default'])) {
            return self::$pools['default'];
        }

        if (!defined('G5_MYSQL_HOST')) {
            if (!defined('_GNUBOARD_')) {
                define('_GNUBOARD_', true);
            }
            $dbconfig_path = dirname(dirname(__DIR__)) . '/data/dbconfig.php';
            if (file_exists($dbconfig_path)) {
                require_once $dbconfig_path;
            }
        }

        $pdo = self::create_pool('default', [
            'host' => defined('G5_MYSQL_HOST') ? G5_MYSQL_HOST : 'localhost',
            'database' => defined('G5_MYSQL_DB') ? G5_MYSQL_DB : 'gnuboard',
            'user' => defined('G5_MYSQL_USER') ? G5_MYSQL_USER : 'root',
            'password' => defined('G5_MYSQL_PASSWORD') ? G5_MYSQL_PASSWORD : '',
            'charset' => defined('G5_DB_CHARSET') ? G5_DB_CHARSET : 'utf8mb4',
            'persistent' => false,
        ]);

        self::autoMigrate($pdo);

        return $pdo;
    }

    /**
     * 자동 마이그레이션 실행
     */
    private static function autoMigrate(PDO $pdo): void
    {
        if (self::$migrated !== null) {
            return;
        }
        self::$migrated = 'checked';

        if (!function_exists('env')) {
            require_once dirname(__DIR__) . '/config/env.php';
        }

        if (!env('SQL_DEV_MODE', false)) {
            return;
        }

        $migrations_path = dirname(__DIR__) . '/migrations';
        if (!is_dir($migrations_path)) {
            return;
        }

        $table = 'migrations';
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (
            version VARCHAR(14) NOT NULL,
            name VARCHAR(255) NOT NULL,
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $stmt = $pdo->query("SELECT version FROM {$table} ORDER BY version");
        $executed = array_column($stmt->fetchAll(), 'version');

        $files = glob($migrations_path . '/*.sql');
        $pending = [];

        foreach ($files as $file) {
            $basename = basename($file, '.sql');
            $parts = explode('_', $basename, 2);
            $version = $parts[0];
            if (!in_array($version, $executed)) {
                $pending[$version] = $file;
            }
        }

        if (!empty($pending)) {
            ksort($pending);
            foreach ($pending as $version => $file) {
                $name = isset($parts[1]) ? $parts[1] : $basename;
                $sql = file_get_contents($file);
                $sql = str_replace('{PREFIX}', 'g5_', $sql);

                try {
                    $pdo->beginTransaction();
                    $pdo->exec($sql);
                    $stmt = $pdo->prepare("INSERT INTO {$table} (version, name) VALUES (?, ?)");
                    $stmt->execute([$version, $name]);
                    $pdo->commit();

                    if (function_exists('log_debug')) {
                        log_debug("sqlx: Migration {$version} executed");
                    }
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if (function_exists('log_error')) {
                        log_error("sqlx: Migration {$version} failed", ['error' => $e->getMessage()]);
                    }
                }
            }
        }
    }

    /**
     * 특정 풀의 PDO 인스턴스 반환
     * Rust: pool 참조
     * 
     * @param string $name 풀 이름
     * @return PDO
     */
    public static function pool(string $name = 'default'): PDO
    {
        if (!isset(self::$pools[$name])) {
            if ($name === 'default') {
                return self::connect();
            }
            throw new RuntimeException("sqlx: Pool '{$name}' not found. Create it first with create_pool()");
        }
        return self::$pools[$name];
    }

    /**
     * 현재 사용할 기본 풀 설정
     * 
     * @param string $name 풀 이름
     */
    public static function use_pool(string $name): void
    {
        if (!isset(self::$pools[$name]) && $name !== 'default') {
            throw new RuntimeException("sqlx: Pool '{$name}' not found");
        }
        self::$currentPool = $name;
    }

    /**
     * 특정 풀 연결 종료
     */
    public static function disconnect(string $name = 'default'): void
    {
        unset(self::$pools[$name]);
    }

    /**
     * 모든 풀 연결 종료
     */
    public static function disconnect_all(): void
    {
        self::$pools = [];
    }

    /**
     * 등록된 모든 풀 이름 반환
     */
    public static function pool_names(): array
    {
        return array_keys(self::$pools);
    }

    // =========================================================================
    // Query Builder
    // =========================================================================

    /**
     * 쿼리 생성 (sqlx::query와 동일)
     * 
     * @param string $sql SQL 쿼리 (? 또는 :name 플레이스홀더)
     * @return self
     */
    public static function query(string $sql): self
    {
        self::connect();  // 기본 연결 확인
        return new self($sql, self::$currentPool);
    }

    /**
     * 특정 풀에서 쿼리 실행
     * Rust: sqlx::query(...).fetch_one(&specific_pool)
     * 
     * @param string $poolName 풀 이름
     * @param string $sql SQL 쿼리
     * @return self
     */
    public static function query_with_pool(string $poolName, string $sql): self
    {
        self::pool($poolName);  // 풀 존재 확인
        return new self($sql, $poolName);
    }

    /**
     * 위치 기반 바인딩 (?)
     * Rust: .bind(&value)
     * 
     * @param mixed $value 바인딩 값
     * @return self
     */
    public function bind($value): self
    {
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * 이름 기반 바인딩 (:name)
     * 
     * @param string $name 파라미터 이름 (: 없이)
     * @param mixed $value 바인딩 값
     * @return self
     */
    public function bind_named(string $name, $value): self
    {
        $this->namedBindings[$name] = $value;
        return $this;
    }

    // =========================================================================
    // Fetch Methods
    // =========================================================================

    /**
     * 단일 행 조회 (없으면 Exception)
     * Rust: .fetch_one(&pool).await?
     * 
     * @return array
     * @throws RuntimeException 결과가 없을 때
     */
    public function fetch_one(): array
    {
        $result = $this->fetch_optional();

        if ($result === null) {
            throw new RuntimeException('sqlx: No rows returned for fetch_one()');
        }

        return $result;
    }

    /**
     * 단일 행 조회 (없으면 null) ⭐ 실무 필수
     * Rust: .fetch_optional(&pool).await?
     * 
     * @return array|null
     */
    public function fetch_optional(): ?array
    {
        $stmt = $this->prepareAndExecute();
        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * 단일 값 조회 (COUNT, SUM, EXISTS 등)
     * Rust: query_scalar()
     * 
     * @return mixed
     */
    public function fetch_scalar(): mixed
    {
        $stmt = $this->prepareAndExecute();
        return $stmt->fetchColumn();
    }

    /**
     * 모든 행 조회
     * Rust: .fetch_all(&pool).await?
     * 
     * @return array
     */
    public function fetch_all(): array
    {
        $stmt = $this->prepareAndExecute();
        return $stmt->fetchAll();
    }

    /**
     * DTO/클래스로 매핑하여 조회
     * Rust: query_as::<_, User>()
     * 
     * @param string $class 클래스명
     * @return object|null
     */
    public function fetch_into(string $class): ?object
    {
        $stmt = $this->prepareAndExecute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, $class);
        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * 모든 행을 DTO/클래스로 매핑
     * 
     * @param string $class 클래스명
     * @return array
     */
    public function fetch_all_into(string $class): array
    {
        $stmt = $this->prepareAndExecute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, $class);
    }

    // =========================================================================
    // Execute Methods
    // =========================================================================

    /**
     * 쿼리 실행 (INSERT/UPDATE/DELETE)
     * Rust: .execute(&pool).await?
     * 
     * @return bool
     */
    public function execute(): bool
    {
        $stmt = $this->prepareAndExecute();
        return true;
    }

    /**
     * INSERT 후 마지막 ID 반환
     * 
     * @return int
     */
    public function execute_insert_id(): int
    {
        $this->prepareAndExecute();
        return (int) self::pool($this->poolName)->lastInsertId();
    }

    /**
     * 영향받은 행 수 반환
     * 
     * @return int
     */
    public function execute_rows_affected(): int
    {
        $stmt = $this->prepareAndExecute();
        return $stmt->rowCount();
    }

    // =========================================================================
    // Transaction API ⭐⭐⭐
    // =========================================================================

    /**
     * 트랜잭션 시작
     * Rust: pool.begin().await?
     */
    public static function begin(): void
    {
        $pool = self::pool();

        if ($pool->inTransaction()) {
            if (function_exists('log_warning')) {
                log_warning('sqlx: Transaction already started');
            }
            return;
        }

        $pool->beginTransaction();

        if (self::$debug && function_exists('log_debug')) {
            log_debug('sqlx: Transaction BEGIN');
        }
    }

    /**
     * 커밋
     * Rust: tx.commit().await?
     */
    public static function commit(): void
    {
        $pool = self::pool();
        if (!$pool->inTransaction()) {
            if (function_exists('log_warning')) {
                log_warning('sqlx: No transaction to commit');
            }
            return;
        }

        $pool->commit();

        if (self::$debug && function_exists('log_debug')) {
            log_debug('sqlx: Transaction COMMIT');
        }
    }

    /**
     * 롤백
     * Rust: tx.rollback().await?
     */
    public static function rollback(): void
    {
        $pool = self::pool();
        if (!$pool->inTransaction()) {
            if (function_exists('log_warning')) {
                log_warning('sqlx: No transaction to rollback');
            }
            return;
        }

        $pool->rollBack();

        if (self::$debug && function_exists('log_debug')) {
            log_debug('sqlx: Transaction ROLLBACK');
        }
    }

    /**
     * 클로저 기반 트랜잭션 (권장)
     * 예외 발생 시 자동 롤백
     * 
     * @param callable $callback 트랜잭션 내 실행할 함수
     * @return mixed 콜백 반환값
     * @throws Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        self::begin();

        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (Throwable $e) {
            self::rollback();

            if (function_exists('log_error')) {
                log_error('sqlx: Transaction failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }

            throw $e;
        }
    }

    /**
     * 현재 트랜잭션 중인지 확인
     * 
     * @return bool
     */
    public static function in_transaction(): bool
    {
        return isset(self::$pools[self::$currentPool]) && self::pool()->inTransaction();
    }

    // =========================================================================
    // Migration API ⭐⭐⭐
    // =========================================================================

    /**
     * 마이그레이션 실행
     * 
     * @param string $path 마이그레이션 디렉토리 경로
     * @return array 실행된 마이그레이션 목록
     */
    public static function migrate(string $path = ''): array
    {
        if (!$path) {
            $path = defined('G5_API_PATH') ? G5_API_PATH . '/migrations' : __DIR__ . '/../migrations';
        }

        if (!is_dir($path)) {
            throw new RuntimeException("Migration directory not found: {$path}");
        }

        $pool = self::pool();

        // 마이그레이션 테이블 생성
        $pool->exec("
            CREATE TABLE IF NOT EXISTS _sqlx_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 이미 실행된 마이그레이션 조회
        $executed = self::query("SELECT name FROM _sqlx_migrations")
            ->fetch_all();
        $executedNames = array_column($executed, 'name');

        // 마이그레이션 파일 목록
        $files = glob($path . '/*.sql');
        sort($files);  // 숫자 순서대로 정렬

        $migrated = [];

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $executedNames)) {
                continue;  // 이미 실행됨
            }

            $sql = file_get_contents($file);

            try {
                self::begin();

                // 세미콜론으로 분리하여 각 쿼리 실행
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if ($statement) {
                        self::pool()->exec($statement);
                    }
                }

                // 마이그레이션 기록
                self::query("INSERT INTO _sqlx_migrations (name) VALUES (?)")
                    ->bind($name)
                    ->execute();

                self::commit();

                $migrated[] = $name;

                if (function_exists('log_info')) {
                    log_info("sqlx: Migration executed", ['name' => $name]);
                }
            } catch (Throwable $e) {
                self::rollback();

                if (function_exists('log_error')) {
                    log_error("sqlx: Migration failed", [
                        'name' => $name,
                        'error' => $e->getMessage()
                    ]);
                }

                throw new RuntimeException("Migration failed: {$name} - " . $e->getMessage());
            }
        }

        return $migrated;
    }

    // =========================================================================
    // Debug & Logging
    // =========================================================================

    /**
     * 디버그 모드 활성화/비활성화
     */
    public static function enable_log(bool $enable = true): void
    {
        self::$debug = $enable;
    }

    /**
     * 현재 디버그 상태
     */
    public static function is_debug(): bool
    {
        return self::$debug;
    }

    // =========================================================================
    // Internal Methods
    // =========================================================================

    /**
     * PDO Prepared Statement 생성 및 실행
     * 
     * @return PDOStatement
     * @throws PDOException
     */
    private function prepareAndExecute(): PDOStatement
    {
        try {
            $stmt = self::pool($this->poolName)->prepare($this->sql);

            // 위치 기반 바인딩 (?)
            foreach ($this->bindings as $i => $value) {
                $stmt->bindValue($i + 1, $value, $this->getPdoType($value));
            }

            // 이름 기반 바인딩 (:name)
            foreach ($this->namedBindings as $name => $value) {
                $param = ':' . ltrim($name, ':');
                $stmt->bindValue($param, $value, $this->getPdoType($value));
            }

            $stmt->execute();

            // 디버그 로깅
            if (self::$debug) {
                $this->logQuery();
            }

            return $stmt;
        } catch (PDOException $e) {
            // 에러 로깅
            if (function_exists('log_db_error')) {
                log_db_error($this->sql, $e->getMessage(), [
                    'bindings' => $this->bindings,
                    'named' => $this->namedBindings
                ]);
            }

            throw $e;
        }
    }

    /**
     * 값의 PDO 타입 반환
     */
    private function getPdoType($value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    /**
     * 쿼리 로깅
     */
    private function logQuery(): void
    {
        $elapsed = round((microtime(true) - $this->startTime) * 1000, 2);

        $logData = [
            'sql' => $this->sql,
            'time_ms' => $elapsed
        ];

        if (!empty($this->bindings)) {
            $logData['bindings'] = $this->bindings;
        }
        if (!empty($this->namedBindings)) {
            $logData['named'] = $this->namedBindings;
        }

        if (function_exists('log_debug')) {
            log_debug('sqlx: Query executed', $logData);
        }
    }
}
