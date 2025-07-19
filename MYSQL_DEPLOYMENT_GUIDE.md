# Car Rental ERP - MySQL Deployment Guide

## 🚀 Quick Start (Recommended)

### One-Command Deployment
```bash
# Clone repository and run deployment script
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system
sudo ./deploy_mysql.sh
```

That's it! The script will handle everything automatically.

## 📋 Prerequisites

### System Requirements
- **Ubuntu 22.04+** (or compatible Linux distribution)
- **MySQL 8.0+** (already running on your server ✅)
- **Nginx** (already installed on your server ✅)
- **Root/sudo access** for system configuration
- **Domain name** (optional, can use localhost for testing)

### Verify Prerequisites
```bash
# Check MySQL is running
sudo systemctl status mysql

# Check nginx is installed
nginx -v

# Check MySQL version
mysql --version
```

## 🛠️ Deployment Options

### Option 1: Default Installation (/opt/carrental)
```bash
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system
sudo ./deploy_mysql.sh
```

### Option 2: Custom Installation Path
```bash
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system

# Install to custom directory (e.g., /home/myuser/carrental)
sudo INSTALL_PATH=/home/myuser/carrental ./deploy_mysql.sh
```

### Option 3: Custom Domain with SSL
```bash
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system

# Deploy with your domain and automatic SSL
sudo DOMAIN_NAME=mycarrental.com ./deploy_mysql.sh
```

### Option 4: Advanced Configuration
```bash
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system

# Full customization
sudo INSTALL_PATH=/var/www/carrental \
     DOMAIN_NAME=carrental.example.com \
     DB_NAME=my_carrental \
     DB_USER=carrental_user \
     APP_USER=webapp \
     ./deploy_mysql.sh
```

## 🔧 Configuration Variables

The deployment script accepts these environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `INSTALL_PATH` | `/opt/carrental` | Where to install the application |
| `DOMAIN_NAME` | `localhost` | Your domain name |
| `DB_NAME` | `carrental` | MySQL database name |
| `DB_USER` | `carrental` | MySQL database user |
| `DB_PASSWORD` | *auto-generated* | MySQL database password |
| `APP_USER` | `carrental` | System user to run the application |
| `NGINX_SITE_NAME` | `carrental` | Nginx site configuration name |
| `ENABLE_SSL` | `true` | Enable Let's Encrypt SSL |
| `SKIP_MYSQL_SETUP` | `false` | Skip database creation (if already done) |

## 📁 Installation Path Options

### Why You Don't Need /opt

The `/opt` directory is just a convention for optional software. You can install anywhere:

#### Popular Installation Paths:
```bash
# Traditional system location
INSTALL_PATH=/opt/carrental

# User home directory
INSTALL_PATH=/home/myuser/carrental

# Web server directory
INSTALL_PATH=/var/www/carrental

# Custom business directory
INSTALL_PATH=/srv/carrental

# Development directory
INSTALL_PATH=/home/developer/projects/carrental
```

#### Path Considerations:
- **Permissions**: Ensure the path is writable
- **Backups**: Choose a path included in your backup strategy
- **Security**: Avoid world-writable directories
- **Disk Space**: Ensure adequate space for logs and backups

## 🔐 MySQL Database Setup

### Automatic Setup (Recommended)
The script will prompt for your MySQL root password and automatically:
- Create the database
- Create a dedicated user
- Set proper permissions
- Generate secure passwords

