<?php

namespace DealFileManager\Models;

use DealFileManager\Config\Database;
use PDO;

class Document
{
    private int $id;
    private int $dealFileId;
    private string $documentType;
    private string $fileName;
    private string $filePath;
    private ?string $fileType;
    private ?int $fileSize;
    private ?\DateTime $documentDate;
    private string $source = 'upload';
    private string $status = 'pending';
    private ?string $uploadedBy;
    private \DateTime $uploadedAt;
    private ?string $notes;

    /**
     * Create new document
     */
    public static function create(array $data): self
    {
        $db = Database::getConnection();
        
        $sql = "INSERT INTO documents (
            deal_file_id, document_type, file_name, file_path,
            file_type, file_size, document_date, source, status, uploaded_by, notes
        ) VALUES (
            :deal_file_id, :doc_type, :file_name, :file_path,
            :file_type, :file_size, :doc_date, :source, :status, :uploaded_by, :notes
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':deal_file_id' => $data['deal_file_id'],
            ':doc_type' => $data['document_type'],
            ':file_name' => $data['file_name'],
            ':file_path' => $data['file_path'],
            ':file_type' => $data['file_type'] ?? null,
            ':file_size' => $data['file_size'] ?? null,
            ':doc_date' => $data['document_date'] ?? null,
            ':source' => $data['source'] ?? 'upload',
            ':status' => $data['status'] ?? 'pending',
            ':uploaded_by' => $data['uploaded_by'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);

        return self::getById((int)$db->lastInsertId());
    }

    /**
     * Get document by ID
     */
    public static function getById(int $id): ?self
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM documents WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch();
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Get all documents for a deal file
     */
    public static function getByDealFileId(int $dealFileId): array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM documents WHERE deal_file_id = :deal_file_id ORDER BY uploaded_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':deal_file_id' => $dealFileId]);
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = self::fromArray($row);
        }
        
        return $results;
    }

    /**
     * Get documents by type for a deal file
     */
    public static function getByTypeAndDealFile(int $dealFileId, string $documentType): array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM documents WHERE deal_file_id = :deal_file_id AND document_type = :doc_type ORDER BY uploaded_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':deal_file_id' => $dealFileId,
            ':doc_type' => $documentType
        ]);
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = self::fromArray($row);
        }
        
        return $results;
    }

    /**
     * Update document
     */
    public function update(array $data): bool
    {
        $db = Database::getConnection();
        
        $allowedFields = ['document_type', 'status', 'notes', 'document_date'];
        $updates = [];
        $params = [':id' => $this->id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE documents SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete document
     */
    public function delete(): bool
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
        
        $db = Database::getConnection();
        $sql = "DELETE FROM documents WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        return $stmt->execute([':id' => $this->id]);
    }

    /**
     * Count documents by type for a deal file
     */
    public static function countByType(int $dealFileId, string $documentType): int
    {
        $db = Database::getConnection();
        $sql = "SELECT COUNT(*) as count FROM documents WHERE deal_file_id = :deal_file_id AND document_type = :doc_type";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':deal_file_id' => $dealFileId,
            ':doc_type' => $documentType
        ]);
        
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Create from array
     */
    private static function fromArray(array $data): self
    {
        $doc = new self();
        $doc->id = (int)$data['id'];
        $doc->dealFileId = (int)$data['deal_file_id'];
        $doc->documentType = $data['document_type'];
        $doc->fileName = $data['file_name'];
        $doc->filePath = $data['file_path'];
        $doc->fileType = $data['file_type'];
        $doc->fileSize = $data['file_size'];
        $doc->documentDate = $data['document_date'] ? new \DateTime($data['document_date']) : null;
        $doc->source = $data['source'];
        $doc->status = $data['status'];
        $doc->uploadedBy = $data['uploaded_by'];
        $doc->uploadedAt = new \DateTime($data['uploaded_at']);
        $doc->notes = $data['notes'];
        return $doc;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getDealFileId(): int { return $this->dealFileId; }
    public function getDocumentType(): string { return $this->documentType; }
    public function getFileName(): string { return $this->fileName; }
    public function getFilePath(): string { return $this->filePath; }
    public function getFileType(): ?string { return $this->fileType; }
    public function getFileSize(): ?int { return $this->fileSize; }
    public function getStatus(): string { return $this->status; }
    public function getUploadedAt(): \DateTime { return $this->uploadedAt; }
    public function getUploadedBy(): ?string { return $this->uploadedBy; }
    public function getNotes(): ?string { return $this->notes; }
    public function getSource(): string { return $this->source; }
}
