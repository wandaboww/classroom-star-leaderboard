<?php
/**
 * Database — PDO Singleton Wrapper
 */

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                // Di production: log error, jangan tampilkan detail koneksi
                $appConfig = require dirname(__DIR__) . '/config/app.php';
                if ($appConfig['env'] === 'development') {
                    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
                } else {
                    die(json_encode(['success' => false, 'message' => 'Service unavailable. Please try again later.']));
                }
            }
        }
        return self::$instance;
    }

    /**
     * Shorthand untuk prepare + execute + fetchAll
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Shorthand untuk fetch satu baris
     */
    public static function queryOne(string $sql, array $params = []): array|false
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Execute INSERT / UPDATE / DELETE, return affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }
}
