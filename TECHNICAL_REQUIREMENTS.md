# Technical Requirements for ANIPM Website

## 🖥️ System Requirements

### Minimum Requirements
- **Operating System**: Ubuntu 20.04 LTS, CentOS 8, or Debian 11
- **CPU**: 2 cores (2.0 GHz)
- **RAM**: 2 GB
- **Storage**: 10 GB available space
- **Network**: 10 Mbps bandwidth

### Recommended Requirements
- **Operating System**: Ubuntu 22.04 LTS (Latest stable)
- **CPU**: 4 cores (2.5 GHz or higher)
- **RAM**: 4 GB or more
- **Storage**: 50 GB SSD (for better performance)
- **Network**: 100 Mbps bandwidth

### Production Requirements
- **CPU**: 8 cores (3.0 GHz or higher)
- **RAM**: 8 GB or more
- **Storage**: 100 GB SSD
- **Network**: 1 Gbps bandwidth
- **Load Balancer**: Nginx or HAProxy
- **CDN**: CloudFlare or similar

## 📦 Software Dependencies

### Core Components
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **PHP**: 8.1 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Node.js**: 18.x LTS
- **Package Managers**: Composer, npm/yarn

### PHP Extensions Required
```bash
# Required PHP extensions
php8.1-cli
php8.1-fpm
php8.1-mysql
php8.1-xml
php8.1-curl
php8.1-mbstring
php8.1-zip
php8.1-bcmath
php8.1-json
php8.1-gd
php8.1-dom
php8.1-opcache
```

### System Services
- **Process Manager**: Supervisor
- **Cron**: For scheduled tasks
- **Fail2ban**: For security (recommended)
- **Firewall**: UFW or iptables

## 🗄️ Database Requirements

### MySQL Configuration
```sql
-- Minimum database settings
[mysqld]
innodb_buffer_pool_size = 1G
max_connections = 200
query_cache_size = 32M
tmp_table_size = 64M
max_heap_table_size = 64M
```

### Database Schema
```sql
-- Primary database
CREATE DATABASE anipm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Required tables
CREATE TABLE status_checks (
    id VARCHAR(255) PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Performance indexes
CREATE INDEX idx_status_checks_timestamp ON status_checks(timestamp);
CREATE INDEX idx_status_checks_client ON status_checks(client_name);
CREATE INDEX idx_status_checks_created ON status_checks(created_at);
```

## 🔌 Network Configuration

### Port Requirements
- **HTTP**: 80 (public)
- **HTTPS**: 443 (public)
- **MySQL**: 3306 (internal only)
- **Backend API**: 8001 (internal only)
- **Frontend Dev**: 3000 (internal only)

### Firewall Rules
```bash
# Ubuntu/Debian (UFW)
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw deny 3306/tcp  # Block external MySQL access
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### DNS Configuration
```dns
# Required DNS records
yourdomain.com.     A     YOUR_SERVER_IP
www.yourdomain.com. A     YOUR_SERVER_IP
```

## 📁 File Structure Requirements

### Directory Permissions
```bash
# Application root
/var/www/anipm/                 755 (www-data:www-data)

# Backend specific
/var/www/anipm/backend/storage/ 775 (www-data:www-data)
/var/www/anipm/backend/bootstrap/cache/ 775 (www-data:www-data)

# Frontend documents
/var/www/anipm/frontend/src/documents/ 644 (www-data:www-data)

# Log files
/var/log/supervisor/            644 (root:root)
/var/log/nginx/                 644 (www-data:www-data)
```

### Required Files
```
/var/www/anipm/
├── backend/
│   ├── .env                    # Environment configuration
│   ├── composer.json           # PHP dependencies
│   ├── artisan                 # Laravel CLI
│   └── app/                    # Application code
├── frontend/
│   ├── .env                    # Environment configuration
│   ├── package.json            # Node.js dependencies
│   ├── build/                  # Production build
│   └── src/documents/          # Document files
└── public/                     # Web root
```

## 🔧 Application Configuration

### Environment Variables

#### Backend (.env)
```env
# Application
APP_NAME=ANIPM
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=UTC

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=anipm_db
DB_USERNAME=anipm_user
DB_PASSWORD=your_secure_password

# Caching
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=single
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# Security
BCRYPT_ROUNDS=12
```

#### Frontend (.env)
```env
# API Configuration
REACT_APP_BACKEND_URL=https://yourdomain.com

# Development
WDS_SOCKET_PORT=443
GENERATE_SOURCEMAP=false

