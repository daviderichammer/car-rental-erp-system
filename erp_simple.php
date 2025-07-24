<?php
// Simple Car Rental ERP System
// Mobile-friendly with minimal JavaScript

// Database configuration
$host = 'localhost';
$dbname = 'car_rental_erp';
$username = 'root';
$password = 'SecureRootPass123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
$message = '';
$messageType = '';
$debugInfo = '';

// Debug: Show all POST data on page
if ($_POST) {
    $debugInfo = "DEBUG: POST data received: " . print_r($_POST, true);
    $action = $_POST['action'] ?? '';
    $debugInfo .= "\nDEBUG: Action = " . $action;
    
    switch ($action) {
        case 'add_vehicle':
            try {
                $debugInfo .= "\nDEBUG: Attempting to add vehicle...";
                $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, license_plate, daily_rate, status) VALUES (?, ?, ?, ?, ?, 'available')");
                $result = $stmt->execute([
                    $_POST['make'],
                    $_POST['model'],
                    $_POST['year'],
                    $_POST['license_plate'],
                    $_POST['daily_rate']
                ]);
                $debugInfo .= "\nDEBUG: Insert result = " . ($result ? 'SUCCESS' : 'FAILED');
                $message = "Vehicle added successfully!";
                $messageType = "success";
            } catch(PDOException $e) {
                $debugInfo .= "\nDEBUG: Database error = " . $e->getMessage();
                $message = "Error adding vehicle: " . $e->getMessage();
                $messageType = "error";
            }
            break;
            
        case 'add_customer':
            try {
                $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, date_of_birth, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['date_of_birth']
                ]);
                $message = "Customer added successfully!";
                $messageType = "success";
            } catch(PDOException $e) {
                $message = "Error adding customer: " . $e->getMessage();
                $messageType = "error";
            }
            break;
            
        case 'add_maintenance':
            try {
                $stmt = $pdo->prepare("INSERT INTO maintenance_schedules (vehicle_id, maintenance_type, description, scheduled_date, estimated_cost, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
                $stmt->execute([
                    $_POST['vehicle_id'],
                    $_POST['maintenance_type'],
                    $_POST['description'],
                    $_POST['scheduled_date'],
                    $_POST['estimated_cost']
                ]);
                $message = "Maintenance scheduled successfully!";
                $messageType = "success";
            } catch(PDOException $e) {
                $message = "Error scheduling maintenance: " . $e->getMessage();
                $messageType = "error";
            }
            break;
            
        case 'add_transaction':
            try {
                $stmt = $pdo->prepare("INSERT INTO financial_transactions (type, amount, description, transaction_date, status) VALUES (?, ?, ?, CURDATE(), 'completed')");
                $stmt->execute([
                    $_POST['type'],
                    $_POST['amount'],
                    $_POST['description']
                ]);
                $message = "Transaction added successfully!";
                $messageType = "success";
            } catch(PDOException $e) {
                $message = "Error adding transaction: " . $e->getMessage();
                $messageType = "error";
            }
            break;
    }
}

// Fetch data for display
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
$maintenance = $pdo->query("SELECT m.*, v.make, v.model FROM maintenance_schedules m LEFT JOIN vehicles v ON m.vehicle_id = v.id ORDER BY m.id DESC")->fetchAll();
$transactions = $pdo->query("SELECT * FROM financial_transactions ORDER BY id DESC")->fetchAll();

