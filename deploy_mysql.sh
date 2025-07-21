#!/bin/bash

# Car Rental ERP - MySQL Production Deployment Script
# This script deploys the Car Rental ERP system with MySQL backend
# Compatible with Ubuntu 22.04+ and existing nginx installations

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables with defaults
INSTALL_PATH="${INSTALL_PATH:-/opt/carrental}"
DOMAIN_NAME="${DOMAIN_NAME:-localhost}"
DB_NAME="${DB_NAME:-carrental}"
DB_USER="${DB_USER:-carrental}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 32)}"
APP_USER="${APP_USER:-carrental}"
NGINX_SITE_NAME="${NGINX_SITE_NAME:-carrental}"
ENABLE_SSL="${ENABLE_SSL:-true}"
SKIP_MYSQL_SETUP="${SKIP_MYSQL_SETUP:-false}"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

# Function to detect OS
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    else
        print_error "Cannot detect OS version"
        exit 1
    fi
    
    if [[ $OS != "Ubuntu" ]]; then
        print_warning "This script is designed for Ubuntu. Proceed with caution on $OS."
    fi
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if MySQL is running
    if ! systemctl is-active --quiet mysql; then
        print_error "MySQL is not running. Please start MySQL service first."
        print_status "Try: sudo systemctl start mysql"
        exit 1
    fi
    
    # Check if nginx is installed
    if ! command -v nginx &> /dev/null; then
        print_error "Nginx is not installed. Please install nginx first."
        print_status "Try: sudo apt install nginx"
        exit 1
    fi
    
    # Check MySQL version
    MYSQL_VERSION=$(mysql --version | grep -oP 'mysql\s+Ver\s+\K[0-9]+\.[0-9]+')
    print_status "Found MySQL version: $MYSQL_VERSION"
    
    if [[ $(echo "$MYSQL_VERSION < 8.0" | bc -l) -eq 1 ]]; then
        print_warning "MySQL version $MYSQL_VERSION detected. MySQL 8.0+ recommended."
    fi
    
    print_success "Prerequisites check completed"
}

# Function to create application user
create_app_user() {
    print_status "Creating application user: $APP_USER"
    
    if id "$APP_USER" &>/dev/null; then
        print_warning "User $APP_USER already exists"
    else
        adduser --system --group --home "$INSTALL_PATH" --shell /bin/bash "$APP_USER"
        usermod -a -G www-data "$APP_USER"
        print_success "Created user: $APP_USER"
    fi
    
    # Create directory structure
    mkdir -p "$INSTALL_PATH"/{app,logs,backups,scripts}
    chown -R "$APP_USER:$APP_USER" "$INSTALL_PATH"
    
    print_success "Directory structure created at $INSTALL_PATH"
}

# Function to install system dependencies
install_dependencies() {
    print_status "Installing system dependencies..."
    
    # Update package lists
    apt update
    
    # Install required packages
    apt install -y \
        python3.11 \
        python3.11-venv \
        python3.11-dev \
        python3-pip \
        nodejs \
        npm \
        build-essential \
        libssl-dev \
        libffi-dev \
        libmysqlclient-dev \
        pkg-config \
        curl \
        wget \
        git \
        unzip \
        supervisor \
        certbot \
        python3-certbot-nginx \
        fail2ban \
        ufw
    
    # Install pnpm globally
    npm install -g pnpm
    
    print_success "System dependencies installed"
}

# Function to setup MySQL database
setup_mysql() {
    if [[ "$SKIP_MYSQL_SETUP" == "true" ]]; then
        print_status "Skipping MySQL setup as requested"
        return
    fi
    
    print_status "Setting up MySQL database..."
    
    # Prompt for MySQL root password
    echo -n "Enter MySQL root password: "
    read -s MYSQL_ROOT_PASSWORD
    echo
    
    # Test MySQL connection
    if ! mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1;" &>/dev/null; then
        print_error "Cannot connect to MySQL with provided credentials"
        exit 1
    fi
    
    # Create database and user
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    print_success "MySQL database '$DB_NAME' and user '$DB_USER' created"
    print_status "Database password: $DB_PASSWORD"
    
    # Save database credentials
    cat > "$INSTALL_PATH/.db_credentials" << EOF
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASSWORD
EOF
    chmod 600 "$INSTALL_PATH/.db_credentials"
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/.db_credentials"
}

# Function to clone and setup application
setup_application() {
    print_status "Setting up application..."
    
    # Clone repository if not exists
    if [[ ! -d "$INSTALL_PATH/app" ]]; then
        sudo -u "$APP_USER" git clone https://github.com/daviderichammer/car-rental-erp-system.git "$INSTALL_PATH/app"
    else
        print_status "Application directory exists, pulling latest changes..."
        cd "$INSTALL_PATH/app"
        sudo -u "$APP_USER" git pull origin main
    fi
    
    print_success "Application code ready"
}