# Build optimization
REACT_APP_ENV=production
```

### Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/anipm/public;
    index index.html index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Client upload size
    client_max_body_size 100M;

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
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
    }

    # Document downloads
    location /documents {
        alias /var/www/anipm/frontend/src/documents;
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
    }

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ \.php$ {
        deny all;
    }

    # Security - block access to backend files
    location ~ ^/(backend|storage|bootstrap|vendor|composer|artisan) {
        deny all;
    }
}
```

#### Apache Configuration (Alternative)
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/anipm/public
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    # Frontend routes
    <Directory /var/www/anipm/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # React Router support
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Backend API proxy
    ProxyPreserveHost On
    ProxyRequests Off
    ProxyPass /api/ http://127.0.0.1:8001/api/
    ProxyPassReverse /api/ http://127.0.0.1:8001/api/
    
    # Document downloads
    Alias /documents /var/www/anipm/frontend/src/documents
    <Directory /var/www/anipm/frontend/src/documents>
        Options -Indexes
        AllowOverride None
        Require all granted
        Header set Cache-Control "public, max-age=31536000"
    </Directory>
</VirtualHost>
```

## 🔄 Process Management

### Supervisor Configuration
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
environment=PATH="/usr/bin:/usr/local/bin"
```

### Systemd Services (Alternative)
```ini
# /etc/systemd/system/anipm-backend.service
[Unit]
Description=ANIPM Backend Service
After=mysql.service
Wants=mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/anipm/backend
Environment=PATH=/usr/bin:/usr/local/bin
ExecStart=/usr/bin/php /var/www/anipm/backend/artisan serve --host=0.0.0.0 --port=8001
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

## 🔐 Security Requirements

### SSL/TLS Configuration
```nginx
# SSL configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-CHACHA20-POLY1305;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 1d;
ssl_session_tickets off;
ssl_stapling on;
ssl_stapling_verify on;
```

### File Permissions Security
```bash
# Set secure permissions
find /var/www/anipm -type f -exec chmod 644 {} \;
find /var/www/anipm -type d -exec chmod 755 {} \;
chmod 775 /var/www/anipm/backend/storage
chmod 775 /var/www/anipm/backend/bootstrap/cache
chmod 600 /var/www/anipm/backend/.env
chmod 600 /var/www/anipm/frontend/.env
```

## 📊 Performance Requirements

### Expected Load
- **Concurrent Users**: 100-500
- **Page Views**: 10,000-50,000 per day
- **API Requests**: 100,000-500,000 per day
- **Document Downloads**: 1,000-5,000 per day

### Performance Benchmarks
- **Page Load Time**: < 3 seconds
- **API Response Time**: < 500ms
- **Database Query Time**: < 100ms
- **Document Download Time**: < 5 seconds

### Optimization Settings
```php
# PHP-FPM optimization
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000
```

```mysql
# MySQL optimization
innodb_buffer_pool_size = 2G
query_cache_size = 64M
max_connections = 500
thread_cache_size = 16
table_open_cache = 2000
```

## 🔍 Monitoring Requirements

### Health Check Endpoints
- `GET /` - Frontend health check
- `GET /api/` - Backend health check
- `GET /api/status` - Database connectivity check

### Log Files to Monitor
- `/var/log/nginx/access.log` - Web server access
- `/var/log/nginx/error.log` - Web server errors
- `/var/log/supervisor/anipm-backend.log` - Backend application
- `/var/log/mysql/error.log` - Database errors
- `/var/log/syslog` - System logs

### Metrics to Track
- **Response Times**: API and page load times
- **Error Rates**: 4xx and 5xx HTTP errors
- **Resource Usage**: CPU, memory, disk usage
- **Database Performance**: Query times and connection counts

## 🔧 Maintenance Requirements

### Regular Tasks
- **Daily**: Check service status, review error logs
- **Weekly**: Database backups, security updates
- **Monthly**: Performance analysis, capacity planning
- **Quarterly**: Full system updates, security audit

### Backup Strategy
- **Database**: Daily automated backups
- **Files**: Weekly full backups
- **Configuration**: Version controlled
- **Retention**: 30 days for daily, 12 months for weekly

## 🚀 Deployment Requirements

### CI/CD Pipeline (Optional)
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to server
        run: |
          ssh user@server "cd /var/www/anipm && git pull"
          ssh user@server "cd /var/www/anipm/backend && composer install --no-dev"
          ssh user@server "cd /var/www/anipm/frontend && npm install && npm run build"
          ssh user@server "sudo supervisorctl restart anipm-backend"
```

### Rollback Strategy
- **Database**: Maintain database dumps before updates
- **Code**: Git-based rollback to previous version
- **Configuration**: Backup configuration files
- **Services**: Quick service restart procedures

This technical requirements document provides comprehensive specifications for hosting the ANIPM website on a Linux VPS server with proper security, performance, and maintenance considerations.