# ANIPM Website Troubleshooting Guide

## 🚨 Common Issues and Solutions

### 1. Service Not Starting

#### Problem: Backend service fails to start
**Symptoms:**
- HTTP 502 Bad Gateway error
- API endpoints not responding
- `sudo supervisorctl status` shows backend as FATAL

**Diagnosis:**
```bash
# Check supervisor logs
sudo supervisorctl tail anipm-backend

# Check if PHP is installed
php --version

# Check if backend files exist
ls -la /var/www/anipm/backend/

# Check environment file
ls -la /var/www/anipm/backend/.env
```

**Solutions:**
```bash
# Install missing PHP dependencies
sudo apt update
sudo apt install php8.1 php8.1-fpm php8.1-mysql composer

# Install Laravel dependencies
cd /var/www/anipm/backend
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Clear Laravel cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Fix permissions
sudo chown -R www-data:www-data /var/www/anipm/backend/storage
sudo chmod -R 775 /var/www/anipm/backend/storage

# Restart service
sudo supervisorctl restart anipm-backend
```

#### Problem: Frontend service fails to start
**Symptoms:**
- Website not loading
- White screen or blank page
- `sudo supervisorctl status` shows frontend as FATAL

**Diagnosis:**
```bash
# Check supervisor logs
sudo supervisorctl tail anipm-frontend

# Check if Node.js is installed
node --version
npm --version

# Check if frontend files exist
ls -la /var/www/anipm/frontend/

# Check if build exists
ls -la /var/www/anipm/frontend/build/
```

**Solutions:**
```bash
# Install Node.js if missing
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install frontend dependencies
cd /var/www/anipm/frontend
npm install

# Build production version
npm run build

# Copy build to web root
sudo cp -r build/* /var/www/anipm/public/

# Restart Nginx
sudo systemctl restart nginx
```

### 2. Database Connection Issues

#### Problem: Database connection failed
**Symptoms:**
- Laravel errors about database connection
- Status API endpoints returning errors
- "Connection refused" errors in logs

**Diagnosis:**
```bash
# Check if MySQL is running
sudo systemctl status mysql

# Test database connection
mysql -u anipm_user -p anipm_db

# Check Laravel configuration
cd /var/www/anipm/backend
php artisan config:show database

# Check environment variables
cat .env | grep DB_
```

**Solutions:**
```bash
# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql

# Create database if missing
sudo mysql -u root -p
```

```sql
CREATE DATABASE IF NOT EXISTS anipm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'anipm_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON anipm_db.* TO 'anipm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Update Laravel configuration
cd /var/www/anipm/backend
php artisan config:clear
php artisan migrate

# Restart backend service
sudo supervisorctl restart anipm-backend
```

### 3. SSL/HTTPS Issues

#### Problem: SSL certificate not working
**Symptoms:**
- "This site is not secure" warning
- Mixed content warnings
- SSL certificate errors

**Diagnosis:**
```bash
# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Check Nginx SSL configuration
sudo nginx -t

# Check certificate files
ls -la /etc/letsencrypt/live/yourdomain.com/

# Check certificate expiry
openssl x509 -in /etc/letsencrypt/live/yourdomain.com/cert.pem -noout -dates
```

**Solutions:**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Renew SSL certificate
sudo certbot renew --dry-run

# Set up auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Update Nginx configuration
sudo nginx -t && sudo systemctl reload nginx
```

### 4. File Permission Issues

#### Problem: Files not accessible or writable
**Symptoms:**
- HTTP 403 Forbidden errors
- "Permission denied" errors
- Document downloads failing

**Diagnosis:**
```bash
# Check file permissions
ls -la /var/www/anipm/
ls -la /var/www/anipm/backend/storage/
ls -la /var/www/anipm/frontend/src/documents/

# Check file ownership
ls -la /var/www/anipm/ | grep -E "(storage|documents|public)"

# Check web server user
ps aux | grep nginx
ps aux | grep apache
```

**Solutions:**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/anipm/

# Fix permissions
sudo chmod -R 755 /var/www/anipm/
sudo chmod -R 775 /var/www/anipm/backend/storage/
sudo chmod -R 775 /var/www/anipm/backend/bootstrap/cache/
sudo chmod -R 644 /var/www/anipm/frontend/src/documents/

# Fix environment files
sudo chmod 600 /var/www/anipm/backend/.env
sudo chmod 600 /var/www/anipm/frontend/.env

# Restart services
sudo systemctl restart nginx
sudo supervisorctl restart anipm-backend
```

### 5. Performance Issues

#### Problem: Website loading slowly
**Symptoms:**
- High response times
- Timeout errors
- Server overload

