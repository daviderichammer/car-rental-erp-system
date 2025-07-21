#!/bin/bash

# Car Rental ERP - Final MySQL Deployment Script
# This version handles frontend build failures and dist directory issues
# Compatible with Ubuntu 22.04+ and existing nginx installations

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables with defaults
INSTALL_PATH="${INSTALL_PATH:-/var/www/carrental}"
DOMAIN_NAME="${DOMAIN_NAME:-localhost}"
DB_NAME="${DB_NAME:-carrental}"
DB_USER="${DB_USER:-carrental}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 32)}"
APP_USER="${APP_USER:-carrental}"
NGINX_SITE_NAME="${NGINX_SITE_NAME:-carrental}"
ENABLE_SSL="${ENABLE_SSL:-true}"
SKIP_MYSQL_SETUP="${SKIP_MYSQL_SETUP:-false}"
GITHUB_REPO="${GITHUB_REPO:-https://github.com/daviderichammer/car-rental-erp-system.git}"

# Package manager preference (will be auto-detected)
PACKAGE_MANAGER=""

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

# Function to detect and install the best available package manager
detect_package_manager() {
    print_status "Detecting and setting up Node.js package manager..."
    
    # Check if pnpm is already available
    if command -v pnpm &> /dev/null; then
        PACKAGE_MANAGER="pnpm"
        print_success "Found existing pnpm installation"
        return 0
    fi
    
    # Try to install pnpm using multiple methods
    print_status "Attempting to install pnpm..."
    
    # Method 1: Official pnpm installer
    if curl -fsSL https://get.pnpm.io/install.sh | sh -; then
        # Reload environment
        export PATH="$HOME/.local/share/pnpm:$PATH"
        if command -v pnpm &> /dev/null; then
            PACKAGE_MANAGER="pnpm"
            print_success "Installed pnpm using official installer"
            return 0
        fi
    fi
    
    # Method 2: npm global install
    if npm install -g pnpm --unsafe-perm=true --allow-root 2>/dev/null; then
        if command -v pnpm &> /dev/null; then
            PACKAGE_MANAGER="pnpm"
            print_success "Installed pnpm using npm"
            return 0
        fi
    fi
    
    # Method 3: Try yarn
    print_status "PNPM installation failed, trying yarn..."
    if npm install -g yarn --unsafe-perm=true --allow-root 2>/dev/null; then
        if command -v yarn &> /dev/null; then
            PACKAGE_MANAGER="yarn"
            print_success "Installed and will use yarn"
            return 0
        fi
    fi
    
    # Fallback: Use npm (always available)
    print_warning "Could not install pnpm or yarn, falling back to npm"
    PACKAGE_MANAGER="npm"
    return 0
}

# Function to validate frontend build output
validate_frontend_build() {
    local frontend_dir="$1"
    local dist_dir="$frontend_dir/dist"
    
    print_status "Validating frontend build output..."
    
    # Check if dist directory exists
    if [[ ! -d "$dist_dir" ]]; then
        print_error "Build failed: dist directory not found at $dist_dir"
        return 1
    fi
    
    # Check if dist directory has content
    if [[ -z "$(ls -A "$dist_dir" 2>/dev/null)" ]]; then
        print_error "Build failed: dist directory is empty"
        return 1
    fi
    
    # Check for essential files
    if [[ ! -f "$dist_dir/index.html" ]]; then
        print_error "Build failed: index.html not found in dist directory"
        return 1
    fi
    
    # Check for assets directory
    if [[ ! -d "$dist_dir/assets" ]]; then
        print_warning "Assets directory not found, but continuing..."
    fi
    
    print_success "Frontend build validation passed"
    return 0
}

# Function to install frontend dependencies with comprehensive error handling
install_frontend_deps() {
    local frontend_dir="$1"
    cd "$frontend_dir"
    
    print_status "Installing frontend dependencies using $PACKAGE_MANAGER..."
    
    # Clean any existing installations first
    print_status "Cleaning previous installations..."
    sudo -u "$APP_USER" rm -rf node_modules package-lock.json yarn.lock pnpm-lock.yaml 2>/dev/null || true
    
    case "$PACKAGE_MANAGER" in
        "pnpm")
            print_status "Installing with pnpm..."
            if sudo -u "$APP_USER" pnpm install --frozen-lockfile=false; then
                print_success "Frontend dependencies installed with pnpm"
                return 0
            else
                print_warning "PNPM install failed, trying npm..."
                PACKAGE_MANAGER="npm"
            fi
            ;;& # Continue to next case
        "yarn")
            print_status "Installing with yarn..."
            if sudo -u "$APP_USER" yarn install --network-timeout 300000; then
                print_success "Frontend dependencies installed with yarn"
                return 0
            else
                print_warning "Yarn install failed, trying npm..."
                PACKAGE_MANAGER="npm"
            fi
            ;;& # Continue to next case
        "npm"|*)
            print_status "Installing with npm..."
            if sudo -u "$APP_USER" npm install --legacy-peer-deps --timeout=300000; then
                print_success "Frontend dependencies installed with npm"
                return 0
            else
                print_error "All package managers failed to install dependencies"
                print_status "Attempting to diagnose the issue..."
                
                # Show package.json for debugging
                print_status "Package.json contents:"
                cat package.json | head -20
                
                # Check Node.js version
                print_status "Node.js version: $(node --version)"
                print_status "NPM version: $(npm --version)"
                
                return 1
            fi
            ;;
    esac
}

