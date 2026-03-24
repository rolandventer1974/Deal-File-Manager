<?php

namespace DealFileManager\Controllers;

use DealFileManager\Utils\Logger;

class LoginController
{
    /**
     * Display login page
     */
    public function index(): void
    {
        try {
            $pageTitle = 'Deal File Manager - Login';
            $this->render('login/index', compact('pageTitle'));
        } catch (\Exception $e) {
            Logger::error("Login page error: " . $e->getMessage());
            $this->renderError('Failed to load login page', 500);
        }
    }

    /**
     * Render login view
     */
    private function render(string $viewPath, array $data = []): void
    {
        extract($data);
        include __DIR__ . '/../../views/' . $viewPath . '.php';
    }

    /**
     * Render error page
     */
    private function renderError(string $message, int $statusCode = 500): void
    {
        http_response_code($statusCode);
        echo "Error: {$message}";
    }
}
