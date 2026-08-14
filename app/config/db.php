<?php
// =============================================================================
// UNIFIND - SISTEMA DE ACHADOS E PERDIDOS (MYSQL + REDIS)
// Configuração de Conexão com MySQL (PDO) e Redis
// =============================================================================

class Database {
    private static ?PDO $pdo = null;
    private static $redis = null;
    private static bool $schemaChecked = false;

    /**
     * Retorna a conexão PDO com o MySQL configurada
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            // Suporte a DATABASE_URL ou MYSQL_URL (comum em Railway, Render, Heroku)
            $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
            
            if ($dbUrl) {
                $parsed = parse_url($dbUrl);
                $host = $parsed['host'] ?? 'localhost';
                $port = $parsed['port'] ?? 3306;
                $user = $parsed['user'] ?? 'root';
                $pass = $parsed['pass'] ?? '';
                $dbName = ltrim($parsed['path'] ?? 'unifind_db', '/');
            } else {
                $host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1';
                $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: getenv('MYSQLPORT') ?: '3306';
                $dbName = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'unifind_db';
                $user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: 'root';
                $pass = getenv('DB_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Tenta conectar sem o nome do banco para auto-criar se necessário
                try {
                    $dsnNoDb = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $tempPdo = new PDO($dsnNoDb, $user, $pass, $options);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    self::$pdo = new PDO($dsn, $user, $pass, $options);
                } catch (Exception $ex) {
                    die("Erro de conexão com o banco de dados MySQL: " . $e->getMessage());
                }
            }

            // Garante que as tabelas existem se executado em servidor online novo
            if (!self::$schemaChecked) {
                self::ensureSchema(self::$pdo);
                self::$schemaChecked = true;
            }
        }

        return self::$pdo;
    }

    /**
     * Retorna a instância do Redis configurada ou null em caso de indisponibilidade
     */
    public static function getRedis() {
        if (self::$redis === null) {
            $redisUrl = getenv('REDIS_URL');
            if ($redisUrl) {
                $parsed = parse_url($redisUrl);
                $redisHost = $parsed['host'] ?? '127.0.0.1';
                $redisPort = $parsed['port'] ?? 6379;
                $redisPass = $parsed['pass'] ?? null;
            } else {
                $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
                $redisPort = getenv('REDIS_PORT') ?: 6379;
                $redisPass = getenv('REDIS_PASSWORD') ?: null;
            }

            if (class_exists('Redis')) {
                try {
                    $r = new Redis();
                    $connected = @$r->connect($redisHost, (int)$redisPort, 1.5);
                    if ($connected) {
                        if ($redisPass) {
                            $r->auth($redisPass);
                        }
                        self::$redis = $r;
                    }
                } catch (Exception $e) {
                    self::$redis = null;
                }
            }
        }
        return self::$redis;
    }

    /**
     * Auto-inicialização de schema para garantir que tudo funcione mesmo em hosts remotos
     */
    private static function ensureSchema(PDO $pdo): void {
        try {
            $tables = $pdo->query("SHOW TABLES LIKE 'itens'")->fetchAll();
            if (empty($tables)) {
                $initFile = __DIR__ . '/../../mysql/init.sql';
                if (file_exists($initFile)) {
                    $sql = file_get_contents($initFile);
                    $pdo->exec($sql);
                }
            }
        } catch (Exception $e) {
            // Silencia caso já exista ou não tenha permissão de DDL
        }
    }
}

// -----------------------------------------------------------------------------
// Funções auxiliares (Helpers) para manipulação de dados com MySQL
// -----------------------------------------------------------------------------

function getDb(): PDO {
    return Database::getConnection();
}

function dbQuery(string $sql, array $params = []): PDOStatement {
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

function dbFetchOne(string $sql, array $params = []): ?array {
    $result = dbQuery($sql, $params)->fetch();
    return $result ?: null;
}

function dbInsert(string $table, array $data): int {
    $columns = array_keys($data);
    $fields = implode(', ', array_map(fn($col) => "`{$col}`", $columns));
    $placeholders = implode(', ', array_map(fn($col) => ":{$col}", $columns));

    $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
    $stmt = getDb()->prepare($sql);
    
    $params = [];
    foreach ($data as $col => $val) {
        $params[":{$col}"] = $val;
    }
    
    $stmt->execute($params);
    return (int)getDb()->lastInsertId();
}

function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int {
    $setParts = [];
    $params = [];

    foreach ($data as $col => $val) {
        $setParts[] = "`{$col}` = :set_{$col}";
        $params[":set_{$col}"] = $val;
    }

    $setSql = implode(', ', $setParts);
    $sql = "UPDATE `{$table}` SET {$setSql} WHERE {$where}";

    $stmt = getDb()->prepare($sql);
    $stmt->execute(array_merge($params, $whereParams));
    return $stmt->rowCount();
}