### Manual Setup (If Preferred)
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE carrental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with secure password
CREATE USER 'carrental'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON carrental.* TO 'carrental'@'localhost';
FLUSH PRIVILEGES;
```

Then run deployment with:
```bash
sudo SKIP_MYSQL_SETUP=true DB_PASSWORD=your_secure_password ./deploy_mysql.sh
```

## 🌐 Domain and SSL Configuration

### Local Development (No SSL)
```bash
# For testing on localhost
sudo DOMAIN_NAME=localhost ENABLE_SSL=false ./deploy_mysql.sh
```

### Production with Custom Domain
```bash
# Automatic SSL with Let's Encrypt
sudo DOMAIN_NAME=mycarrental.com ./deploy_mysql.sh
```

### Multiple Domains
```bash
# Primary domain (SSL will cover both)
sudo DOMAIN_NAME=carrental.com ./deploy_mysql.sh
```

The script automatically configures:
- HTTP to HTTPS redirect
- Let's Encrypt SSL certificate
- Auto-renewal cron job
- Security headers

## 📊 What the Script Does

### 1. System Preparation
- Updates system packages
- Installs Python 3.11, Node.js, and dependencies
- Creates application user and directories
- Configures firewall (UFW)

### 2. Database Configuration
- Creates MySQL database and user
- Generates secure passwords
- Tests database connectivity
- Saves credentials securely

### 3. Application Setup
- Clones latest code from GitHub
- Creates Python virtual environment
- Installs all dependencies (including MySQL drivers)
- Builds React frontend for production

### 4. Service Configuration
- Creates systemd service for backend
- Configures Gunicorn WSGI server
- Sets up nginx reverse proxy
- Configures SSL with Let's Encrypt

### 5. Security & Monitoring
- Installs and configures Fail2Ban
- Sets up log rotation
- Creates backup scripts
- Configures monitoring cron jobs

### 6. Final Steps
- Starts all services
- Verifies deployment
- Displays access information

## 🔍 Deployment Verification

After deployment, verify everything is working:

### Check Services
```bash
# Check backend service
sudo systemctl status carrental-backend

# Check nginx
sudo systemctl status nginx

# Check MySQL
sudo systemctl status mysql
```

### Test Application
```bash
# Test health endpoint
curl http://your-domain.com/health

# Test API
curl http://your-domain.com/api/auth/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@carrental.com","password":"admin123"}'
```

### Access Web Interface
- **URL**: https://your-domain.com (or http://localhost)
- **Login**: admin@carrental.com
- **Password**: admin123

## 🔧 Post-Deployment Configuration

### Update Domain in Nginx (If Changed Later)
```bash
# Edit nginx configuration
sudo nano /etc/nginx/sites-available/carrental

# Replace domain name and reload
sudo nginx -t && sudo systemctl reload nginx
```

### Add SSL Certificate for New Domain
```bash
# Generate certificate for new domain
sudo certbot --nginx -d newdomain.com -d www.newdomain.com
```

### Update Database Connection
```bash
# Edit environment file
sudo nano /opt/carrental/app/backend/.env

# Update DATABASE_URL and restart service
sudo systemctl restart carrental-backend
```

## 📁 Directory Structure

After deployment, your installation will look like:

```
/opt/carrental/  (or your custom path)
├── app/                    # Application code
│   ├── backend/           # Flask backend
│   ├── frontend/          # React frontend
│   └── *.md              # Documentation
├── logs/                  # Application logs
├── backups/               # Automated backups
│   ├── database/         # MySQL dumps
│   └── application/      # Code backups
├── scripts/               # Maintenance scripts
└── .db_credentials       # Database credentials
```

## 🔄 Service Management

### Backend Service
```bash
# Start/stop/restart backend
sudo systemctl start carrental-backend
sudo systemctl stop carrental-backend
sudo systemctl restart carrental-backend

# View logs
sudo journalctl -u carrental-backend -f

# Check status
sudo systemctl status carrental-backend
```

### Nginx
```bash
# Restart nginx
sudo systemctl restart nginx

# Reload configuration
sudo systemctl reload nginx

# Test configuration
sudo nginx -t
```

### MySQL
```bash
# Connect to database
mysql -u carrental -p carrental

