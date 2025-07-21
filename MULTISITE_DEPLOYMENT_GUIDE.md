# Car Rental ERP - Multi-Site Deployment Guide

## 🌐 Multi-Site Nginx Compatibility

**Yes, the Car Rental ERP nginx configuration works perfectly with multi-site hosting!** This guide explains the optimizations and best practices for deploying on servers hosting multiple websites.

## 🎯 Multi-Site Optimizations Overview

### ✅ **What Makes It Multi-Site Ready**

The deployment includes several optimizations specifically designed for multi-site environments:

1. **Site-Specific Configuration Files** - Each site gets its own nginx configuration
2. **Unique Backend Ports** - Automatic port detection and assignment
3. **Isolated Rate Limiting** - Site-specific rate limiting zones
4. **Separate Log Files** - Individual access and error logs per site
5. **Custom Error Pages** - Site-branded error pages
6. **Resource Isolation** - Optimized resource usage for shared hosting
7. **Service Naming** - Unique systemd service names per site

### ⚡ **Multi-Site Deployment Script**

Use the optimized multi-site deployment script:

```bash
# Multi-site optimized deployment
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system
sudo ./deploy_mysql_multisite.sh
```

## 🔧 Multi-Site Configuration Details

### **Nginx Configuration Optimizations**

#### **1. Unique Upstream Names**
```nginx
# Each site gets a unique upstream name
upstream carrental_backend {
    server 127.0.0.1:8000 fail_timeout=0;
}

upstream site2_backend {
    server 127.0.0.1:8001 fail_timeout=0;
}
```

#### **2. Site-Specific Rate Limiting**
```nginx
# Separate rate limiting zones per site
limit_req_zone $binary_remote_addr zone=carrental_api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=carrental_login:10m rate=5r/m;

limit_req_zone $binary_remote_addr zone=site2_api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=site2_login:10m rate=5r/m;
```

#### **3. Isolated SSL Sessions**
```nginx
# Site-specific SSL session cache
ssl_session_cache shared:SSL_carrental:10m;
ssl_session_cache shared:SSL_site2:10m;
```

#### **4. Custom Log Formats**
```nginx
# Site-specific log format with performance metrics
log_format carrental_access '$remote_addr - $remote_user [$time_local] "$request" '
                            '$status $body_bytes_sent "$http_referer" '
                            '"$http_user_agent" rt=$request_time';
```

### **Backend Port Management**

#### **Automatic Port Detection**
The multi-site script automatically detects available ports:

```bash
# Automatic port assignment
BACKEND_PORT=8000  # Default
# If 8000 is in use, tries 8001, 8002, etc.
```

#### **Manual Port Assignment**
```bash
# Deploy multiple sites with specific ports
sudo DOMAIN_NAME=site1.com BACKEND_PORT=8001 SITE_PREFIX=site1 ./deploy_mysql_multisite.sh
sudo DOMAIN_NAME=site2.com BACKEND_PORT=8002 SITE_PREFIX=site2 ./deploy_mysql_multisite.sh
sudo DOMAIN_NAME=site3.com BACKEND_PORT=8003 SITE_PREFIX=site3 ./deploy_mysql_multisite.sh
```

### **Service Isolation**

#### **Unique Service Names**
```bash
# Each site gets its own systemd service
systemctl status carrental-site1
systemctl status carrental-site2
systemctl status carrental-site3
```

#### **Resource Optimization**
```python
# Gunicorn worker optimization for multi-site
workers = max(2, min(multiprocessing.cpu_count(), 4))  # Limited for sharing
```

## 📊 Multi-Site Deployment Examples

### **Example 1: Multiple Car Rental Businesses**
```bash
# Business 1
sudo DOMAIN_NAME=carrental1.com \
     SITE_PREFIX=cr1 \
     BACKEND_PORT=8001 \
     DB_NAME=carrental1 \
     INSTALL_PATH=/opt/carrental1 \
     ./deploy_mysql_multisite.sh

# Business 2
sudo DOMAIN_NAME=carrental2.com \
     SITE_PREFIX=cr2 \
     BACKEND_PORT=8002 \
     DB_NAME=carrental2 \
     INSTALL_PATH=/opt/carrental2 \
     ./deploy_mysql_multisite.sh
```

### **Example 2: Development and Production**
```bash
# Production site
sudo DOMAIN_NAME=carrental.com \
     SITE_PREFIX=prod \
     BACKEND_PORT=8000 \
     ./deploy_mysql_multisite.sh

# Staging site
sudo DOMAIN_NAME=staging.carrental.com \
     SITE_PREFIX=staging \
     BACKEND_PORT=8001 \
     DB_NAME=carrental_staging \
     ./deploy_mysql_multisite.sh
```

