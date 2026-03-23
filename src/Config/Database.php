<?php

namespace DealFileManager\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    /**
     * Initialize database configuration from environment variables
     */
    public static function loadConfig(): void
    {
        self::$config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'name' => $_ENV['DB_NAME'] ?? 'deal_file_manager',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASSWORD'] ?? '',
        ];
    }

    /**
     * Get database connection
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::loadConfig();
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }

    /**
     * Create database connection
     */
    private static function createConnection(): PDO
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s:%s;dbname=%s;charset=utf8mb4',
                self::$config['host'],
                self::$config['port'],
                self::$config['name']
            );

            $pdo = new PDO(
                $dsn,
                self::$config['user'],
                self::$config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection error. Please contact administrator.");
        }
    }

    /**
     * Close database connection
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }
}
