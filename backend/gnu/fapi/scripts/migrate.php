<?php
/**
 * Migration Runner
 * 
 * 마이그레이션 파일을 실행하고 버전 관리
 * 
 * 사용법:
 *   php migrate.php              # 모든 마이그레이션 실행
 *   php migrate.php status       # 상태 확인
 *   php migrate.php rollback     # 마지막 마이그레이션 롤백
 *   php migrate.php reset       # 모든 마이그레이션 롤백
 *   php migrate.php 001          # 특정 버전까지만 실행
 */

require_once __DIR__ . '/../bootstrap.php';

class Migrator
{
    private string $migrationsPath;
    private string $tableName;
    private PDO $pdo;

    public function __construct()
    {
        $this->migrationsPath = G5_API_PATH . '/migrations';
        $this->tableName = 'migrations';
        
        $host = getenv('G5_MYSQL_HOST') ?: 'localhost';
        $db = getenv('G5_MYSQL_DB') ?: '';
        $user = getenv('G5_MYSQL_USER') ?: 'root';
        $pass = getenv('G5_MYSQL_PASSWORD') ?: '';
        $charset = getenv('G5_DB_CHARSET') ?: 'utf8mb4';
        $port = getenv('G5_MYSQL_PORT') ?: 3306;

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            version VARCHAR(14) NOT NULL,
            name VARCHAR(255) NOT NULL,
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $this->pdo->exec($sql);
    }

    public function getExecutedVersions(): array
    {
        $stmt = $this->pdo->query("SELECT version FROM {$this->tableName} ORDER BY version");
        return array_column($stmt->fetchAll(), 'version');
    }

    public function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            $basename = basename($file, '.sql');
            $parts = explode('_', $basename, 2);
            if (count($parts) >= 1) {
                $version = $parts[0];
                $name = isset($parts[1]) ? $parts[1] : $basename;
                $migrations[$version] = [
                    'file' => $file,
                    'version' => $version,
                    'name' => $name
                ];
            }
        }
        
        ksort($migrations);
        return $migrations;
    }

    public function getPendingMigrations(): array
    {
        $executed = $this->getExecutedVersions();
        $files = $this->getMigrationFiles();
        $pending = [];
        
        foreach ($files as $version => $migration) {
            if (!in_array($version, $executed)) {
                $pending[$version] = $migration;
            }
        }
        
        return $pending;
    }

    public function run(?string $targetVersion = null): void
    {
        $this->ensureTable();
        
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            echo "✓ 모든 마이그레이션이 실행되어 있습니다.\n";
            return;
        }

        if ($targetVersion) {
            $pending = array_filter($pending, fn($v) => $v <= $targetVersion, ARRAY_FILTER_USE_KEY);
        }

        foreach ($pending as $version => $migration) {
            $this->runMigration($version, $migration['name'], $migration['file']);
        }
    }

    public function status(): void
    {
        $this->ensureTable();
        
        $executed = $this->getExecutedVersions();
        $files = $this->getMigrationFiles();
        
        echo "\n=== 마이그레이션 상태 ===\n\n";
        
        if (empty($files)) {
            echo "마이그레이션 파일이 없습니다.\n";
            return;
        }

        foreach ($files as $version => $migration) {
            $status = in_array($version, $executed) ? '✓' : '○';
            $marker = in_array($version, $executed) ? ' [실행됨]' : ' [대기]';
            echo sprintf("%s %s %s%s\n", $status, $version, $migration['name'], $marker);
        }
        
        $pending = count($this->getPendingMigrations());
        echo "\n{$pending}개 마이그레이션 대기 중\n";
    }

    public function rollback(int $steps = 1): void
    {
        $executed = $this->getExecutedVersions();
        
        if (empty($executed)) {
            echo "롤백할 마이그레이션이 없습니다.\n";
            return;
        }

        $lastVersions = array_slice($executed, -$steps);
        
        foreach (array_reverse($lastVersions) as $version) {
            $this->rollbackMigration($version);
        }
    }

    public function reset(): void
    {
        $executed = $this->getExecutedVersions();
        
        if (empty($executed)) {
            echo "롤백할 마이그레이션이 없습니다.\n";
            return;
        }

        foreach (array_reverse($executed) as $version) {
            $this->rollbackMigration($version);
        }
    }

    private function runMigration(string $version, string $name, string $file): void
    {
        echo "▶ {$version} - {$name} 실행 중...\n";
        
        $sql = file_get_contents($file);
        $sql = str_replace('{PREFIX}', 'g5_', $sql);
        
        try {
            $this->pdo->beginTransaction();
            
            $this->pdo->exec($sql);
            
            $stmt = $this->pdo->prepare("INSERT INTO {$this->tableName} (version, name) VALUES (?, ?)");
            $stmt->execute([$version, $name]);
            
            $this->pdo->commit();
            echo "✓ {$version} - {$name} 완료\n";
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            echo "✗ {$version} - {$name} 실패: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function rollbackMigration(string $version): void
    {
        echo "◀ {$version} 롤백 중...\n";
        
        $stmt = $this->pdo->prepare("SELECT name FROM {$this->tableName} WHERE version = ?");
        $stmt->execute([$version]);
        $row = $stmt->fetch();
        
        if (!$row) {
            echo "✗ {$version} 기록을 찾을 수 없습니다.\n";
            return;
        }
        
        echo "✗ {$version} - {$row['name']} 롤백 완료 (데이터는 수동 복구 필요)\n";
        
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE version = ?");
        $stmt->execute([$version]);
    }
}

$action = $argv[1] ?? 'run';
$target = $argv[2] ?? null;

$migrator = new Migrator();

switch ($action) {
    case 'status':
        $migrator->status();
        break;
    case 'rollback':
        $migrator->rollback((int)($target ?: 1));
        break;
    case 'reset':
        $migrator->reset();
        break;
    case 'run':
    default:
        $migrator->run($target);
        break;
}
