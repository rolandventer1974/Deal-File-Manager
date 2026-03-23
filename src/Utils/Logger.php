<?php

namespace DealFileManager\Utils;

class Logger
{
    private static string $logFile;

    /**
     * Set log file path
     */
    public static function setLogFile(string $filePath): void
    {
        self::$logFile = $filePath;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
    }

    /**
     * Log message
     */
    public static function log(string $message, string $level = 'INFO', ?array $context = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        $logEntry = "[{$timestamp}] [{$level}] [{$ip}] {$message}";
        
        if (!empty($context)) {
            $logEntry .= " | Context: " . json_encode($context);
        }
        
        $logEntry .= "\n";

        if (isset(self::$logFile)) {
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
        }
        
        // Also log to PHP error log
        error_log($logEntry);
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, ?array $context = null): void
    {
        self::log($message, 'DEBUG', $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, ?array $context = null): void
    {
        self::log($message, 'INFO', $context);
    }

    /**
     * Log warning
     */
    public static function warning(string $message, ?array $context = null): void
    {
        self::log($message, 'WARNING', $context);
    }

    /**
     * Log error
     */
    public static function error(string $message, ?array $context = null): void
    {
        self::log($message, 'ERROR', $context);
    }

    /**
     * Log activity
     */
    public static function logActivity(int $dealFileId, string $action, string $performedBy, ?string $details = null): void
    {
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO activity_logs (deal_file_id, action, performed_by, details, ip_address)
                    VALUES (:deal_file_id, :action, :performed_by, :details, :ip_address)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':deal_file_id' => $dealFileId,
                ':action' => $action,
                ':performed_by' => $performedBy,
                ':details' => $details,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            self::info("Activity logged: {$action} on deal file {$dealFileId}");
        } catch (\Exception $e) {
            self::error("Failed to log activity: " . $e->getMessage());
        }
    }
}
