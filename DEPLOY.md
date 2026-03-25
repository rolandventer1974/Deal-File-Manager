# Digital Ocean Deployment Guide for Deal File Manager

## Prerequisites

- Digital Ocean account with a Droplet (Ubuntu 20.04 or 22.04)
- Domain name configured to point to your Droplet IP
- SSH access to your Droplet
- Approximately 2 minutes to complete setup

## Step-by-Step Deployment

### 1. Create a Digital Ocean Droplet

- Choose **Ubuntu 22.04 x64** image
- Select desired size (minimum: $6/month for development, $12+/month for production)
- Add SSH key for authentication
- Choose nearest region for lower latency
- Add backups (recommended)

### 2. Connect to Your Droplet

```bash
ssh root@your_droplet_ip
```

### 3. Download and Run Setup Script

```bash
# Download the application
cd /tmp
git clone <your-repo-url> dealfilemanager
cd dealfilemanager

# Make setup script executable
chmod +x setup.sh

# Run setup with your domain
./setup.sh dealfilemanager.co.za
```

The script will:
- Update system packages
- Install Nginx, PHP 8.0, MySQL
- Create database and user
- Install PHP dependencies
- Configure Nginx
- Set up automatic backups
- Generate secure API key

### 4. Configure SSL Certificate

After the setup completes, secure your site with Let's Encrypt:

```bash
certbot certonly --webroot -w /var/www/dealfilemanager/public \
  -d dealfilemanager.co.za -d www.dealfilemanager.co.za
```

Update Nginx configuration with certificate paths and reload:

```bash
systemctl reload nginx
```

### 5. Verify Installation

- Visit `https://yourdomain.com` in your browser
- Create a test deal file
- Check that dashboard loads

### 6. Configure ColdFusion Integration

In your ColdFusion AutoSLM application:

1. **Add API Endpoint:**
   ```
   https://yourdomain.com/api.php?action=receive-otp
   ```

2. **Add Authorization Header:**
   ```
   Authorization: Bearer {API_KEY_FROM_SETUP}
   ```

3. **Test OTP Submission:**
   - Create a test deal with OTP
   - Monitor logs: `tail -f /var/www/dealfilemanager/logs/api.log`

## File Locations

- Application: `/var/www/dealfilemanager`
- Configuration: `/var/www/dealfilemanager/.env`
- Logs: `/var/www/dealfilemanager/logs/`
- Uploads: `/var/www/dealfilemanager/public/uploads/`
- Backups: `/var/backups/dealfilemanager/`
- Nginx config: `/etc/nginx/sites-available/dealfilemanager`

## Post-Installation

### Update Environment Variables

Edit `/var/www/dealfilemanager/.env`:

```bash
nano /var/www/dealfilemanager/.env
```

Key settings to configure:
- Email configuration for notifications
- ColdFusion API endpoint (for reverse integration)
- Log levels

### Create First Admin User

```bash
cd /var/www/dealfilemanager
# Future: User management system
```

### Configure Firewall (UFW)

```bash
# Allow SSH, HTTP, HTTPS
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### Monitor Application

Check logs for any issues:

```bash
# Application logs
tail -f /var/www/dealfilemanager/logs/app.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# MySQL logs
tail -f /var/log/mysql/error.log
```

### Database Backups

Automatic daily backups run at 2 AM UTC. Check backup status:

```bash
ls -lh /var/backups/dealfilemanager/
```

Manual backup:

```bash
mkdir -p /var/backups/manual
mysqldump -u dfm_user -p deal_file_manager | gzip > /var/backups/manual/backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

## Troubleshooting

### Nginx Not Starting

Check configuration:

```bash
nginx -t
systemctl status nginx
```

### PHP Errors

Check PHP-FPM status:

```bash
systemctl status php8.0-fpm
```

View error logs:

```bash
tail -f /var/log/php8.0-fpm.log
```

### Database Connection Issues

Test connection:

```bash
mysql -u dfm_user -p -h localhost deal_file_manager -e "SELEC COUNT(*) FROM deal_files;"
```

### SSL Certificate Issues

Renew certificate:

```bash
certbot renew --dry-run
certbot renew
```

Check certificate expiry:

```bash
certbot certificates
```

## Performance Optimization

### PHP-FPM Tuning

Edit `/etc/php/8.0/fpm/pool.d/www.conf`:

```ini
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
```

### MySQL Optimization

For development/small deployments, default settings are fine. For larger deployments:

```bash
# Edit MySQL config
nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Set appropriate:
- `innodb_buffer_pool_size`
- `query_cache_size`
- `max_connections`

### Nginx Caching

Already configured in nginx.conf for static assets (CSS, JS, images).

## Monitoring

### Install UFW and Configure Firewall

```bash
# Status
ufw status

# View rules
ufw show added
```

### Monitor Disk Usage

```bash
df -h
du -sh /var/www/dealfilemanager/public/uploads/
```

### System Resources

```bash
# CPU and memory
top -b -n 1 | head -20

# Disk I/O
iostat -x 1 5
```

## Scaling Up

When you outgrow a single server:

1. **Database**: Move MySQL to separate database server
2. **Storage**: Use object storage (Digital Ocean Spaces) for uploads
3. **Load Balancing**: Add load balancer with multiple application servers
4. **Caching**: Implement Redis for session/cache storage

## Security Hardening

### SSH Security

Disable root login and password authentication:

```bash
nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
# Set: PasswordAuthentication no
systemctl restart ssh
```

### Fail2Ban for Brute Force Protection

```bash
apt-get install fail2ban
systemctl enable fail2ban
systemctl start fail2ban
```

### Regular Security Updates

Automatic security updates:

```bash
apt-get install unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades
```

## Support

- Check logs for error details
- Review Digital Ocean documentation
- Contact development team for application-specific issues

## Useful Commands

```bash
# Restart services
systemctl restart nginx
systemctl restart php8.0-fpm
systemctl restart mysql

# View service status
systemctl status mysql
systemctl status php8.0-fpm
systemctl status nginx

# Clear application logs
> /var/www/dealfilemanager/logs/app.log

# Check PHP version
php -v

# Check disk space
df -h

# Check memory
free -h

# Check process
ps aux | grep php
```
