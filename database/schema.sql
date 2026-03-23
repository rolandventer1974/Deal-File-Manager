-- Deal File Manager Database Schema

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS deal_file_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE deal_file_manager;

-- Deal Files Table
CREATE TABLE IF NOT EXISTS deal_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_id_number VARCHAR(100),
    customer_email VARCHAR(255),
    customer_mobile VARCHAR(20),
    vehicle_year INT,
    vehicle_make VARCHAR(100),
    vehicle_model VARCHAR(100),
    vehicle_specification VARCHAR(255),
    vin_number VARCHAR(50) UNIQUE,
    sales_executive_name VARCHAR(255),
    sales_manager_name VARCHAR(255),
    finance_company VARCHAR(255),
    status ENUM('incomplete', 'pending_review', 'complete', 'archived') DEFAULT 'incomplete',
    completion_percentage INT DEFAULT 0,
    created_by VARCHAR(255),
    created_from ENUM('api', 'manual') DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_customer_name (customer_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents Table
CREATE TABLE IF NOT EXISTS documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    deal_file_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    document_date DATE,
    source ENUM('api', 'upload', 'imported') DEFAULT 'upload',
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    uploaded_by VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    INDEX idx_deal_file_id (deal_file_id),
    INDEX idx_document_type (document_type),
    FOREIGN KEY (deal_file_id) REFERENCES deal_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document Types Reference Table
CREATE TABLE IF NOT EXISTS document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150),
    description TEXT,
    allowed_formats VARCHAR(255),
    required BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    deal_file_id INT,
    action VARCHAR(100),
    performed_by VARCHAR(255),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_deal_file_id (deal_file_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (deal_file_id) REFERENCES deal_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Tokens Table (for ColdFusion integration)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_key VARCHAR(255) UNIQUE NOT NULL,
    token_secret VARCHAR(255) NOT NULL,
    source_system VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_token_key (token_key),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default document types
INSERT INTO document_types (type_name, display_name, description, allowed_formats, required, sort_order) VALUES
('otp', 'Offer to Purchase', 'Offer to Purchase Agreement', 'pdf,doc,docx', TRUE, 1),
('vehicle_inspection', 'Vehicle Inspection Report', 'Vehicle inspection and condition report', 'pdf,jpg,jpeg,png', FALSE, 2),
('costing_sheet', 'Costing Sheet', 'Finance costing and availability sheet', 'pdf,doc,docx,xls,xlsx', FALSE, 3),
('delivery_document', 'Signed Delivery Document', 'Signed delivery confirmation', 'pdf,jpg,jpeg,png', FALSE, 4),
('insurance_quote', 'Insurance Quote', 'Insurance quote for vehicle', 'pdf', FALSE, 5),
('registration_docs', 'Registration Documents', 'Vehicle registration papers', 'pdf,jpg,jpeg,png', FALSE, 6),
('service_history', 'Service History', 'Vehicle service records', 'pdf,doc,docx', FALSE, 7),
('warranty_info', 'Warranty Information', 'Warranty details and terms', 'pdf,doc,docx', FALSE, 8),
('finance_agreement', 'Finance Agreement', 'Finance agreement documents', 'pdf,doc,docx', FALSE, 9),
('id_verification', 'ID Verification', 'Customer ID document copy', 'pdf,jpg,jpeg,png', FALSE, 10),
('proof_of_income', 'Proof of Income', 'Income verification documents', 'pdf,doc,docx,jpg,jpeg', FALSE, 11),
('bank_statement', 'Bank Statement', 'Bank statement for reference', 'pdf,jpg,jpeg', FALSE, 12),
('other', 'Other Documents', 'Additional supporting documents', 'pdf,doc,docx,jpg,jpeg,png,xls,xlsx', FALSE, 13);
