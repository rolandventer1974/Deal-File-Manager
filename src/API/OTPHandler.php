<?php

namespace DealFileManager\API;

use DealFileManager\Models\DealFile;
use DealFileManager\Models\Document;
use DealFileManager\Utils\ApiResponse;
use DealFileManager\Utils\Authentication;
use DealFileManager\Utils\Logger;

class OTPHandler
{
    /**
     * Handle OTP submission from ColdFusion
     */
    public static function receiveOTP(array $requestData): void
    {
        // Validate API authentication
        $authHeader = Authentication::getAuthorizationHeader();
        if (!$authHeader) {
            ApiResponse::unauthorized('Missing authorization token');
        }

        $token = str_replace('Bearer ', '', $authHeader);
        $apiTokenData = Authentication::validateApiToken($token);
        
        if (!$apiTokenData) {
            ApiResponse::unauthorized('Invalid API token');
        }

        // Validate required fields
        $required = ['customer_name', 'vin_number', 'vehicle_year', 'vehicle_make', 'vehicle_model'];
        $errors = [];
        
        foreach ($required as $field) {
            if (empty($requestData[$field])) {
                $errors[$field] = "Field '{$field}' is required";
            }
        }

        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }

        try {
            // Check if deal file already exists for this VIN
            $existingDeal = self::findExistingDealFile($requestData);
            
            if ($existingDeal) {
                Logger::info("Deal file already exists for VIN: {$requestData['vin_number']}");
                ApiResponse::success([
                    'deal_file_id' => $existingDeal->getId(),
                    'reference_number' => $existingDeal->getReferenceNumber(),
                    'status' => 'existing'
                ], 'Deal file already exists', 200);
            }

            // Create new deal file
            $dealFileData = [
                'customer_name' => $requestData['customer_name'],
                'customer_id_number' => $requestData['customer_id_number'] ?? null,
                'customer_email' => $requestData['customer_email'] ?? null,
                'customer_mobile' => $requestData['customer_mobile'] ?? null,
                'vehicle_year' => $requestData['vehicle_year'],
                'vehicle_make' => $requestData['vehicle_make'],
                'vehicle_model' => $requestData['vehicle_model'],
                'vehicle_specification' => $requestData['vehicle_specification'] ?? null,
                'vin_number' => $requestData['vin_number'],
                'sales_executive_name' => $requestData['sales_executive_name'] ?? null,
                'sales_manager_name' => $requestData['sales_manager_name'] ?? null,
                'finance_company' => $requestData['finance_company'] ?? null,
                'created_by' => $apiTokenData['source_system'] ?? 'AutoSLM',
                'created_from' => 'api'
            ];

            $dealFile = DealFile::create($dealFileData);

            // Add OTP document if provided
            if (!empty($requestData['otp_document'])) {
                self::storeOTPDocument($dealFile->getId(), $requestData);
            }

            Logger::info("New deal file created via API: {$dealFile->getReferenceNumber()}", [
                'deal_file_id' => $dealFile->getId(),
                'vin' => $requestData['vin_number']
            ]);

            ApiResponse::success([
                'deal_file_id' => $dealFile->getId(),
                'reference_number' => $dealFile->getReferenceNumber(),
                'status' => 'created'
            ], 'Deal file created successfully', 201);

        } catch (\Exception $e) {
            Logger::error("Error creating deal file via OTP: " . $e->getMessage());
            ApiResponse::error('Failed to create deal file', $e->getMessage(), 500);
        }
    }

    /**
     * Find existing deal file by VIN or customer details
     */
    private static function findExistingDealFile(array $data): ?DealFile
    {
        if (!empty($data['vin_number'])) {
            $dealFile = DealFile::getByReferenceNumber($data['vin_number']);
            if ($dealFile) {
                return $dealFile;
            }
        }

        return null;
    }

    /**
     * Store OTP document from API
     */
    private static function storeOTPDocument(int $dealFileId, array $requestData): void
    {
        // If OTP is sent as base64 or URL, we need to handle it
        $otpData = [
            'deal_file_id' => $dealFileId,
            'document_type' => 'otp',
            'file_name' => 'OTP_From_API.pdf',
            'file_path' => '/path/to/otp/document',
            'file_type' => 'pdf',
            'source' => 'api',
            'status' => 'verified',
            'uploaded_by' => 'AutoSLM',
            'notes' => 'OTP received from ColdFusion AutoSLM'
        ];

        // If binary content provided
        if (!empty($requestData['otp_content'])) {
            $filePath = $this->saveBinaryDocument($dealFileId, 'OTP_From_API', $requestData['otp_content']);
            $otpData['file_path'] = $filePath;
            $otpData['file_size'] = filesize($filePath);
        }

        if (!empty($requestData['otp_url'])) {
            $filePath = $this->downloadDocument($dealFileId, 'OTP_From_API', $requestData['otp_url']);
            $otpData['file_path'] = $filePath;
            $otpData['file_size'] = filesize($filePath);
        }

        Document::create($otpData);
    }

    /**
     * Save binary document content
     */
    private function saveBinaryDocument(int $dealFileId, string $baseName, string $content): string
    {
        $dealFile = DealFile::getById($dealFileId);
        $uploadDir = \DealFileManager\Utils\FileManager::getUploadDirForDealFile($dealFile->getReferenceNumber());
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $baseName . '_' . time() . '.pdf';
        $filePath = $uploadDir . '/' . $fileName;
        
        file_put_contents($filePath, base64_decode($content));
        chmod($filePath, 0644);

        return $filePath;
    }

    /**
     * Download document from URL
     */
    private function downloadDocument(int $dealFileId, string $baseName, string $url): string
    {
        $dealFile = DealFile::getById($dealFileId);
        $uploadDir = \DealFileManager\Utils\FileManager::getUploadDirForDealFile($dealFile->getReferenceNumber());
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $baseName . '_' . time() . '.pdf';
        $filePath = $uploadDir . '/' . $fileName;
        
        $content = file_get_contents($url);
        file_put_contents($filePath, $content);
        chmod($filePath, 0644);

        return $filePath;
    }
}
