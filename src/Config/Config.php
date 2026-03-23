<?php

namespace DealFileManager\Config;

class Config
{
    private static array $config = [];

    /**
     * Load configuration from .env file
     */
    public static function load(string $envFile): void
    {
        if (!file_exists($envFile)) {
            throw new \Exception("Configuration file not found: {$envFile}");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || trim($line) === '') {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, ' "\'');
            $_ENV[$key] = $value;
            self::$config[$key] = $value;
        }
    }

    /**
     * Get configuration value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? self::$config[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, mixed $value): void
    {
        $_ENV[$key] = $value;
        self::$config[$key] = $value;
    }

    /**
     * Check if configuration key exists
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset(self::$config[$key]);
    }

    /**
     * Get all configurations
     */
    public static function all(): array
    {
        return array_merge(self::$config, $_ENV);
    }
}
