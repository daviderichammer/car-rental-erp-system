#!/usr/bin/env python3
import http.server
import socketserver
import urllib.request
import urllib.parse
import json
import os
from urllib.error import HTTPError, URLError

class ProxyHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory='/home/ubuntu', **kwargs)
    
    def end_headers(self):
        self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
        self.send_header('Pragma', 'no-cache')
        self.send_header('Expires', '0')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        super().end_headers()
    
    def do_OPTIONS(self):
        self.send_response(200)
        self.end_headers()
    
    def do_GET(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            super().do_GET()
    
    def do_POST(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            self.send_response(405)
            self.end_headers()
    
    def do_PUT(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            self.send_response(405)
            self.end_headers()
    
    def do_DELETE(self):
        if self.path.startswith('/api/'):
            self.proxy_api_request()
        else:
            self.send_response(405)
            self.end_headers()
    
    def proxy_api_request(self):
        try:
            # Backend API URL
            backend_url = f"http://198.91.25.229:5001{self.path}"
            
            # Prepare request data
            content_length = int(self.headers.get('Content-Length', 0))
            post_data = self.rfile.read(content_length) if content_length > 0 else None
            
            # Create request
            req = urllib.request.Request(backend_url, data=post_data, method=self.command)
            
            # Copy headers
            for header, value in self.headers.items():
                if header.lower() not in ['host', 'content-length']:
                    req.add_header(header, value)
            
            # Make request to backend
            try:
                with urllib.request.urlopen(req, timeout=30) as response:
                    # Send response
                    self.send_response(response.getcode())
                    
                    # Copy response headers
                    for header, value in response.headers.items():
                        if header.lower() not in ['server', 'date']:
                            self.send_header(header, value)
                    
                    self.end_headers()
                    
                    # Copy response body
                    self.wfile.write(response.read())
                    
            except HTTPError as e:
                # Handle HTTP errors from backend
                self.send_response(e.code)
                self.send_header('Content-Type', 'application/json')
                self.end_headers()
                
                error_response = {
                    "error": f"Backend error: {e.reason}",
                    "status_code": e.code
                }
                self.wfile.write(json.dumps(error_response).encode())
                
        except URLError as e:
            # Handle connection errors
            self.send_response(503)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            
            error_response = {
                "error": f"Backend connection failed: {str(e)}",
                "status_code": 503
            }
            self.wfile.write(json.dumps(error_response).encode())
            
        except Exception as e:
            # Handle other errors
            self.send_response(500)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            
            error_response = {
                "error": f"Proxy error: {str(e)}",
                "status_code": 500
            }
            self.wfile.write(json.dumps(error_response).encode())

if __name__ == "__main__":
    PORT = 8085
    Handler = ProxyHTTPRequestHandler
    
    with socketserver.TCPServer(("", PORT), Handler) as httpd:
        print(f"Proxy server running at port {PORT}")
        print(f"Frontend: http://localhost:{PORT}/complete_erp.html")
        print(f"API Proxy: http://localhost:{PORT}/api/* -> http://198.91.25.229:5001/api/*")
        httpd.serve_forever()

