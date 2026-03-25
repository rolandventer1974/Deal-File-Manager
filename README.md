# Deal File Manager

A PHP-based document management system for managing deal files in auto sales. This application integrates with ColdFusion-based AutoSLM via REST API to receive Offer to Purchase (OTP) documents and allows dealers to manage and upload supporting documentation.

## Features

- **Dashboard**: View all incomplete deal files with status and completion percentage
- **Deal File Management**: Create and manage deal files with customer and vehicle information
- **Document Management**: Upload, organize, and track multi-type documents (PDFs, Word, Images, Spreadsheets)
- **API Integration**: Receive documents from ColdFusion AutoSLM application via REST API
- **Status Tracking**: Monitor completion status for each deal file
- **Activity Logging**: Track all actions and changes to deal files
- **Responsive Design**: Mobile-friendly interface

## System Requirements

- PHP 7.4 or 8.0+
- MySQL 5.7 or MariaDB 10.2+
- Web Server (Apache/Nginx with mod_rewrite/URL rewriting)
- 50MB+ disk space for document storage
- Internet connectivity for API integration

## Quick Installation (Digital Ocean Ubuntu)

Run the automated installation script:

```bash
chmod +x setup.sh
./setup.sh dealfilemanager.co.za
```

Or follow manual installation below.

## Manual Installation

### 1. Clone Repository

```bash
cd /var/www
git clone <repository-url> dealfilemanager
cd dealfilemanager
```

### 2. Set Permissions

```bash
chmod -R 755 .
chmod -R 777 public/uploads logs
chown -R www-data:www-data .
```

### 3. Configure Environment

```bash
cp .env.example .env
# Edit .env with your database credentials
nano .env
```

### 4. Create Database

```bash
mysql -u root -p < database/schema.sql
```

### 5. Install Dependencies

```bash
composer install
```

### 6. Configure Web Server

Use provided `nginx.conf` or `apache.conf` files.

## API Documentation

### Authentication

All API calls require a Bearer token in the Authorization header:

```
Authorization: Bearer {API_KEY}
```

### Receive OTP from ColdFusion

```
POST /public/api.php?action=receive-otp
```

Request body:
```json
{
    "customer_name": "John Doe",
    "customer_id_number": "ID123456",
    "customer_email": "john@example.com",
    "customer_mobile": "+1234567890",
    "vehicle_year": 2022,
    "vehicle_make": "Toyota",
    "vehicle_model": "Camry",
    "vehicle_specification": "SE",
    "vin_number": "JTDKRFVU2J1034567",
    "sales_executive_name": "Jane Smith",
    "sales_manager_name": "Bob Johnson",
    "finance_company": "ABC Finance",
    "otp_url": "https://source/otp-document.pdf"
}
```

### Upload Document

```
POST /public/api.php?action=upload-document
```

FormData:
- `deal_file_id` - ID of the deal file
- `document_type` - Type of document
- `document` - File to upload
- `notes` - Optional notes

### Get Documents

```
GET /public/api.php?action=get-documents&deal_file_id=1
```

### Download Document

```
GET /public/api.php?action=download-document&document_id=5
```

## Document Types Supported

- Offer to Purchase
- Vehicle Inspection Report
- Costing Sheet
- Signed Delivery Document
- Insurance Quote
- Registration Documents
- Service History
- Warranty Information
- Finance Agreement
- ID Verification
- Proof of Income
- Bank Statement
- Other Documents

## Project Structure

```
dealfilemanager/
├── public/               # Web root
│   ├── index.php        # Main application entry
│   ├── api.php          # API endpoint
│   ├── css/
│   ├── js/
│   └── uploads/         # Document storage
├── src/                 # Application source
│   ├── API/
│   ├── Config/
│   ├── Controllers/
│   ├── Models/
│   └── Utils/
├── views/               # Templates
│   ├── dashboard/
│   ├── dealfiles/
│   └── layouts/
├── database/            # Database schema
├── logs/                # Application logs
├── composer.json
└── install.sh
```

## Logs

Monitor application logs:

```bash
tail -f logs/app.log
```

## Backup & Maintenance

The automated installation creates daily backups at 2 AM.

Manual backup:

```bash
mysqldump -u dfm_user -p deal_file_manager > backup.sql
tar -czf uploads_backup.tar.gz public/uploads/
```

## Security Notes

- Always use HTTPS in production
- Store API key securely
- Update database password in `.env`
- Keep PHP and dependencies updated
- Regularly backup database and uploads
- Monitor logs for suspicious activity

## Support

For issues or questions, contact the development team.

## License

Copyright 2026. All rights reserved.