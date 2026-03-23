<?php

namespace DealFileManager\Models;

use DealFileManager\Config\Database;
use PDO;

class DealFile
{
    private int $id;
    private string $referenceNumber;
    private string $customerName;
    private ?string $customerIdNumber;
    private ?string $customerEmail;
    private ?string $customerMobile;
    private ?int $vehicleYear;
    private ?string $vehicleMake;
    private ?string $vehicleModel;
    private ?string $vehicleSpecification;
    private ?string $vinNumber;
    private ?string $salesExecutiveName;
    private ?string $salesManagerName;
    private ?string $financeCompany;
    private string $status = 'incomplete';
    private int $completionPercentage = 0;
    private ?string $createdBy;
    private string $createdFrom = 'manual';
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    /**
     * Create a new deal file
     */
    public static function create(array $data): self
    {
        $db = Database::getConnection();
        
        $referenceNumber = self::generateReferenceNumber();
        
        $sql = "INSERT INTO deal_files (
            reference_number, customer_name, customer_id_number, customer_email,
            customer_mobile, vehicle_year, vehicle_make, vehicle_model,
            vehicle_specification, vin_number, sales_executive_name,
            sales_manager_name, finance_company, created_by, created_from
        ) VALUES (
            :ref, :name, :id_num, :email, :mobile, :year, :make,
            :model, :spec, :vin, :exec, :manager, :finance, :created_by, :created_from
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':ref' => $referenceNumber,
            ':name' => $data['customer_name'],
            ':id_num' => $data['customer_id_number'] ?? null,
            ':email' => $data['customer_email'] ?? null,
            ':mobile' => $data['customer_mobile'] ?? null,
            ':year' => $data['vehicle_year'] ?? null,
            ':make' => $data['vehicle_make'] ?? null,
            ':model' => $data['vehicle_model'] ?? null,
            ':spec' => $data['vehicle_specification'] ?? null,
            ':vin' => $data['vin_number'] ?? null,
            ':exec' => $data['sales_executive_name'] ?? null,
            ':manager' => $data['sales_manager_name'] ?? null,
            ':finance' => $data['finance_company'] ?? null,
            ':created_by' => $data['created_by'] ?? 'system',
            ':created_from' => $data['created_from'] ?? 'manual'
        ]);

        return self::getById((int)$db->lastInsertId());
    }

    /**
     * Get deal file by ID
     */
    public static function getById(int $id): ?self
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM deal_files WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return self::fromArray($row);
    }

    /**
     * Get deal file by reference number
     */
    public static function getByReferenceNumber(string $refNum): ?self
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM deal_files WHERE reference_number = :ref";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ref' => $refNum]);
        
        $row = $stmt->fetch();
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Get incomplete deal files (dashboard)
     */
    public static function getIncomplete(int $limit = 50, int $offset = 0, ?string $sortBy = null): array
    {
        $db = Database::getConnection();
        
        $sortBy = $sortBy ?: 'created_at DESC';
        
        $sql = "SELECT * FROM deal_files 
                WHERE status IN ('incomplete', 'pending_review')
                ORDER BY {$sortBy}
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = self::fromArray($row);
        }
        
        return $results;
    }

    /**
     * Get all deal files with filtering and sorting
     */
    public static function getAll(array $filters = [], string $sortBy = 'created_at DESC', int $limit = 50, int $offset = 0): array
    {
        $db = Database::getConnection();
        
        $sql = "SELECT * FROM deal_files WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['customer_name'])) {
            $sql .= " AND customer_name LIKE :customer_name";
            $params[':customer_name'] = '%' . $filters['customer_name'] . '%';
        }
        
        if (!empty($filters['created_from'])) {
            $sql .= " AND created_from = :created_from";
            $params[':created_from'] = $filters['created_from'];
        }
        
        $sql .= " ORDER BY {$sortBy} LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = self::fromArray($row);
        }
        
        return $results;
    }

    /**
     * Update deal file
     */
    public function update(array $data): bool
    {
        $db = Database::getConnection();
        
        $allowedFields = [
            'customer_name', 'customer_id_number', 'customer_email',
            'customer_mobile', 'vehicle_year', 'vehicle_make', 'vehicle_model',
            'vehicle_specification', 'vin_number', 'sales_executive_name',
            'sales_manager_name', 'finance_company', 'status'
        ];
        
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
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE deal_files SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Update completion percentage
     */
    public function updateCompletionPercentage(): void
    {
        $db = Database::getConnection();
        
        // Calculate completion based on required documents
        $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN d.status = 'verified' THEN 1 ELSE 0 END) as verified
                FROM document_types dt
                LEFT JOIN documents d ON dt.type_name = d.document_type AND d.deal_file_id = :deal_file_id
                WHERE dt.required = TRUE";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':deal_file_id' => $this->id]);
        $result = $stmt->fetch();
        
        $total = $result['total'] ?? 1;
        $verified = $result['verified'] ?? 0;
        $percentage = (int)(($verified / $total) * 100);
        
        $updateSql = "UPDATE deal_files SET completion_percentage = :percentage WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([
            ':percentage' => $percentage,
            ':id' => $this->id
        ]);
        
        $this->completionPercentage = $percentage;
    }

    /**
     * Count incomplete deal files
     */
    public static function countIncomplete(): int
    {
        $db = Database::getConnection();
        $sql = "SELECT COUNT(*) as count FROM deal_files WHERE status IN ('incomplete', 'pending_review')";
        $stmt = $db->query($sql);
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Generate unique reference number
     */
    private static function generateReferenceNumber(): string
    {
        return 'DFM-' . strtoupper(bin2hex(random_bytes(5))) . '-' . time();
    }

    /**
     * Create from array
     */
    private static function fromArray(array $data): self
    {
        $dealFile = new self();
        $dealFile->id = (int)$data['id'];
        $dealFile->referenceNumber = $data['reference_number'];
        $dealFile->customerName = $data['customer_name'];
        $dealFile->customerIdNumber = $data['customer_id_number'];
        $dealFile->customerEmail = $data['customer_email'];
        $dealFile->customerMobile = $data['customer_mobile'];
        $dealFile->vehicleYear = $data['vehicle_year'];
        $dealFile->vehicleMake = $data['vehicle_make'];
        $dealFile->vehicleModel = $data['vehicle_model'];
        $dealFile->vehicleSpecification = $data['vehicle_specification'];
        $dealFile->vinNumber = $data['vin_number'];
        $dealFile->salesExecutiveName = $data['sales_executive_name'];
        $dealFile->salesManagerName = $data['sales_manager_name'];
        $dealFile->financeCompany = $data['finance_company'];
        $dealFile->status = $data['status'];
        $dealFile->completionPercentage = (int)$data['completion_percentage'];
        $dealFile->createdBy = $data['created_by'];
        $dealFile->createdFrom = $data['created_from'];
        $dealFile->createdAt = new \DateTime($data['created_at']);
        $dealFile->updatedAt = new \DateTime($data['updated_at']);
        return $dealFile;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function getCustomerMobile(): ?string { return $this->customerMobile; }
    public function getStatus(): string { return $this->status; }
    public function getCompletionPercentage(): int { return $this->completionPercentage; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }
    public function getVehicleYear(): ?int { return $this->vehicleYear; }
    public function getVehicleMake(): ?string { return $this->vehicleMake; }
    public function getVehicleModel(): ?string { return $this->vehicleModel; }
    public function getSalesExecutiveName(): ?string { return $this->salesExecutiveName; }
    public function getSalesManagerName(): ?string { return $this->salesManagerName; }
    public function getFinanceCompany(): ?string { return $this->financeCompany; }

    // Setters
    public function setStatus(string $status): void { $this->status = $status; }
}
