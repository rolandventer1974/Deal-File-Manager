# ColdFusion Integration Guide

This guide explains how to integrate your ColdFusion AutoSLM application with the Deal File Manager API.

## Overview

Deal File Manager provides REST API endpoints for:
- Submitting Offers to Purchase (OTP)
- Creating deal files from ColdFusion
- Uploading supporting documents
- Checking deal file status

## API Authentication

All API requests must include an Authorization header with a Bearer token:

```
Authorization: Bearer YOUR_API_KEY
```

**Important**: Keep your API key secure. Never expose it in client-side code.

## API Endpoints

### 1. Create Deal File / Submit OTP

**Endpoint**: `POST https://dealfilemanager.co.za/api.php?action=receive-otp`

**Description**: Creates a new deal file and optionally uploads the OTP document.

**Request Headers**:
```
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

**Request Body**:
```json
{
    "customer_name": "John Doe",
    "customer_id_number": "ID123456",
    "customer_email": "john@example.com",
    "customer_mobile": "+1-234-567-8900",
    "vehicle_year": 2022,
    "vehicle_make": "Toyota",
    "vehicle_model": "Camry",
    "vehicle_specification": "SE Hybrid",
    "vin_number": "JTDKRFVU2J1234567",
    "sales_executive_name": "Jane Smith",
    "sales_manager_name": "Bob Johnson",
    "finance_company": "ABC Finance Corp",
    "otp_url": "https://your-server.com/path/to/otp-document.pdf"
}
```

**Response** (Success - 201):
```json
{
    "success": true,
    "message": "Deal file created successfully",
    "data": {
        "deal_file_id": 42,
        "reference_number": "DFM-A1B2C3D4-1711228800",
        "status": "created"
    }
}
```

**Response** (Already Exists - 200):
```json
{
    "success": true,
    "message": "Deal file already exists",
    "data": {
        "deal_file_id": 42,
        "reference_number": "DFM-A1B2C3D4-1711228800",
        "status": "existing"
    }
}
```

**Error Responses**:
- 400: Missing required fields
- 401: Invalid or missing API token
- 422: Validation error
- 500: Server error

### 2. Upload Document

**Endpoint**: `POST /api.php?action=upload-document`

**Description**: Uploads a document to an existing deal file.

**Request Headers**:
```
Authorization: Bearer YOUR_API_KEY
Content-Type: multipart/form-data
```

**Request Parameters**:
- `deal_file_id` (required): ID of the deal file
- `document_type` (required): Type of document (see list below)
- `document` (required): The file to upload
- `notes` (optional): Additional notes about the document
- `uploaded_by` (optional): Name of person uploading

**Example using cURL**:
```bash
curl -X POST "https://yourdomain.com/api.php?action=upload-document" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "deal_file_id=42" \
  -F "document_type=vehicle_inspection" \
  -F "document=@/path/to/inspection_report.pdf" \
  -F "notes=Inspection completed 2026-03-23" \
  -F "uploaded_by=John Smith"
```

**Response** (Success - 201):
```json
{
    "success": true,
    "message": "Document uploaded successfully",
    "data": {
        "document_id": 123,
        "file_name": "inspection_report.pdf",
        "document_type": "vehicle_inspection",
        "status": "pending"
    }
}
```

**Supported Document Types**:
- `otp` - Offer to Purchase (required)
- `vehicle_inspection` - Vehicle Inspection Report
- `costing_sheet` - Finance Costing Sheet
- `delivery_document` - Signed Delivery Document
- `insurance_quote` - Insurance Quote
- `registration_docs` - Registration Documents
- `service_history` - Service History
- `warranty_info` - Warranty Information
- `finance_agreement` - Finance Agreement
- `id_verification` - ID Verification Copy
- `proof_of_income` - Proof of Income
- `bank_statement` - Bank Statement
- `other` - Other Documents

**File Restrictions**:
- Maximum size: 50 MB
- Supported formats: PDF, DOC, DOCX, JPG, JPEG, PNG, XLS, XLSX

### 3. Get Deal File Details

**Endpoint**: `GET https://dealfilemanager.co.za/api.php?action=get-deal-file&deal_file_id={id}`

**Response**:
```json
{
    "success": true,
    "data": {
        "id": 42,
        "reference": "DFM-A1B2C3D4-1711228800",
        "customer_name": "John Doe",
        "status": "incomplete",
        "completion": "40%"
    }
}
```

### 4. Get Deal File Documents

**Endpoint**: `GET https://dealfilemanager.co.za/api.php?action=get-documents&deal_file_id={id}`

**Response**:
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "type": "otp",
            "name": "offer_to_purchase.pdf",
            "size": "1.2 MB",
            "status": "verified",
            "uploaded_at": "2026-03-22 14:30:00",
            "uploaded_by": "System"
        }
    ]
}
```

### 5. Update Document Status

**Endpoint**: `PATCH https://dealfilemanager.co.za/api.php?action=update-document-status&document_id={id}`

**Request Body**:
```json
{
    "status": "verified"
}
```

**Valid Statuses**: `pending`, `verified`, `rejected`

## ColdFusion Integration Example

### CFML Code

