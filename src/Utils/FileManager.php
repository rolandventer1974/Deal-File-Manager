<?php

namespace DealFileManager\Utils;

class FileManager
{
    private const UPLOAD_DIR = '/var/www/dealfilemanager/public/uploads';
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
    private const MAX_FILE_SIZE = 52428800; // 50MB

    /**
     * Upload and save a file
     */
    public static function uploadFile(array $fileArray, string $dealFileRef): array
    {
        // Validate file
        $validation = self::validateFile($fileArray);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        try {
            // Create upload directory if doesn't exist
            $uploadDir = self::getUploadDirForDealFile($dealFileRef);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileName = $fileArray['name'];
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $uniqueName = $baseName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $filePath = $uploadDir . '/' . $uniqueName;

            // Move uploaded file
            if (!move_uploaded_file($fileArray['tmp_name'], $filePath)) {
                return ['success' => false, 'error' => 'Failed to save file'];
            }

            // Set appropriate permissions
            chmod($filePath, 0644);

            return [
                'success' => true,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $extension,
                'file_size' => filesize($filePath),
                'unique_name' => $uniqueName
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate uploaded file
     */
    private static function validateFile(array $fileArray): array
    {
        // Check if file was uploaded
        if (!isset($fileArray['tmp_name']) || empty($fileArray['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }

        // Check file size
        if ($fileArray['size'] > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed (50MB)'];
        }

        // Check file extension
        $extension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return ['valid' => false, 'error' => 'Invalid file type detected'];
        }

        return ['valid' => true];
    }

    /**
     * Get upload directory for deal file
     */
    public static function getUploadDirForDealFile(string $dealFileRef): string
    {
        return self::UPLOAD_DIR . '/' . $dealFileRef;
    }

    /**
     * Delete file
     */
    public static function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true;
    }

    /**
     * Get file download path
     */
    public static function getDownloadUrl(string $filePath): string
    {
        $relativePath = str_replace(self::UPLOAD_DIR, '', $filePath);
        return '/uploads' . $relativePath;
    }

    /**
     * Size human readable
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
