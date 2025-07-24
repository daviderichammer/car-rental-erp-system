<?php
// Simple Car Rental ERP System
// No complex JavaScript, no APIs, just HTML forms and PHP

// Database connection
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
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
            case 'add_vehicle':
                $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, license_plate, daily_rate) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['make'], $_POST['model'], $_POST['year'], $_POST['license_plate'], $_POST['daily_rate']]);
                $message = "Vehicle added successfully!";
                $message_type = "success";
                break;
                
            case 'add_customer':
                $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, date_of_birth) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['date_of_birth']]);
                $message = "Customer added successfully!";
                $message_type = "success";
                break;
                
            case 'add_maintenance':
                $stmt = $pdo->prepare("INSERT INTO maintenance_schedules (vehicle_id, maintenance_type, description, scheduled_date, estimated_cost) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['vehicle_id'], $_POST['maintenance_type'], $_POST['description'], $_POST['scheduled_date'], $_POST['estimated_cost']]);
                $message = "Maintenance scheduled successfully!";
                $message_type = "success";
                break;
                
            case 'add_transaction':
                $stmt = $pdo->prepare("INSERT INTO financial_transactions (transaction_type, amount, description, transaction_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['transaction_type'], $_POST['amount'], $_POST['description'], $_POST['transaction_date']]);
                $message = "Transaction added successfully!";
                $message_type = "success";
                break;
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get data for display
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();
$customers = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
$maintenance = $pdo->query("SELECT m.*, v.make, v.model FROM maintenance_schedules m LEFT JOIN vehicles v ON m.vehicle_id = v.id ORDER BY m.id DESC")->fetchAll();
$transactions = $pdo->query("SELECT * FROM financial_transactions ORDER BY id DESC")->fetchAll();

// Get counts for dashboard
$vehicle_count = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$customer_count = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$maintenance_count = $pdo->query("SELECT COUNT(*) FROM maintenance_schedules")->fetchColumn();
$transaction_count = $pdo->query("SELECT COUNT(*) FROM financial_transactions")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Car Rental ERP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f4f4;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .nav button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .nav button:hover {
            background: #2980b9;
        }
        
        .nav button.active {
            background: #e74c3c;
        }
        
        .section {
            background: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #3498db;
            color: white;
            padding: 1.5rem;
            text-align: center;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 1rem;
        }
        
        .btn:hover {
            background: #229954;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .table tr:hover {
            background: #f5f5f5;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .nav {
                flex-direction: column;
            }
            
            .table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚗 Simple Car Rental ERP System</h1>
        <p>Easy to use, easy to modify</p>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="nav">
            <button onclick="showSection('dashboard')" class="active" id="dashboard-btn">📊 Dashboard</button>
            <button onclick="showSection('vehicles')" id="vehicles-btn">🚗 Vehicles</button>
            <button onclick="showSection('customers')" id="customers-btn">👥 Customers</button>
            <button onclick="showSection('maintenance')" id="maintenance-btn">🔧 Maintenance</button>
            <button onclick="showSection('financial')" id="financial-btn">💰 Financial</button>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
            <h2>📊 Dashboard Overview</h2>
            <div class="dashboard">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $vehicle_count; ?></div>
                    <div>Total Vehicles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $customer_count; ?></div>
                    <div>Total Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $maintenance_count; ?></div>
                    <div>Maintenance Records</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $transaction_count; ?></div>
                    <div>Financial Transactions</div>
                </div>
            </div>
        </div>

        <!-- Vehicles Section -->
        <div id="vehicles" class="section">
            <h2>🚗 Vehicle Management</h2>
            
            <h3>Add New Vehicle</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_vehicle">
                <div class="form-row">
                    <div class="form-group">
                        <label>Make:</label>
                        <input type="text" name="make" required>
                    </div>
                    <div class="form-group">
                        <label>Model:</label>
                        <input type="text" name="model" required>
                    </div>
                    <div class="form-group">
                        <label>Year:</label>
                        <input type="number" name="year" min="1900" max="2030" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>License Plate:</label>
                        <input type="text" name="license_plate" required>
                    </div>
                    <div class="form-group">
                        <label>Daily Rate ($):</label>
                        <input type="number" name="daily_rate" step="0.01" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn">Add Vehicle</button>
            </form>

            <h3>Current Vehicles</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Make</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>License Plate</th>
                        <th>Daily Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?php echo $vehicle['id']; ?></td>
                        <td><?php echo htmlspecialchars($vehicle['make']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                        <td><?php echo $vehicle['year']; ?></td>
                        <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                        <td>$<?php echo number_format($vehicle['daily_rate'], 2); ?></td>
                        <td><?php echo ucfirst($vehicle['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Customers Section -->
        <div id="customers" class="section">
            <h2>👥 Customer Management</h2>
            
            <h3>Add New Customer</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_customer">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone:</label>
                        <input type="tel" name="phone" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Date of Birth:</label>
                    <input type="date" name="date_of_birth" required>
                </div>
                <button type="submit" class="btn">Add Customer</button>
            </form>

            <h3>Current Customers</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php echo $customer['date_of_birth']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Maintenance Section -->
        <div id="maintenance" class="section">
            <h2>🔧 Maintenance Management</h2>
            
            <h3>Schedule Maintenance</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_maintenance">
                <div class="form-row">
                    <div class="form-group">
                        <label>Vehicle:</label>
                        <select name="vehicle_id" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>">
                                <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['license_plate'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Maintenance Type:</label>
                        <select name="maintenance_type" required>
                            <option value="">Select Type</option>
                            <option value="oil_change">Oil Change</option>
                            <option value="tire_rotation">Tire Rotation</option>
                            <option value="brake_service">Brake Service</option>
                            <option value="general_inspection">General Inspection</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Scheduled Date:</label>
                        <input type="date" name="scheduled_date" required>
                    </div>
                    <div class="form-group">
                        <label>Estimated Cost ($):</label>
                        <input type="number" name="estimated_cost" step="0.01" min="0" required>
                    </div>
                </div>
                <button type="submit" class="btn">Schedule Maintenance</button>
            </form>

            <h3>Maintenance Records</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Scheduled Date</th>
                        <th>Estimated Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance as $record): ?>
                    <tr>
                        <td><?php echo $record['id']; ?></td>
                        <td><?php echo htmlspecialchars($record['make'] . ' ' . $record['model']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $record['maintenance_type'])); ?></td>
                        <td><?php echo htmlspecialchars($record['description']); ?></td>
                        <td><?php echo $record['scheduled_date']; ?></td>
                        <td>$<?php echo number_format($record['estimated_cost'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Financial Section -->
        <div id="financial" class="section">
            <h2>💰 Financial Management</h2>
            
            <h3>Add Transaction</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_transaction">
                <div class="form-row">
                    <div class="form-group">
                        <label>Transaction Type:</label>
                        <select name="transaction_type" required>
                            <option value="">Select Type</option>
                            <option value="rental_payment">Rental Payment</option>
                            <option value="maintenance_cost">Maintenance Cost</option>
                            <option value="insurance">Insurance</option>
                            <option value="fuel">Fuel</option>
                            <option value="other_income">Other Income</option>
                            <option value="other_expense">Other Expense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount ($):</label>
                        <input type="number" name="amount" step="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Transaction Date:</label>
                    <input type="date" name="transaction_date" required>
                </div>
                <button type="submit" class="btn">Add Transaction</button>
            </form>

            <h3>Transaction History</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo $transaction['id']; ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?></td>
                        <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                        <td><?php echo $transaction['transaction_date']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.nav button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected section
            document.getElementById(sectionName).classList.add('active');
            document.getElementById(sectionName + '-btn').classList.add('active');
        }
    </script>
</body>
</html>

