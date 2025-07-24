<?php
require_once 'api_config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all maintenance records with vehicle details
            $stmt = $pdo->query("
                SELECT m.*, 
                       CONCAT(v.make, ' ', v.model) as vehicle_name,
                       v.license_plate
                FROM maintenance_records m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                ORDER BY m.scheduled_date DESC
            ");
            $maintenance = $stmt->fetchAll();
            
            ApiResponse::success(['maintenance' => $maintenance]);
            break;
            
        case 'POST':
            // Create new maintenance record
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Validate required fields
            $required = ['vehicle_id', 'service_type', 'scheduled_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    ApiResponse::error("Missing required field: $field", 400);
                }
            }
            
            // Validate date format
            $scheduledDate = DateTime::createFromFormat('Y-m-d', $input['scheduled_date']);
            if (!$scheduledDate) {
                ApiResponse::error('Invalid date format. Use YYYY-MM-DD', 400);
            }
            
            // Check if vehicle exists
            $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ?");
            $stmt->execute([$input['vehicle_id']]);
            if (!$stmt->fetch()) {
                ApiResponse::error('Vehicle not found', 404);
            }
            
            // Insert new maintenance record
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_records (vehicle_id, service_type, description, scheduled_date, estimated_cost, status) 
                VALUES (?, ?, ?, ?, ?, 'scheduled')
            ");
            
            $success = $stmt->execute([
                $input['vehicle_id'],
                $input['service_type'],
                $input['description'] ?? null,
                $input['scheduled_date'],
                isset($input['estimated_cost']) ? (float)$input['estimated_cost'] : null
            ]);
            
            if ($success) {
                $maintenanceId = $pdo->lastInsertId();
                ApiResponse::success(['id' => $maintenanceId], 'Maintenance record created successfully', 201);
            } else {
                ApiResponse::error('Failed to create maintenance record', 500);
            }
            break;
            
        case 'PUT':
            // Update maintenance record
            $input = json_decode(file_get_contents('php://input'), true);
            $maintenanceId = $_GET['id'] ?? null;
            
            if (!$maintenanceId) {
                ApiResponse::error('Maintenance ID required', 400);
            }
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['vehicle_id', 'service_type', 'description', 'scheduled_date', 'completed_date', 'estimated_cost', 'actual_cost', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('No valid fields to update', 400);
            }
            
            $params[] = $maintenanceId;
            $sql = "UPDATE maintenance_records SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Maintenance record updated successfully');
            } else {
                ApiResponse::error('Maintenance record not found or no changes made', 404);
            }
            break;
            
        case 'DELETE':
            // Delete maintenance record
            $maintenanceId = $_GET['id'] ?? null;
            
            if (!$maintenanceId) {
                ApiResponse::error('Maintenance ID required', 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM maintenance_records WHERE id = ?");
            $success = $stmt->execute([$maintenanceId]);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Maintenance record deleted successfully');
            } else {
                ApiResponse::error('Maintenance record not found', 404);
            }
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Maintenance API Error: " . $e->getMessage());
    ApiResponse::error('Internal server error', 500, $e->getMessage());
}
?>

