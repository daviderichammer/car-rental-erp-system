<?php
// MySQL-based API for Car Rental ERP System
// Connects to the existing car_rental_erp database

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'car_rental_erp';
$username = 'root';
$password = 'SecureRootPass123!';

// Get the request path and method
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the API endpoint
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Determine the resource type
$resource = '';
if (count($pathParts) >= 2 && $pathParts[0] === 'api') {
    $resource = $pathParts[1];
}

// Database connection
function getConnection() {
    global $host, $dbname, $username, $password;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        sendError('Database connection failed: ' . $e->getMessage(), 500);
    }
}

// Helper functions
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function generateVehicleNumber() {
    return 'VEH-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function sendResponse($data, $message = 'Success', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Route the request
switch ($resource) {
    case 'vehicles':
        handleVehicles($method);
        break;
    case 'customers':
        handleCustomers($method);
        break;
    case 'reservations':
        handleReservations($method);
        break;
    case 'maintenance':
        handleMaintenance($method);
        break;
    case 'transactions':
        handleTransactions($method);
        break;
    default:
        sendError('Invalid API endpoint', 404);
}

function handleVehicles($method) {
    $pdo = getConnection();
    
    switch ($method) {
        case 'GET':
            try {
                $stmt = $pdo->query("
                    SELECT v.*, vc.category_name, vc.base_daily_rate, vc.category_code
                    FROM vehicles v 
                    LEFT JOIN vehicle_categories vc ON v.category_id = vc.category_id 
                    WHERE v.is_active = 1
                    ORDER BY v.created_at DESC
                ");
                $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format the response to match frontend expectations
                $formattedVehicles = [];
                foreach ($vehicles as $vehicle) {
                    $formattedVehicles[] = [
                        'id' => $vehicle['vehicle_id'],
                        'vehicle_id' => $vehicle['vehicle_id'],
                        'vehicle_number' => $vehicle['vehicle_number'],
                        'license_plate' => $vehicle['license_plate'],
                        'make' => $vehicle['make'],
                        'model' => $vehicle['model'],
                        'year' => (int)$vehicle['year'],
                        'color' => $vehicle['color'] ?: 'Unknown',
                        'status' => $vehicle['status'],
                        'daily_rate' => $vehicle['base_daily_rate'] ?: 0,
                        'category' => [
                            'category_id' => $vehicle['category_id'],
                            'category_name' => $vehicle['category_name'] ?: 'Unknown',
                            'category_code' => $vehicle['category_code'] ?: 'UNK',
                            'base_daily_rate' => (float)($vehicle['base_daily_rate'] ?: 0)
                        ],
                        'created_at' => $vehicle['created_at'],
                        'updated_at' => $vehicle['updated_at']
                    ];
                }
                
                sendResponse(['vehicles' => $formattedVehicles]);
            } catch (PDOException $e) {
                sendError('Failed to fetch vehicles: ' . $e->getMessage(), 500);
            }
            break;
            
        case 'POST':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !$input['make'] || !$input['model'] || !$input['license_plate']) {
                    sendError('Missing required fields: make, model, license_plate');
                }
                
                // Get default category
                $stmt = $pdo->query("SELECT category_id FROM vehicle_categories LIMIT 1");
                $defaultCategory = $stmt->fetch(PDO::FETCH_ASSOC);
                $categoryId = $defaultCategory ? $defaultCategory['category_id'] : generateUUID();
                
                $vehicleId = generateUUID();
                $vehicleNumber = generateVehicleNumber();
                $vin = 'VIN' . strtoupper(substr(md5($vehicleId), 0, 14));
                
                $stmt = $pdo->prepare("
                    INSERT INTO vehicles (
                        vehicle_id, vehicle_number, license_plate, vin, category_id,
                        make, model, year, color, status, condition_rating,
                        current_mileage, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $vehicleId,
                    $vehicleNumber,
                    $input['license_plate'],
                    $vin,
                    $categoryId,
                    $input['make'],
                    $input['model'],
                    (int)($input['year'] ?: date('Y')),
                    $input['color'] ?: 'Unknown',
                    'available',
                    5, // condition_rating
                    0, // current_mileage
                    1  // is_active
                ]);
                
                $newVehicle = [
                    'vehicle_id' => $vehicleId,
                    'vehicle_number' => $vehicleNumber,
                    'license_plate' => $input['license_plate'],
                    'make' => $input['make'],
                    'model' => $input['model'],
                    'year' => (int)($input['year'] ?: date('Y')),
                    'status' => 'available',
                    'daily_rate' => (float)($input['daily_rate'] ?: 0)
                ];
                
                sendResponse($newVehicle, 'Vehicle created successfully', 201);
            } catch (PDOException $e) {
                sendError('Failed to create vehicle: ' . $e->getMessage(), 500);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function handleCustomers($method) {
    $pdo = getConnection();
    
    switch ($method) {
        case 'GET':
            try {
                $stmt = $pdo->query("
                    SELECT c.*, u.first_name, u.last_name, u.email, u.phone_number, u.date_of_birth
                    FROM customers c 
                    LEFT JOIN users u ON c.user_id = u.user_id 
                    WHERE c.is_active = 1
                    ORDER BY c.created_at DESC
                ");
                $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format the response
                $formattedCustomers = [];
                foreach ($customers as $customer) {
                    $formattedCustomers[] = [
                        'id' => $customer['customer_id'],
                        'customer_id' => $customer['customer_id'],
                        'first_name' => $customer['first_name'] ?: 'Unknown',
                        'last_name' => $customer['last_name'] ?: 'Unknown',
                        'email' => $customer['email'] ?: 'N/A',
                        'phone_number' => $customer['phone_number'] ?: 'N/A',
                        'date_of_birth' => $customer['date_of_birth'] ?: 'N/A',
                        'status' => 'active',
                        'created_at' => $customer['created_at'],
                        'updated_at' => $customer['updated_at']
                    ];
                }
                
                sendResponse(['customers' => $formattedCustomers]);
            } catch (PDOException $e) {
                sendError('Failed to fetch customers: ' . $e->getMessage(), 500);
            }
            break;
            
        case 'POST':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !$input['first_name'] || !$input['last_name'] || !$input['email']) {
                    sendError('Missing required fields: first_name, last_name, email');
                }
                
                $pdo->beginTransaction();
                
                // Create user first
                $userId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        user_id, first_name, last_name, email, phone_number, 
                        date_of_birth, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $userId,
                    $input['first_name'],
                    $input['last_name'],
                    $input['email'],
                    $input['phone_number'] ?: null,
                    $input['date_of_birth'] ?: null,
                    1
                ]);
                
                // Create customer
                $customerId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO customers (
                        customer_id, user_id, customer_number, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                
                $customerNumber = 'CUST-' . strtoupper(substr(md5($customerId), 0, 8));
                $stmt->execute([$customerId, $userId, $customerNumber, 1]);
                
                $pdo->commit();
                
                $newCustomer = [
                    'customer_id' => $customerId,
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'email' => $input['email'],
                    'phone_number' => $input['phone_number'] ?: '',
                    'date_of_birth' => $input['date_of_birth'] ?: ''
                ];
                
                sendResponse($newCustomer, 'Customer created successfully', 201);
            } catch (PDOException $e) {
                $pdo->rollBack();
                sendError('Failed to create customer: ' . $e->getMessage(), 500);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function handleReservations($method) {
    $pdo = getConnection();
    
    switch ($method) {
        case 'GET':
            try {
                $stmt = $pdo->query("
                    SELECT r.*, 
                           CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                           CONCAT(v.make, ' ', v.model) as vehicle_name,
                           v.license_plate,
                           vc.category_name
                    FROM reservations r
                    LEFT JOIN customers c ON r.customer_id = c.customer_id
                    LEFT JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN vehicles v ON r.vehicle_id = v.vehicle_id
                    LEFT JOIN vehicle_categories vc ON v.category_id = vc.category_id
                    WHERE r.is_active = 1
                    ORDER BY r.created_at DESC
                ");
                $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse(['reservations' => $reservations]);
            } catch (PDOException $e) {
                sendError('Failed to fetch reservations: ' . $e->getMessage(), 500);
            }
            break;
            
        case 'POST':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !$input['customer_id'] || !$input['vehicle_id'] || !$input['pickup_date'] || !$input['return_date']) {
                    sendError('Missing required fields: customer_id, vehicle_id, pickup_date, return_date');
                }
                
                $reservationId = generateUUID();
                $reservationNumber = 'RES' . strtoupper(substr(md5($reservationId), 0, 8));
                
                $stmt = $pdo->prepare("
                    INSERT INTO reservations (
                        reservation_id, reservation_number, customer_id, vehicle_id,
                        pickup_date, return_date, total_amount, status, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $reservationId,
                    $reservationNumber,
                    $input['customer_id'],
                    $input['vehicle_id'],
                    $input['pickup_date'],
                    $input['return_date'],
                    (float)($input['total_amount'] ?: 0),
                    'pending',
                    1
                ]);
                
                $newReservation = [
                    'reservation_id' => $reservationId,
                    'reservation_number' => $reservationNumber,
                    'customer_id' => $input['customer_id'],
                    'vehicle_id' => $input['vehicle_id'],
                    'pickup_date' => $input['pickup_date'],
                    'return_date' => $input['return_date'],
                    'total_amount' => (float)($input['total_amount'] ?: 0),
                    'status' => 'pending'
                ];
                
                sendResponse($newReservation, 'Reservation created successfully', 201);
            } catch (PDOException $e) {
                sendError('Failed to create reservation: ' . $e->getMessage(), 500);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function handleMaintenance($method) {
    $pdo = getConnection();
    
    switch ($method) {
        case 'GET':
            try {
                $stmt = $pdo->query("
                    SELECT ms.*, 
                           CONCAT(v.make, ' ', v.model) as vehicle_name,
                           v.license_plate
                    FROM maintenance_schedules ms
                    LEFT JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
                    WHERE ms.is_active = 1
                    ORDER BY ms.scheduled_date DESC
                ");
                $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse(['maintenance' => $maintenance]);
            } catch (PDOException $e) {
                sendError('Failed to fetch maintenance records: ' . $e->getMessage(), 500);
            }
            break;
            
        case 'POST':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !$input['vehicle_id'] || !$input['service_type'] || !$input['scheduled_date']) {
                    sendError('Missing required fields: vehicle_id, service_type, scheduled_date');
                }
                
                $maintenanceId = generateUUID();
                
                $stmt = $pdo->prepare("
                    INSERT INTO maintenance_schedules (
                        maintenance_id, vehicle_id, service_type, description,
                        scheduled_date, estimated_cost, status, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $maintenanceId,
                    $input['vehicle_id'],
                    $input['service_type'],
                    $input['description'] ?: '',
                    $input['scheduled_date'],
                    (float)($input['estimated_cost'] ?: 0),
                    'scheduled',
                    1
                ]);
                
                $newMaintenance = [
                    'maintenance_id' => $maintenanceId,
                    'vehicle_id' => $input['vehicle_id'],
                    'service_type' => $input['service_type'],
                    'description' => $input['description'] ?: '',
                    'scheduled_date' => $input['scheduled_date'],
                    'estimated_cost' => (float)($input['estimated_cost'] ?: 0),
                    'status' => 'scheduled'
                ];
                
                sendResponse($newMaintenance, 'Maintenance record created successfully', 201);
            } catch (PDOException $e) {
                sendError('Failed to create maintenance record: ' . $e->getMessage(), 500);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function handleTransactions($method) {
    $pdo = getConnection();
    
    switch ($method) {
        case 'GET':
            try {
                $stmt = $pdo->query("
                    SELECT p.*, 
                           CONCAT(u.first_name, ' ', u.last_name) as customer_name
                    FROM payments p
                    LEFT JOIN customers c ON p.customer_id = c.customer_id
                    LEFT JOIN users u ON c.user_id = u.user_id
                    WHERE p.is_active = 1
                    ORDER BY p.payment_date DESC
                ");
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format for frontend
                $formattedTransactions = [];
                foreach ($transactions as $transaction) {
                    $formattedTransactions[] = [
                        'id' => $transaction['payment_id'],
                        'payment_id' => $transaction['payment_id'],
                        'payment_type' => $transaction['payment_type'] ?: 'rental_payment',
                        'customer_id' => $transaction['customer_id'],
                        'amount' => (float)$transaction['amount'],
                        'description' => $transaction['description'] ?: 'N/A',
                        'transaction_date' => $transaction['payment_date'],
                        'payment_method' => $transaction['payment_method'] ?: 'cash',
                        'status' => $transaction['status'] ?: 'pending',
                        'customer_name' => $transaction['customer_name'] ?: 'Unknown',
                        'created_at' => $transaction['created_at']
                    ];
                }
                
                sendResponse(['transactions' => $formattedTransactions]);
            } catch (PDOException $e) {
                sendError('Failed to fetch transactions: ' . $e->getMessage(), 500);
            }
            break;
            
        case 'POST':
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input || !$input['payment_type'] || !$input['amount'] || !$input['transaction_date']) {
                    sendError('Missing required fields: payment_type, amount, transaction_date');
                }
                
                $paymentId = generateUUID();
                
                $stmt = $pdo->prepare("
                    INSERT INTO payments (
                        payment_id, customer_id, amount, payment_method, payment_type,
                        description, payment_date, status, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $paymentId,
                    $input['customer_id'] ?: null,
                    (float)$input['amount'],
                    $input['payment_method'] ?: 'cash',
                    $input['payment_type'],
                    $input['description'] ?: '',
                    $input['transaction_date'],
                    'pending',
                    1
                ]);
                
                $newTransaction = [
                    'payment_id' => $paymentId,
                    'payment_type' => $input['payment_type'],
                    'customer_id' => $input['customer_id'] ?: null,
                    'amount' => (float)$input['amount'],
                    'description' => $input['description'] ?: '',
                    'transaction_date' => $input['transaction_date'],
                    'payment_method' => $input['payment_method'] ?: 'cash',
                    'status' => 'pending'
                ];
                
                sendResponse($newTransaction, 'Transaction created successfully', 201);
            } catch (PDOException $e) {
                sendError('Failed to create transaction: ' . $e->getMessage(), 500);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}
?>

