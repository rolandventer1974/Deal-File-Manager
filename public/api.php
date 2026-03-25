<?php

/**
 * API Endpoints for Deal File Manager
 * Called from ColdFusion and web application
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Config.php';
require_once __DIR__ . '/../src/Config/Database.php';

use DealFileManager\Config\Config;
use DealFileManager\Config\Database;
use DealFileManager\API\OTPHandler;
use DealFileManager\API\DocumentHandler;
use DealFileManager\Utils\ApiResponse;
use DealFileManager\Utils\Logger;
use DealFileManager\Models\DealFile;

// Load environment configuration
Config::load(__DIR__ . '/../.env');

// Initialize database
Database::loadConfig();

// Set up logging
Logger::setLogFile(Config::get('LOG_FILE', '/var/www/dealfilemanager/logs/api.log'));

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Log API request
Logger::info("API Request: {$method} /{$action}", [
    'method' => $method,
    'action' => $action,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

try {
    switch ($action) {
        case 'receive-otp':
            if ($method !== 'POST') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
            OTPHandler::receiveOTP($requestData);
            break;

        case 'upload-document':
            if ($method !== 'POST') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $dealFileId = (int)($_POST['deal_file_id'] ?? 0);
            if ($dealFileId <= 0) {
                ApiResponse::validationError(['deal_file_id' => 'Invalid deal file ID']);
            }
            DocumentHandler::uploadDocument($dealFileId, $_FILES, $_POST);
            break;

        case 'get-documents':
            if ($method !== 'GET') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $dealFileId = (int)($_GET['deal_file_id'] ?? 0);
            if ($dealFileId <= 0) {
                ApiResponse::validationError(['deal_file_id' => 'Invalid deal file ID']);
            }
            DocumentHandler::getDocuments($dealFileId);
            break;

        case 'download-document':
            if ($method !== 'GET') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $documentId = (int)($_GET['document_id'] ?? 0);
            if ($documentId <= 0) {
                ApiResponse::validationError(['document_id' => 'Invalid document ID']);
            }
            DocumentHandler::downloadDocument($documentId);
            break;

        case 'delete-document':
            if ($method !== 'DELETE') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $documentId = (int)($_GET['document_id'] ?? 0);
            if ($documentId <= 0) {
                ApiResponse::validationError(['document_id' => 'Invalid document ID']);
            }
            DocumentHandler::deleteDocument($documentId);
            break;

        case 'update-document-status':
            if ($method !== 'PATCH') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
            $documentId = (int)($_GET['document_id'] ?? 0);
            $status = $requestData['status'] ?? '';
            if ($documentId <= 0) {
                ApiResponse::validationError(['document_id' => 'Invalid document ID']);
            }
            if (empty($status)) {
                ApiResponse::validationError(['status' => 'Status is required']);
            }
            DocumentHandler::updateDocumentStatus($documentId, $status);
            break;

        case 'get-deal-file':
            if ($method !== 'GET') {
                ApiResponse::error('Method not allowed', null, 405);
            }
            $dealFileId = (int)($_GET['deal_file_id'] ?? 0);
            if ($dealFileId <= 0) {
                ApiResponse::validationError(['deal_file_id' => 'Invalid deal file ID']);
            }
            $dealFile = DealFile::getById($dealFileId);
            if (!$dealFile) {
                ApiResponse::notFound('Deal file not found');
            }
            ApiResponse::success([
                'id' => $dealFile->getId(),
                'reference' => $dealFile->getReferenceNumber(),
                'customer_name' => $dealFile->getCustomerName(),
                'status' => $dealFile->getStatus(),
                'completion' => $dealFile->getCompletionPercentage() . '%'
            ]);
            break;

        default:
            ApiResponse::error('Unknown action', null, 400);
    }

} catch (\Exception $e) {
    Logger::error("API Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    ApiResponse::error('Internal server error', null, 500);
}