**Diagnosis:**
```bash
# Check system resources
top
htop
free -h
df -h

# Check service status
sudo systemctl status nginx
sudo systemctl status mysql
sudo supervisorctl status

# Check logs for errors
tail -f /var/log/nginx/error.log
tail -f /var/log/mysql/error.log
tail -f /var/log/supervisor/anipm-backend.log

# Test response times
curl -w "@curl-format.txt" -o /dev/null -s https://yourdomain.com/api/
```

**Solutions:**
```bash
# Optimize PHP configuration
sudo nano /etc/php/8.1/fpm/php.ini
# Increase: memory_limit = 512M
# Increase: max_execution_time = 300
# Enable: opcache.enable=1

# Optimize MySQL
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add: innodb_buffer_pool_size = 1G
# Add: query_cache_size = 32M

# Enable Laravel optimization
cd /var/www/anipm/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable Nginx caching
sudo nano /etc/nginx/sites-available/anipm
# Add caching headers and gzip compression

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo systemctl restart nginx
sudo supervisorctl restart anipm-backend
```

### 6. Document Download Issues

#### Problem: Documents not downloading
**Symptoms:**
- 404 errors on document links
- Documents show as text instead of downloading
- Permission denied errors

**Diagnosis:**
```bash
# Check document files exist
ls -la /var/www/anipm/frontend/src/documents/

# Check Nginx configuration
sudo nginx -t

# Test document access
curl -I https://yourdomain.com/documents/link.docx
curl -I https://yourdomain.com/api/download/link.docx

# Check backend logs
tail -f /var/log/supervisor/anipm-backend.log
```

**Solutions:**
```bash
# Fix document permissions
sudo chmod -R 644 /var/www/anipm/frontend/src/documents/
sudo chown -R www-data:www-data /var/www/anipm/frontend/src/documents/

# Update Nginx configuration
sudo nano /etc/nginx/sites-available/anipm
# Ensure document alias is correct:
# location /documents {
#     alias /var/www/anipm/frontend/src/documents;
#     expires 1y;
#     add_header Cache-Control "public, immutable";
# }

# Test and reload Nginx
sudo nginx -t && sudo systemctl reload nginx

# Check Laravel routes
cd /var/www/anipm/backend
php artisan route:list | grep download
```

### 7. API Endpoint Issues

#### Problem: API endpoints not responding
**Symptoms:**
- HTTP 404 on /api/ routes
- CORS errors
- JSON parsing errors

**Diagnosis:**
```bash
# Test API endpoints
curl -I https://yourdomain.com/api/
curl -I https://yourdomain.com/api/status
curl -I https://yourdomain.com/api/news-ticker

# Check backend service
sudo supervisorctl status anipm-backend

# Check Laravel routes
cd /var/www/anipm/backend
php artisan route:list

# Check backend logs
tail -f /var/log/supervisor/anipm-backend.log
```

**Solutions:**
```bash
# Ensure backend is running
sudo supervisorctl start anipm-backend

# Clear Laravel cache
cd /var/www/anipm/backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Check API controller
ls -la /var/www/anipm/backend/app/Http/Controllers/Api/

# Update Nginx proxy configuration
sudo nano /etc/nginx/sites-available/anipm
# Ensure API proxy is correct:
# location /api {
#     proxy_pass http://127.0.0.1:8001;
#     proxy_set_header Host $host;
#     proxy_set_header X-Real-IP $remote_addr;
#     proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
#     proxy_set_header X-Forwarded-Proto $scheme;
# }

# Test and reload Nginx
sudo nginx -t && sudo systemctl reload nginx
```

### 8. Memory and Resource Issues

#### Problem: Out of memory errors
**Symptoms:**
- HTTP 500 errors
- "Fatal error: Allowed memory size exhausted"
- System freezing or crashing

**Diagnosis:**
```bash
# Check memory usage
free -h
ps aux --sort=-%mem | head -20

# Check disk usage
df -h
du -sh /var/www/anipm/

# Check swap usage
swapon --show

# Check system logs
tail -f /var/log/syslog
journalctl -f
```

**Solutions:**
```bash
# Increase PHP memory limit
sudo nano /etc/php/8.1/fpm/php.ini
# Set: memory_limit = 1024M

# Add swap file if needed
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Optimize MySQL memory usage
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Set: innodb_buffer_pool_size = 512M
# Set: max_connections = 100

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo supervisorctl restart anipm-backend
```

## 🔧 Diagnostic Commands

