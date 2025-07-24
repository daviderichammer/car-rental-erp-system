<?php
/**
 * API Proxy for Car Rental ERP System
 * Forwards API requests from frontend to Flask backend
 * Resolves SSL/mixed content security issues
 */

// Enable CORS for all requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Flask API configuration
$FLASK_API_BASE = 'http://localhost:5001';

// Get the request path and remove /api prefix
$request_uri = $_SERVER['REQUEST_URI'];
$api_path = str_replace('/api', '', parse_url($request_uri, PHP_URL_PATH));
$query_string = parse_url($request_uri, PHP_URL_QUERY);

// Build the Flask API URL
$flask_url = $FLASK_API_BASE . '/api' . $api_path;
if ($query_string) {
    $flask_url .= '?' . $query_string;
}

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$input_data = null;

if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $input_data = file_get_contents('php://input');
}

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_URL => $flask_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

// Add request data for POST/PUT requests
if ($input_data) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input_data);
}

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

// Handle cURL errors
if ($curl_error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API connection failed',
        'details' => $curl_error,
        'flask_url' => $flask_url
    ]);
    exit();
}

// Set the response code and return the response
http_response_code($http_code);
echo $response;
?>