### **Example 3: Different Brands**
```bash
# Luxury car rental
sudo DOMAIN_NAME=luxurycars.com \
     SITE_PREFIX=luxury \
     BACKEND_PORT=8001 \
     DB_NAME=luxury_cars \
     ./deploy_mysql_multisite.sh

# Budget car rental
sudo DOMAIN_NAME=budgetcars.com \
     SITE_PREFIX=budget \
     BACKEND_PORT=8002 \
     DB_NAME=budget_cars \
     ./deploy_mysql_multisite.sh
```

## 🔐 Multi-Site Security Considerations

### **Isolation Benefits**
1. **Database Isolation** - Each site has its own database
2. **Process Isolation** - Separate backend processes
3. **Log Isolation** - Individual log files prevent data mixing
4. **Rate Limiting** - Site-specific limits prevent cross-site impact
5. **SSL Isolation** - Separate SSL sessions and certificates

### **Security Headers Per Site**
```nginx
# Site-specific Content Security Policy
add_header Content-Security-Policy "default-src 'self'; connect-src 'self' https://api.site1.com;" always;

# For another site
add_header Content-Security-Policy "default-src 'self'; connect-src 'self' https://api.site2.com;" always;
```

### **CORS Configuration**
```nginx
# Site-specific CORS for fonts
location ~* \.(woff|woff2|ttf|eot)$ {
    add_header Access-Control-Allow-Origin "https://carrental1.com";
}
```

## 📈 Performance Optimization for Multi-Site

### **Resource Allocation**
```bash
# Optimized worker count per site
workers = max(2, min(cpu_count, 4))  # Prevents resource exhaustion

# Shared memory optimization
worker_tmp_dir = "/dev/shm"  # Use RAM for temporary files
```

### **Connection Pooling**
```nginx
# Upstream keepalive connections
upstream carrental_backend {
    server 127.0.0.1:8000;
    keepalive 32;  # Reuse connections
}
```

### **Caching Strategy**
```nginx
# Site-specific caching
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    # Separate cache per site
}
```

## 🔍 Monitoring Multi-Site Deployments

### **Site-Specific Health Checks**
```bash
# Check each site individually
curl http://site1.com/health
curl http://site2.com/health
curl http://site3.com/health
```

### **Service Status Monitoring**
```bash
# Monitor all Car Rental ERP services
systemctl status carrental-*

# Individual site monitoring
systemctl status carrental-site1
systemctl status carrental-site2
```

### **Log Monitoring**
```bash
# Site-specific logs
tail -f /var/log/nginx/site1_access.log
tail -f /var/log/nginx/site2_access.log

# Backend logs per site
journalctl -u carrental-site1 -f
journalctl -u carrental-site2 -f
```

### **Port Usage Monitoring**
```bash
# Check which ports are in use
netstat -tlnp | grep -E ':(8000|8001|8002|8003)'

# Check specific backend ports
ss -tlnp | grep :8001
```

## 🛠️ Multi-Site Management Commands

### **Deployment Management**
```bash
# Deploy new site
sudo DOMAIN_NAME=newsite.com BACKEND_PORT=8004 ./deploy_mysql_multisite.sh

# Update existing site
cd /opt/carrental-newsite/app
sudo -u carrental git pull origin main
sudo systemctl restart carrental-newsite
```

### **Service Management**
```bash
# Start all Car Rental ERP sites
sudo systemctl start carrental-*

# Stop specific site
sudo systemctl stop carrental-site1

# Restart all sites
sudo systemctl restart carrental-*
```

### **Nginx Management**
```bash
# Test all nginx configurations
sudo nginx -t

# Reload nginx (affects all sites)
sudo systemctl reload nginx

# Check site-specific configuration
sudo nginx -T | grep -A 20 "server_name site1.com"
```

## 📋 Multi-Site Configuration Variables

| Variable | Default | Multi-Site Purpose |
|----------|---------|-------------------|
| `BACKEND_PORT` | `8000` | Unique port per site |
| `SITE_PREFIX` | `carrental` | Unique naming prefix |
| `ISOLATED_LOGS` | `true` | Separate log files |
| `CUSTOM_ERROR_PAGES` | `true` | Site-branded errors |
| `MULTISITE_MODE` | `true` | Enable optimizations |
| `NGINX_SITE_NAME` | `carrental` | Nginx config filename |
| `DB_NAME` | `carrental` | Unique database per site |
| `INSTALL_PATH` | `/opt/carrental` | Separate directories |

## 🔄 Multi-Site Update Procedures

### **Update Single Site**
```bash
# Update specific site
cd /opt/carrental-site1/app
sudo -u carrental git pull origin main
sudo systemctl restart carrental-site1
```

### **Update All Sites**
```bash
# Update all Car Rental ERP sites
for service in $(systemctl list-units --type=service | grep carrental- | awk '{print $1}'); do
    site_path=$(systemctl show $service -p WorkingDirectory --value)
    echo "Updating $service at $site_path"
    cd $site_path/../
    sudo -u carrental git pull origin main
    sudo systemctl restart $service
done
```

