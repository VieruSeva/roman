# ANIPM Website Deployment Guide

## 🎯 Overview
This is a comprehensive deployment guide for the ANIPM (Agentia Nationala pentru Industria Prelucratoare si Mediu) website. The application is a full-stack web application built with React.js frontend, PHP Laravel backend, and MySQL database.

## 📋 Application Architecture

### Technology Stack
- **Frontend**: React.js 18.3.1 (Single Page Application)
- **Backend**: PHP Laravel 10.x (REST API)
- **Database**: MySQL/MariaDB 8.0+
- **Web Server**: Nginx (Reverse Proxy)
- **Process Manager**: Supervisor
- **Container**: Docker (Multi-stage build)

### Service Ports
- **Frontend**: Port 3000 (React Development Server)
- **Backend**: Port 8001 (Laravel Artisan Serve)
- **Database**: Port 3306 (MySQL)
- **Nginx**: Port 80/443 (External Access)

## 🚀 Deployment Options

### Option 1: Docker Deployment (Recommended)

#### Prerequisites
- Docker Engine 20.10+
- Docker Compose 2.0+
- Minimum 2GB RAM
- 10GB available disk space

#### Quick Start
```bash
# 1. Clone/Upload the application files
cd /path/to/your/app

# 2. Build the Docker image
docker build -t anipm-website .

# 3. Run the container
docker run -d \
  --name anipm-website \
  -p 80:80 \
  -e FRONTEND_ENV="WDS_SOCKET_PORT=443,REACT_APP_BACKEND_URL=https://yourdomain.com" \
  anipm-website

# 4. Check container status
docker ps
docker logs anipm-website
```

#### Docker Compose (Recommended for Production)
Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
      - "443:443"
    environment:
      - FRONTEND_ENV=WDS_SOCKET_PORT=443,REACT_APP_BACKEND_URL=https://yourdomain.com
    volumes:
      - ./uploads:/var/www/html/storage/app/public
    restart: unless-stopped
    depends_on:
      - mysql

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: your_secure_password
      MYSQL_DATABASE: anipm_db
      MYSQL_USER: anipm_user
      MYSQL_PASSWORD: your_secure_password
    volumes:
      - mysql_data:/var/lib/mysql
    restart: unless-stopped

volumes:
  mysql_data:
```

Run with:
```bash
docker-compose up -d
```

### Option 2: Manual Deployment

#### System Requirements
- **Operating System**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+
- **PHP**: 8.1 or higher
- **Node.js**: 18.x or higher
- **MySQL**: 8.0 or higher
- **Nginx**: 1.18 or higher
- **RAM**: Minimum 2GB, Recommended 4GB+
- **Storage**: Minimum 10GB free space

#### Step-by-Step Manual Installation

##### 1. System Updates
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

##### 2. Install Required Packages
```bash
# Ubuntu/Debian
sudo apt install -y nginx mysql-server php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-json composer nodejs npm supervisor

# CentOS/RHEL
sudo yum install -y nginx mysql-server php php-fpm php-mysql php-xml php-curl php-mbstring php-zip php-bcmath php-json composer nodejs npm supervisor
```

##### 3. Database Setup
```bash
# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE anipm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'anipm_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON anipm_db.* TO 'anipm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

##### 4. Application Setup
```bash
# Create application directory
sudo mkdir -p /var/www/anipm
sudo chown -R www-data:www-data /var/www/anipm

# Upload/Copy application files
# (Upload your application files to /var/www/anipm)

# Set proper permissions
sudo chown -R www-data:www-data /var/www/anipm
sudo chmod -R 755 /var/www/anipm
```

##### 5. Backend Configuration
```bash
# Navigate to backend directory
cd /var/www/anipm/backend

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Create .env file
cp .env.example .env

# Edit .env file with your database credentials
nano .env
```

**Backend .env configuration:**
```env
APP_NAME=ANIPM
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=anipm_db
DB_USERNAME=anipm_user
DB_PASSWORD=your_secure_password

LOG_CHANNEL=single
LOG_LEVEL=error
```

```bash
# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Cache configuration
php artisan config:cache
php artisan route:cache

# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

##### 6. Frontend Configuration
```bash
# Navigate to frontend directory
cd /var/www/anipm/frontend

# Install Node.js dependencies
npm install

# Create .env file
nano .env
```

**Frontend .env configuration:**
```env
REACT_APP_BACKEND_URL=https://yourdomain.com
WDS_SOCKET_PORT=443
```

```bash
# Build production version
npm run build

