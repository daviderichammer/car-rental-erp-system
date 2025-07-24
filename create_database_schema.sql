-- Car Rental ERP Database Schema
-- Simple, straightforward table structure

USE car_rental_erp;

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    daily_rate DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'rented', 'maintenance', 'archived') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Maintenance schedules table
CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_type ENUM('oil_change', 'tire_rotation', 'brake_service', 'general_inspection', 'other') NOT NULL,
    description TEXT,
    scheduled_date DATE NOT NULL,
    estimated_cost DECIMAL(10,2),
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Financial transactions table
CREATE TABLE IF NOT EXISTS financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('payment', 'expense', 'refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample data for testing
INSERT INTO vehicles (make, model, year, license_plate, daily_rate) VALUES
('Toyota', 'Camry', 2025, 'TOY-TEST-2025', 75.00),
('Tesla', 'Model 3', 2022, 'TESLA-123', 95.00),
('Honda', 'Civic', 2024, 'HON-CIV-2024', 65.00),
('BMW', 'X5', 2023, 'BMW-X5-2023', 120.00),
('Mercedes', 'C-Class', 2025, 'MERC-C-2025', 110.00);

INSERT INTO customers (first_name, last_name, email, phone, date_of_birth) VALUES
('John', 'Doe', 'john.doe@email.com', '555-123-4567', '1985-06-15'),
('Sarah', 'Wilson', 'sarah.wilson@email.com', '555-987-6543', '1990-03-22'),
('Mike', 'Johnson', 'mike.johnson@email.com', '555-456-7890', '1988-11-08'),
('Emily', 'Davis', 'emily.davis@email.com', '555-321-0987', '1992-07-14'),
('David', 'Brown', 'david.brown@email.com', '555-654-3210', '1987-12-03');

INSERT INTO maintenance_schedules (vehicle_id, maintenance_type, description, scheduled_date, estimated_cost) VALUES
(1, 'oil_change', 'Regular oil change service', '2025-08-15', 75.00),
(2, 'tire_rotation', 'Rotate tires for even wear', '2025-08-20', 45.00),
(3, 'brake_service', 'Brake pad replacement', '2025-08-25', 250.00);

INSERT INTO financial_transactions (type, amount, description, transaction_date) VALUES
('payment', 225.00, 'Vehicle rental payment - Toyota Camry', '2025-07-20'),
('payment', 380.00, 'Vehicle rental payment - Tesla Model 3', '2025-07-22'),
('expense', 75.00, 'Oil change maintenance', '2025-07-23');