# Function to build frontend with comprehensive error handling
build_frontend() {
    local frontend_dir="$1"
    cd "$frontend_dir"
    
    print_status "Building frontend using $PACKAGE_MANAGER..."
    
    # Create environment file for production
    print_status "Creating production environment file..."
    sudo -u "$APP_USER" cat > .env.production << EOF
VITE_API_BASE_URL=https://$DOMAIN_NAME/api
VITE_APP_TITLE=Car Rental ERP
VITE_APP_VERSION=1.0.0
NODE_ENV=production
EOF
    
    case "$PACKAGE_MANAGER" in
        "pnpm")
            print_status "Building with pnpm..."
            if sudo -u "$APP_USER" pnpm run build; then
                if validate_frontend_build "$frontend_dir"; then
                    print_success "Frontend built successfully with pnpm"
                    return 0
                fi
            fi
            print_warning "PNPM build failed or validation failed, trying npm..."
            PACKAGE_MANAGER="npm"
            ;;& # Continue to next case
        "yarn")
            print_status "Building with yarn..."
            if sudo -u "$APP_USER" yarn build; then
                if validate_frontend_build "$frontend_dir"; then
                    print_success "Frontend built successfully with yarn"
                    return 0
                fi
            fi
            print_warning "Yarn build failed or validation failed, trying npm..."
            PACKAGE_MANAGER="npm"
            ;;& # Continue to next case
        "npm"|*)
            print_status "Building with npm..."
            if sudo -u "$APP_USER" npm run build; then
                if validate_frontend_build "$frontend_dir"; then
                    print_success "Frontend built successfully with npm"
                    return 0
                fi
            fi
            
            # If npm build also failed, try alternative build methods
            print_warning "Standard build failed, trying alternative methods..."
            
            # Try direct vite build
            print_status "Attempting direct Vite build..."
            if sudo -u "$APP_USER" npx vite build; then
                if validate_frontend_build "$frontend_dir"; then
                    print_success "Frontend built successfully with direct Vite"
                    return 0
                fi
            fi
            
            # Try with different Node options
            print_status "Attempting build with increased memory..."
            if sudo -u "$APP_USER" NODE_OPTIONS="--max-old-space-size=4096" npm run build; then
                if validate_frontend_build "$frontend_dir"; then
                    print_success "Frontend built successfully with increased memory"
                    return 0
                fi
            fi
            
            print_error "All build methods failed"
            print_status "Attempting to create a minimal dist directory as fallback..."
            
            # Create minimal fallback
            create_fallback_frontend "$frontend_dir"
            return 1
            ;;
    esac
}

