#!/bin/bash

# Deal File Manager - Installation Script for Digital Ocean Ubuntu
# This script sets up the application on a fresh Ubuntu 20.04/22.04 server

set -e

echo "========================================"
echo "Deal File Manager - Installation Script"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
DOMAIN_NAME="${1:-yourdomain.com}"
APP_DIR="/var/www/deal-file-manager"
DB_NAME="deal_file_manager"
DB_USER="dfm_user"
DB_PASSWORD=$(openssl rand -base64 12)
APP_ENV_FILE="${APP_DIR}/.env"

echo -e "${YELLOW}Installation will use the following settings:${NC}"
echo "Domain: $DOMAIN_NAME"
echo "App Directory: $APP_DIR"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"
echo "Database Password: $DB_PASSWORD"
echo ""

read -p "Continue with installation? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Update system
echo -e "${YELLOW}Updating system packages...${NC}"
apt-get update
apt-get upgrade -y

# Install web server and PHP
echo -e "${YELLOW}Installing web server and PHP...${NC}"
apt-get install -y nginx php8.0 php8.0-fpm php8.0-mysql php8.0-mbstring php8.0-curl php8.0-json php8.0-dom

# Install MySQL/MariaDB
echo -e "${YELLOW}Installing MySQL server...${NC}"
apt-get install -y mysql-server

# Install other tools
echo -e "${YELLOW}Installing additional tools...${NC}"
apt-get install -y curl git composer certbot python3-certbot-nginx

# Create database user and database
echo -e "${YELLOW}Setting up database...${NC}"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -u root -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Create application directory
echo -e "${YELLOW}Creating application directory...${NC}"
mkdir -p ${APP_DIR}

# Copy application files (assuming already uploaded)
if [ ! -f "${APP_DIR}/composer.json" ]; then
    echo -e "${RED}Application files not found in ${APP_DIR}${NC}"
    echo "Please upload the application files to ${APP_DIR} first"
    exit 1
fi

# Set permissions
echo -e "${YELLOW}Setting permissions...${NC}"
chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 777 ${APP_DIR}/public/uploads
chmod -R 777 ${APP_DIR}/logs

# Install PHP dependencies
echo -e "${YELLOW}Installing PHP dependencies...${NC}"
cd ${APP_DIR}
composer install --no-dev --optimize-autoloader

# Create environment file
echo -e "${YELLOW}Creating environment configuration...${NC}"
cp .env.example .env
sed -i "s/DB_HOST=.*/DB_HOST=localhost/" .env
sed -i "s/DB_NAME=.*/DB_NAME=${DB_NAME}/" .env
sed -i "s/DB_USER=.*/DB_USER=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN_NAME}|" .env
sed -i "s|UPLOAD_DIR=.*|UPLOAD_DIR=${APP_DIR}/public/uploads|" .env
sed -i "s|LOG_FILE=.*|LOG_FILE=${APP_DIR}/logs/app.log|" .env
sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env

# Generate API key
API_KEY=$(openssl rand -base64 32)
sed -i "s/API_KEY=.*/API_KEY=${API_KEY}/" .env

# Import database schema
echo -e "${YELLOW}Importing database schema...${NC}"
mysql -u ${DB_USER} -p${DB_PASSWORD} ${DB_NAME} < database/schema.sql

# Configure Nginx
echo -e "${YELLOW}Configuring Nginx...${NC}"
cp nginx.conf /etc/nginx/sites-available/deal-file-manager
sed -i "s/yourdomain.com/${DOMAIN_NAME}/g" /etc/nginx/sites-available/deal-file-manager
sed -i "s|/var/www/deal-file-manager|${APP_DIR}|g" /etc/nginx/sites-available/deal-file-manager

# Enable Nginx site
ln -sf /etc/nginx/sites-available/deal-file-manager /etc/nginx/sites-enabled/

# Disable default site
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t

# Start services
echo -e "${YELLOW}Starting services...${NC}"
systemctl restart nginx
systemctl restart php8.0-fpm
systemctl restart mysql

# Setup SSL certificate
echo -e "${YELLOW}Setting up SSL certificate...${NC}"
certbot certonly --nginx -d ${DOMAIN_NAME} -d www.${DOMAIN_NAME} --non-interactive --agree-tos -m admin@${DOMAIN_NAME}

# Create cron job for log rotation
echo -e "${YELLOW}Setting up log rotation...${NC}"
cat > /etc/logrotate.d/deal-file-manager <<EOF
${APP_DIR}/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0640 www-data www-data
}
EOF

# Create a backup script
echo -e "${YELLOW}Creating backup script...${NC}"
cat > /usr/local/bin/backup-deal-file-manager.sh <<'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/deal-file-manager"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u dfm_user -p${DB_PASSWORD} deal_file_manager > $BACKUP_DIR/db_${TIMESTAMP}.sql
gzip $BACKUP_DIR/db_${TIMESTAMP}.sql

# Backup uploads
tar -czf $BACKUP_DIR/uploads_${TIMESTAMP}.tar.gz /var/www/deal-file-manager/public/uploads/

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $TIMESTAMP"
EOF

chmod +x /usr/local/bin/backup-deal-file-manager.sh

# Add to crontab
echo "0 2 * * * /usr/local/bin/backup-deal-file-manager.sh" | crontab -

# Print summary
echo ""
echo -e "${GREEN}========================================"
echo "Installation Complete!"
echo "========================================${NC}"
echo ""
echo "Application URL: ${GREEN}https://${DOMAIN_NAME}${NC}"
echo "API Endpoint: ${GREEN}https://${DOMAIN_NAME}/api.php${NC}"
echo ""
echo "Database Information:"
echo "  Database: ${GREEN}${DB_NAME}${NC}"
echo "  User: ${GREEN}${DB_USER}${NC}"
echo "  Password: ${GREEN}${DB_PASSWORD}${NC}"
echo ""
echo "API Key: ${GREEN}${API_KEY}${NC}"
echo ""
echo "Configuration file: ${GREEN}${APP_ENV_FILE}${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Update ColdFusion with the API key for integration"
echo "2. Create admin user account"
echo "3. Configure email settings in .env"
echo "4. Review logs in: ${APP_DIR}/logs/"
echo ""
echo -e "${YELLOW}Important:${NC}"
echo "- Save the database password in a secure location"
echo "- Backup scripts run daily at 2 AM"
echo "- SSL certificate auto-renewal configured"
echo ""
