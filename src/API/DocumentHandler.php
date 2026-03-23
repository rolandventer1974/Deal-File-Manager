<?php

namespace DealFileManager\API;

use DealFileManager\Models\DealFile;
use DealFileManager\Models\Document;
use DealFileManager\Utils\ApiResponse;
use DealFileManager\Utils\FileManager;
use DealFileManager\Utils\Logger;

class DocumentHandler
{
    /**
     * Handle document upload
     */
    public static function uploadDocument(int $dealFileId, array $files, array $data): void
    {
        try {
            $dealFile = DealFile::getById($dealFileId);
            
            if (!$dealFile) {
                ApiResponse::notFound('Deal file not found');
            }

            if (empty($files['document']) || empty($data['document_type'])) {
                ApiResponse::validationError([
                    'document' => 'Document file is required',
                    'document_type' => 'Document type is required'
                ]);
            }

            $uploadResult = FileManager::uploadFile($files['document'], $dealFile->getReferenceNumber());
            
            if (!$uploadResult['success']) {
                ApiResponse::error('File upload failed', $uploadResult['error']);
            }

            // Create document record
            $documentData = [
                'deal_file_id' => $dealFileId,
                'document_type' => $data['document_type'],
                'file_name' => $uploadResult['file_name'],
                'file_path' => $uploadResult['file_path'],
                'file_type' => $uploadResult['file_type'],
                'file_size' => $uploadResult['file_size'],
                'source' => 'upload',
                'status' => 'pending',
                'uploaded_by' => $data['uploaded_by'] ?? 'user',
                'notes' => $data['notes'] ?? null
            ];

            $document = Document::create($documentData);

            // Update deal file completion
            $dealFile->updateCompletionPercentage();

            Logger::info("Document uploaded", [
                'deal_file_id' => $dealFileId,
                'document_type' => $data['document_type'],
                'file_name' => $uploadResult['file_name']
            ]);

            ApiResponse::success([
                'document_id' => $document->getId(),
                'file_name' => $document->getFileName(),
                'document_type' => $document->getDocumentType(),
                'status' => $document->getStatus()
            ], 'Document uploaded successfully', 201);

        } catch (\Exception $e) {
            Logger::error("Document upload error: " . $e->getMessage());
            ApiResponse::error('Document upload failed', $e->getMessage(), 500);
        }
    }

    /**
     * Get all documents for a deal file
     */
    public static function getDocuments(int $dealFileId): void
    {
        try {
            $dealFile = DealFile::getById($dealFileId);
            
            if (!$dealFile) {
                ApiResponse::notFound('Deal file not found');
            }

            $documents = Document::getByDealFileId($dealFileId);
            
            $documentsList = [];
            foreach ($documents as $doc) {
                $documentsList[] = [
                    'id' => $doc->getId(),
                    'type' => $doc->getDocumentType(),
                    'name' => $doc->getFileName(),
                    'size' => FileManager::formatFileSize($doc->getFileSize() ?? 0),
                    'status' => $doc->getStatus(),
                    'uploaded_at' => $doc->getUploadedAt()->format('Y-m-d H:i:s'),
                    'uploaded_by' => $doc->getUploadedBy()
                ];
            }

            ApiResponse::success($documentsList);

        } catch (\Exception $e) {
            Logger::error("Error fetching documents: " . $e->getMessage());
            ApiResponse::error('Failed to fetch documents', $e->getMessage(), 500);
        }
    }

    /**
     * Download document
     */
    public static function downloadDocument(int $documentId): void
    {
        try {
            $document = Document::getById($documentId);
            
            if (!$document) {
                ApiResponse::notFound('Document not found');
            }

            $filePath = $document->getFilePath();
            
            if (!file_exists($filePath)) {
                ApiResponse::notFound('File not found on server');
            }

            // Log download
            Logger::info("Document downloaded", ['document_id' => $documentId]);

            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($document->getFileName()) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');

            readfile($filePath);
            exit;

        } catch (\Exception $e) {
            Logger::error("Error downloading document: " . $e->getMessage());
            ApiResponse::error('Failed to download document', $e->getMessage(), 500);
        }
    }

    /**
     * Delete document
     */
    public static function deleteDocument(int $documentId): void
    {
        try {
            $document = Document::getById($documentId);
            
            if (!$document) {
                ApiResponse::notFound('Document not found');
            }

            $dealFileId = $document->getDealFileId();
            $document->delete();

            // Update completion percentage
            $dealFile = DealFile::getById($dealFileId);
            $dealFile->updateCompletionPercentage();

            Logger::info("Document deleted", ['document_id' => $documentId]);

            ApiResponse::success(null, 'Document deleted successfully');

        } catch (\Exception $e) {
            Logger::error("Error deleting document: " . $e->getMessage());
            ApiResponse::error('Failed to delete document', $e->getMessage(), 500);
        }
    }

    /**
     * Update document status
     */
    public static function updateDocumentStatus(int $documentId, string $status): void
    {
        try {
            $document = Document::getById($documentId);
            
            if (!$document) {
                ApiResponse::notFound('Document not found');
            }

            $validStatuses = ['pending', 'verified', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                ApiResponse::validationError(['status' => 'Invalid status value']);
            }

            $document->update(['status' => $status]);

            // Update completion percentage
            $dealFile = DealFile::getById($document->getDealFileId());
            $dealFile->updateCompletionPercentage();

            Logger::info("Document status updated", [
                'document_id' => $documentId,
                'status' => $status
            ]);

            ApiResponse::success(['status' => $status], 'Status updated successfully');

        } catch (\Exception $e) {
            Logger::error("Error updating document status: " . $e->getMessage());
            ApiResponse::error('Failed to update status', $e->getMessage(), 500);
        }
    }
}