# Function to setup backend
setup_backend() {
    print_status "Setting up Flask backend..."
    
    cd "$INSTALL_PATH/app/backend"
    
    # Create virtual environment
    sudo -u "$APP_USER" python3.11 -m venv venv
    
    # Install Python dependencies
    sudo -u "$APP_USER" bash -c "source venv/bin/activate && pip install --upgrade pip wheel setuptools"
    sudo -u "$APP_USER" bash -c "source venv/bin/activate && pip install -r requirements.txt"
    sudo -u "$APP_USER" bash -c "source venv/bin/activate && pip install gunicorn PyMySQL cryptography"
    
    # Create environment configuration
    cat > "$INSTALL_PATH/app/backend/.env" << EOF
FLASK_ENV=production
SECRET_KEY=$(openssl rand -base64 32)
DATABASE_URL=mysql+pymysql://$DB_USER:$DB_PASSWORD@localhost/$DB_NAME
CORS_ORIGINS=https://$DOMAIN_NAME
JWT_SECRET_KEY=$(openssl rand -base64 32)
EOF
    
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/app/backend/.env"
    chmod 600 "$INSTALL_PATH/app/backend/.env"
    
    # Create WSGI entry point
    cat > "$INSTALL_PATH/app/backend/wsgi.py" << 'EOF'
#!/usr/bin/env python3
import os
import sys

# Add the application directory to Python path
sys.path.insert(0, os.path.dirname(__file__))

from src.main import app

if __name__ == "__main__":
    app.run()
EOF
    
    chmod +x "$INSTALL_PATH/app/backend/wsgi.py"
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/app/backend/wsgi.py"
    
    # Create Gunicorn configuration
    cat > "$INSTALL_PATH/app/backend/gunicorn.conf.py" << EOF
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

# Restart workers after this many requests
max_requests = 1000
max_requests_jitter = 50

# Logging
accesslog = "$INSTALL_PATH/logs/gunicorn_access.log"
errorlog = "$INSTALL_PATH/logs/gunicorn_error.log"
loglevel = "info"

# Process naming
proc_name = "carrental_backend"

# Server mechanics
daemon = False
pidfile = "$INSTALL_PATH/logs/gunicorn.pid"
user = "$APP_USER"
group = "$APP_USER"
tmp_upload_dir = None
EOF
    
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/app/backend/gunicorn.conf.py"
    
    print_success "Backend setup completed"
}

