<?php

namespace DealFileManager\Controllers;

use DealFileManager\Models\DealFile;
use DealFileManager\Models\Document;
use DealFileManager\Utils\Logger;

class DashboardController
{
    /**
     * Display dashboard with incomplete deal files
     */
    public function index(): void
    {
        try {
            // Get filter and sort parameters
            $sort = $_GET['sort'] ?? 'created_at DESC';
            $filter = $_GET['filter'] ?? 'incomplete';
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            // Validate sort parameter
            $allowedSorts = [
                'created_at DESC',
                'created_at ASC',
                'customer_name ASC',
                'customer_name DESC',
                'completion_percentage DESC',
                'completion_percentage ASC',
                'updated_at DESC'
            ];
            
            if (!in_array($sort, $allowedSorts)) {
                $sort = 'created_at DESC';
            }

            // Get deal files
            $filters = [];
            if ($filter === 'incomplete') {
                $filters['status'] = 'incomplete';
            } elseif ($filter === 'pending') {
                $filters['status'] = 'pending_review';
            }

            $dealFiles = DealFile::getAll($filters, $sort, $perPage, $offset);
            $totalCount = DealFile::countIncomplete();
            $totalPages = ceil($totalCount / $perPage);

            // Prepare data for view
            $data = [
                'dealFiles' => $dealFiles,
                'totalCount' => $totalCount,
                'page' => $page,
                'totalPages' => $totalPages,
                'sort' => $sort,
                'filter' => $filter,
                'pageTitle' => 'Deal File Manager - Dashboard'
            ];

            Logger::info("Dashboard accessed", ['page' => $page, 'filter' => $filter]);

            // Render view
            $this->render('dashboard/index', $data);

        } catch (\Exception $e) {
            Logger::error("Dashboard error: " . $e->getMessage());
            $this->renderError('Failed to load dashboard', 500);
        }
    }

    /**
     * Quick stats API
     */
    public function stats(): void
    {
        try {
            $stats = [
                'total_incomplete' => DealFile::countIncomplete(),
                'total_files' => DealFile::countIncomplete(), // Could add more stats
                'recent_created' => date('Y-m-d'),
                'completion_avg' => 0 // Calculate from database
            ];

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $stats]);

        } catch (\Exception $e) {
            Logger::error("Stats error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch stats']);
        }
    }

    /**
     * Render dashboard view
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
