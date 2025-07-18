# Car Rental ERP - Nginx Production Deployment Guide

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Server Preparation](#server-preparation)
4. [Repository Deployment](#repository-deployment)
5. [Backend Deployment](#backend-deployment)
6. [Frontend Build and Deployment](#frontend-build-and-deployment)
7. [Nginx Configuration](#nginx-configuration)
8. [SSL/TLS Configuration](#ssltls-configuration)
9. [Process Management](#process-management)
10. [Security Hardening](#security-hardening)
11. [Monitoring and Logging](#monitoring-and-logging)
12. [Backup and Recovery](#backup-and-recovery)
13. [Troubleshooting](#troubleshooting)
14. [Maintenance and Updates](#maintenance-and-updates)

## Overview

This comprehensive guide provides step-by-step instructions for deploying the Car Rental ERP system to a production server using nginx as the web server and reverse proxy. The deployment architecture includes a React frontend served by nginx, a Flask backend running as a WSGI application, and nginx handling SSL termination, static file serving, and load balancing.

### Deployment Architecture

The production deployment follows a modern web application architecture pattern where nginx serves as the entry point for all HTTP requests. Static files from the React frontend are served directly by nginx for optimal performance, while API requests are proxied to the Flask backend application running on a separate port. This configuration provides excellent performance, security, and scalability characteristics suitable for enterprise deployment.

The system utilizes a reverse proxy configuration where nginx handles SSL termination, compression, caching, and security headers, while the Flask application focuses solely on business logic and API responses. This separation of concerns allows for better resource utilization and easier maintenance of the production environment.

## Prerequisites

### System Requirements

**Operating System**: Ubuntu 22.04 LTS (recommended) or similar Linux distribution
**Memory**: Minimum 4GB RAM (8GB recommended for production)
**Storage**: Minimum 20GB available disk space
**Network**: Static IP address or domain name configured
**SSL Certificate**: Valid SSL certificate for HTTPS (Let's Encrypt recommended)

### Software Dependencies

**Nginx**: Version 1.18 or higher (already installed on your server)
**Python**: Version 3.11 or higher
**Node.js**: Version 20.18.0 or higher
**Git**: For repository cloning and updates
**Supervisor**: For process management (recommended)
**UFW**: For firewall configuration

### Access Requirements

**Root or sudo access**: Required for system configuration
**Domain name**: Configured to point to your server IP
**SSH access**: For remote server management
**GitHub access**: For repository cloning

## Server Preparation

### System Updates and Security

Begin by ensuring your server is fully updated and secured. This foundational step is critical for maintaining a secure production environment and ensuring compatibility with all required software packages.

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common

# Configure automatic security updates
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

### User Account Setup

Create a dedicated user account for running the application. This follows security best practices by avoiding running applications as root and provides better isolation of application processes.

```bash
# Create application user
sudo adduser --system --group --home /opt/carrental carrental

# Add user to necessary groups
sudo usermod -a -G www-data carrental

# Create application directories
sudo mkdir -p /opt/carrental/{app,logs,backups}
sudo chown -R carrental:carrental /opt/carrental
```

### Firewall Configuration

Configure the firewall to allow only necessary traffic while maintaining security. The configuration allows SSH for management, HTTP for Let's Encrypt certificate validation, and HTTPS for production traffic.

```bash
# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw --force enable

# Verify firewall status
sudo ufw status verbose
```

### Python Environment Setup

Install Python and create a virtual environment for the application. This ensures dependency isolation and prevents conflicts with system packages.

```bash
# Install Python and pip
sudo apt install -y python3.11 python3.11-venv python3.11-dev python3-pip

# Install build dependencies
sudo apt install -y build-essential libssl-dev libffi-dev python3.11-dev

# Verify Python installation
python3.11 --version
```

### Node.js Installation

Install Node.js using the NodeSource repository to ensure you have the latest LTS version required for building the React frontend.

```bash
# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install pnpm globally
sudo npm install -g pnpm

# Verify installations
node --version
npm --version
pnpm --version
```

## Repository Deployment

### Clone the Repository

Clone the Car Rental ERP repository to the server and set up the proper directory structure for production deployment.

```bash
# Switch to application user
sudo su - carrental

# Clone the repository
cd /opt/carrental
git clone https://github.com/daviderichammer/car-rental-erp-system.git app

# Set proper permissions
sudo chown -R carrental:carrental /opt/carrental/app
```

### Repository Structure Verification

Verify that the repository has been cloned correctly and contains all necessary components for deployment.

```bash
# Verify repository structure
cd /opt/carrental/app
ls -la

# Expected structure:
# backend/          - Flask backend application
# frontend/         - React frontend application
# *.md             - Documentation files
# README.md        - Project documentation
```

## Backend Deployment

### Virtual Environment Setup

Create and configure a Python virtual environment specifically for the backend application. This ensures dependency isolation and makes it easier to manage Python packages.

```bash
# Create virtual environment
cd /opt/carrental/app/backend
python3.11 -m venv venv

# Activate virtual environment
source venv/bin/activate

# Upgrade pip and install wheel
pip install --upgrade pip wheel setuptools
```

### Backend Dependencies Installation

Install all required Python packages for the Flask backend application. The requirements.txt file contains all necessary dependencies with specific versions for production stability.

```bash
# Install backend dependencies
pip install -r requirements.txt

# Install additional production dependencies
pip install gunicorn supervisor

# Verify installation
pip list
```

### Database Setup

Initialize the SQLite database and create the necessary tables for the application. In production, you may want to consider migrating to PostgreSQL for better performance and concurrent access.

```bash
# Create database directory
mkdir -p /opt/carrental/app/backend/src/database

# Initialize database (this will be done automatically on first run)
# The application will create the database and tables automatically
```

### Backend Configuration

Create production configuration files for the Flask application. This includes environment variables, database configuration, and security settings.

```bash
# Create environment configuration
cat > /opt/carrental/app/backend/.env << 'EOF'
FLASK_ENV=production
SECRET_KEY=your-super-secret-production-key-change-this
DATABASE_URL=sqlite:///opt/carrental/app/backend/src/database/app.db
CORS_ORIGINS=https://yourdomain.com
JWT_SECRET_KEY=your-jwt-secret-key-change-this
EOF

# Set proper permissions
chmod 600 /opt/carrental/app/backend/.env
```

### WSGI Configuration

Create a WSGI configuration file for running the Flask application with Gunicorn in production.

```bash
# Create WSGI entry point
cat > /opt/carrental/app/backend/wsgi.py << 'EOF'
#!/usr/bin/env python3
import os
import sys

# Add the application directory to Python path
sys.path.insert(0, os.path.dirname(__file__))

from src.main import app

if __name__ == "__main__":
    app.run()
EOF

# Make executable
chmod +x /opt/carrental/app/backend/wsgi.py
```

### Gunicorn Configuration

Create a Gunicorn configuration file optimized for production deployment with proper worker processes and performance settings.

```bash
# Create Gunicorn configuration
cat > /opt/carrental/app/backend/gunicorn.conf.py << 'EOF'
import multiprocessing

# Server socket
bind = "127.0.0.1:8000"
backlog = 2048

# Worker processes
workers = multiprocessing.cpu_count() * 2 + 1
worker_class = "sync"
worker_connections = 1000
timeout = 30
keepalive = 2

# Restart workers after this many requests, to help prevent memory leaks
max_requests = 1000
max_requests_jitter = 50

# Logging
accesslog = "/opt/carrental/logs/gunicorn_access.log"
errorlog = "/opt/carrental/logs/gunicorn_error.log"
loglevel = "info"

# Process naming
proc_name = "carrental_backend"

# Server mechanics
daemon = False
pidfile = "/opt/carrental/logs/gunicorn.pid"
user = "carrental"
group = "carrental"
tmp_upload_dir = None

# SSL (handled by nginx)
keyfile = None
certfile = None
EOF
```

## Frontend Build and Deployment

### Frontend Dependencies Installation

Install Node.js dependencies and build the React frontend for production deployment.

```bash
# Navigate to frontend directory
cd /opt/carrental/app/frontend

# Install dependencies
pnpm install

# Verify installation
pnpm list
```

### Production Build Configuration

Configure the frontend build process for production deployment, including API endpoint configuration and optimization settings.

```bash
# Update API configuration for production
cat > /opt/carrental/app/frontend/.env.production << 'EOF'
VITE_API_BASE_URL=https://yourdomain.com/api
VITE_APP_TITLE=Car Rental ERP
VITE_APP_VERSION=1.0.0
EOF
```

### Build Frontend Application

Build the React application for production deployment with optimizations enabled.

```bash
# Build the frontend application
pnpm run build

# Verify build output
ls -la dist/

# Expected output:
# index.html       - Main HTML file
# assets/          - Compiled CSS and JS files
# favicon.ico      - Application icon
```

### Frontend Deployment

Deploy the built frontend files to the nginx web root directory.

```bash
# Create nginx web root
sudo mkdir -p /var/www/carrental

# Copy built files
sudo cp -r /opt/carrental/app/frontend/dist/* /var/www/carrental/

# Set proper permissions
sudo chown -R www-data:www-data /var/www/carrental
sudo chmod -R 755 /var/www/carrental
```

## Nginx Configuration

### Main Nginx Configuration

Configure nginx with optimized settings for serving the Car Rental ERP application. This configuration includes performance optimizations, security headers, and proper caching policies.

```bash
# Backup existing nginx configuration
sudo cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup

# Create optimized nginx configuration
sudo tee /etc/nginx/nginx.conf > /dev/null << 'EOF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    # Basic Settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;
    
    # MIME Types
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # Logging Settings
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;
    
    # Gzip Settings
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
    
    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
    
    # Include site configurations
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF
```

### Site-Specific Configuration

Create a site-specific nginx configuration for the Car Rental ERP application with proper routing, caching, and security settings.

```bash
# Remove default site
sudo rm -f /etc/nginx/sites-enabled/default

# Create site configuration
sudo tee /etc/nginx/sites-available/carrental << 'EOF'
# Upstream backend servers
upstream carrental_backend {
    server 127.0.0.1:8000 fail_timeout=0;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    # SSL Configuration (will be configured with Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;
    
    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;
    
    # Security headers
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';" always;
    
    # Document root
    root /var/www/carrental;
    index index.html;
    
    # Client max body size
    client_max_body_size 10M;
    
    # Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    # API routes - proxy to backend
    location /api/ {
        # Rate limiting
        limit_req zone=api burst=20 nodelay;
        
        # Proxy settings
        proxy_pass http://carrental_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # Timeouts
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
        
        # Buffering
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        
        # No caching for API responses
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
        add_header Pragma "no-cache" always;
        add_header Expires "0" always;
    }
    
    # Login endpoint - additional rate limiting
    location /api/auth/login {
        limit_req zone=login burst=5 nodelay;
        
        proxy_pass http://carrental_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Static assets with long-term caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
        
        # CORS for fonts
        location ~* \.(woff|woff2|ttf|eot)$ {
            add_header Access-Control-Allow-Origin *;
        }
    }
    
    # HTML files with short-term caching
    location ~* \.html$ {
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # React Router - serve index.html for all routes
    location / {
        try_files $uri $uri/ /index.html;
        
        # Short-term caching for SPA
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Custom error pages
    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;
    
    location = /404.html {
        root /var/www/carrental;
        internal;
    }
    
    location = /50x.html {
        root /var/www/carrental;
        internal;
    }
}
EOF

# Enable the site
sudo ln -s /etc/nginx/sites-available/carrental /etc/nginx/sites-enabled/

# Test nginx configuration
sudo nginx -t
```

### Custom Error Pages

Create custom error pages that match the application's design and provide helpful information to users.

```bash
# Create custom 404 page
sudo tee /var/www/carrental/404.html > /dev/null << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Car Rental ERP</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; margin-bottom: 20px; }
        p { color: #666; margin-bottom: 30px; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="/" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>
EOF

# Create custom 50x page
sudo tee /var/www/carrental/50x.html > /dev/null << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable - Car Rental ERP</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc2626; margin-bottom: 20px; }
        p { color: #666; margin-bottom: 30px; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Service Temporarily Unavailable</h1>
        <p>We're experiencing technical difficulties. Please try again in a few minutes.</p>
        <a href="/" class="btn">Try Again</a>
    </div>
</body>
</html>
EOF
```

## SSL/TLS Configuration

### Let's Encrypt Installation

Install Certbot for automated SSL certificate management using Let's Encrypt. This provides free, automatically renewing SSL certificates for your domain.

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Stop nginx temporarily for certificate generation
sudo systemctl stop nginx
```

### SSL Certificate Generation

Generate SSL certificates for your domain using Let's Encrypt. Replace 'yourdomain.com' with your actual domain name.

```bash
# Generate SSL certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Update nginx configuration with your actual domain
sudo sed -i 's/yourdomain.com/your-actual-domain.com/g' /etc/nginx/sites-available/carrental

# Test nginx configuration
sudo nginx -t

# Start nginx
sudo systemctl start nginx
```

### SSL Certificate Auto-Renewal

Configure automatic renewal of SSL certificates to ensure continuous HTTPS operation.

```bash
# Test certificate renewal
sudo certbot renew --dry-run

# Add renewal cron job
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -

# Verify cron job
sudo crontab -l
```

## Process Management

### Systemd Service Configuration

Create systemd service files for managing the backend application as a system service with automatic startup and restart capabilities.

```bash
# Create systemd service file
sudo tee /etc/systemd/system/carrental-backend.service > /dev/null << 'EOF'
[Unit]
Description=Car Rental ERP Backend
After=network.target

[Service]
Type=exec
User=carrental
Group=carrental
WorkingDirectory=/opt/carrental/app/backend
Environment=PATH=/opt/carrental/app/backend/venv/bin
ExecStart=/opt/carrental/app/backend/venv/bin/gunicorn --config gunicorn.conf.py wsgi:app
ExecReload=/bin/kill -s HUP $MAINPID
Restart=always
RestartSec=10
KillMode=mixed
TimeoutStopSec=5
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/opt/carrental
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd and enable service
sudo systemctl daemon-reload
sudo systemctl enable carrental-backend.service

# Start the service
sudo systemctl start carrental-backend.service

# Check service status
sudo systemctl status carrental-backend.service
```

### Service Management Commands

Essential commands for managing the Car Rental ERP backend service in production.

```bash
# Start the service
sudo systemctl start carrental-backend

# Stop the service
sudo systemctl stop carrental-backend

# Restart the service
sudo systemctl restart carrental-backend

# Check service status
sudo systemctl status carrental-backend

# View service logs
sudo journalctl -u carrental-backend -f

# Enable automatic startup
sudo systemctl enable carrental-backend

# Disable automatic startup
sudo systemctl disable carrental-backend
```

## Security Hardening

### Nginx Security Configuration

Implement additional security measures to protect the application from common web vulnerabilities and attacks.

```bash
# Create additional security configuration
sudo tee /etc/nginx/conf.d/security.conf > /dev/null << 'EOF'
# Hide nginx version
server_tokens off;

# Prevent clickjacking
add_header X-Frame-Options SAMEORIGIN always;

# Prevent MIME type sniffing
add_header X-Content-Type-Options nosniff always;

# Enable XSS protection
add_header X-XSS-Protection "1; mode=block" always;

# Referrer policy
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# Content Security Policy
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';" always;

# Permissions policy
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

# Remove server signature
more_clear_headers Server;
EOF
```

### Fail2Ban Configuration

Install and configure Fail2Ban to protect against brute force attacks and automated scanning.

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create custom jail configuration
sudo tee /etc/fail2ban/jail.local > /dev/null << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
backend = systemd

[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log

[nginx-noscript]
enabled = true
port = http,https
logpath = /var/log/nginx/access.log
maxretry = 6

[nginx-badbots]
enabled = true
port = http,https
logpath = /var/log/nginx/access.log
maxretry = 2

[nginx-noproxy]
enabled = true
port = http,https
logpath = /var/log/nginx/access.log
maxretry = 2
EOF

# Start and enable Fail2Ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Check Fail2Ban status
sudo fail2ban-client status
```

### File Permissions and Ownership

Set proper file permissions and ownership to minimize security risks and ensure proper application operation.

```bash
# Set proper ownership
sudo chown -R carrental:carrental /opt/carrental
sudo chown -R www-data:www-data /var/www/carrental

# Set proper permissions for application files
sudo find /opt/carrental/app -type f -exec chmod 644 {} \;
sudo find /opt/carrental/app -type d -exec chmod 755 {} \;

# Set executable permissions for scripts
sudo chmod +x /opt/carrental/app/backend/wsgi.py
sudo chmod +x /opt/carrental/app/backend/venv/bin/*

# Secure sensitive files
sudo chmod 600 /opt/carrental/app/backend/.env
sudo chmod 600 /etc/nginx/sites-available/carrental

# Set proper permissions for web files
sudo find /var/www/carrental -type f -exec chmod 644 {} \;
sudo find /var/www/carrental -type d -exec chmod 755 {} \;
```

## Monitoring and Logging

### Log Configuration

Configure comprehensive logging for monitoring application performance, debugging issues, and security auditing.

```bash
# Create log directories
sudo mkdir -p /opt/carrental/logs
sudo chown carrental:carrental /opt/carrental/logs

# Configure log rotation for application logs
sudo tee /etc/logrotate.d/carrental > /dev/null << 'EOF'
/opt/carrental/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 carrental carrental
    postrotate
        systemctl reload carrental-backend
    endscript
}
EOF

# Configure nginx log rotation
sudo tee /etc/logrotate.d/nginx > /dev/null << 'EOF'
/var/log/nginx/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    prerotate
        if [ -d /etc/logrotate.d/httpd-prerotate ]; then \
            run-parts /etc/logrotate.d/httpd-prerotate; \
        fi \
    endscript
    postrotate
        invoke-rc.d nginx rotate >/dev/null 2>&1
    endscript
}
EOF
```

### System Monitoring

Set up basic system monitoring to track resource usage and application health.

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Create monitoring script
sudo tee /opt/carrental/scripts/monitor.sh > /dev/null << 'EOF'
#!/bin/bash

# System monitoring script for Car Rental ERP
LOG_FILE="/opt/carrental/logs/system_monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Check system resources
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | awk -F'%' '{print $1}')
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.2f", $3/$2 * 100.0)}')
DISK_USAGE=$(df -h / | awk 'NR==2{printf "%s", $5}')

# Check service status
BACKEND_STATUS=$(systemctl is-active carrental-backend)
NGINX_STATUS=$(systemctl is-active nginx)

# Log system status
echo "[$DATE] CPU: ${CPU_USAGE}%, Memory: ${MEMORY_USAGE}%, Disk: ${DISK_USAGE}, Backend: ${BACKEND_STATUS}, Nginx: ${NGINX_STATUS}" >> $LOG_FILE

# Check if services are running and restart if needed
if [ "$BACKEND_STATUS" != "active" ]; then
    echo "[$DATE] Backend service is down, attempting restart..." >> $LOG_FILE
    systemctl restart carrental-backend
fi

if [ "$NGINX_STATUS" != "active" ]; then
    echo "[$DATE] Nginx service is down, attempting restart..." >> $LOG_FILE
    systemctl restart nginx
fi
EOF

# Make script executable
sudo chmod +x /opt/carrental/scripts/monitor.sh

# Add to crontab for regular monitoring
echo "*/5 * * * * /opt/carrental/scripts/monitor.sh" | sudo crontab -
```

### Health Check Endpoint

The nginx configuration includes a health check endpoint at `/health` that returns a simple status message. This can be used by monitoring systems to verify that the web server is responding correctly.

```bash
# Test health check endpoint
curl -I http://localhost/health

# Expected response:
# HTTP/1.1 200 OK
# Content-Type: text/plain
```

## Backup and Recovery

### Database Backup

Implement automated database backup procedures to protect against data loss and enable point-in-time recovery.

```bash
# Create backup directory
sudo mkdir -p /opt/carrental/backups/database
sudo chown carrental:carrental /opt/carrental/backups/database

# Create database backup script
sudo tee /opt/carrental/scripts/backup_database.sh > /dev/null << 'EOF'
#!/bin/bash

# Database backup script for Car Rental ERP
BACKUP_DIR="/opt/carrental/backups/database"
DB_FILE="/opt/carrental/app/backend/src/database/app.db"
DATE=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="$BACKUP_DIR/carrental_db_$DATE.db"

# Create backup
if [ -f "$DB_FILE" ]; then
    cp "$DB_FILE" "$BACKUP_FILE"
    gzip "$BACKUP_FILE"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Database backup created: ${BACKUP_FILE}.gz"
    
    # Remove backups older than 30 days
    find "$BACKUP_DIR" -name "*.gz" -mtime +30 -delete
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Old backups cleaned up"
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - ERROR: Database file not found: $DB_FILE"
fi
EOF

# Make script executable
sudo chmod +x /opt/carrental/scripts/backup_database.sh

# Add to crontab for daily backups
echo "0 2 * * * /opt/carrental/scripts/backup_database.sh >> /opt/carrental/logs/backup.log 2>&1" | sudo crontab -
```

### Application Backup

Create comprehensive backup procedures for the entire application, including code, configuration, and data.

```bash
# Create application backup script
sudo tee /opt/carrental/scripts/backup_application.sh > /dev/null << 'EOF'
#!/bin/bash

# Application backup script for Car Rental ERP
BACKUP_DIR="/opt/carrental/backups/application"
APP_DIR="/opt/carrental/app"
DATE=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="$BACKUP_DIR/carrental_app_$DATE.tar.gz"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Create application backup (excluding node_modules and venv)
tar -czf "$BACKUP_FILE" \
    --exclude="$APP_DIR/backend/venv" \
    --exclude="$APP_DIR/frontend/node_modules" \
    --exclude="$APP_DIR/frontend/dist" \
    --exclude="$APP_DIR/.git" \
    -C /opt/carrental app

echo "$(date '+%Y-%m-%d %H:%M:%S') - Application backup created: $BACKUP_FILE"

# Remove backups older than 7 days
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +7 -delete
echo "$(date '+%Y-%m-%d %H:%M:%S') - Old application backups cleaned up"
EOF

# Make script executable
sudo chmod +x /opt/carrental/scripts/backup_application.sh

# Add to crontab for weekly backups
echo "0 3 * * 0 /opt/carrental/scripts/backup_application.sh >> /opt/carrental/logs/backup.log 2>&1" | sudo crontab -
```

### Recovery Procedures

Document the procedures for recovering from various failure scenarios.

```bash
# Create recovery documentation
sudo tee /opt/carrental/docs/recovery_procedures.md > /dev/null << 'EOF'
# Recovery Procedures

## Database Recovery

### Restore from backup:
1. Stop the backend service: `sudo systemctl stop carrental-backend`
2. Locate the backup file: `ls -la /opt/carrental/backups/database/`
3. Extract the backup: `gunzip /opt/carrental/backups/database/carrental_db_YYYYMMDD_HHMMSS.db.gz`
4. Replace the current database: `cp /opt/carrental/backups/database/carrental_db_YYYYMMDD_HHMMSS.db /opt/carrental/app/backend/src/database/app.db`
5. Set proper permissions: `chown carrental:carrental /opt/carrental/app/backend/src/database/app.db`
6. Start the backend service: `sudo systemctl start carrental-backend`

## Application Recovery

### Restore from backup:
1. Stop services: `sudo systemctl stop carrental-backend nginx`
2. Extract backup: `tar -xzf /opt/carrental/backups/application/carrental_app_YYYYMMDD_HHMMSS.tar.gz -C /opt/carrental/`
3. Reinstall dependencies: `cd /opt/carrental/app/backend && source venv/bin/activate && pip install -r requirements.txt`
4. Rebuild frontend: `cd /opt/carrental/app/frontend && pnpm install && pnpm run build`
5. Deploy frontend: `sudo cp -r /opt/carrental/app/frontend/dist/* /var/www/carrental/`
6. Start services: `sudo systemctl start carrental-backend nginx`

## Complete System Recovery

### From scratch deployment:
1. Follow the deployment guide from the beginning
2. Restore database from backup after initial setup
3. Verify all services are running correctly
EOF
```

## Troubleshooting

### Common Issues and Solutions

This section covers the most common issues you may encounter during deployment and operation, along with their solutions.

#### Backend Service Issues

**Issue**: Backend service fails to start
```bash
# Check service status and logs
sudo systemctl status carrental-backend
sudo journalctl -u carrental-backend -n 50

# Common solutions:
# 1. Check Python virtual environment
cd /opt/carrental/app/backend
source venv/bin/activate
python -c "import flask; print('Flask OK')"

# 2. Check database permissions
ls -la /opt/carrental/app/backend/src/database/
sudo chown carrental:carrental /opt/carrental/app/backend/src/database/app.db

# 3. Check port availability
sudo netstat -tlnp | grep :8000
```

**Issue**: Database connection errors
```bash
# Check database file exists and is readable
ls -la /opt/carrental/app/backend/src/database/app.db

# Recreate database if corrupted
cd /opt/carrental/app/backend
source venv/bin/activate
python -c "from src.main import app, db; app.app_context().push(); db.create_all()"
```

#### Nginx Configuration Issues

**Issue**: Nginx fails to start or reload
```bash
# Test nginx configuration
sudo nginx -t

# Check nginx error logs
sudo tail -f /var/log/nginx/error.log

# Common fixes:
# 1. Fix syntax errors in configuration
sudo nano /etc/nginx/sites-available/carrental

# 2. Check SSL certificate paths
sudo ls -la /etc/letsencrypt/live/yourdomain.com/

# 3. Verify upstream backend is running
curl -I http://127.0.0.1:8000
```

**Issue**: 502 Bad Gateway errors
```bash
# Check if backend is running
sudo systemctl status carrental-backend

# Check backend logs
sudo journalctl -u carrental-backend -f

# Test backend directly
curl -I http://127.0.0.1:8000

# Restart services in order
sudo systemctl restart carrental-backend
sudo systemctl reload nginx
```

#### SSL Certificate Issues

**Issue**: SSL certificate errors or expiration
```bash
# Check certificate status
sudo certbot certificates

# Renew certificates manually
sudo certbot renew

# Test certificate renewal
sudo certbot renew --dry-run

# Check certificate expiration
openssl x509 -in /etc/letsencrypt/live/yourdomain.com/cert.pem -text -noout | grep "Not After"
```

#### Performance Issues

**Issue**: Slow response times
```bash
# Check system resources
htop
iotop
df -h

# Check nginx access logs for slow requests
sudo tail -f /var/log/nginx/access.log

# Check backend performance
sudo journalctl -u carrental-backend -f

# Optimize Gunicorn workers
sudo nano /opt/carrental/app/backend/gunicorn.conf.py
# Adjust workers = multiprocessing.cpu_count() * 2 + 1
```

#### Frontend Issues

**Issue**: Frontend not loading or showing errors
```bash
# Check nginx is serving frontend files
ls -la /var/www/carrental/

# Check nginx access logs
sudo tail -f /var/log/nginx/access.log

# Rebuild and redeploy frontend
cd /opt/carrental/app/frontend
pnpm run build
sudo cp -r dist/* /var/www/carrental/
sudo systemctl reload nginx
```

### Diagnostic Commands

Essential commands for diagnosing issues with the Car Rental ERP deployment.

```bash
# System status overview
sudo systemctl status carrental-backend nginx
sudo netstat -tlnp | grep -E ':(80|443|8000)'
sudo ps aux | grep -E '(nginx|gunicorn)'

# Log monitoring
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
sudo journalctl -u carrental-backend -f
sudo tail -f /opt/carrental/logs/gunicorn_error.log

# Resource monitoring
htop
df -h
free -h
iostat 1

# Network connectivity
curl -I http://localhost/health
curl -I https://yourdomain.com/health
curl -I http://127.0.0.1:8000

# SSL certificate check
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com
```

## Maintenance and Updates

### Regular Maintenance Tasks

Establish a regular maintenance schedule to ensure optimal performance and security of your Car Rental ERP deployment.

#### Daily Tasks
- Monitor system logs for errors or unusual activity
- Check service status and resource usage
- Verify backup completion
- Review security logs and fail2ban status

#### Weekly Tasks
- Update system packages: `sudo apt update && sudo apt upgrade`
- Review and rotate log files
- Check SSL certificate expiration dates
- Analyze performance metrics and optimize if needed

#### Monthly Tasks
- Update application dependencies
- Review and update security configurations
- Test backup and recovery procedures
- Perform security audit and vulnerability assessment

### Application Updates

When updating the Car Rental ERP application, follow these procedures to ensure smooth deployment with minimal downtime.

```bash
# Create update script
sudo tee /opt/carrental/scripts/update_application.sh > /dev/null << 'EOF'
#!/bin/bash

# Application update script for Car Rental ERP
APP_DIR="/opt/carrental/app"
BACKUP_DIR="/opt/carrental/backups/updates"
DATE=$(date '+%Y%m%d_%H%M%S')

echo "Starting application update at $(date)"

# Create backup before update
mkdir -p "$BACKUP_DIR"
echo "Creating backup..."
/opt/carrental/scripts/backup_application.sh

# Stop services
echo "Stopping services..."
sudo systemctl stop carrental-backend

# Update code from repository
echo "Updating code..."
cd "$APP_DIR"
git fetch origin
git pull origin main

# Update backend dependencies
echo "Updating backend dependencies..."
cd "$APP_DIR/backend"
source venv/bin/activate
pip install -r requirements.txt

# Update frontend dependencies and rebuild
echo "Updating frontend..."
cd "$APP_DIR/frontend"
pnpm install
pnpm run build

# Deploy updated frontend
echo "Deploying frontend..."
sudo cp -r dist/* /var/www/carrental/

# Start services
echo "Starting services..."
sudo systemctl start carrental-backend
sudo systemctl reload nginx

# Verify services are running
sleep 5
if systemctl is-active --quiet carrental-backend && systemctl is-active --quiet nginx; then
    echo "Update completed successfully at $(date)"
else
    echo "ERROR: Services failed to start after update"
    exit 1
fi
EOF

# Make script executable
sudo chmod +x /opt/carrental/scripts/update_application.sh
```

### Security Updates

Keep the system secure by regularly applying security updates and monitoring for vulnerabilities.

```bash
# Create security update script
sudo tee /opt/carrental/scripts/security_update.sh > /dev/null << 'EOF'
#!/bin/bash

# Security update script
echo "Starting security updates at $(date)"

# Update package lists
apt update

# Install security updates only
apt list --upgradable | grep -i security
unattended-upgrade

# Update SSL certificates
certbot renew --quiet

# Restart services if needed
if [ -f /var/run/reboot-required ]; then
    echo "System reboot required after security updates"
    # Schedule reboot during maintenance window
    # shutdown -r +60 "System will reboot in 1 hour for security updates"
fi

echo "Security updates completed at $(date)"
EOF

# Make script executable
sudo chmod +x /opt/carrental/scripts/security_update.sh

# Add to crontab for weekly security updates
echo "0 4 * * 1 /opt/carrental/scripts/security_update.sh >> /opt/carrental/logs/security_updates.log 2>&1" | sudo crontab -
```

### Performance Optimization

Monitor and optimize system performance to ensure the best user experience.

```bash
# Create performance monitoring script
sudo tee /opt/carrental/scripts/performance_check.sh > /dev/null << 'EOF'
#!/bin/bash

# Performance monitoring script
LOG_FILE="/opt/carrental/logs/performance.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Check response times
FRONTEND_TIME=$(curl -o /dev/null -s -w '%{time_total}' https://yourdomain.com/)
API_TIME=$(curl -o /dev/null -s -w '%{time_total}' https://yourdomain.com/api/health)

# Log performance metrics
echo "[$DATE] Frontend: ${FRONTEND_TIME}s, API: ${API_TIME}s" >> $LOG_FILE

# Alert if response times are too high
if (( $(echo "$FRONTEND_TIME > 3.0" | bc -l) )); then
    echo "[$DATE] WARNING: Frontend response time high: ${FRONTEND_TIME}s" >> $LOG_FILE
fi

if (( $(echo "$API_TIME > 1.0" | bc -l) )); then
    echo "[$DATE] WARNING: API response time high: ${API_TIME}s" >> $LOG_FILE
fi
EOF

# Make script executable
sudo chmod +x /opt/carrental/scripts/performance_check.sh

# Add to crontab for regular performance monitoring
echo "*/15 * * * * /opt/carrental/scripts/performance_check.sh" | sudo crontab -
```

This comprehensive nginx deployment guide provides everything needed to successfully deploy and maintain the Car Rental ERP system in a production environment. The configuration includes security best practices, performance optimizations, monitoring capabilities, and maintenance procedures to ensure reliable operation of your car rental business management system.

