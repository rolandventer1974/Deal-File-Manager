<?php

namespace DealFileManager\Controllers;

use DealFileManager\Models\DealFile;
use DealFileManager\Models\Document;
use DealFileManager\Utils\FileManager;
use DealFileManager\Utils\Logger;

class DealFileController
{
    /**
     * Show deal file creation form
     */
    public function create(): void
    {
        try {
            $data = [
                'pageTitle' => 'Create New Deal File',
                'formAction' => '/dealfiles/store'
            ];

            $this->render('dealfiles/create', $data);

        } catch (\Exception $e) {
            Logger::error("Create form error: " . $e->getMessage());
            $this->renderError('Failed to load form', 500);
        }
    }

    /**
     * Store new deal file
     */
    public function store(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                return;
            }

            // Validate required fields
            $required = ['customer_name', 'vehicle_year', 'vehicle_make', 'vehicle_model'];
            $errors = [];

            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }

            if (!empty($errors)) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Create deal file
            $dealFileData = [
                'customer_name' => trim($_POST['customer_name']),
                'customer_id_number' => trim($_POST['customer_id_number'] ?? ''),
                'customer_email' => trim($_POST['customer_email'] ?? ''),
                'customer_mobile' => trim($_POST['customer_mobile'] ?? ''),
                'vehicle_year' => (int)$_POST['vehicle_year'],
                'vehicle_make' => trim($_POST['vehicle_make']),
                'vehicle_model' => trim($_POST['vehicle_model']),
                'vehicle_specification' => trim($_POST['vehicle_specification'] ?? ''),
                'vin_number' => trim($_POST['vin_number'] ?? ''),
                'sales_executive_name' => trim($_POST['sales_executive_name'] ?? ''),
                'sales_manager_name' => trim($_POST['sales_manager_name'] ?? ''),
                'finance_company' => trim($_POST['finance_company'] ?? ''),
                'created_by' => $_SESSION['user']['name'] ?? 'web_user',
                'created_from' => 'manual'
            ];

            $dealFile = DealFile::create($dealFileData);

            Logger::info("Deal file created via web form", [
                'deal_file_id' => $dealFile->getId(),
                'reference' => $dealFile->getReferenceNumber()
            ]);

            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Deal file created successfully',
                'data' => [
                    'id' => $dealFile->getId(),
                    'reference' => $dealFile->getReferenceNumber()
                ]
            ]);

        } catch (\Exception $e) {
            Logger::error("Store deal file error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Show deal file details
     */
    public function show(int $id): void
    {
        try {
            $dealFile = DealFile::getById($id);

            if (!$dealFile) {
                http_response_code(404);
                echo "Deal file not found";
                return;
            }

            $documents = Document::getByDealFileId($id);

            $data = [
                'pageTitle' => 'Deal File: ' . $dealFile->getCustomerName(),
                'dealFile' => $dealFile,
                'documents' => $documents,
                'formAction' => '/dealfiles/' . $id . '/update'
            ];

            $this->render('dealfiles/show', $data);

        } catch (\Exception $e) {
            Logger::error("Show deal file error: " . $e->getMessage());
            $this->renderError('Failed to load deal file', 500);
        }
    }

    /**
     * Update deal file
     */
    public function update(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                return;
            }

            $dealFile = DealFile::getById($id);

            if (!$dealFile) {
                http_response_code(404);
                return;
            }

            // Update deal file
            $updates = [
                'customer_name' => $_POST['customer_name'] ?? null,
                'customer_email' => $_POST['customer_email'] ?? null,
                'customer_mobile' => $_POST['customer_mobile'] ?? null,
                'sales_executive_name' => $_POST['sales_executive_name'] ?? null,
                'sales_manager_name' => $_POST['sales_manager_name'] ?? null,
                'finance_company' => $_POST['finance_company'] ?? null,
                'status' => $_POST['status'] ?? null
            ];

            $updates = array_filter($updates, fn($v) => $v !== null);

            if (!empty($updates)) {
                $dealFile->update($updates);
            }

            Logger::info("Deal file updated", ['deal_file_id' => $id]);

            header('Location: /dealfiles/' . $id);

        } catch (\Exception $e) {
            Logger::error("Update deal file error: " . $e->getMessage());
            $this->renderError('Failed to update deal file', 500);
        }
    }

    /**
     * Render view
     */
    private function render(string $viewPath, array $data = []): void
    {
        extract($data);
        include __DIR__ . '/../../views/' . $viewPath . '.php';
    }

    /**
     * Render error
     */
    private function renderError(string $message, int $statusCode = 500): void
    {
        http_response_code($statusCode);
        echo "Error: {$message}";
    }
}
