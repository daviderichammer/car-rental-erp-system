<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'CarRental2025!') {
        $_SESSION['authenticated'] = true;
    } else {
        // Show login form
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = 'Invalid credentials';
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Car Rental ERP - Login</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .login-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
                .form-group { margin-bottom: 15px; }
                label { display: block; margin-bottom: 5px; font-weight: bold; }
                input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
                button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
                button:hover { background: #0056b3; }
                .error { color: red; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <form method="POST" class="login-form">
                <h2>Car Rental ERP Login</h2>
                <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental ERP System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .nav-tabs {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            padding: 12px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .nav-tab:hover {
            background: #e9ecef;
        }
        
        .nav-tab.active {
            background: #007bff;
            color: white;
        }
        
        .content {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 500px;
        }
        
        .module {
            display: none;
        }
        
        .module.active {
            display: block;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .vehicle-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        
        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .vehicle-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .vehicle-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rented {
            background: #f8d7da;
            color: #721c24;
        }
        
        .vehicle-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
        }
        
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            margin: 5px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Car Rental ERP System</h1>
            <div class="nav-tabs">
                <button class="nav-tab active" onclick="showModule('dashboard')">📊 Dashboard</button>
                <button class="nav-tab" onclick="showModule('vehicles')">🚗 Vehicles</button>
                <button class="nav-tab" onclick="showModule('customers')">👥 Customers</button>
                <button class="nav-tab" onclick="showModule('reservations')">📅 Reservations</button>
                <button class="nav-tab" onclick="showModule('pricing')">💰 Pricing</button>
                <button class="nav-tab" onclick="showModule('maintenance')">🔧 Maintenance</button>
                <button class="nav-tab" onclick="showModule('financial')">📈 Financial</button>
                <button class="nav-tab" onclick="showModule('reports')">📋 Reports</button>
            </div>
        </div>
        
        <div class="content">
            <!-- Dashboard Module -->
            <div id="dashboard" class="module active">
                <h2>Dashboard Overview</h2>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="total-vehicles">Loading...</div>
                        <div class="stat-label">Total Vehicles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="available-vehicles">Loading...</div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="rented-vehicles">Loading...</div>
                        <div class="stat-label">Rented</div>
                    </div>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3>Recent Activity</h3>
                    <p>System is operational. All modules are functioning correctly.</p>
                </div>
            </div>
            
            <!-- Vehicles Module -->
            <div id="vehicles" class="module">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Vehicle Management</h2>
                    <button class="btn btn-success" onclick="openAddVehicleModal()">+ Add Vehicle</button>
                </div>
                <div id="vehicles-list">
                    <div class="loading">Loading vehicles...</div>
                </div>
            </div>
            
            <!-- Other Modules -->
            <div id="customers" class="module">
                <h2>Customer Management</h2>
                <p>Customer management functionality will be implemented here.</p>
            </div>
            
            <div id="reservations" class="module">
                <h2>Reservation Management</h2>
                <p>Reservation management functionality will be implemented here.</p>
            </div>
            
            <div id="pricing" class="module">
                <h2>Pricing Management</h2>
                <p>Pricing management functionality will be implemented here.</p>
            </div>
            
            <div id="maintenance" class="module">
                <h2>Maintenance Management</h2>
                <p>Maintenance management functionality will be implemented here.</p>
            </div>
            
            <div id="financial" class="module">
                <h2>Financial Management</h2>
                <p>Financial management functionality will be implemented here.</p>
            </div>
            
            <div id="reports" class="module">
                <h2>Reports & Analytics</h2>
                <p>Reports and analytics functionality will be implemented here.</p>
            </div>
        </div>
    </div>
    
    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddVehicleModal()">&times;</span>
            <h3>Add New Vehicle</h3>
            <form id="addVehicleForm">
                <div class="form-group">
                    <label for="make">Make</label>
                    <input type="text" id="make" name="make" required>
                </div>
                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" id="model" name="model" required>
                </div>
                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" required min="1900" max="2030">
                </div>
                <div class="form-group">
                    <label for="license_plate">License Plate</label>
                    <input type="text" id="license_plate" name="license_plate" required>
                </div>
                <div class="form-group">
                    <label for="daily_rate">Daily Rate ($)</label>
                    <input type="number" id="daily_rate" name="daily_rate" step="0.01" min="0" value="29.99" required>
                </div>
                <div id="add-vehicle-message"></div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeAddVehicleModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = '/api';
        let vehicles = [];
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadVehicles();
        });
        
        // Navigation functions
        function showModule(moduleId) {
            // Hide all modules
            document.querySelectorAll('.module').forEach(module => {
                module.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected module
            document.getElementById(moduleId).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Load module-specific data
            if (moduleId === 'vehicles') {
                loadVehicles();
            }
        }
        
        // Dashboard functions
        async function loadDashboardData() {
            try {
                const response = await fetch(`${API_BASE}/vehicles/`);
                if (!response.ok) throw new Error('Failed to fetch vehicles');
                
                const data = await response.json();
                const vehicles = data.vehicles || data; // Handle both response formats
                
                // Calculate statistics
                const total = vehicles.length;
                const available = vehicles.filter(v => v.status === 'available').length;
                const rented = total - available;
                
                // Update dashboard
                document.getElementById('total-vehicles').textContent = total;
                document.getElementById('available-vehicles').textContent = available;
                document.getElementById('rented-vehicles').textContent = rented;
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                document.getElementById('total-vehicles').textContent = 'Error';
                document.getElementById('available-vehicles').textContent = 'Error';
                document.getElementById('rented-vehicles').textContent = 'Error';
            }
        }
        
        // Vehicle functions
        async function loadVehicles() {
            const vehiclesList = document.getElementById('vehicles-list');
            vehiclesList.innerHTML = '<div class="loading">Loading vehicles...</div>';
            
            try {
                const response = await fetch(`${API_BASE}/vehicles/`);
                if (!response.ok) throw new Error('Failed to fetch vehicles');
                
                const data = await response.json();
                vehicles = data.vehicles || data; // Handle both response formats
                displayVehicles(vehicles);
                
            } catch (error) {
                console.error('Error loading vehicles:', error);
                vehiclesList.innerHTML = `<div class="error">Error loading vehicles: ${error.message}</div>`;
            }
        }
        
        function displayVehicles(vehicleList) {
            const vehiclesList = document.getElementById('vehicles-list');
            
            if (vehicleList.length === 0) {
                vehiclesList.innerHTML = '<div class="loading">No vehicles found. Add your first vehicle!</div>';
                return;
            }
            
            vehiclesList.innerHTML = vehicleList.map(vehicle => `
                <div class="vehicle-card">
                    <div class="vehicle-header">
                        <div class="vehicle-title">${vehicle.make} ${vehicle.model} (${vehicle.year})</div>
                        <div class="vehicle-status status-${vehicle.status || 'available'}">${vehicle.status || 'available'}</div>
                    </div>
                    <div class="vehicle-details">
                        <div class="detail-item">
                            <span class="detail-label">License:</span> ${vehicle.license_plate}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Vehicle #:</span> ${vehicle.vehicle_number || 'N/A'}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Category:</span> ${vehicle.category?.name || 'Economy'}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Daily Rate:</span> $${vehicle.daily_rate || vehicle.category?.base_daily_rate || '29.99'}
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Modal functions
        function openAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'block';
            document.getElementById('addVehicleForm').reset();
            document.getElementById('add-vehicle-message').innerHTML = '';
        }
        
        function closeAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'none';
        }
        
        // Form submission
        document.getElementById('addVehicleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('add-vehicle-message');
            messageDiv.innerHTML = '<div class="loading">Adding vehicle...</div>';
            
            const formData = new FormData(e.target);
            const vehicleData = {
                make: formData.get('make'),
                model: formData.get('model'),
                year: parseInt(formData.get('year')),
                license_plate: formData.get('license_plate'),
                daily_rate: parseFloat(formData.get('daily_rate'))
            };
            
            try {
                const response = await fetch(`${API_BASE}/vehicles/`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(vehicleData)
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `HTTP ${response.status}`);
                }
                
                const result = await response.json();
                messageDiv.innerHTML = '<div class="success">Vehicle added successfully!</div>';
                
                // Reset form and close modal after delay
                setTimeout(() => {
                    closeAddVehicleModal();
                    loadVehicles(); // Reload vehicle list
                    loadDashboardData(); // Update dashboard stats
                }, 1500);
                
            } catch (error) {
                console.error('Error adding vehicle:', error);
                messageDiv.innerHTML = `<div class="error">Error adding vehicle: ${error.message}</div>`;
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addVehicleModal');
            if (event.target === modal) {
                closeAddVehicleModal();
            }
        }
    </script>
</body>
</html>

