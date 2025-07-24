<?php
// Simple JSON-based API for Car Rental ERP System
// This matches the existing frontend expectations

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

// Data directory
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Helper functions
function loadData($resource) {
    global $dataDir;
    $file = "$dataDir/$resource.json";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    return [];
}

function saveData($resource, $data) {
    global $dataDir;
    $file = "$dataDir/$resource.json";
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function generateId() {
    return uniqid('', true);
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

// Initialize default data if files don't exist
function initializeDefaultData() {
    // Default vehicles
    $vehicles = loadData('vehicles');
    if (empty($vehicles)) {
        $vehicles = [
            [
                'id' => generateId(),
                'make' => 'Tesla',
                'model' => 'Model 3',
                'year' => 2023,
                'license_plate' => 'TESLA-001',
                'daily_rate' => 89.99,
                'status' => 'available',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => generateId(),
                'make' => 'BMW',
                'model' => 'X5',
                'year' => 2022,
                'license_plate' => 'BMW-X5-001',
                'daily_rate' => 129.99,
                'status' => 'available',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => generateId(),
                'make' => 'Mercedes',
                'model' => 'C-Class',
                'year' => 2023,
                'license_plate' => 'MERC-C300',
                'daily_rate' => 109.99,
                'status' => 'rented',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        saveData('vehicles', $vehicles);
    }
    
    // Default customers
    $customers = loadData('customers');
    if (empty($customers)) {
        $customers = [
            [
                'id' => generateId(),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@email.com',
                'phone_number' => '555-123-4567',
                'date_of_birth' => '1985-03-15',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => generateId(),
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@email.com',
                'phone_number' => '555-987-6543',
                'date_of_birth' => '1990-07-22',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        saveData('customers', $customers);
    }
    
    // Default reservations
    $reservations = loadData('reservations');
    if (empty($reservations)) {
        $reservations = [
            [
                'id' => generateId(),
                'customer_id' => $customers[0]['id'],
                'vehicle_id' => $vehicles[2]['id'],
                'pickup_date' => '2025-07-25 10:00:00',
                'return_date' => '2025-07-27 10:00:00',
                'total_amount' => 219.98,
                'status' => 'confirmed',
                'customer_name' => 'John Doe',
                'vehicle_name' => 'Mercedes C-Class',
                'license_plate' => 'MERC-C300',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        saveData('reservations', $reservations);
    }
    
    // Default maintenance records
    $maintenance = loadData('maintenance');
    if (empty($maintenance)) {
        $maintenance = [
            [
                'id' => generateId(),
                'vehicle_id' => $vehicles[0]['id'],
                'service_type' => 'Oil Change',
                'description' => 'Regular oil change and filter replacement',
                'scheduled_date' => '2025-08-01',
                'estimated_cost' => 75.00,
                'status' => 'scheduled',
                'vehicle_name' => 'Tesla Model 3',
                'license_plate' => 'TESLA-001',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        saveData('maintenance', $maintenance);
    }
    
    // Default transactions
    $transactions = loadData('transactions');
    if (empty($transactions)) {
        $transactions = [
            [
                'id' => generateId(),
                'payment_type' => 'rental_payment',
                'customer_id' => $customers[0]['id'],
                'amount' => 150.00,
                'description' => 'Rental payment for Mercedes C-Class',
                'transaction_date' => '2025-07-23',
                'payment_method' => 'cash',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => generateId(),
                'payment_type' => 'rental_payment',
                'customer_id' => $customers[1]['id'],
                'amount' => 250.00,
                'description' => 'Weekly rental payment',
                'transaction_date' => '2025-07-20',
                'payment_method' => 'cash',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        saveData('transactions', $transactions);
    }
}

// Initialize default data
initializeDefaultData();

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
    switch ($method) {
        case 'GET':
            $vehicles = loadData('vehicles');
            sendResponse(['vehicles' => $vehicles]);
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !$input['make'] || !$input['model'] || !$input['license_plate']) {
                sendError('Missing required fields');
            }
            
            $vehicles = loadData('vehicles');
            $newVehicle = [
                'id' => generateId(),
                'make' => $input['make'],
                'model' => $input['model'],
                'year' => (int)($input['year'] ?: date('Y')),
                'license_plate' => $input['license_plate'],
                'daily_rate' => (float)($input['daily_rate'] ?: 0),
                'status' => 'available',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $vehicles[] = $newVehicle;
            saveData('vehicles', $vehicles);
            sendResponse($newVehicle, 'Vehicle created successfully', 201);
            break;
        default:
            sendError('Method not allowed', 405);
    }
}

function handleCustomers($method) {
    switch ($method) {
        case 'GET':
            $customers = loadData('customers');
            sendResponse(['customers' => $customers]);
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !$input['first_name'] || !$input['last_name'] || !$input['email']) {
                sendError('Missing required fields');
            }
            
            $customers = loadData('customers');
            $newCustomer = [
                'id' => generateId(),
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'phone_number' => $input['phone_number'] ?: '',
                'date_of_birth' => $input['date_of_birth'] ?: '',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $customers[] = $newCustomer;
            saveData('customers', $customers);
            sendResponse($newCustomer, 'Customer created successfully', 201);
            break;
        default:
            sendError('Method not allowed', 405);
    }
}

function handleReservations($method) {
    switch ($method) {
        case 'GET':
            $reservations = loadData('reservations');
            sendResponse(['reservations' => $reservations]);
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !$input['customer_id'] || !$input['vehicle_id'] || !$input['pickup_date'] || !$input['return_date']) {
                sendError('Missing required fields');
            }
            
            $reservations = loadData('reservations');
            $customers = loadData('customers');
            $vehicles = loadData('vehicles');
            
            // Find customer and vehicle names
            $customer = array_filter($customers, fn($c) => $c['id'] === $input['customer_id'])[0] ?? null;
            $vehicle = array_filter($vehicles, fn($v) => $v['id'] === $input['vehicle_id'])[0] ?? null;
            
            $newReservation = [
                'id' => generateId(),
                'customer_id' => $input['customer_id'],
                'vehicle_id' => $input['vehicle_id'],
                'pickup_date' => $input['pickup_date'],
                'return_date' => $input['return_date'],
                'total_amount' => (float)($input['total_amount'] ?: 0),
                'status' => 'pending',
                'customer_name' => $customer ? $customer['first_name'] . ' ' . $customer['last_name'] : 'Unknown',
                'vehicle_name' => $vehicle ? $vehicle['make'] . ' ' . $vehicle['model'] : 'Unknown',
                'license_plate' => $vehicle ? $vehicle['license_plate'] : 'Unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $reservations[] = $newReservation;
            saveData('reservations', $reservations);
            sendResponse($newReservation, 'Reservation created successfully', 201);
            break;
        default:
            sendError('Method not allowed', 405);
    }
}

function handleMaintenance($method) {
    switch ($method) {
        case 'GET':
            $maintenance = loadData('maintenance');
            sendResponse(['maintenance' => $maintenance]);
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !$input['vehicle_id'] || !$input['service_type'] || !$input['scheduled_date']) {
                sendError('Missing required fields');
            }
            
            $maintenance = loadData('maintenance');
            $vehicles = loadData('vehicles');
            
            // Find vehicle name
            $vehicle = array_filter($vehicles, fn($v) => $v['id'] === $input['vehicle_id'])[0] ?? null;
            
            $newMaintenance = [
                'id' => generateId(),
                'vehicle_id' => $input['vehicle_id'],
                'service_type' => $input['service_type'],
                'description' => $input['description'] ?: '',
                'scheduled_date' => $input['scheduled_date'],
                'estimated_cost' => (float)($input['estimated_cost'] ?: 0),
                'status' => 'scheduled',
                'vehicle_name' => $vehicle ? $vehicle['make'] . ' ' . $vehicle['model'] : 'Unknown',
                'license_plate' => $vehicle ? $vehicle['license_plate'] : 'Unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $maintenance[] = $newMaintenance;
            saveData('maintenance', $maintenance);
            sendResponse($newMaintenance, 'Maintenance record created successfully', 201);
            break;
        default:
            sendError('Method not allowed', 405);
    }
}

function handleTransactions($method) {
    switch ($method) {
        case 'GET':
            $transactions = loadData('transactions');
            sendResponse(['transactions' => $transactions]);
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !$input['payment_type'] || !$input['amount'] || !$input['transaction_date']) {
                sendError('Missing required fields');
            }
            
            $transactions = loadData('transactions');
            $newTransaction = [
                'id' => generateId(),
                'payment_type' => $input['payment_type'],
                'customer_id' => $input['customer_id'] ?: null,
                'amount' => (float)$input['amount'],
                'description' => $input['description'] ?: '',
                'transaction_date' => $input['transaction_date'],
                'payment_method' => $input['payment_method'] ?: 'cash',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $transactions[] = $newTransaction;
            saveData('transactions', $transactions);
            sendResponse($newTransaction, 'Transaction created successfully', 201);
            break;
        default:
            sendError('Method not allowed', 405);
    }
}
?>