### **Rolling Updates**
```bash
# Update sites one at a time (zero downtime)
sites=("carrental-site1" "carrental-site2" "carrental-site3")
for site in "${sites[@]}"; do
    echo "Updating $site..."
    # Update code
    # Restart service
    # Wait for health check
    sleep 10
done
```

## 🚨 Troubleshooting Multi-Site Issues

### **Port Conflicts**
```bash
# Check for port conflicts
sudo netstat -tlnp | grep :8000
sudo lsof -i :8000

# Find available ports
for port in {8000..8010}; do
    if ! netstat -ln | grep -q ":$port "; then
        echo "Port $port is available"
    fi
done
```

### **Service Conflicts**
```bash
# Check all Car Rental ERP services
systemctl list-units --type=service | grep carrental

# Check failed services
systemctl --failed | grep carrental

# View service logs
journalctl -u carrental-site1 --since "1 hour ago"
```

### **Nginx Configuration Issues**
```bash
# Test nginx configuration
sudo nginx -t

# Check site-specific configuration
sudo nginx -T | grep -A 50 "server_name yoursite.com"

# Validate upstream definitions
sudo nginx -T | grep -A 5 "upstream.*backend"
```

### **Database Connection Issues**
```bash
# Test database connections per site
mysql -u carrental1 -p carrental1
mysql -u carrental2 -p carrental2

# Check database isolation
mysql -u root -p -e "SHOW DATABASES;" | grep carrental
```

## 📊 Multi-Site Resource Planning

### **Server Resource Requirements**

| Sites | RAM | CPU | Disk | MySQL |
|-------|-----|-----|------|-------|
| 1 site | 2GB | 2 cores | 10GB | 1 DB |
| 2-3 sites | 4GB | 4 cores | 20GB | 2-3 DBs |
| 4-5 sites | 8GB | 6 cores | 40GB | 4-5 DBs |
| 6+ sites | 16GB+ | 8+ cores | 80GB+ | 6+ DBs |

### **Port Allocation Strategy**
```bash
# Recommended port allocation
Site 1 (Production): 8000
Site 2 (Staging): 8001
Site 3 (Development): 8002
Site 4 (Client 1): 8003
Site 5 (Client 2): 8004
# etc.
```

### **Database Naming Convention**
```bash
# Recommended database naming
carrental_prod      # Production
carrental_staging   # Staging
carrental_dev       # Development
carrental_client1   # Client 1
carrental_client2   # Client 2
```

## 🎯 Best Practices for Multi-Site Hosting

### **1. Planning and Organization**
- **Use consistent naming conventions** across all sites
- **Document port assignments** to avoid conflicts
- **Plan resource allocation** based on expected traffic
- **Implement monitoring** for all sites

### **2. Security Isolation**
- **Separate databases** for each site
- **Unique SSL certificates** per domain
- **Site-specific rate limiting** to prevent cross-site impact
- **Isolated log files** for security auditing

### **3. Performance Optimization**
- **Limit worker processes** per site to prevent resource exhaustion
- **Use connection pooling** for database efficiency
- **Implement caching strategies** appropriate for each site
- **Monitor resource usage** across all sites

### **4. Maintenance Procedures**
- **Stagger updates** to maintain availability
- **Test configurations** before applying to production
- **Maintain separate backup schedules** for each site
- **Document site-specific configurations**

## 🚀 Quick Multi-Site Deployment

### **Deploy First Site**
```bash
git clone https://github.com/daviderichammer/car-rental-erp-system.git
cd car-rental-erp-system
sudo DOMAIN_NAME=site1.com ./deploy_mysql_multisite.sh
```

### **Deploy Additional Sites**
```bash
# Site 2
sudo DOMAIN_NAME=site2.com \
     SITE_PREFIX=site2 \
     BACKEND_PORT=8001 \
     DB_NAME=carrental2 \
     INSTALL_PATH=/opt/carrental2 \
     ./deploy_mysql_multisite.sh

# Site 3
sudo DOMAIN_NAME=site3.com \
     SITE_PREFIX=site3 \
     BACKEND_PORT=8002 \
     DB_NAME=carrental3 \
     INSTALL_PATH=/opt/carrental3 \
     ./deploy_mysql_multisite.sh
```

## 🎉 Multi-Site Success!

Your nginx server can now host multiple Car Rental ERP sites with:

✅ **Complete Isolation** - Each site operates independently
✅ **Optimized Performance** - Resource-efficient multi-site configuration
✅ **Easy Management** - Simple deployment and maintenance procedures
✅ **Scalable Architecture** - Add new sites without affecting existing ones
✅ **Professional Security** - Site-specific security and SSL configuration
✅ **Comprehensive Monitoring** - Individual site monitoring and logging

The multi-site deployment script handles all the complexity of port management, service isolation, and nginx configuration optimization for your multi-site hosting environment.

**Repository**: https://github.com/daviderichammer/car-rental-erp-system
**Multi-Site Script**: `./deploy_mysql_multisite.sh`