```cfml
<cfscript>
    // API Configuration
    apiKey = application.dealFileManagerApiKey;
    apiEndpoint = "https://yourdomain.com/api.php";
    
    // Prepare deal data
    dealData = {
        'customer_name': form.customerName,
        'customer_id_number': form.idNumber,
        'customer_email': form.email,
        'customer_mobile': form.phone,
        'vehicle_year': form.vehicleYear,
        'vehicle_make': form.vehicleMake,
        'vehicle_model': form.vehicleModel,
        'vehicle_specification': form.specification,
        'vin_number': form.vin,
        'sales_executive_name': form.salesExec,
        'sales_manager_name': form.salesMgr,
        'finance_company': form.financeCompany,
        'otp_url': 'https://yourserver.com/documents/otp-' & form.dealId & '.pdf'
    };
    
    // Convert to JSON
    jsonData = serializeJSON(dealData);
    
    // Make API call
    httpService = new http(
        method = "POST",
        url = "https://dealfilemanager.co.za/api.php?action=receive-otp",
        charset = "UTF-8"
    );
    
    httpService.addParam(
        type = "header",
        name = "Authorization",
        value = "Bearer #apiKey#"
    );
    
    httpService.addParam(
        type = "header",
        name = "Content-Type",
        value = "application/json"
    );
    
    httpService.addParam(
        type = "body",
        value = jsonData
    );
    
    // Execute request
    result = httpService.send().getPrefix();
    
    // Parse response
    if (result.statusCode == 201) {
        response = deserializeJSON(result.filecontent);
        if (response.success) {
            dealFileId = response.data.deal_file_id;
            writeLog(file="dfm_integration", text="Deal file created: #dealFileId#");
        }
    } else {
        writeLog(file="dfm_integration", text="Error: #result.statusCode# - #result.filecontent#");
    }
</cfscript>
```

### Upload Document Example

```cfml
<cfscript>
    // Prepare multipart request for file upload
    httpService = new http(
        method = "POST",
        url = "https://dealfilemanager.co.za/api.php?action=upload-document",
        charset = "UTF-8"
    );
    
    httpService.addParam(
        type = "header",
        name = "Authorization",
        value = "Bearer #apiKey#"
    );
    
    httpService.addParam(
        type = "formfield",
        name = "deal_file_id",
        value = dealFileId
    );
    
    httpService.addParam(
        type = "formfield",
        name = "document_type",
        value = "vehicle_inspection"
    );
    
    httpService.addParam(
        type = "formfield",
        name = "notes",
        value = "Inspection completed and approved"
    );
    
    httpService.addParam(
        type = "file",
        name = "document",
        file = "/path/to/inspection_report.pdf"
    );
    
    result = httpService.send().getPrefix();
    response = deserializeJSON(result.filecontent);
    
    if (response.success) {
        // Document uploaded successfully
        documentId = response.data.document_id;
    }
</cfscript>
```

## Error Handling

Handle API errors gracefully:

```cfml
<cfscript>
    try {
        // API call here
        httpService.send();
    } catch (any e) {
        writeLog(file="dfm_integration", text="API Error: #e.message#");
        // Display user-friendly error message
        throw(type="custom.dfm.api_error", message="Failed to create deal file");
    }
</cfscript>
```

## Testing the Integration

### Test OTP Submission

```bash
curl -X POST "https://dealfilemanager.co.za/api.php?action=receive-otp" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Test Customer",
    "customer_email": "test@example.com",
    "vehicle_year": 2022,
    "vehicle_make": "Toyota",
    "vehicle_model": "Camry",
    "vin_number": "JTDKRFVU2J1234567",
    "otp_url": "https://example.com/test.pdf"
  }'
```

### Expected Response

```json
{
    "success": true,
    "message": "Deal file created successfully",
    "data": {
        "deal_file_id": 1,
        "reference_number": "DFM-ABC123-1234567890",
        "status": "created"
    }
}
```

## Best Practices

1. **Store API Key Securely**: Never hardcode API keys
2. **Use HTTPS Always**: Encrypt data in transit
3. **Handle Errors**: Implement proper error handling and logging
4. **Test Thoroughly**: Test in development before production
5. **Monitor Logs**: Check integration logs regularly
6. **Rate Limiting**: Don't make excessive API calls
7. **Validate Input**: Validate data before sending to API
8. **Use Try-Catch**: Handle exceptions gracefully

## Troubleshooting

### 401 Unauthorized

- Verify API key is correct
- Check Authorization header format (Bearer + space + token)
- Ensure API key hasn't expired or been revoked

### 422 Validation Error

- Check all required fields are provided
- Verify field formats (email, phone, year, etc.)
- Check VIN format (should be 17 characters)

### 500 Internal Server Error

- Check server logs: `/var/www/dealfilemanager/logs/api.log`
- Verify database connection
- Check file upload directory permissions

### File Upload Fails

- Verify file size < 50 MB
- Check file format is supported
- Ensure upload directory is writable

## Support

For integration support, contact the development team with:
- API endpoint being used
- Error message and response code
- Relevant log entries
- Sample request data (sanitized)

## API Rate Limits

Currently, no rate limiting is enforced. However, avoid:
- Submitting duplicate OTP records (check before submitting)
- Uploading same file multiple times
- Excessive polling of deal file status

## Version History

- **v1.0** (2026-03): Initial API release with OTP and document management
