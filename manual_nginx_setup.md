# Manual Nginx Configuration for Car Rental ERP

This guide provides step-by-step instructions to manually configure nginx for the Car Rental ERP system, especially useful if the deployment script didn't set up nginx configurations properly.

## Prerequisites

- Car Rental ERP backend is running (usually on port 8000 or 8001)
- Nginx is installed and running
- You have sudo/root access

## Step 1: Check Current Backend Status

First, verify that your backend is running:

```bash
# Check if backend service is running
sudo systemctl status carrental-backend

# Check what port the backend is using
sudo netstat -tlnp | grep python
# or
sudo ss -tlnp | grep python

# Test backend directly
curl http://localhost:8000/api/health
# or try port 8001 if 8000 doesn't work
curl http://localhost:8001/api/health
```

## Step 2: Create Nginx Site Configuration

Create the nginx configuration file for your site:

```bash
# For infiniteautorentals.com
sudo nano /etc/nginx/sites-available/infiniteautorentals.com
```

Add the following configuration (adjust the backend port as needed):

```nginx
# Upstream backend servers for infiniteautorentals.com
upstream infiniteautorentals_backend {
    server 127.0.0.1:8000 fail_timeout=0;
    # Change to 8001 if your backend is running on that port
}

# Rate limiting zones
limit_req_zone $binary_remote_addr zone=infiniteautorentals_api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=infiniteautorentals_general:10m rate=30r/s;

# Main server configuration
server {
    listen 80;
    server_name infiniteautorentals.com www.infiniteautorentals.com;
    
    # Security headers
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Document root
    root /var/www/infiniteautorentals.com;
    index index.html;
    
    # Client max body size
    client_max_body_size 10M;
    
    # Logging (site-specific)
    access_log /var/log/nginx/infiniteautorentals_access.log;
    error_log /var/log/nginx/infiniteautorentals_error.log;
    
    # API routes - proxy to backend with rate limiting
    location /api/ {
        limit_req zone=infiniteautorentals_api burst=20 nodelay;
        
        proxy_pass http://infiniteautorentals_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
        
        # Buffer settings
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        
        # CORS headers for API
        add_header Access-Control-Allow-Origin "https://infiniteautorentals.com" always;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept" always;
        add_header Access-Control-Allow-Credentials true always;
        
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header Access-Control-Allow-Origin "https://infiniteautorentals.com";
            add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
            add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept";
            add_header Access-Control-Allow-Credentials true;
            add_header Content-Length 0;
            add_header Content-Type text/plain;
            return 204;
        }
    }
    
    # Static assets with aggressive caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp|avif)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        
        # Compression
        gzip_static on;
        
        # Security headers for assets
        add_header X-Content-Type-Options nosniff always;
    }
    
    # React Router - serve index.html for all routes
    location / {
        limit_req zone=infiniteautorentals_general burst=50 nodelay;
        
        try_files $uri $uri/ /index.html;
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
        
        # Security headers
        add_header X-Frame-Options DENY always;
        add_header X-Content-Type-Options nosniff always;
        add_header X-XSS-Protection "1; mode=block" always;
    }
    
    # Health check endpoint (no rate limiting)
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
    
    # Robots.txt
    location /robots.txt {
        return 200 "User-agent: *\nDisallow: /\n";
        add_header Content-Type text/plain;
    }
    
    # Security: Block access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    location ~ \.(sql|log|conf)$ {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

## Step 3: Enable the Site

```bash
# Enable the site
sudo ln -sf /etc/nginx/sites-available/infiniteautorentals.com /etc/nginx/sites-enabled/

# Test nginx configuration
sudo nginx -t

# If test passes, reload nginx
sudo systemctl reload nginx
```

## Step 4: Create Web Directory and Deploy Frontend

```bash
# Create web directory
sudo mkdir -p /var/www/infiniteautorentals.com

