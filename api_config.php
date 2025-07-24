<?php
// Database configuration for Car Rental ERP API
class DatabaseConfig {
    private static $host = 'localhost';
    private static $dbname = 'car_rental_erp';
    private static $username = 'root';
    private static $password = 'Dogebag1!';
    private static $pdo = null;
    
    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return self::$pdo;
    }
    
    public static function createTables() {
        $pdo = self::getConnection();
        
        // Create vehicles table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vehicles (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                make VARCHAR(100) NOT NULL,
                model VARCHAR(100) NOT NULL,
                year INT NOT NULL,
                license_plate VARCHAR(20) UNIQUE NOT NULL,
                daily_rate DECIMAL(10,2) NOT NULL,
                status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create customers table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS customers (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                phone_number VARCHAR(20),
                date_of_birth DATE,
                license_number VARCHAR(50),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create reservations table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reservations (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                customer_id VARCHAR(36) NOT NULL,
                vehicle_id VARCHAR(36) NOT NULL,
                pickup_date DATETIME NOT NULL,
                return_date DATETIME NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
            )
        ");
        
        // Create maintenance_records table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS maintenance_records (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                vehicle_id VARCHAR(36) NOT NULL,
                service_type VARCHAR(100) NOT NULL,
                description TEXT,
                scheduled_date DATE NOT NULL,
                completed_date DATE NULL,
                estimated_cost DECIMAL(10,2),
                actual_cost DECIMAL(10,2),
                status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
            )
        ");
        
        // Create financial_transactions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS financial_transactions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                payment_type ENUM('rental_payment', 'maintenance_cost', 'fuel_cost', 'insurance', 'other_income', 'other_expense') NOT NULL,
                customer_id VARCHAR(36) NULL,
                amount DECIMAL(10,2) NOT NULL,
                description TEXT,
                transaction_date DATE NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'cash',
                status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
            )
        ");
        
        return true;
    }
}

// Response helper class
class ApiResponse {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
    public static function error($message = 'Error', $code = 400, $details = null) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'details' => $details
        ]);
        exit;
    }
}

// CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>