# Function to setup frontend
setup_frontend() {
    print_status "Setting up React frontend..."
    
    cd "$INSTALL_PATH/app/frontend"
    
    # Install dependencies
    sudo -u "$APP_USER" pnpm install
    
    # Create production environment file
    cat > "$INSTALL_PATH/app/frontend/.env.production" << EOF
VITE_API_BASE_URL=https://$DOMAIN_NAME/api
VITE_APP_TITLE=Car Rental ERP
VITE_APP_VERSION=1.0.0
EOF
    
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/app/frontend/.env.production"
    
    # Build frontend
    sudo -u "$APP_USER" pnpm run build
    
    # Deploy to nginx web root
    mkdir -p /var/www/"$NGINX_SITE_NAME"
    cp -r "$INSTALL_PATH/app/frontend/dist"/* /var/www/"$NGINX_SITE_NAME"/
    chown -R www-data:www-data /var/www/"$NGINX_SITE_NAME"
    
    print_success "Frontend setup completed"
}

# Function to create systemd service
create_systemd_service() {
    print_status "Creating systemd service..."
    
    cat > /etc/systemd/system/carrental-backend.service << EOF
[Unit]
Description=Car Rental ERP Backend
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=exec
User=$APP_USER
Group=$APP_USER
WorkingDirectory=$INSTALL_PATH/app/backend
Environment=PATH=$INSTALL_PATH/app/backend/venv/bin
ExecStart=$INSTALL_PATH/app/backend/venv/bin/gunicorn --config gunicorn.conf.py wsgi:app
ExecReload=/bin/kill -s HUP \$MAINPID
Restart=always
RestartSec=10
KillMode=mixed
TimeoutStopSec=5
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=$INSTALL_PATH
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable carrental-backend.service
    
    print_success "Systemd service created and enabled"
}

# Function to configure nginx
configure_nginx() {
    print_status "Configuring nginx..."
    
    # Remove default site
    rm -f /etc/nginx/sites-enabled/default
    
    # Create site configuration
    cat > /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
# Upstream backend servers
upstream carrental_backend {
    server 127.0.0.1:8000 fail_timeout=0;
}

# Redirect HTTP to HTTPS (if SSL enabled)
server {
    listen 80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    
EOF

    if [[ "$ENABLE_SSL" == "true" && "$DOMAIN_NAME" != "localhost" ]]; then
        cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << 'EOF'
    return 301 https://$server_name$request_uri;
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    
    # SSL Configuration (will be configured with Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;
    
    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;
EOF
    else
        cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << 'EOF'
    # Development/localhost configuration
EOF
    fi

    cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
    
    # Security headers
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Document root
    root /var/www/$NGINX_SITE_NAME;
    index index.html;
    
    # Client max body size
    client_max_body_size 10M;
    
    # API routes - proxy to backend
    location /api/ {
        proxy_pass http://carrental_backend;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Timeouts
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
    }
    
    # Static assets with caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # React Router - serve index.html for all routes
    location / {
        try_files \$uri \$uri/ /index.html;
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\\n";
        add_header Content-Type text/plain;
    }
}
EOF

    # Enable the site
    ln -sf /etc/nginx/sites-available/"$NGINX_SITE_NAME" /etc/nginx/sites-enabled/
    
    # Test nginx configuration
    if nginx -t; then
        print_success "Nginx configuration is valid"
    else
        print_error "Nginx configuration has errors"
        exit 1
    fi
}

# Function to setup SSL with Let's Encrypt
setup_ssl() {
    if [[ "$ENABLE_SSL" != "true" || "$DOMAIN_NAME" == "localhost" ]]; then
        print_status "Skipping SSL setup (disabled or localhost)"
        return
    fi
    
    print_status "Setting up SSL certificate with Let's Encrypt..."
    
    # Stop nginx temporarily
    systemctl stop nginx
    
    # Generate certificate
    if certbot certonly --standalone -d "$DOMAIN_NAME" -d "www.$DOMAIN_NAME" --non-interactive --agree-tos --email "admin@$DOMAIN_NAME"; then
        print_success "SSL certificate generated successfully"
        
        # Setup auto-renewal
        echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
        print_success "SSL auto-renewal configured"
    else
        print_warning "SSL certificate generation failed. Continuing without SSL."
        # Update nginx config to disable SSL
        sed -i 's/return 301 https:\/\/\$server_name\$request_uri;//' /etc/nginx/sites-available/"$NGINX_SITE_NAME"
        sed -i '/listen 443 ssl http2;/,/^}$/d' /etc/nginx/sites-available/"$NGINX_SITE_NAME"
    fi
}

# Function to create backup scripts
create_backup_scripts() {
    print_status "Creating backup scripts..."
    
    # Database backup script
    cat > "$INSTALL_PATH/scripts/backup_database.sh" << EOF
#!/bin/bash
BACKUP_DIR="$INSTALL_PATH/backups/database"
DATE=\$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="\$BACKUP_DIR/carrental_mysql_\$DATE.sql"

mkdir -p "\$BACKUP_DIR"

# Load database credentials
source "$INSTALL_PATH/.db_credentials"

# Create MySQL backup
mysqldump -u "\$DB_USER" -p"\$DB_PASSWORD" "$DB_NAME" > "\$BACKUP_FILE"
gzip "\$BACKUP_FILE"

echo "\$(date '+%Y-%m-%d %H:%M:%S') - MySQL backup created: \${BACKUP_FILE}.gz"

# Remove backups older than 30 days
find "\$BACKUP_DIR" -name "*.gz" -mtime +30 -delete
EOF
    
    chmod +x "$INSTALL_PATH/scripts/backup_database.sh"
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/scripts/backup_database.sh"
    
    # Application backup script
    cat > "$INSTALL_PATH/scripts/backup_application.sh" << EOF
#!/bin/bash
BACKUP_DIR="$INSTALL_PATH/backups/application"
DATE=\$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="\$BACKUP_DIR/carrental_app_\$DATE.tar.gz"

mkdir -p "\$BACKUP_DIR"

# Create application backup
tar -czf "\$BACKUP_FILE" \\
    --exclude="$INSTALL_PATH/app/backend/venv" \\
    --exclude="$INSTALL_PATH/app/frontend/node_modules" \\
    --exclude="$INSTALL_PATH/app/frontend/dist" \\
    --exclude="$INSTALL_PATH/app/.git" \\
    -C "$INSTALL_PATH" app

echo "\$(date '+%Y-%m-%d %H:%M:%S') - Application backup created: \$BACKUP_FILE"

# Remove backups older than 7 days
find "\$BACKUP_DIR" -name "*.tar.gz" -mtime +7 -delete
EOF
    
    chmod +x "$INSTALL_PATH/scripts/backup_application.sh"
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/scripts/backup_application.sh"
    
    # Setup cron jobs for backups
    (crontab -l 2>/dev/null; echo "0 2 * * * $INSTALL_PATH/scripts/backup_database.sh >> $INSTALL_PATH/logs/backup.log 2>&1") | crontab -
    (crontab -l 2>/dev/null; echo "0 3 * * 0 $INSTALL_PATH/scripts/backup_application.sh >> $INSTALL_PATH/logs/backup.log 2>&1") | crontab -
    
    print_success "Backup scripts created and scheduled"
}

# Function to start services
start_services() {
    print_status "Starting services..."
    
    # Start backend service
    systemctl start carrental-backend.service
    
    # Start nginx
    systemctl start nginx
    
    # Check service status
    sleep 3
    if systemctl is-active --quiet carrental-backend && systemctl is-active --quiet nginx; then
        print_success "All services started successfully"
    else
        print_error "Some services failed to start"
        systemctl status carrental-backend --no-pager
        systemctl status nginx --no-pager
        exit 1
    fi
}

# Function to display deployment summary
display_summary() {
    print_success "=== Car Rental ERP Deployment Complete ==="
    echo
    print_status "Installation Details:"
    echo "  Installation Path: $INSTALL_PATH"
    echo "  Application User: $APP_USER"
    echo "  Database: MySQL ($DB_NAME)"
    echo "  Domain: $DOMAIN_NAME"
    echo
    print_status "Access Information:"
    if [[ "$ENABLE_SSL" == "true" && "$DOMAIN_NAME" != "localhost" ]]; then
        echo "  Frontend: https://$DOMAIN_NAME"
        echo "  API: https://$DOMAIN_NAME/api"
    else
        echo "  Frontend: http://$DOMAIN_NAME"
        echo "  API: http://$DOMAIN_NAME/api"
    fi
    echo "  Default Login: admin@carrental.com / admin123"
    echo
    print_status "Service Management:"
    echo "  Start Backend: sudo systemctl start carrental-backend"
    echo "  Stop Backend: sudo systemctl stop carrental-backend"
    echo "  View Logs: sudo journalctl -u carrental-backend -f"
    echo "  Restart Nginx: sudo systemctl restart nginx"
    echo
    print_status "Database Credentials:"
    echo "  Database: $DB_NAME"
    echo "  Username: $DB_USER"
    echo "  Password: $DB_PASSWORD"
    echo "  (Saved in: $INSTALL_PATH/.db_credentials)"
    echo
    print_status "Backup Scripts:"
    echo "  Database: $INSTALL_PATH/scripts/backup_database.sh"
    echo "  Application: $INSTALL_PATH/scripts/backup_application.sh"
    echo
    print_success "Deployment completed successfully!"
}

# Function to show usage
show_usage() {
    echo "Car Rental ERP - MySQL Deployment Script"
    echo
    echo "Usage: $0 [OPTIONS]"
    echo
    echo "Environment Variables:"
    echo "  INSTALL_PATH      Installation directory (default: /opt/carrental)"
    echo "  DOMAIN_NAME       Domain name (default: localhost)"
    echo "  DB_NAME           Database name (default: carrental)"
    echo "  DB_USER           Database user (default: carrental)"
    echo "  DB_PASSWORD       Database password (auto-generated if not set)"
    echo "  APP_USER          Application user (default: carrental)"
    echo "  NGINX_SITE_NAME   Nginx site name (default: carrental)"
    echo "  ENABLE_SSL        Enable SSL with Let's Encrypt (default: true)"
    echo "  SKIP_MYSQL_SETUP  Skip MySQL database setup (default: false)"
    echo
    echo "Examples:"
    echo "  # Basic deployment to /opt/carrental"
    echo "  sudo ./deploy_mysql.sh"
    echo
    echo "  # Custom installation path"
    echo "  sudo INSTALL_PATH=/home/myuser/carrental ./deploy_mysql.sh"
    echo
    echo "  # Custom domain with SSL"
    echo "  sudo DOMAIN_NAME=mycarrental.com ./deploy_mysql.sh"
    echo
    echo "  # Skip MySQL setup (if already configured)"
    echo "  sudo SKIP_MYSQL_SETUP=true ./deploy_mysql.sh"
    echo
}

# Main deployment function
main() {
    echo "=== Car Rental ERP - MySQL Deployment Script ==="
    echo
    
    # Show usage if requested
    if [[ "$1" == "--help" || "$1" == "-h" ]]; then
        show_usage
        exit 0
    fi
    
    # Check if running as root
    check_root
    
    # Detect OS
    detect_os
    
    # Check prerequisites
    check_prerequisites
    
    # Create application user and directories
    create_app_user
    
    # Install system dependencies
    install_dependencies
    
    # Setup MySQL database
    setup_mysql
    
    # Clone and setup application
    setup_application
    
    # Setup backend
    setup_backend
    
    # Setup frontend
    setup_frontend
    
    # Create systemd service
    create_systemd_service
    
    # Configure nginx
    configure_nginx
    
    # Setup SSL
    setup_ssl
    
    # Create backup scripts
    create_backup_scripts
    
    # Start services
    start_services
    
    # Display summary
    display_summary
}

# Run main function
main "$@"

