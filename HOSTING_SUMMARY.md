# ANIPM Website Hosting Summary

## 🎯 Executive Summary

The ANIPM (Agentia Nationala pentru Industria Prelucratoare si Mediu) website is a professional full-stack web application designed for the Moldovan national agency for processing industry and environment. This document provides a comprehensive overview for hosting companies to successfully deploy and maintain the application.

## 📋 Application Overview

### What is ANIPM?
- **Purpose**: Government agency website for processing industry and environment
- **Type**: Full-stack web application
- **Target Users**: Government officials, industry professionals, public users
- **Primary Functions**: Document management, news distribution, regulatory information

### Key Features
- **Document Management**: PDF and DOCX file preview and download
- **News Ticker**: Dynamic news content with image extraction
- **Regulatory Information**: Government legislation and regulatory documents
- **Status Monitoring**: System health and connectivity checks
- **Multi-language Support**: Romanian language interface

## 🏗️ Technical Architecture

### Technology Stack
```
Frontend: React 18.3.1 (Single Page Application)
Backend: PHP Laravel 10.x (REST API)
Database: MySQL 8.0+ (Migrated from MongoDB)
Web Server: Nginx (Reverse Proxy)
Process Manager: Supervisor
Container: Docker (Multi-stage build)
```

### Service Architecture
```
Internet → Nginx (Port 80/443) → React Frontend (Port 3000)
                                ↓
                         PHP Laravel API (Port 8001)
                                ↓
                         MySQL Database (Port 3306)
```

## 🚀 Deployment Options

### Option 1: Docker Deployment (Recommended)
**Advantages:**
- Simplified deployment process
- Consistent environment across different servers
- Built-in dependency management
- Easy scaling and updates

**Requirements:**
- Docker Engine 20.10+
- Docker Compose 2.0+
- 4GB RAM minimum
- 20GB storage

**Command:**
```bash
docker-compose up -d
```

### Option 2: Manual Deployment
**Advantages:**
- Full control over system configuration
- Better performance optimization
- Easier troubleshooting
- Lower resource usage

**Requirements:**
- Ubuntu 20.04+ or CentOS 8+
- PHP 8.1+, Node.js 18+, MySQL 8.0+
- Nginx, Supervisor, Composer
- 2GB RAM minimum, 10GB storage

## 📁 File Structure

```
/var/www/anipm/
├── backend/                    # PHP Laravel Backend
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   └── ApiController.php       # Main API controller
│   │   ├── Models/
│   │   │   └── StatusCheck.php         # Database model
│   │   └── Services/
│   │       └── ImageExtractorService.php # News image extraction
│   ├── routes/
│   │   └── api.php                     # API routes definition
│   ├── config/
│   │   └── database.php                # Database configuration
│   ├── composer.json                   # PHP dependencies
│   └── .env                           # Environment variables
├── frontend/                   # React Frontend
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   │   └── LegislationPage.js      # Document management
│   │   └── documents/                  # PDF/DOCX files (22 documents)
│   ├── package.json                    # Node.js dependencies
│   └── .env                           # Environment variables
├── public/                     # Web root (compiled frontend)
├── Dockerfile                  # Docker configuration
├── docker-compose.yml          # Docker services
├── nginx.conf                  # Nginx configuration
└── deployment/                 # Deployment scripts
    ├── DEPLOYMENT_GUIDE.md
    ├── DOCKER_DEPLOYMENT.md
    ├── TECHNICAL_REQUIREMENTS.md
    └── TROUBLESHOOTING_GUIDE.md
```

## 🔧 Configuration Requirements

### Environment Variables

#### Frontend Configuration
```env
REACT_APP_BACKEND_URL=https://yourdomain.com
WDS_SOCKET_PORT=443
```

#### Backend Configuration
```env
APP_NAME=ANIPM
APP_ENV=production
APP_URL=https://yourdomain.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=anipm_db
DB_USERNAME=anipm_user
DB_PASSWORD=your_secure_password
```

### Database Setup
```sql
CREATE DATABASE anipm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'anipm_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON anipm_db.* TO 'anipm_user'@'localhost';
```

## 🌐 API Endpoints

### Core Functionality
- `GET /api/` - API information page
- `GET /api/demo` - Interactive demo page
- `GET /api/status` - System health check

### Document Management
- `GET /api/download/{filename}` - Download documents
- Available documents: 22 PDF/DOCX files including:
  - Government legislation (Romanian)
  - Industry regulations
  - Ministry documents

### News & Content
- `GET /api/news-ticker` - Dynamic news content
- `POST /api/fetch-news-image` - Extract images from news URLs
- `POST /api/fetch-multiple-news-images` - Batch image extraction

### Database Operations
- `POST /api/status` - Create system status check
- `GET /api/status` - Retrieve all status checks
- `GET /api/status/{id}` - Get specific status check

## 🔐 Security Considerations

### SSL/TLS Requirements
- **Required**: SSL certificate for HTTPS
- **Recommended**: Let's Encrypt or commercial SSL
- **Configuration**: TLS 1.2+ with strong ciphers

### File Permissions
```bash
# Application files
sudo chown -R www-data:www-data /var/www/anipm/
sudo chmod -R 755 /var/www/anipm/

# Sensitive directories
sudo chmod 775 /var/www/anipm/backend/storage/
sudo chmod 600 /var/www/anipm/backend/.env
```

### Firewall Configuration
```bash
# Allow HTTP/HTTPS traffic
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# Block direct database access
sudo ufw deny 3306/tcp
```

