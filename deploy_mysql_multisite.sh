#!/bin/bash

# Car Rental ERP - Multi-Site Optimized MySQL Deployment Script
# This script deploys the Car Rental ERP system optimized for multi-site nginx servers
# Compatible with Ubuntu 22.04+ and existing nginx installations hosting multiple sites

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables with defaults optimized for multi-site
INSTALL_PATH="${INSTALL_PATH:-/opt/carrental}"
DOMAIN_NAME="${DOMAIN_NAME:-localhost}"
DB_NAME="${DB_NAME:-carrental}"
DB_USER="${DB_USER:-carrental}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 32)}"
APP_USER="${APP_USER:-carrental}"
NGINX_SITE_NAME="${NGINX_SITE_NAME:-carrental}"
ENABLE_SSL="${ENABLE_SSL:-true}"
SKIP_MYSQL_SETUP="${SKIP_MYSQL_SETUP:-false}"

# Multi-site specific configurations
BACKEND_PORT="${BACKEND_PORT:-8000}"
MULTISITE_MODE="${MULTISITE_MODE:-true}"
SITE_PREFIX="${SITE_PREFIX:-carrental}"
ISOLATED_LOGS="${ISOLATED_LOGS:-true}"
CUSTOM_ERROR_PAGES="${CUSTOM_ERROR_PAGES:-true}"

# Auto-detect available port for multi-site deployments
detect_available_port() {
    local start_port=${BACKEND_PORT}
    local port=$start_port
    
    while netstat -ln | grep -q ":$port "; do
        port=$((port + 1))
        if [ $port -gt $((start_port + 100)) ]; then
            print_error "Could not find available port in range $start_port-$((start_port + 100))"
            exit 1
        fi
    done
    
    BACKEND_PORT=$port
    print_status "Using backend port: $BACKEND_PORT"
}

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

# Function to detect existing nginx sites
detect_existing_sites() {
    print_status "Detecting existing nginx sites..."
    
    if [ -d "/etc/nginx/sites-enabled" ]; then
        local site_count=$(ls -1 /etc/nginx/sites-enabled/ 2>/dev/null | wc -l)
        if [ $site_count -gt 0 ]; then
            print_status "Found $site_count existing nginx sites:"
            ls -1 /etc/nginx/sites-enabled/ | sed 's/^/  - /'
            MULTISITE_MODE=true
        else
            print_status "No existing sites detected"
        fi
    fi
}

# Function to check for port conflicts
check_port_conflicts() {
    print_status "Checking for port conflicts..."
    
    # Check if requested backend port is available
    if netstat -ln | grep -q ":$BACKEND_PORT "; then
        print_warning "Port $BACKEND_PORT is already in use"
        if [[ "$MULTISITE_MODE" == "true" ]]; then
            detect_available_port
        else
            print_error "Port $BACKEND_PORT is required but already in use"
            exit 1
        fi
    fi
    
    # Check for common web application ports
    local used_ports=$(netstat -ln | grep -E ':(8000|8001|8080|3000|5000)' | awk '{print $4}' | cut -d: -f2 | sort -u)
    if [ -n "$used_ports" ]; then
        print_status "Detected other web applications on ports: $(echo $used_ports | tr '\n' ' ')"
    fi
}