# If you have a built frontend, copy it
# (Adjust the source path based on where your frontend dist is located)
sudo cp -r /var/www/infiniteautorentals.com/app/frontend/dist/* /var/www/infiniteautorentals.com/

# If no frontend dist exists, create a simple index.html
sudo cat > /var/www/infiniteautorentals.com/index.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental ERP - System Ready</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .title {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .status {
            font-size: 1.2rem;
            margin: 20px 0;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            font-size: 1rem;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #45a049;
        }
        .api-status {
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">🚗 Car Rental ERP</h1>
        <p class="status">System is operational and ready for use!</p>
        
        <div class="api-status">
            <h3>API Status: <span id="api-status">Checking...</span></h3>
        </div>
        
        <div>
            <a href="/api/health" class="btn">API Health Check</a>
            <a href="/api/auth/login" class="btn">Login Endpoint</a>
        </div>
        
        <div style="margin-top: 30px; font-size: 0.9rem; opacity: 0.8;">
            <p><strong>Default Login:</strong></p>
            <p>Email: admin@carrental.com</p>
            <p>Password: admin123</p>
        </div>
    </div>
    
    <script>
        // Check API status
        fetch('/api/health')
            .then(response => {
                if (response.ok) {
                    document.getElementById('api-status').innerHTML = '<span style="color: #4ade80;">✅ Online</span>';
                } else {
                    document.getElementById('api-status').innerHTML = '<span style="color: #f87171;">❌ Error</span>';
                }
            })
            .catch(() => {
                document.getElementById('api-status').innerHTML = '<span style="color: #f87171;">❌ Offline</span>';
            });
    </script>
</body>
</html>
EOF

# Set proper permissions
sudo chown -R www-data:www-data /var/www/infiniteautorentals.com
```

## Step 5: Test the Configuration

```bash
# Test nginx configuration
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx

# Test the site
curl -I http://infiniteautorentals.com
curl http://infiniteautorentals.com/health
curl http://infiniteautorentals.com/api/health
```

## Step 6: Optional - Setup SSL with Let's Encrypt

If you want SSL (recommended for production):

```bash
# Install certbot if not already installed
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d infiniteautorentals.com -d www.infiniteautorentals.com

# Test automatic renewal
sudo certbot renew --dry-run
```

## Troubleshooting

### Backend Not Responding

```bash
# Check backend service
sudo systemctl status carrental-backend

# Check backend logs
sudo journalctl -u carrental-backend -f

# Check if backend is listening on the correct port
sudo netstat -tlnp | grep :8000
sudo netstat -tlnp | grep :8001

# Restart backend if needed
sudo systemctl restart carrental-backend
```

### Nginx Configuration Errors

```bash
# Check nginx syntax
sudo nginx -t

# Check nginx error logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/infiniteautorentals_error.log

# Check nginx access logs
sudo tail -f /var/log/nginx/infiniteautorentals_access.log
```

### Permission Issues

```bash
# Fix web directory permissions
sudo chown -R www-data:www-data /var/www/infiniteautorentals.com
sudo chmod -R 755 /var/www/infiniteautorentals.com

# Check SELinux if applicable
sudo setsebool -P httpd_can_network_connect 1
```

## Alternative Configuration for Different Domains

If you're using a different domain name, replace `infiniteautorentals.com` with your domain throughout the configuration. For example, for `mycarrental.com`:

```bash
# Create configuration
sudo nano /etc/nginx/sites-available/mycarrental.com

# Update all instances of:
# - infiniteautorentals.com → mycarrental.com
# - infiniteautorentals_backend → mycarrental_backend
# - infiniteautorentals_api → mycarrental_api
# - infiniteautorentals_general → mycarrental_general
# - /var/www/infiniteautorentals.com → /var/www/mycarrental.com
```

## Multi-Site Configuration

If you're running multiple sites on the same server, each site should have:

1. **Unique upstream name** (e.g., `site1_backend`, `site2_backend`)
2. **Unique rate limiting zones** (e.g., `site1_api`, `site2_api`)
3. **Different backend ports** (e.g., 8000, 8001, 8002)
4. **Separate web directories** (e.g., `/var/www/site1.com`, `/var/www/site2.com`)
5. **Site-specific log files**

This manual configuration should get your nginx setup working properly with the Car Rental ERP system!

