<?php
require_once 'api_config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all vehicles
            $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY created_at DESC");
            $vehicles = $stmt->fetchAll();
            
            ApiResponse::success(['vehicles' => $vehicles]);
            break;
            
        case 'POST':
            // Create new vehicle
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Validate required fields
            $required = ['make', 'model', 'year', 'license_plate', 'daily_rate'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    ApiResponse::error("Missing required field: $field", 400);
                }
            }
            
            // Check if license plate already exists
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE license_plate = ?");
            $stmt->execute([$input['license_plate']]);
            if ($stmt->fetch()) {
                ApiResponse::error('License plate already exists', 409);
            }
            
            // Insert new vehicle
            $stmt = $pdo->prepare("
                INSERT INTO vehicles (make, model, year, license_plate, daily_rate, status) 
                VALUES (?, ?, ?, ?, ?, 'available')
            ");
            
            $success = $stmt->execute([
                $input['make'],
                $input['model'],
                (int)$input['year'],
                $input['license_plate'],
                (float)$input['daily_rate']
            ]);
            
            if ($success) {
                $vehicleId = $pdo->lastInsertId();
                ApiResponse::success(['id' => $vehicleId], 'Vehicle created successfully', 201);
            } else {
                ApiResponse::error('Failed to create vehicle', 500);
            }
            break;
            
        case 'PUT':
            // Update vehicle
            $input = json_decode(file_get_contents('php://input'), true);
            $vehicleId = $_GET['id'] ?? null;
            
            if (!$vehicleId) {
                ApiResponse::error('Vehicle ID required', 400);
            }
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['make', 'model', 'year', 'license_plate', 'daily_rate', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('No valid fields to update', 400);
            }
            
            $params[] = $vehicleId;
            $sql = "UPDATE vehicles SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Vehicle updated successfully');
            } else {
                ApiResponse::error('Vehicle not found or no changes made', 404);
            }
            break;
            
        case 'DELETE':
            // Delete vehicle
            $vehicleId = $_GET['id'] ?? null;
            
            if (!$vehicleId) {
                ApiResponse::error('Vehicle ID required', 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $success = $stmt->execute([$vehicleId]);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Vehicle deleted successfully');
            } else {
                ApiResponse::error('Vehicle not found', 404);
            }
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Vehicles API Error: " . $e->getMessage());
    ApiResponse::error('Internal server error', 500, $e->getMessage());
}
?>