# Function to create a minimal fallback frontend
create_fallback_frontend() {
    local frontend_dir="$1"
    local dist_dir="$frontend_dir/dist"
    
    print_status "Creating minimal fallback frontend..."
    
    sudo -u "$APP_USER" mkdir -p "$dist_dir"
    
    # Create a basic index.html
    sudo -u "$APP_USER" cat > "$dist_dir/index.html" << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental ERP - Setup in Progress</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Car Rental ERP System</h1>
        <div class="spinner"></div>
        <p>System setup is in progress...</p>
        <p>The frontend build encountered issues, but the backend is running.</p>
        <p>Please check the deployment logs and rebuild the frontend manually.</p>
        <hr style="margin: 2rem 0; opacity: 0.3;">
        <p><strong>API Status:</strong> <span id="api-status">Checking...</span></p>
        <p><strong>Backend:</strong> <a href="/api/health" style="color: #fff;">Health Check</a></p>
    </div>
    
    <script>
        // Check API status
        fetch('/api/health')
            .then(response => response.ok ? 'Online' : 'Offline')
            .catch(() => 'Offline')
            .then(status => {
                document.getElementById('api-status').textContent = status;
                document.getElementById('api-status').style.color = status === 'Online' ? '#4ade80' : '#f87171';
            });
    </script>
</body>
</html>
EOF
    
    print_warning "Created fallback frontend. You'll need to rebuild the React app manually later."
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
    MYSQL_VERSION=$(mysql --version | grep -oP 'mysql\s+Ver\s+\K[0-9]+\.[0-9]+' || echo "unknown")
    print_status "Found MySQL version: $MYSQL_VERSION"
    
    # Check if git is installed
    if ! command -v git &> /dev/null; then
        print_status "Installing git..."
        apt update && apt install -y git
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
    
    # Detect and install package manager
    detect_package_manager
    
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

# Function to clone and setup application with proper git handling
setup_application() {
    print_status "Setting up application with proper git handling..."
    
    # Remove existing app directory if it exists and is problematic
    if [[ -d "$INSTALL_PATH/app" ]]; then
        print_status "Removing existing app directory..."
        rm -rf "$INSTALL_PATH/app"
    fi
    
    # Clone repository as the app user
    print_status "Cloning repository from $GITHUB_REPO..."
    cd "$INSTALL_PATH"
    
    # Clone with proper error handling
    if sudo -u "$APP_USER" git clone "$GITHUB_REPO" app; then
        print_success "Repository cloned successfully"
    else
        print_error "Failed to clone repository"
        print_status "Attempting to download as zip archive..."
        
        # Fallback: download as zip if git clone fails
        sudo -u "$APP_USER" wget -O app.zip "https://github.com/daviderichammer/car-rental-erp-system/archive/refs/heads/main.zip"
        sudo -u "$APP_USER" unzip app.zip
        sudo -u "$APP_USER" mv car-rental-erp-system-main app
        rm -f app.zip
        
        # Initialize git repository
        cd app
        sudo -u "$APP_USER" git init
        sudo -u "$APP_USER" git remote add origin "$GITHUB_REPO"
        sudo -u "$APP_USER" git add .
        sudo -u "$APP_USER" git commit -m "Initial commit from zip download"
        
        print_success "Application downloaded and git repository initialized"
    fi
    
    # Verify the app directory structure
    if [[ ! -d "$INSTALL_PATH/app/backend" ]] || [[ ! -d "$INSTALL_PATH/app/frontend" ]]; then
        print_error "Application structure is incorrect. Missing backend or frontend directories."
        print_status "Expected structure: $INSTALL_PATH/app/backend and $INSTALL_PATH/app/frontend"
        exit 1
    fi
    
    print_success "Application code ready"
}

# Function to setup backend with better error handling
setup_backend() {
    print_status "Setting up Flask backend..."
    
    cd "$INSTALL_PATH/app/backend"
    
    # Create virtual environment
    if sudo -u "$APP_USER" python3.11 -m venv venv; then
        print_success "Virtual environment created"
    else
        print_error "Failed to create virtual environment"
        exit 1
    fi
    
    # Install Python dependencies with better error handling
    print_status "Installing Python dependencies..."
    if sudo -u "$APP_USER" bash -c "source venv/bin/activate && pip install --upgrade pip wheel setuptools"; then
        print_success "Base packages upgraded"
    else
        print_error "Failed to upgrade base packages"
        exit 1
    fi
    
    if sudo -u "$APP_USER" bash -c "source venv/bin/activate && pip install -r requirements.txt"; then
        print_success "Requirements installed"
    else
        print_error "Failed to install requirements"
        exit 1
    fi
    
    if sudo -u "$APP_USER" bash -c "source venv/bin/activate && pip install gunicorn PyMySQL cryptography"; then
        print_success "Additional packages installed"
    else
        print_error "Failed to install additional packages"
        exit 1
    fi
    
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

# Function to setup frontend with comprehensive build error handling
setup_frontend() {
    print_status "Setting up React frontend..."
    
    cd "$INSTALL_PATH/app/frontend"
    
    # Install dependencies using the detected package manager
    if ! install_frontend_deps "$INSTALL_PATH/app/frontend"; then
        print_error "Failed to install frontend dependencies with all package managers"
        exit 1
    fi
    
    # Build frontend using the detected package manager
    if build_frontend "$INSTALL_PATH/app/frontend"; then
        print_success "Frontend build completed successfully"
    else
        print_warning "Frontend build failed, but continuing with fallback"
    fi
    
    # Deploy to nginx web root
    mkdir -p /var/www/"$NGINX_SITE_NAME"
    
    # Copy dist contents with error handling
    if [[ -d "$INSTALL_PATH/app/frontend/dist" ]] && [[ -n "$(ls -A "$INSTALL_PATH/app/frontend/dist" 2>/dev/null)" ]]; then
        cp -r "$INSTALL_PATH/app/frontend/dist"/* /var/www/"$NGINX_SITE_NAME"/
        print_success "Frontend deployed to nginx web root"
    else
        print_error "No dist directory found or it's empty"
        print_status "Creating minimal fallback frontend..."
        create_fallback_frontend "$INSTALL_PATH/app/frontend"
        cp -r "$INSTALL_PATH/app/frontend/dist"/* /var/www/"$NGINX_SITE_NAME"/
        print_warning "Deployed fallback frontend - manual rebuild required"
    fi
    
    chown -R www-data:www-data /var/www/"$NGINX_SITE_NAME"
    
    print_success "Frontend setup completed using $PACKAGE_MANAGER"
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
    
    # Remove default site if it exists
    if [[ -f /etc/nginx/sites-enabled/default ]]; then
        rm -f /etc/nginx/sites-enabled/default
        print_status "Removed default nginx site"
    fi
    
    # Create site configuration
    cat > /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
# Upstream backend servers
upstream carrental_backend {
    server 127.0.0.1:8000 fail_timeout=0;
}

# Main server configuration
server {
    listen 80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    
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

# Function to start services
start_services() {
    print_status "Starting services..."
    
    # Start backend service
    if systemctl start carrental-backend.service; then
        print_success "Backend service started"
    else
        print_error "Failed to start backend service"
        systemctl status carrental-backend --no-pager
        exit 1
    fi
    
    # Start nginx
    if systemctl start nginx; then
        print_success "Nginx started"
    else
        print_error "Failed to start nginx"
        systemctl status nginx --no-pager
        exit 1
    fi
    
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
    echo "  Package Manager: $PACKAGE_MANAGER"
    echo
    print_status "Access Information:"
    echo "  Frontend: http://$DOMAIN_NAME"
    echo "  API: http://$DOMAIN_NAME/api"
    echo "  Health Check: http://$DOMAIN_NAME/health"
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
    print_status "Frontend Rebuild (if needed):"
    echo "  cd $INSTALL_PATH/app/frontend"
    echo "  sudo -u $APP_USER npm run build"
    echo "  sudo cp -r dist/* /var/www/$NGINX_SITE_NAME/"
    echo "  sudo systemctl reload nginx"
    echo
    print_status "Application Updates:"
    echo "  cd $INSTALL_PATH/app && sudo -u $APP_USER git pull origin main"
    echo "  sudo systemctl restart carrental-backend"
    echo
    print_success "Deployment completed successfully using $PACKAGE_MANAGER!"
    
    # Check if we used fallback frontend
    if [[ -f "/var/www/$NGINX_SITE_NAME/index.html" ]]; then
        if grep -q "Setup in Progress" "/var/www/$NGINX_SITE_NAME/index.html" 2>/dev/null; then
            print_warning "NOTICE: Fallback frontend is active. Please rebuild the React app manually."
        fi
    fi
}

# Function to show usage
show_usage() {
    echo "Car Rental ERP - Final MySQL Deployment Script"
    echo
    echo "This script handles frontend build failures with comprehensive error recovery:"
    echo "  1. Tries multiple package managers (pnpm -> yarn -> npm)"
    echo "  2. Validates build output and handles missing dist directory"
    echo "  3. Creates fallback frontend if build completely fails"
    echo "  4. Provides detailed error diagnostics and recovery steps"
    echo
    echo "Usage: $0 [OPTIONS]"
    echo
    echo "Environment Variables:"
    echo "  INSTALL_PATH      Installation directory (default: /var/www/carrental)"
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
    echo "  # Basic deployment to /var/www/carrental"
    echo "  sudo ./deploy_mysql_final.sh"
    echo
    echo "  # Custom installation path"
    echo "  sudo INSTALL_PATH=/var/www/infiniteautorentals.com ./deploy_mysql_final.sh"
    echo
    echo "  # Custom domain"
    echo "  sudo DOMAIN_NAME=infiniteautorentals.com ./deploy_mysql_final.sh"
    echo
}

# Main deployment function
main() {
    echo "=== Car Rental ERP - Final MySQL Deployment Script ==="
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
    
    # Install system dependencies and detect package manager
    install_dependencies
    
    # Setup MySQL database
    setup_mysql
    
    # Clone and setup application with proper git handling
    setup_application
    
    # Setup backend
    setup_backend
    
    # Setup frontend with comprehensive build error handling
    setup_frontend
    
    # Create systemd service
    create_systemd_service
    
    # Configure nginx
    configure_nginx
    
    # Start services
    start_services
    
    # Display summary
    display_summary
}

# Run main function
main "$@"