# Move build to web root
sudo cp -r build/* /var/www/anipm/public/
```

##### 7. Nginx Configuration
```bash
# Create Nginx configuration
sudo nano /etc/nginx/sites-available/anipm
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/anipm/public;
    index index.html index.php;

    # Frontend routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API routes
    location /api {
        proxy_pass http://127.0.0.1:8001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # Document downloads
    location /documents {
        alias /var/www/anipm/frontend/src/documents;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/anipm /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

##### 8. Supervisor Configuration
```bash
# Create supervisor configuration
sudo nano /etc/supervisor/conf.d/anipm.conf
```

```ini
[program:anipm-backend]
command=/usr/bin/php /var/www/anipm/backend/artisan serve --host=0.0.0.0 --port=8001
directory=/var/www/anipm/backend
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/anipm-backend.log
stderr_logfile=/var/log/supervisor/anipm-backend-error.log
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start anipm-backend

# Check status
sudo supervisorctl status
```

## 🔧 Environment Configuration

### Required Environment Variables

#### Frontend (.env)
```env
REACT_APP_BACKEND_URL=https://yourdomain.com
WDS_SOCKET_PORT=443
```

#### Backend (.env)
```env
APP_NAME=ANIPM
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=anipm_db
DB_USERNAME=anipm_user
DB_PASSWORD=your_secure_password

LOG_CHANNEL=single
LOG_LEVEL=error
```

### File Permissions
```bash
# Application directory
sudo chown -R www-data:www-data /var/www/anipm
sudo chmod -R 755 /var/www/anipm

# Laravel specific
sudo chmod -R 775 /var/www/anipm/backend/storage
sudo chmod -R 775 /var/www/anipm/backend/bootstrap/cache

# Documents directory
sudo chmod -R 644 /var/www/anipm/frontend/src/documents
```

## 📁 File Structure

```
/var/www/anipm/
├── backend/                    # PHP Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   └── ApiController.php
│   │   ├── Models/
│   │   │   └── StatusCheck.php
│   │   └── Services/
│   │       └── ImageExtractorService.php
│   ├── routes/
│   │   └── api.php
│   ├── config/
│   │   └── database.php
│   ├── composer.json
│   ├── .env
│   └── artisan
├── frontend/                   # React Frontend
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   └── documents/          # PDF/DOCX files
│   ├── public/
│   ├── package.json
│   └── .env
├── Dockerfile                  # Docker build configuration
├── docker-compose.yml          # Docker Compose configuration
├── nginx.conf                  # Nginx configuration
└── entrypoint.sh              # Container startup script
```

## 🌐 API Endpoints

### Core Endpoints
- `GET /api/` - API information page
- `GET /api/demo` - Interactive demo page

### Document Management
- `GET /api/download/{filename}` - Download documents
- Documents available:
  - PDF files: Various legislation documents
  - DOCX files: Ministry documents

### News & Content
- `GET /api/news-ticker` - News ticker data
- `POST /api/fetch-news-image` - Extract image from news URL
- `POST /api/fetch-multiple-news-images` - Extract multiple images

### Status Checks
- `POST /api/status` - Create status check
- `GET /api/status` - Get all status checks
- `GET /api/status/{id}` - Get specific status check

## 🔒 Security Considerations

### SSL/TLS Configuration
```bash
# Install Certbot for Let's Encrypt
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo systemctl enable certbot.timer
```

### Firewall Configuration
```bash
# Ubuntu/Debian
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw allow 3306/tcp  # MySQL (if remote access needed)
sudo ufw enable

# CentOS/RHEL
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### Database Security
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database backup user
sudo mysql -u root -p
```

```sql
CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'secure_backup_password';
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON anipm_db.* TO 'backup_user'@'localhost';
FLUSH PRIVILEGES;
```

## 📊 Monitoring & Logging

### Log Files Locations
```bash
# Application logs
/var/log/supervisor/anipm-backend.log
/var/log/supervisor/anipm-backend-error.log

# Web server logs
/var/log/nginx/access.log
/var/log/nginx/error.log

# Database logs
/var/log/mysql/error.log

# System logs
/var/log/syslog
```

### Health Check Commands
```bash
# Check all services
sudo systemctl status nginx
sudo systemctl status mysql
sudo supervisorctl status

# Check application health
curl -I https://yourdomain.com
curl -I https://yourdomain.com/api/

# Check database connection
mysql -u anipm_user -p anipm_db -e "SELECT 1"
```

## 🔄 Backup & Recovery

### Database Backup
```bash
# Create backup script
sudo nano /usr/local/bin/backup-anipm.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/anipm"
DB_NAME="anipm_db"
DB_USER="backup_user"
DB_PASS="secure_backup_password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/anipm_db_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/anipm_db_$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "anipm_db_*.sql.gz" -mtime +7 -delete

echo "Backup completed: anipm_db_$DATE.sql.gz"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-anipm.sh

# Add to crontab (daily backup at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup-anipm.sh
```

### File Backup
```bash
# Application files backup
sudo tar -czf /var/backups/anipm/anipm_files_$(date +%Y%m%d).tar.gz /var/www/anipm
```

## 🚨 Troubleshooting Guide

### Common Issues

#### 1. Backend Service Not Starting
```bash
# Check logs
sudo supervisorctl tail anipm-backend

# Common fixes
cd /var/www/anipm/backend
composer install
php artisan key:generate
php artisan config:cache
sudo supervisorctl restart anipm-backend
```

#### 2. Database Connection Error
```bash
# Test database connection
mysql -u anipm_user -p anipm_db

# Check Laravel configuration
cd /var/www/anipm/backend
php artisan config:clear
php artisan cache:clear
```

#### 3. Frontend Not Loading
```bash
# Check Nginx configuration
sudo nginx -t
sudo systemctl reload nginx

# Rebuild frontend
cd /var/www/anipm/frontend
npm run build
sudo cp -r build/* /var/www/anipm/public/
```

#### 4. Document Downloads Not Working
```bash
# Check file permissions
sudo chmod -R 644 /var/www/anipm/frontend/src/documents
sudo chown -R www-data:www-data /var/www/anipm/frontend/src/documents

# Check Nginx document alias
sudo nginx -t
```

### Performance Optimization

#### 1. Enable Caching
```bash
# Laravel caching
cd /var/www/anipm/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 2. Database Optimization
```sql
-- Add indexes for better performance
USE anipm_db;
CREATE INDEX idx_status_checks_timestamp ON status_checks(timestamp);
CREATE INDEX idx_status_checks_client ON status_checks(client_name);
```

#### 3. Nginx Optimization
```nginx
# Add to nginx configuration
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Enable gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

## 🔍 Testing Deployment

### 1. Frontend Tests
```bash
# Check main page
curl -I https://yourdomain.com

# Check if React app loads
curl -s https://yourdomain.com | grep -i "react"
```

### 2. Backend API Tests
```bash
# Test API endpoints
curl -I https://yourdomain.com/api/
curl -I https://yourdomain.com/api/news-ticker
curl -I https://yourdomain.com/api/status
```

### 3. Document Download Tests
```bash
# Test document downloads
curl -I https://yourdomain.com/api/download/link.docx
curl -I https://yourdomain.com/documents/link.docx
```

### 4. Database Tests
```bash
# Test database connectivity
cd /var/www/anipm/backend
php artisan tinker
# In tinker: \App\Models\StatusCheck::count()
```

## 📞 Support Information

### Technical Specifications
- **Application Type**: Full-stack Web Application
- **Primary Language**: PHP (Backend), JavaScript (Frontend)
- **Framework**: Laravel 10.x, React 18.x
- **Database**: MySQL 8.0+
- **Deployment**: Docker or Manual
- **License**: Custom Application

### Required Server Specifications
- **CPU**: 2+ cores
- **RAM**: 4GB+ (minimum 2GB)
- **Storage**: 20GB+ available space
- **Network**: 100Mbps+ bandwidth
- **OS**: Ubuntu 20.04+, CentOS 8+, or Debian 11+

### Maintenance Schedule
- **Database Backups**: Daily at 2:00 AM
- **Security Updates**: Monthly
- **Application Updates**: As needed
- **Log Rotation**: Weekly

## 📝 Notes for Hosting Provider

1. **Domain Configuration**: Replace `yourdomain.com` with actual domain
2. **SSL Certificate**: Recommended to use Let's Encrypt or provided SSL
3. **Database Password**: Use strong, unique passwords
4. **File Permissions**: Critical for security and functionality
5. **Regular Backups**: Essential for data protection
6. **Monitoring**: Set up monitoring for all services
7. **Updates**: Keep all components updated for security

This deployment guide provides comprehensive instructions for hosting the ANIPM website on a Linux VPS server. The application is production-ready and includes all necessary security measures and optimization configurations.