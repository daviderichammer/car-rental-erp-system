#!/usr/bin/env python3
import http.server
import socketserver
import urllib.request
import urllib.parse
import json
import os

class ProxyHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
        self.send_header('Pragma', 'no-cache')
        self.send_header('Expires', '0')
        super().end_headers()
    
    def do_GET(self):
        if self.path == '/' or self.path == '/index.html':
            self.send_response(200)
            self.send_header('Content-type', 'text/html')
            self.end_headers()
            with open('/home/ubuntu/fresh_erp.html', 'rb') as f:
                content = f.read().decode('utf-8')
                # Update API_BASE to use the same origin (proxy)
                content = content.replace("const API_BASE = 'http://198.91.25.229';", "const API_BASE = window.location.origin;")
                self.wfile.write(content.encode('utf-8'))
        elif self.path.startswith('/api/'):
            # Proxy API requests to the backend
            self.proxy_api_request()
        else:
            super().do_GET()
    
    def do_POST(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            super().do_POST()
    
    def do_PUT(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            super().do_PUT()
    
    def do_DELETE(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            super().do_DELETE()
    
    def do_OPTIONS(self):
        if self.path.startswith('/api/'):
            self.send_response(200)
            self.send_header('Access-Control-Allow-Origin', '*')
            self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            self.end_headers()
        else:
            super().do_OPTIONS()
    
    def proxy_api_request(self):
        try:
            # Backend server URL
            backend_url = f"http://198.91.25.229{self.path}"
            
            # Get request body if present
            content_length = int(self.headers.get('Content-Length', 0))
            request_body = self.rfile.read(content_length) if content_length > 0 else None
            
            # Create request
            req = urllib.request.Request(backend_url, data=request_body, method=self.command)
            
            # Copy headers (except Host)
            for header, value in self.headers.items():
                if header.lower() not in ['host', 'content-length']:
                    req.add_header(header, value)
            
            # Make request to backend
            with urllib.request.urlopen(req) as response:
                # Send response status
                self.send_response(response.status)
                
                # Copy response headers
                for header, value in response.headers.items():
                    if header.lower() not in ['server', 'date']:
                        self.send_header(header, value)
                
                # Add CORS headers
                self.send_header('Access-Control-Allow-Origin', '*')
                self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                
                self.end_headers()
                
                # Send response body
                self.wfile.write(response.read())
                
        except urllib.error.HTTPError as e:
            # Handle HTTP errors
            self.send_response(e.code)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            error_response = {
                'error': f'Backend error: {e.reason}',
                'status': e.code
            }
            self.wfile.write(json.dumps(error_response).encode('utf-8'))
            
        except Exception as e:
            # Handle other errors
            self.send_response(500)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            
            error_response = {
                'error': f'Proxy error: {str(e)}',
                'status': 500
            }
            self.wfile.write(json.dumps(error_response).encode('utf-8'))

if __name__ == "__main__":
    PORT = 8082
    Handler = ProxyHTTPRequestHandler
    
    with socketserver.TCPServer(('0.0.0.0', PORT), Handler) as httpd:
        print(f'Proxy server running on port {PORT}')
        httpd.serve_forever()

