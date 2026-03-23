<?php

namespace DealFileManager\Utils;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(string $message = 'Error', mixed $error = null, int $statusCode = 400): void
    {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'error' => $error
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(array $errors): void
    {
        self::sendResponse([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 401);
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Send JSON response
     */
    private static function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
