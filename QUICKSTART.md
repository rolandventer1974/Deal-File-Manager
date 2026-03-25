# Quick Start Guide

## Installation

### Option 1: Automated (Digital Ocean)

```bash
chmod +x setup.sh
./setup.sh dealfilemanager.co.za
```

### Option 2: Manual Installation

1. **Prerequisites**
   ```bash
   # Install PHP, MySQL, Nginx
   apt-get install nginx php8.0 php8.0-fpm php8.0-mysql mysql-server composer
   ```

2. **Setup Database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   nano .env # Edit database credentials
   ```

4. **Install Dependencies**
   ```bash
   composer install
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 .
   chmod -R 777 public/uploads logs
   ```

6. **Configure Web Server** (see nginx.conf or apache.conf)

## First Use

1. Open browser: `https://yourdomain.com`
2. Navigate to "Create Deal File" button
3. Fill in customer and vehicle information
4. Click "Create Deal File"
5. Upload supporting documents as needed

## API Integration

### Get API Key

Check `.env` file for `API_KEY=`

### Test API

```bash
curl -X POST "https://yourdomain.com/api.php?action=receive-otp" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Test",
    "vehicle_year": 2022,
    "vehicle_make": "Toyota",
    "vehicle_model": "Camry",
    "vin_number": "TEST1234567890ABC"
  }'
```

## Documentation

- **Setup & Deployment**: See [DEPLOY.md](DEPLOY.md)
- **API Integration**: See [API_INTEGRATION.md](API_INTEGRATION.md)
- **Full README**: See [README.md](README.md)

## Directory Structure

```
.
├── public/              # Web root
│   ├── index.php       # App entry
│   ├── api.php         # API entry
│   ├── css/
│   ├── js/
│   └── uploads/        # Documents
├── src/                # Source code
├── views/              # Templates
├── database/           # Database schema
└── logs/               # Application logs
```

## Common Tasks

### Monitor Logs
```bash
tail -f logs/app.log
```

### Backup Database
```bash
mysqldump -u dfm_user -p deal_file_manager > backup.sql
```

### Restart Services
```bash
systemctl restart nginx php8.0-fpm mysql
```

### Check Status
```bash
systemctl status nginx
```

## Support

For detailed information and troubleshooting, see the [README.md](README.md) and [DEPLOY.md](DEPLOY.md) files.