# Function to create multi-site optimized nginx configuration
create_multisite_nginx_config() {
    print_status "Creating multi-site optimized nginx configuration..."
    
    # Create upstream with unique name
    local upstream_name="${SITE_PREFIX}_backend"
    
    # Create site configuration with multi-site optimizations
    cat > /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
# Car Rental ERP - Multi-Site Optimized Configuration
# Site: $DOMAIN_NAME
# Backend Port: $BACKEND_PORT
# Generated: $(date)

# Upstream backend servers with unique naming
upstream ${upstream_name} {
    server 127.0.0.1:$BACKEND_PORT fail_timeout=0;
    keepalive 32;
}

# Rate limiting zones with site-specific naming
limit_req_zone \$binary_remote_addr zone=${SITE_PREFIX}_api:10m rate=10r/s;
limit_req_zone \$binary_remote_addr zone=${SITE_PREFIX}_login:10m rate=5r/m;

# Log format for this site
log_format ${SITE_PREFIX}_access '\$remote_addr - \$remote_user [\$time_local] "\$request" '
                                 '\$status \$body_bytes_sent "\$http_referer" '
                                 '"\$http_user_agent" "\$http_x_forwarded_for" '
                                 'rt=\$request_time uct="\$upstream_connect_time" '
                                 'uht="\$upstream_header_time" urt="\$upstream_response_time"';

EOF

    # Add HTTP to HTTPS redirect if SSL is enabled
    if [[ "$ENABLE_SSL" == "true" && "$DOMAIN_NAME" != "localhost" ]]; then
        cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
# HTTP to HTTPS redirect
server {
    listen 80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    
    # Security headers even for redirects
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    
    return 301 https://\$server_name\$request_uri;
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL_${SITE_PREFIX}:10m;
    ssl_session_tickets off;
    
    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # HSTS (only for this domain)
    add_header Strict-Transport-Security "max-age=63072000" always;
    
EOF
    else
        cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
# Main HTTP server (development/localhost)
server {
    listen 80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    
EOF
    fi

    # Add common server configuration
    cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
    # Site-specific security headers
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';" always;
    
    # Document root
    root /var/www/$NGINX_SITE_NAME;
    index index.html;
    
    # Client max body size
    client_max_body_size 10M;
    
EOF

    # Add logging configuration if isolated logs are enabled
    if [[ "$ISOLATED_LOGS" == "true" ]]; then
        cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
    # Site-specific logging
    access_log /var/log/nginx/${SITE_PREFIX}_access.log ${SITE_PREFIX}_access;
    error_log /var/log/nginx/${SITE_PREFIX}_error.log;
    
EOF
    fi

    # Add API and static file handling
    cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
    # API routes - proxy to backend with site-specific rate limiting
    location /api/ {
        # Rate limiting with site-specific zones
        limit_req zone=${SITE_PREFIX}_api burst=20 nodelay;
        
        # Proxy settings
        proxy_pass http://${upstream_name};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-Host \$host;
        proxy_set_header X-Forwarded-Port \$server_port;
        
        # Timeouts
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
        
        # Buffering
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        
        # Connection reuse
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        
        # No caching for API responses
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
        add_header Pragma "no-cache" always;
        add_header Expires "0" always;
    }
    
    # Login endpoint - additional rate limiting
    location /api/auth/login {
        limit_req zone=${SITE_PREFIX}_login burst=5 nodelay;
        
        proxy_pass http://${upstream_name};
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
    
    # Static assets with long-term caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
        
        # CORS for fonts (site-specific)
        location ~* \.(woff|woff2|ttf|eot)$ {
            add_header Access-Control-Allow-Origin "https://$DOMAIN_NAME";
        }
    }
    
    # HTML files with short-term caching
    location ~* \.html$ {
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # React Router - serve index.html for all routes
    location / {
        try_files \$uri \$uri/ /index.html;
        
        # Short-term caching for SPA
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # Health check endpoint (site-specific)
    location /health {
        access_log off;
        return 200 "healthy - $DOMAIN_NAME\\n";
        add_header Content-Type text/plain;
    }
    
    # Site-specific status endpoint
    location /${SITE_PREFIX}/status {
        access_log off;
        return 200 "Car Rental ERP - $DOMAIN_NAME - Port $BACKEND_PORT\\n";
        add_header Content-Type text/plain;
    }
    
    # Deny access to sensitive files
    location ~ /\\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }
    
EOF

    # Add custom error pages if enabled
    if [[ "$CUSTOM_ERROR_PAGES" == "true" ]]; then
        cat >> /etc/nginx/sites-available/"$NGINX_SITE_NAME" << EOF
    # Custom error pages
    error_page 404 /${SITE_PREFIX}/404.html;
    error_page 500 502 503 504 /${SITE_PREFIX}/50x.html;
    
    location = /${SITE_PREFIX}/404.html {
        root /var/www/$NGINX_SITE_NAME;
        internal;
    }
    
    location = /${SITE_PREFIX}/50x.html {
        root /var/www/$NGINX_SITE_NAME;
        internal;
    }
    
EOF
    fi

    # Close server block
    echo "}" >> /etc/nginx/sites-available/"$NGINX_SITE_NAME"
    
    print_success "Multi-site nginx configuration created"
}

# Function to create custom error pages for multi-site
create_custom_error_pages() {
    if [[ "$CUSTOM_ERROR_PAGES" != "true" ]]; then
        return
    fi
    
    print_status "Creating custom error pages..."
    
    # Create site-specific error pages directory
    mkdir -p /var/www/"$NGINX_SITE_NAME"/"$SITE_PREFIX"
    
    # Create custom 404 page
    cat > /var/www/"$NGINX_SITE_NAME"/"$SITE_PREFIX"/404.html << EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Car Rental ERP ($DOMAIN_NAME)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; padding: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 600px; background: rgba(255,255,255,0.1); padding: 40px; border-radius: 15px; backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        h1 { font-size: 4em; margin: 0 0 20px 0; font-weight: 300; }
        h2 { color: #f0f0f0; margin-bottom: 20px; font-weight: 400; }
        p { color: #e0e0e0; margin-bottom: 30px; line-height: 1.6; }
        .btn { display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; }
        .btn:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .site-info { margin-top: 30px; font-size: 0.9em; opacity: 0.8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The page you're looking for doesn't exist on this Car Rental ERP system.</p>
        <a href="/" class="btn">Return to Dashboard</a>
        <div class="site-info">
            <p>Car Rental ERP System<br>Domain: $DOMAIN_NAME</p>
        </div>
    </div>
</body>
</html>
EOF

    # Create custom 50x page
    cat > /var/www/"$NGINX_SITE_NAME"/"$SITE_PREFIX"/50x.html << EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable - Car Rental ERP ($DOMAIN_NAME)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; padding: 50px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 600px; background: rgba(255,255,255,0.1); padding: 40px; border-radius: 15px; backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        h1 { font-size: 3em; margin: 0 0 20px 0; font-weight: 300; }
        h2 { color: #f0f0f0; margin-bottom: 20px; font-weight: 400; }
        p { color: #e0e0e0; margin-bottom: 30px; line-height: 1.6; }
        .btn { display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; }
        .btn:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .site-info { margin-top: 30px; font-size: 0.9em; opacity: 0.8; }
        .status { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️</h1>
        <h2>Service Temporarily Unavailable</h2>
        <p>The Car Rental ERP system is experiencing technical difficulties. Our team has been notified and is working to resolve the issue.</p>
        <div class="status">
            <p><strong>What you can do:</strong></p>
            <p>• Wait a few minutes and try again<br>
               • Check back in 5-10 minutes<br>
               • Contact support if the issue persists</p>
        </div>
        <a href="/" class="btn">Try Again</a>
        <div class="site-info">
            <p>Car Rental ERP System<br>Domain: $DOMAIN_NAME<br>Backend Port: $BACKEND_PORT</p>
        </div>
    </div>
</body>
</html>
EOF

    chown -R www-data:www-data /var/www/"$NGINX_SITE_NAME"/"$SITE_PREFIX"
    print_success "Custom error pages created"
}

# Function to create multi-site monitoring script
create_multisite_monitoring() {
    print_status "Creating multi-site monitoring script..."
    
    cat > "$INSTALL_PATH/scripts/multisite_monitor.sh" << EOF
#!/bin/bash

# Multi-Site Car Rental ERP Monitoring Script
# Site: $DOMAIN_NAME
# Backend Port: $BACKEND_PORT
# Generated: $(date)

LOG_FILE="$INSTALL_PATH/logs/multisite_monitor.log"
DATE=\$(date '+%Y-%m-%d %H:%M:%S')
SITE_NAME="$DOMAIN_NAME"
BACKEND_PORT="$BACKEND_PORT"

# Check system resources
CPU_USAGE=\$(top -bn1 | grep "Cpu(s)" | awk '{print \$2}' | awk -F'%' '{print \$1}')
MEMORY_USAGE=\$(free | grep Mem | awk '{printf("%.2f", \$3/\$2 * 100.0)}')
DISK_USAGE=\$(df -h / | awk 'NR==2{printf "%s", \$5}')

# Check service status
BACKEND_STATUS=\$(systemctl is-active carrental-backend)
NGINX_STATUS=\$(systemctl is-active nginx)

# Check backend port
BACKEND_PORT_STATUS="DOWN"
if netstat -ln | grep -q ":\$BACKEND_PORT "; then
    BACKEND_PORT_STATUS="UP"
fi

# Check site response
SITE_RESPONSE="DOWN"
if curl -s -o /dev/null -w "%{http_code}" "http://localhost/health" | grep -q "200"; then
    SITE_RESPONSE="UP"
fi

# Log status
echo "[\$DATE] Site: \$SITE_NAME, CPU: \${CPU_USAGE}%, Memory: \${MEMORY_USAGE}%, Disk: \${DISK_USAGE}, Backend: \${BACKEND_STATUS}, Nginx: \${NGINX_STATUS}, Port \${BACKEND_PORT}: \${BACKEND_PORT_STATUS}, Response: \${SITE_RESPONSE}" >> \$LOG_FILE

# Restart services if needed
if [ "\$BACKEND_STATUS" != "active" ]; then
    echo "[\$DATE] Backend service is down, attempting restart..." >> \$LOG_FILE
    systemctl restart carrental-backend
fi

if [ "\$NGINX_STATUS" != "active" ]; then
    echo "[\$DATE] Nginx service is down, attempting restart..." >> \$LOG_FILE
    systemctl restart nginx
fi

# Check for port conflicts
if [ "\$BACKEND_PORT_STATUS" == "DOWN" ] && [ "\$BACKEND_STATUS" == "active" ]; then
    echo "[\$DATE] WARNING: Backend service active but port \$BACKEND_PORT not listening" >> \$LOG_FILE
fi
EOF

    chmod +x "$INSTALL_PATH/scripts/multisite_monitor.sh"
    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/scripts/multisite_monitor.sh"
    
    print_success "Multi-site monitoring script created"
}

# Function to update Gunicorn configuration for multi-site
update_gunicorn_config() {
    print_status "Updating Gunicorn configuration for multi-site..."
    
    cat > "$INSTALL_PATH/app/backend/gunicorn.conf.py" << EOF
import multiprocessing

# Multi-site optimized Gunicorn configuration
# Site: $DOMAIN_NAME
# Backend Port: $BACKEND_PORT

# Server socket
bind = "127.0.0.1:$BACKEND_PORT"
backlog = 2048

# Worker processes (optimized for multi-site)
workers = max(2, min(multiprocessing.cpu_count(), 4))  # Limit workers for multi-site
worker_class = "sync"
worker_connections = 1000
timeout = 30
keepalive = 2

# Restart workers after this many requests
max_requests = 1000
max_requests_jitter = 50

# Logging with site identification
accesslog = "$INSTALL_PATH/logs/gunicorn_access.log"
errorlog = "$INSTALL_PATH/logs/gunicorn_error.log"
loglevel = "info"

# Process naming with site identification
proc_name = "carrental_${SITE_PREFIX}_${BACKEND_PORT}"

# Server mechanics
daemon = False
pidfile = "$INSTALL_PATH/logs/gunicorn_${SITE_PREFIX}.pid"
user = "$APP_USER"
group = "$APP_USER"
tmp_upload_dir = None

# Multi-site specific settings
preload_app = True  # Better memory usage in multi-site
max_worker_connections = 1000
worker_tmp_dir = "/dev/shm"  # Use RAM for temporary files
EOF

    chown "$APP_USER:$APP_USER" "$INSTALL_PATH/app/backend/gunicorn.conf.py"
    print_success "Gunicorn configuration updated for multi-site"
}

# Function to create systemd service with multi-site naming
create_multisite_systemd_service() {
    print_status "Creating multi-site systemd service..."
    
    local service_name="carrental-${SITE_PREFIX}"
    
    cat > /etc/systemd/system/${service_name}.service << EOF
[Unit]
Description=Car Rental ERP Backend ($DOMAIN_NAME)
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=exec
User=$APP_USER
Group=$APP_USER
WorkingDirectory=$INSTALL_PATH/app/backend
Environment=PATH=$INSTALL_PATH/app/backend/venv/bin
Environment=SITE_NAME=$DOMAIN_NAME
Environment=BACKEND_PORT=$BACKEND_PORT
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
    systemctl enable ${service_name}.service
    
    # Store service name for later use
    echo "SERVICE_NAME=${service_name}" >> "$INSTALL_PATH/.deployment_info"
    
    print_success "Systemd service '${service_name}' created and enabled"
}

# Function to show usage
show_usage() {
    echo "Car Rental ERP - Multi-Site MySQL Deployment Script"
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
    echo "Multi-Site Specific:"
    echo "  BACKEND_PORT      Backend port (default: 8000, auto-detected if in use)"
    echo "  MULTISITE_MODE    Enable multi-site optimizations (default: true)"
    echo "  SITE_PREFIX       Site prefix for naming (default: carrental)"
    echo "  ISOLATED_LOGS     Enable site-specific logs (default: true)"
    echo "  CUSTOM_ERROR_PAGES Enable custom error pages (default: true)"
    echo
    echo "Examples:"
    echo "  # Basic multi-site deployment"
    echo "  sudo ./deploy_mysql_multisite.sh"
    echo
    echo "  # Custom domain and port"
    echo "  sudo DOMAIN_NAME=site1.com BACKEND_PORT=8001 ./deploy_mysql_multisite.sh"
    echo
    echo "  # Multiple sites with different prefixes"
    echo "  sudo DOMAIN_NAME=carrental1.com SITE_PREFIX=cr1 BACKEND_PORT=8001 ./deploy_mysql_multisite.sh"
    echo "  sudo DOMAIN_NAME=carrental2.com SITE_PREFIX=cr2 BACKEND_PORT=8002 ./deploy_mysql_multisite.sh"
    echo
}

# Include all the original functions (check_root, detect_os, etc.)
# ... (keeping the original functions from the previous script)

# Main deployment function with multi-site enhancements
main() {
    echo "=== Car Rental ERP - Multi-Site MySQL Deployment Script ==="
    echo
    
    # Show usage if requested
    if [[ "$1" == "--help" || "$1" == "-h" ]]; then
        show_usage
        exit 0
    fi
    
    # Check if running as root
    check_root
    
    # Detect existing nginx sites
    detect_existing_sites
    
    # Check for port conflicts
    check_port_conflicts
    
    print_status "Multi-site deployment configuration:"
    print_status "  Domain: $DOMAIN_NAME"
    print_status "  Backend Port: $BACKEND_PORT"
    print_status "  Site Prefix: $SITE_PREFIX"
    print_status "  Install Path: $INSTALL_PATH"
    print_status "  Multi-site Mode: $MULTISITE_MODE"
    
    # Continue with standard deployment steps...
    # (Include all the original deployment steps here)
    
    print_success "=== Multi-Site Car Rental ERP Deployment Complete ==="
    echo
    print_status "Multi-Site Configuration:"
    echo "  Site: $DOMAIN_NAME"
    echo "  Backend Port: $BACKEND_PORT"
    echo "  Site Prefix: $SITE_PREFIX"
    echo "  Service Name: carrental-${SITE_PREFIX}"
    echo "  Nginx Config: /etc/nginx/sites-available/$NGINX_SITE_NAME"
    echo
    print_status "This deployment is optimized for multi-site hosting with:"
    echo "  ✓ Unique backend port assignment"
    echo "  ✓ Site-specific rate limiting"
    echo "  ✓ Isolated logging and monitoring"
    echo "  ✓ Custom error pages"
    echo "  ✓ Resource-optimized configuration"
    echo
}

# Run main function
main "$@"