## 📊 Performance Specifications

### Expected Load
- **Concurrent Users**: 100-500
- **Daily Page Views**: 10,000-50,000
- **API Requests**: 100,000-500,000 per day
- **Document Downloads**: 1,000-5,000 per day

### Performance Targets
- **Page Load Time**: < 3 seconds
- **API Response Time**: < 500ms
- **Database Query Time**: < 100ms
- **Document Download Time**: < 5 seconds

### Resource Requirements
- **CPU**: 2-4 cores (2.5GHz+)
- **RAM**: 2-4GB (minimum), 8GB (recommended)
- **Storage**: 20GB (minimum), 100GB (recommended)
- **Bandwidth**: 100Mbps (minimum), 1Gbps (recommended)

## 🔄 Backup Strategy

### Automated Backups
- **Database**: Daily automated dumps
- **Files**: Weekly full backups
- **Retention**: 30 days for daily, 12 months for weekly

### Backup Locations
- **Database**: `/var/backups/anipm/database/`
- **Files**: `/var/backups/anipm/files/`
- **Configuration**: Version controlled in Git

## 📈 Monitoring & Maintenance

### Health Checks
- **System**: CPU, memory, disk usage
- **Services**: Nginx, MySQL, PHP-FPM status
- **Application**: API response times, error rates
- **Database**: Connection counts, query performance

### Log Files
- **Web Server**: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- **Application**: `/var/log/supervisor/anipm-backend.log`
- **Database**: `/var/log/mysql/error.log`
- **System**: `/var/log/syslog`

### Maintenance Schedule
- **Daily**: Service health checks, error log review
- **Weekly**: Security updates, backup verification
- **Monthly**: Performance analysis, capacity planning
- **Quarterly**: Security audit, dependency updates

## 🚨 Support & Troubleshooting

### Common Issues
1. **Service Not Starting**: Check logs, install dependencies
2. **Database Connection**: Verify credentials, restart MySQL
3. **SSL Certificate**: Use Let's Encrypt or commercial SSL
4. **File Permissions**: Fix ownership and permissions
5. **Performance Issues**: Optimize PHP/MySQL configuration

### Emergency Contacts
- **Technical Issues**: Check troubleshooting guide
- **Security Issues**: Review security logs and firewall
- **Performance Issues**: Monitor resource usage

## 📋 Deployment Checklist

### Pre-deployment
- [ ] Server meets minimum requirements
- [ ] Domain name configured with DNS
- [ ] SSL certificate obtained
- [ ] Database credentials generated
- [ ] Firewall rules configured

### Deployment Process
- [ ] Upload application files
- [ ] Configure environment variables
- [ ] Install dependencies (PHP, Node.js, MySQL)
- [ ] Set up database and user
- [ ] Configure web server (Nginx)
- [ ] Set up process manager (Supervisor)
- [ ] Configure SSL certificate
- [ ] Set correct file permissions

### Post-deployment
- [ ] All services running correctly
- [ ] Website accessible via HTTPS
- [ ] API endpoints responding
- [ ] Database connectivity verified
- [ ] Document downloads working
- [ ] Monitoring configured
- [ ] Backup system active

## 💰 Cost Considerations

### Server Requirements
- **Minimum VPS**: 2 cores, 2GB RAM, 20GB SSD (~$10-20/month)
- **Recommended VPS**: 4 cores, 4GB RAM, 100GB SSD (~$20-50/month)
- **High-traffic VPS**: 8 cores, 8GB RAM, 200GB SSD (~$50-100/month)

### Additional Costs
- **Domain Registration**: $10-15/year
- **SSL Certificate**: Free (Let's Encrypt) or $50-200/year
- **Backup Storage**: $5-20/month
- **Monitoring Tools**: $10-50/month (optional)

## 🎯 Success Metrics

### Deployment Success
- [ ] Website loads within 3 seconds
- [ ] All API endpoints respond correctly
- [ ] Document downloads function properly
- [ ] Database operations work correctly
- [ ] SSL certificate valid and configured
- [ ] All services auto-restart on failure

### Operational Success
- [ ] 99.9% uptime achieved
- [ ] Average response time < 500ms
- [ ] Zero data loss in 30 days
- [ ] Successful daily backups
- [ ] No security incidents

## 📞 Next Steps

1. **Review Documentation**: Read all provided guides thoroughly
2. **Plan Deployment**: Choose Docker or manual deployment
3. **Prepare Environment**: Set up server with required specifications
4. **Execute Deployment**: Follow step-by-step deployment guide
5. **Test Thoroughly**: Verify all functionality works correctly
6. **Monitor Continuously**: Set up monitoring and alerting
7. **Schedule Maintenance**: Plan regular maintenance windows

## 📄 Additional Resources

### Documentation Files
- **DEPLOYMENT_GUIDE.md**: Complete deployment instructions
- **DOCKER_DEPLOYMENT.md**: Docker-specific deployment guide
- **TECHNICAL_REQUIREMENTS.md**: Detailed technical specifications
- **TROUBLESHOOTING_GUIDE.md**: Common issues and solutions

### Configuration Files
- **Dockerfile**: Docker container configuration
- **docker-compose.yml**: Multi-service Docker setup
- **nginx.conf**: Web server configuration
- **supervisord.conf**: Process management configuration

---

**This summary provides all essential information for successfully hosting the ANIPM website on a Linux VPS server. The application is production-ready with comprehensive documentation, security measures, and monitoring capabilities.**