# Check database status
sudo systemctl status mysql
```

## 💾 Backup and Recovery

### Automated Backups
The script sets up automatic backups:
- **Database**: Daily at 2:00 AM
- **Application**: Weekly on Sundays at 3:00 AM

### Manual Backup
```bash
# Database backup
/opt/carrental/scripts/backup_database.sh

# Application backup
/opt/carrental/scripts/backup_application.sh
```

### Restore from Backup
```bash
# Restore database
mysql -u carrental -p carrental < backup_file.sql

# Restore application
tar -xzf backup_file.tar.gz -C /opt/carrental/
```

## 🔄 Updates and Maintenance

### Update Application
```bash
cd /opt/carrental/app
sudo -u carrental git pull origin main

# Restart services
sudo systemctl restart carrental-backend
sudo systemctl reload nginx
```

### Update System
```bash
# Update packages
sudo apt update && sudo apt upgrade

# Update SSL certificates
sudo certbot renew
```

## 🐛 Troubleshooting

### Common Issues

#### Backend Won't Start
```bash
# Check logs
sudo journalctl -u carrental-backend -n 50

# Check database connection
mysql -u carrental -p carrental

# Restart service
sudo systemctl restart carrental-backend
```

#### Nginx 502 Error
```bash
# Check if backend is running
curl http://127.0.0.1:8000

# Check nginx logs
sudo tail -f /var/log/nginx/error.log

# Restart both services
sudo systemctl restart carrental-backend
sudo systemctl reload nginx
```

#### SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificates
sudo certbot renew

# Test nginx configuration
sudo nginx -t
```

#### Database Connection Issues
```bash
# Test MySQL connection
mysql -u carrental -p

# Check database credentials
sudo cat /opt/carrental/.db_credentials

# Verify database exists
mysql -u root -p -e "SHOW DATABASES;"
```

### Log Locations
- **Backend**: `/opt/carrental/logs/gunicorn_*.log`
- **Nginx**: `/var/log/nginx/access.log` and `/var/log/nginx/error.log`
- **System**: `sudo journalctl -u carrental-backend`
- **MySQL**: `/var/log/mysql/error.log`

## 🎯 Production Checklist

Before going live, verify:

- [ ] Domain name points to your server
- [ ] SSL certificate is valid and auto-renewing
- [ ] Database backups are working
- [ ] All services start automatically on boot
- [ ] Firewall is properly configured
- [ ] Log rotation is set up
- [ ] Health checks are responding
- [ ] Default admin password is changed

## 🔐 Security Recommendations

### Change Default Credentials
```bash
# Access the application and change admin password
# Login: admin@carrental.com / admin123
# Go to Settings > Change Password
```

### Firewall Configuration
```bash
# Check firewall status
sudo ufw status

# Allow only necessary ports
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### Regular Updates
```bash
# Set up automatic security updates
sudo apt install unattended-upgrades
sudo dpkg-reconfigure unattended-upgrades
```

## 📞 Support and Resources

### Documentation Files
- `README.md` - Project overview
- `nginx_deployment_guide.md` - Detailed nginx configuration
- `user_manual.md` - User guide for all features
- `project_summary.md` - Technical specifications

### Useful Commands
```bash
# View deployment script help
./deploy_mysql.sh --help

# Check all services
sudo systemctl status carrental-backend nginx mysql

# View all logs
sudo journalctl -f
```

### Getting Help
1. Check the troubleshooting section above
2. Review log files for error messages
3. Verify all prerequisites are met
4. Test individual components (MySQL, nginx, backend)

## 🎉 Success!

After successful deployment, you'll have:

✅ **Production-ready Car Rental ERP system**
✅ **MySQL database with proper security**
✅ **Nginx reverse proxy with SSL**
✅ **Automated backups and monitoring**
✅ **Systemd service management**
✅ **Professional security configuration**

Your Car Rental ERP system is now ready to manage your business operations!

**Access your system**: https://your-domain.com
**Default login**: admin@carrental.com / admin123

Remember to change the default password after first login!

