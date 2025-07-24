<?php
require_once 'api_config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all reservations with customer and vehicle details
            $stmt = $pdo->query("
                SELECT r.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                       CONCAT(v.make, ' ', v.model) as vehicle_name,
                       v.license_plate
                FROM reservations r
                LEFT JOIN customers c ON r.customer_id = c.id
                LEFT JOIN vehicles v ON r.vehicle_id = v.id
                ORDER BY r.created_at DESC
            ");
            $reservations = $stmt->fetchAll();
            
            ApiResponse::success(['reservations' => $reservations]);
            break;
            
        case 'POST':
            // Create new reservation
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Validate required fields
            $required = ['customer_id', 'vehicle_id', 'pickup_date', 'return_date', 'total_amount'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    ApiResponse::error("Missing required field: $field", 400);
                }
            }
            
            // Validate dates
            $pickupDate = DateTime::createFromFormat('Y-m-d H:i:s', $input['pickup_date']);
            $returnDate = DateTime::createFromFormat('Y-m-d H:i:s', $input['return_date']);
            
            if (!$pickupDate || !$returnDate) {
                ApiResponse::error('Invalid date format. Use YYYY-MM-DD HH:MM:SS', 400);
            }
            
            if ($pickupDate >= $returnDate) {
                ApiResponse::error('Return date must be after pickup date', 400);
            }
            
            // Check if vehicle exists and is available
            $stmt = $pdo->prepare("SELECT status FROM vehicles WHERE id = ?");
            $stmt->execute([$input['vehicle_id']]);
            $vehicle = $stmt->fetch();
            
            if (!$vehicle) {
                ApiResponse::error('Vehicle not found', 404);
            }
            
            if ($vehicle['status'] !== 'available') {
                ApiResponse::error('Vehicle is not available', 409);
            }
            
            // Check if customer exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND status = 'active'");
            $stmt->execute([$input['customer_id']]);
            if (!$stmt->fetch()) {
                ApiResponse::error('Customer not found or inactive', 404);
            }
            
            // Check for conflicting reservations
            $stmt = $pdo->prepare("
                SELECT id FROM reservations 
                WHERE vehicle_id = ? 
                AND status IN ('pending', 'confirmed', 'active')
                AND (
                    (pickup_date <= ? AND return_date > ?) OR
                    (pickup_date < ? AND return_date >= ?) OR
                    (pickup_date >= ? AND return_date <= ?)
                )
            ");
            $stmt->execute([
                $input['vehicle_id'],
                $input['pickup_date'], $input['pickup_date'],
                $input['return_date'], $input['return_date'],
                $input['pickup_date'], $input['return_date']
            ]);
            
            if ($stmt->fetch()) {
                ApiResponse::error('Vehicle is already reserved for the selected dates', 409);
            }
            
            // Insert new reservation
            $stmt = $pdo->prepare("
                INSERT INTO reservations (customer_id, vehicle_id, pickup_date, return_date, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            
            $success = $stmt->execute([
                $input['customer_id'],
                $input['vehicle_id'],
                $input['pickup_date'],
                $input['return_date'],
                (float)$input['total_amount']
            ]);
            
            if ($success) {
                $reservationId = $pdo->lastInsertId();
                ApiResponse::success(['id' => $reservationId], 'Reservation created successfully', 201);
            } else {
                ApiResponse::error('Failed to create reservation', 500);
            }
            break;
            
        case 'PUT':
            // Update reservation
            $input = json_decode(file_get_contents('php://input'), true);
            $reservationId = $_GET['id'] ?? null;
            
            if (!$reservationId) {
                ApiResponse::error('Reservation ID required', 400);
            }
            
            if (!$input) {
                ApiResponse::error('Invalid JSON input', 400);
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['customer_id', 'vehicle_id', 'pickup_date', 'return_date', 'total_amount', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                ApiResponse::error('No valid fields to update', 400);
            }
            
            $params[] = $reservationId;
            $sql = "UPDATE reservations SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Reservation updated successfully');
            } else {
                ApiResponse::error('Reservation not found or no changes made', 404);
            }
            break;
            
        case 'DELETE':
            // Cancel reservation
            $reservationId = $_GET['id'] ?? null;
            
            if (!$reservationId) {
                ApiResponse::error('Reservation ID required', 400);
            }
            
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $success = $stmt->execute([$reservationId]);
            
            if ($success && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Reservation cancelled successfully');
            } else {
                ApiResponse::error('Reservation not found', 404);
            }
            break;
            
        default:
            ApiResponse::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Reservations API Error: " . $e->getMessage());
    ApiResponse::error('Internal server error', 500, $e->getMessage());
}
?>