### System Health Check
```bash
#!/bin/bash
# System Health Check Script

echo "=== ANIPM Website Health Check ==="
echo "Date: $(date)"
echo ""

# Check system resources
echo "=== System Resources ==="
echo "CPU Usage:"
top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1"%"}'
echo "Memory Usage:"
free -h | grep "Mem:" | awk '{print $3"/"$2" ("$3/$2*100"%)"}'
echo "Disk Usage:"
df -h / | tail -1 | awk '{print $3"/"$2" ("$5")"}'
echo ""

# Check services
echo "=== Services Status ==="
echo "Nginx: $(systemctl is-active nginx)"
echo "MySQL: $(systemctl is-active mysql)"
echo "PHP-FPM: $(systemctl is-active php8.1-fpm)"
echo "Supervisor: $(systemctl is-active supervisor)"
echo ""

# Check application services
echo "=== Application Services ==="
sudo supervisorctl status
echo ""

# Check website accessibility
echo "=== Website Accessibility ==="
curl -I -s https://yourdomain.com/ | head -1
curl -I -s https://yourdomain.com/api/ | head -1
echo ""

# Check database
echo "=== Database Status ==="
mysql -u anipm_user -p -e "SELECT COUNT(*) as status_checks FROM anipm_db.status_checks;" 2>/dev/null && echo "Database: OK" || echo "Database: ERROR"
echo ""

# Check recent errors
echo "=== Recent Errors ==="
echo "Nginx errors (last 10):"
tail -10 /var/log/nginx/error.log
echo ""
echo "Backend errors (last 10):"
tail -10 /var/log/supervisor/anipm-backend.log
echo ""
```

### Performance Monitoring
```bash
#!/bin/bash
# Performance Monitoring Script

echo "=== ANIPM Performance Monitor ==="
echo "Date: $(date)"
echo ""

# Response time test
echo "=== Response Time Test ==="
curl -w "Homepage: %{time_total}s\n" -o /dev/null -s https://yourdomain.com/
curl -w "API Index: %{time_total}s\n" -o /dev/null -s https://yourdomain.com/api/
curl -w "News Ticker: %{time_total}s\n" -o /dev/null -s https://yourdomain.com/api/news-ticker
echo ""

# Database performance
echo "=== Database Performance ==="
mysql -u anipm_user -p -e "SHOW PROCESSLIST;" 2>/dev/null | wc -l | awk '{print "Active connections: " $1}'
mysql -u anipm_user -p -e "SHOW STATUS LIKE 'Queries';" 2>/dev/null | awk '{print "Total queries: " $2}'
echo ""

# Log analysis
echo "=== Error Rate Analysis ==="
echo "Nginx 5xx errors (last hour):"
grep "$(date -d '1 hour ago' '+%d/%b/%Y:%H')" /var/log/nginx/access.log | grep -c " 5[0-9][0-9] "
echo "Nginx 4xx errors (last hour):"
grep "$(date -d '1 hour ago' '+%d/%b/%Y:%H')" /var/log/nginx/access.log | grep -c " 4[0-9][0-9] "
echo ""
```

## 📞 Emergency Procedures

### Quick Recovery Steps
1. **Stop all services**: `sudo supervisorctl stop all`
2. **Check disk space**: `df -h`
3. **Check memory**: `free -h`
4. **Restart MySQL**: `sudo systemctl restart mysql`
5. **Restart Nginx**: `sudo systemctl restart nginx`
6. **Start backend**: `sudo supervisorctl start anipm-backend`
7. **Test website**: `curl -I https://yourdomain.com/`

### Rollback Procedure
```bash
# Emergency rollback script
#!/bin/bash
echo "Starting emergency rollback..."

# Stop services
sudo supervisorctl stop all
sudo systemctl stop nginx

# Restore from backup
sudo cp -r /var/backups/anipm/latest/* /var/www/anipm/

# Restore database
mysql -u anipm_user -p anipm_db < /var/backups/anipm/latest/database.sql

# Fix permissions
sudo chown -R www-data:www-data /var/www/anipm/
sudo chmod -R 755 /var/www/anipm/

# Start services
sudo systemctl start nginx
sudo supervisorctl start all

echo "Rollback completed. Check website: https://yourdomain.com/"
```

## 📋 Maintenance Checklist

### Daily Checks
- [ ] Check service status
- [ ] Review error logs
- [ ] Monitor resource usage
- [ ] Test website accessibility
- [ ] Check database connectivity

### Weekly Checks
- [ ] Update system packages
- [ ] Review security logs
- [ ] Check backup integrity
- [ ] Monitor performance metrics
- [ ] Clean up log files

### Monthly Checks
- [ ] Security audit
- [ ] Performance optimization
- [ ] Capacity planning
- [ ] Update application dependencies
- [ ] Review firewall rules

This troubleshooting guide covers the most common issues you might encounter when hosting the ANIPM website and provides step-by-step solutions for each problem.