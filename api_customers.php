<?php
require_once 'api_config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all customers
            $stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
            $customers = $stmt->fetchAll();
            
            ApiResponse::success(['customers' => $customers]);
            break;
            
        case 'POST':
            // Create new customer
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Validate required fields
            $required = ['first_name', 'last_name', 'email'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    ApiResponse::error("Missing required field: $field", 400);
                }
            }
            
            // Validate email format
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                ApiResponse::error('Invalid email format', 400);
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                ApiResponse::error('Email already exists', 409);
            }
            
            // Insert new customer
            $stmt = $pdo->prepare("
                INSERT INTO customers (first_name, last_name, email, phone_number, date_of_birth, license_number, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $success = $stmt->execute([
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                $input['phone_number'] ?? null,
                $input['date_of_birth'] ?? null,
                $input['license_number'] ?? null
            ]);
            
            if ($success) {
                $customerId = $pdo->lastInsertId();
                ApiResponse::success(['id' => $customerId], 'Customer created successfully', 201);
            } else {
                ApiResponse::error('Failed to create customer', 500);
            }
            break;
            
        case 'PUT':
            // Update customer
            $input = json_decode(file_get_contents('php://input'), true);
            $customerId = $_GET['id'] ?? null;
            
            if (!$customerId) {
                ApiResponse::error('Customer ID required', 400);
            }
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['first_name', 'last_name', 'email', 'phone_number', 'date_of_birth', 'license_number', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('No valid fields to update', 400);
            }
            
            $params[] = $customerId;
            $sql = "UPDATE customers SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Customer updated successfully');
            } else {
                ApiResponse::error('Customer not found or no changes made', 404);
            }
            break;
            
        case 'DELETE':
            // Delete customer (soft delete by setting status to inactive)
            $customerId = $_GET['id'] ?? null;
            
            if (!$customerId) {
                ApiResponse::error('Customer ID required', 400);
            }
            
            $stmt = $pdo->prepare("UPDATE customers SET status = 'inactive' WHERE id = ?");
            $success = $stmt->execute([$customerId]);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Customer deactivated successfully');
            } else {
                ApiResponse::error('Customer not found', 404);
            }
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Customers API Error: " . $e->getMessage());
    ApiResponse::error('Internal server error', 500, $e->getMessage());
}
?>

