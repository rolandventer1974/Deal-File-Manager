<?php

/**
 * Deal File Manager - Main Router
 * Handles all web requests
 */

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Config.php';
require_once __DIR__ . '/../src/Config/Database.php';

use DealFileManager\Config\Config;
use DealFileManager\Config\Database;
use DealFileManager\Controllers\DashboardController;
use DealFileManager\Controllers\DealFileController;
use DealFileManager\Controllers\LoginController;
use DealFileManager\Utils\Logger;

// Load environment configuration
Config::load(__DIR__ . '/../.env');

// Initialize database
Database::loadConfig();

// Set up logging
Logger::setLogFile(Config::get('LOG_FILE', '/var/www/deal-file-manager/logs/app.log'));

// Parse request path
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestPath = str_replace('/index.php', '', $requestPath);
$requestPath = trim($requestPath, '/');

// Route handler
try {
    if (empty($requestPath) || $requestPath === '') {
        // Login page
        $controller = new LoginController();
        $controller->index();

    } elseif ($requestPath === 'dashboard') {
        // Dashboard
        $controller = new DashboardController();
        $controller->index();

    } elseif (preg_match('#^dealfiles/create$#', $requestPath)) {
        // Create deal file form
        $controller = new DealFileController();
        $controller->create();

    } elseif (preg_match('#^dealfiles/store$#', $requestPath)) {
        // Store new deal file
        $controller = new DealFileController();
        $controller->store();

    } elseif (preg_match('#^dealfiles/(\d+)$#', $requestPath, $matches)) {
        // View deal file
        $controller = new DealFileController();
        $controller->show((int)$matches[1]);

    } elseif (preg_match('#^dealfiles/(\d+)/update$#', $requestPath, $matches)) {
        // Update deal file
        $controller = new DealFileController();
        $controller->update((int)$matches[1]);

    } else {
        http_response_code(404);
        echo "Page not found";
    }

} catch (\Exception $e) {
    Logger::error("Router error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    http_response_code(500);
    echo "An error occurred. Please contact the administrator.";
}