// Get counts for dashboard
$vehicleCount = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$availableVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(amount) FROM financial_transactions WHERE type = 'payment'")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental ERP - Simple & Mobile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 10px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .nav-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            min-width: 120px;
        }
        
        .nav-btn:hover, .nav-btn.active {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .add-btn {
            background: #48bb78;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .add-btn:hover {
            background: #38a169;
            transform: translateY(-2px);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .submit-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: #5a67d8;
        }
        
        .data-grid {
            display: grid;
            gap: 15px;
        }
        
        .data-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .data-card h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .data-card p {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 15px;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
            }
            
            .nav-btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 10px;
                max-width: calc(100% - 20px);
            }
        }
        
        @media (max-width: 480px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .stat-number {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Car Rental ERP System</h1>
            <p>Simple, Mobile-Friendly Management</p>
            
            <div class="nav">
                <button class="nav-btn active" onclick="showSection('dashboard')">📊 Dashboard</button>
                <button class="nav-btn" onclick="showSection('vehicles')">🚗 Vehicles</button>
                <button class="nav-btn" onclick="showSection('customers')">👥 Customers</button>
                <button class="nav-btn" onclick="showSection('maintenance')">🔧 Maintenance</button>
                <button class="n    <div class="container">
        <h1>🚗 Car Rental ERP System</h1>
        
        <!-- Debug Information -->
        <?php if ($debugInfo): ?>
        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-family: monospace; white-space: pre-wrap;">
            <?php echo htmlspecialchars($debugInfo); ?>
        </div>
        <?php endif; ?>
        
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>      
        <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
            <h2>📊 Dashboard Overview</h2>
            <div class="dashboard">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $vehicleCount; ?></div>
                    <div class="stat-label">Total Vehicles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $availableVehicles; ?></div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $customerCount; ?></div>
                    <div class="stat-label">Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($totalRevenue, 0); ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
        </div>
        
        <!-- Vehicles Section -->
        <div id="vehicles" class="section">
            <h2>🚗 Vehicle Management</h2>
            
            <button class="add-btn" onclick="showModal('vehicleModal')">+ Add Vehicle</button>
            
            <div class="data-grid">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="data-card">
                        <h4><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'); ?></h4>
                        <p><strong>License:</strong> <?php echo htmlspecialchars($vehicle['license_plate']); ?></p>
                        <p><strong>Daily Rate:</strong> $<?php echo htmlspecialchars($vehicle['daily_rate']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($vehicle['status'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Customers Section -->
        <div id="customers" class="section">
            <h2>👥 Customer Management</h2>
            
            <button class="add-btn" onclick="showModal('customerModal')">+ Add Customer</button>
            
            <div class="data-grid">
                <?php foreach ($customers as $customer): ?>
                    <div class="data-card">
                        <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                        <p><strong>DOB:</strong> <?php echo htmlspecialchars($customer['date_of_birth']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($customer['status'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Maintenance Section -->
        <div id="maintenance" class="section">
            <h2>🔧 Maintenance Management</h2>
            
            <button class="add-btn" onclick="showModal('maintenanceModal')">+ Schedule Maintenance</button>
            
            <div class="data-grid">
                <?php foreach ($maintenance as $maint): ?>
                    <div class="data-card">
                        <h4><?php echo htmlspecialchars($maint['maintenance_type']); ?></h4>
                        <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($maint['make'] . ' ' . $maint['model']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($maint['description']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($maint['scheduled_date']); ?></p>
                        <p><strong>Cost:</strong> $<?php echo htmlspecialchars($maint['estimated_cost']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($maint['status'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Financial Section -->
        <div id="financial" class="section">
            <h2>💰 Financial Management</h2>
            
            <button class="add-btn" onclick="showModal('transactionModal')">+ Add Transaction</button>
            
            <div class="data-grid">
                <?php foreach ($transactions as $transaction): ?>
                    <div class="data-card">
                        <h4><?php echo htmlspecialchars(ucfirst($transaction['type'])); ?></h4>
                        <p><strong>Amount:</strong> $<?php echo htmlspecialchars($transaction['amount']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($transaction['description']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($transaction['transaction_date']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Vehicle Modal -->
    <div id="vehicleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('vehicleModal')">&times;</span>
            <h2>Add New Vehicle</h2>
            <form method="POST" onsubmit="return validateVehicleForm()">
                <input type="hidden" name="action" value="add_vehicle">
                <div class="form-group">
                    <label>Make *</label>
                    <input type="text" name="make" required>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" required>
                </div>
                <div class="form-group">
                    <label>Year *</label>
                    <input type="number" name="year" min="1900" max="2030" required>
                </div>
                <div class="form-group">
                    <label>License Plate *</label>
                    <input type="text" name="license_plate" required>
                </div>
                <div class="form-group">
                    <label>Daily Rate ($) *</label>
                    <input type="number" name="daily_rate" step="0.01" min="0" required>
                </div>
                <button type="submit" class="submit-btn">Add Vehicle</button>
            </form>
        </div>
    </div>
    
    <!-- Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('customerModal')">&times;</span>
            <h2>Add New Customer</h2>
            <form method="POST" onsubmit="return validateCustomerForm()">
                <input type="hidden" name="action" value="add_customer">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth">
                </div>
                <button type="submit" class="submit-btn">Add Customer</button>
            </form>
        </div>
    </div>
    
    <!-- Maintenance Modal -->
    <div id="maintenanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('maintenanceModal')">&times;</span>
            <h2>Schedule Maintenance</h2>
            <form method="POST" onsubmit="return validateMaintenanceForm()">
                <input type="hidden" name="action" value="add_maintenance">
                <div class="form-group">
                    <label>Vehicle *</label>
                    <select name="vehicle_id" required>
                        <option value="">Select Vehicle</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>">
                                <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' - ' . $vehicle['license_plate']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Maintenance Type *</label>
                    <select name="maintenance_type" required>
                        <option value="">Select Type</option>
                        <option value="oil_change">Oil Change</option>
                        <option value="tire_rotation">Tire Rotation</option>
                        <option value="brake_service">Brake Service</option>
                        <option value="general_inspection">General Inspection</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Scheduled Date *</label>
                    <input type="date" name="scheduled_date" required>
                </div>
                <div class="form-group">
                    <label>Estimated Cost ($)</label>
                    <input type="number" name="estimated_cost" step="0.01" min="0">
                </div>
                <button type="submit" class="submit-btn">Schedule Maintenance</button>
            </form>
        </div>
    </div>
    
    <!-- Transaction Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('transactionModal')">&times;</span>
            <h2>Add Transaction</h2>
            <form method="POST" onsubmit="return validateTransactionForm()">
                <input type="hidden" name="action" value="add_transaction">
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <option value="payment">Payment</option>
                        <option value="expense">Expense</option>
                        <option value="refund">Refund</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($) *</label>
                    <input type="number" name="amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="submit-btn">Add Transaction</button>
            </form>
        </div>
    </div>
    
    <script>
        // Minimal JavaScript for enhanced UX
        
        // Section navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Form validation functions
        function validateVehicleForm() {
            const make = document.querySelector('input[name="make"]').value.trim();
            const model = document.querySelector('input[name="model"]').value.trim();
            const year = document.querySelector('input[name="year"]').value;
            const licensePlate = document.querySelector('input[name="license_plate"]').value.trim();
            const dailyRate = document.querySelector('input[name="daily_rate"]').value;
            
            if (!make || !model || !year || !licensePlate || !dailyRate) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (year < 1900 || year > 2030) {
                alert('Please enter a valid year.');
                return false;
            }
            
            if (dailyRate <= 0) {
                alert('Daily rate must be greater than 0.');
                return false;
            }
            
            return true;
        }
        
        function validateCustomerForm() {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            
            if (!firstName || !lastName || !email) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }
            
            return true;
        }
        
        function validateMaintenanceForm() {
            const vehicleId = document.querySelector('select[name="vehicle_id"]').value;
            const maintenanceType = document.querySelector('select[name="maintenance_type"]').value;
            const scheduledDate = document.querySelector('input[name="scheduled_date"]').value;
            
            if (!vehicleId || !maintenanceType || !scheduledDate) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Check if date is not in the past
            const selectedDate = new Date(scheduledDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Scheduled date cannot be in the past.');
                return false;
            }
            
            return true;
        }
        
        function validateTransactionForm() {
            const type = document.querySelector('select[name="type"]').value;
            const amount = document.querySelector('input[name="amount"]').value;
            
            if (!type || !amount) {
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (amount <= 0) {
                alert('Amount must be greater than 0.');
                return false;
            }
            
            return true;
        }
        
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.querySelector('.message.success');
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
    
    <!-- SIMPLE TEST FORM (NO JAVASCRIPT) -->
    <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 5px solid red; padding: 20px; z-index: 99999; box-shadow: 0 0 20px rgba(0,0,0,0.5);">
        <h3 style="color: red; text-align: center;">SIMPLE TEST FORM</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_vehicle">
            <div>
                <label>Make:</label>
                <input type="text" name="make" value="TestMake" required>
            </div>
            <div>
                <label>Model:</label>
                <input type="text" name="model" value="TestModel" required>
            </div>
            <div>
                <label>Year:</label>
                <input type="number" name="year" value="2024" required>
            </div>
            <div>
                <label>License:</label>
                <input type="text" name="license_plate" value="TEST-SIMPLE" required>
            </div>
            <div>
                <label>Rate:</label>
                <input type="number" name="daily_rate" value="99.99" step="0.01" required>
            </div>
            <button type="submit" style="background: red; color: white; padding: 10px;">ADD VEHICLE (NO JS)</button>
        </form>
    </div>
</body>
</html>

