<?php

namespace DealFileManager\Utils;

use DealFileManager\Config\Database;

class Authentication
{
    /**
     * Validate API token
     */
    public static function validateApiToken(string $token): ?array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM api_tokens WHERE token_key = :token AND is_active = TRUE 
                AND (expires_at IS NULL OR expires_at > NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([':token' => $token]);
        
        $result = $stmt->fetch();
        if ($result) {
            // Update last used timestamp
            $updateSql = "UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':id' => $result['id']]);
        }
        
        return $result;
    }

    /**
     * Get authorization header
     */
    public static function getAuthorizationHeader(): ?string
    {
        $headers = self::getHeaders();
        return $headers['Authorization'] ?? null;
    }

    /**
     * Get all headers
     */
    public static function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('HTTP_', '', $key);
                $header = str_replace('_', '-', $header);
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Verify API request signature
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get current user from session
     */
    public static function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Set current user session
     */
    public static function setCurrentUser(array $userData): void
    {
        $_SESSION['user'] = $userData;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        unset($_SESSION['user']);
    }
}
