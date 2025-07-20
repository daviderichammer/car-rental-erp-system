<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
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
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header h1 {
            color: #2c3e50;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .nav-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .nav-tab {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-tab.active {
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }

        .content {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            min-height: 600px;
        }

        .module {
            display: none;
        }

        .module.active {
            display: block;
        }

        .module h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 2em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin: 5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .table tr:hover {
            background: #f8f9fa;
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 15px;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-box {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 25px;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 18px;
        }

        .error {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .success {
            background: #27ae60;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .vehicle-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
        }

        .vehicle-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .vehicle-card p {
            margin: 5px 0;
            color: #7f8c8d;
        }

        .vehicle-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
                align-items: center;
            }

            .nav-tab {
                width: 200px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            .content {
                padding: 20px;
            }
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
                <h2>📊 Dashboard Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="totalVehicles">-</h3>
                        <p>Total Vehicles</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="availableVehicles">-</h3>
                        <p>Available</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="rentedVehicles">-</h3>
                        <p>Rented</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="totalCustomers">-</h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                <p><strong>Last Updated:</strong> <span id="lastUpdated">-</span></p>
            </div>

            <!-- Vehicles Module -->
            <div id="vehicles" class="module">
                <h2>🚗 Vehicle Management</h2>
                <button class="btn btn-success" onclick="openAddVehicleModal()">+ Add Vehicle</button>
                <input type="text" class="search-box" placeholder="Search vehicles..." onkeyup="searchVehicles(this.value)">
                
                <div id="vehiclesList" class="loading">Loading vehicles...</div>
            </div>

            <!-- Customers Module -->
            <div id="customers" class="module">
                <h2>👥 Customer Management</h2>
                <button class="btn btn-success" onclick="openAddCustomerModal()">+ Add Customer</button>
                <input type="text" class="search-box" placeholder="Search customers..." onkeyup="searchCustomers(this.value)">
                
                <div id="customersList" class="loading">Loading customers...</div>
            </div>

            <!-- Other modules will be implemented with real functionality -->
            <div id="reservations" class="module">
                <h2>📅 Reservation Management</h2>
                <p>Reservation management functionality will be implemented with backend integration.</p>
            </div>

            <div id="pricing" class="module">
                <h2>💰 Pricing Management</h2>
                <p>Pricing management functionality will be implemented with backend integration.</p>
            </div>

            <div id="maintenance" class="module">
                <h2>🔧 Maintenance Management</h2>
                <p>Maintenance management functionality will be implemented with backend integration.</p>
            </div>

            <div id="financial" class="module">
                <h2>📈 Financial Management</h2>
                <p>Financial management functionality will be implemented with backend integration.</p>
            </div>

            <div id="reports" class="module">
                <h2>📋 Reports & Analytics</h2>
                <p>Reports and analytics functionality will be implemented with backend integration.</p>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddVehicleModal()">&times;</span>
            <h2>Add New Vehicle</h2>
            <form id="addVehicleForm" onsubmit="addVehicle(event)">
                <div class="form-group">
                    <label>Make</label>
                    <input type="text" id="vehicleMake" required>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" id="vehicleModel" required>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" id="vehicleYear" min="1900" max="2030" required>
                </div>
                <div class="form-group">
                    <label>License Plate</label>
                    <input type="text" id="vehicleLicense" required>
                </div>
                <div class="form-group">
                    <label>Daily Rate ($)</label>
                    <input type="number" id="vehicleRate" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn" onclick="closeAddVehicleModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCustomerModal()">&times;</span>
            <h2>Add New Customer</h2>
            <form id="addCustomerForm" onsubmit="addCustomer(event)">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="customerFirstName" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="customerLastName" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="customerEmail" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" id="customerPhone" required>
                </div>
                <div class="form-group">
                    <label>Driver's License Number</label>
                    <input type="text" id="customerLicense" required>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="customerDOB" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn" onclick="closeAddCustomerModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '/api';
        let vehicles = [];
        let customers = [];

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadVehicles();
            loadCustomers();
        });

        // Navigation functions
        function showModule(moduleId) {
            // Hide all modules
            const modules = document.querySelectorAll('.module');
            modules.forEach(module => module.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected module
            document.getElementById(moduleId).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Dashboard functions
        async function loadDashboardData() {
            try {
                const response = await fetch(`${API_BASE}/vehicles/`);
                const data = await response.json();
                
                if (data.vehicles) {
                    const totalVehicles = data.vehicles.length;
                    const availableVehicles = data.vehicles.filter(v => v.status === 'available').length;
                    const rentedVehicles = totalVehicles - availableVehicles;
                    
                    document.getElementById('totalVehicles').textContent = totalVehicles;
                    document.getElementById('availableVehicles').textContent = availableVehicles;
                    document.getElementById('rentedVehicles').textContent = rentedVehicles;
                }
                
                // Load customer count (placeholder for now)
                document.getElementById('totalCustomers').textContent = customers.length;
                document.getElementById('lastUpdated').textContent = new Date().toLocaleString();
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Vehicle functions
        async function loadVehicles() {
            try {
                const response = await fetch(`${API_BASE}/vehicles/`);
                const data = await response.json();
                
                if (data.vehicles) {
                    vehicles = data.vehicles;
                    displayVehicles(vehicles);
                } else {
                    document.getElementById('vehiclesList').innerHTML = '<div class="error">Failed to load vehicles</div>';
                }
            } catch (error) {
                console.error('Error loading vehicles:', error);
                document.getElementById('vehiclesList').innerHTML = '<div class="error">Error loading vehicles</div>';
            }
        }

        function displayVehicles(vehicleList) {
            const container = document.getElementById('vehiclesList');
            
            if (vehicleList.length === 0) {
                container.innerHTML = '<p>No vehicles found.</p>';
                return;
            }
            
            const vehicleCards = vehicleList.map(vehicle => `
                <div class="vehicle-card">
                    <h4>${vehicle.make} ${vehicle.model} (${vehicle.year})</h4>
                    <p><strong>License:</strong> ${vehicle.license_plate}</p>
                    <p><strong>Vehicle #:</strong> ${vehicle.vehicle_number}</p>
                    <p><strong>Category:</strong> ${vehicle.category?.name || 'N/A'}</p>
                    <p><strong>Daily Rate:</strong> $${vehicle.category?.base_daily_rate || 'N/A'}</p>
                    <p><strong>Status:</strong> ${vehicle.status || 'Available'}</p>
                    <div class="vehicle-actions">
                        <button class="btn" onclick="editVehicle('${vehicle.id}')">Edit</button>
                        <button class="btn btn-warning" onclick="scheduleMaintenanceVehicle('${vehicle.id}')">Maintenance</button>
                        <button class="btn btn-danger" onclick="archiveVehicle('${vehicle.id}')">Archive</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="vehicle-grid">${vehicleCards}</div>`;
        }

        function searchVehicles(query) {
            const filtered = vehicles.filter(vehicle => 
                vehicle.make.toLowerCase().includes(query.toLowerCase()) ||
                vehicle.model.toLowerCase().includes(query.toLowerCase()) ||
                vehicle.license_plate.toLowerCase().includes(query.toLowerCase())
            );
            displayVehicles(filtered);
        }

        function openAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'block';
        }

        function closeAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'none';
            document.getElementById('addVehicleForm').reset();
        }

        async function addVehicle(event) {
            event.preventDefault();
            
            const vehicleData = {
                make: document.getElementById('vehicleMake').value,
                model: document.getElementById('vehicleModel').value,
                year: parseInt(document.getElementById('vehicleYear').value),
                license_plate: document.getElementById('vehicleLicense').value,
                daily_rate: parseFloat(document.getElementById('vehicleRate').value)
            };
            
            try {
                const response = await fetch(`${API_BASE}/vehicles/`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(vehicleData)
                });
                
                if (response.ok) {
                    closeAddVehicleModal();
                    loadVehicles();
                    loadDashboardData();
                    showSuccess('Vehicle added successfully!');
                } else {
                    const error = await response.text();
                    showError('Failed to add vehicle: ' + error);
                }
            } catch (error) {
                console.error('Error adding vehicle:', error);
                showError('Error adding vehicle: ' + error.message);
            }
        }

        // Customer functions
        async function loadCustomers() {
            try {
                const response = await fetch(`${API_BASE}/customers/`);
                const data = await response.json();
                
                if (data.customers) {
                    customers = data.customers;
                    displayCustomers(customers);
                } else {
                    document.getElementById('customersList').innerHTML = '<div class="error">Failed to load customers</div>';
                }
            } catch (error) {
                console.error('Error loading customers:', error);
                document.getElementById('customersList').innerHTML = '<div class="error">Error loading customers</div>';
            }
        }

        function displayCustomers(customerList) {
            const container = document.getElementById('customersList');
            
            if (customerList.length === 0) {
                container.innerHTML = '<p>No customers found. <button class="btn btn-success" onclick="openAddCustomerModal()">Add your first customer</button></p>';
                return;
            }
            
            const customerCards = customerList.map(customer => `
                <div class="vehicle-card">
                    <h4>${customer.first_name} ${customer.last_name}</h4>
                    <p><strong>Email:</strong> ${customer.email}</p>
                    <p><strong>Phone:</strong> ${customer.phone}</p>
                    <p><strong>License:</strong> ${customer.drivers_license_number}</p>
                    <p><strong>Date of Birth:</strong> ${customer.date_of_birth || 'N/A'}</p>
                    <p><strong>Status:</strong> ${customer.status || 'Active'}</p>
                    <div class="vehicle-actions">
                        <button class="btn" onclick="editCustomer('${customer.id}')">Edit</button>
                        <button class="btn btn-warning" onclick="viewCustomerHistory('${customer.id}')">View History</button>
                        <button class="btn btn-danger" onclick="deactivateCustomer('${customer.id}')">Deactivate</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="vehicle-grid">${customerCards}</div>`;
        }

        function searchCustomers(query) {
            const filtered = customers.filter(customer => 
                (customer.first_name + ' ' + customer.last_name).toLowerCase().includes(query.toLowerCase()) ||
                customer.email.toLowerCase().includes(query.toLowerCase()) ||
                customer.phone.includes(query) ||
                customer.drivers_license_number.toLowerCase().includes(query.toLowerCase())
            );
            displayCustomers(filtered);
        }

        function openAddCustomerModal() {
            document.getElementById('addCustomerModal').style.display = 'block';
        }

        function closeAddCustomerModal() {
            document.getElementById('addCustomerModal').style.display = 'none';
            document.getElementById('addCustomerForm').reset();
        }

        async function addCustomer(event) {
            event.preventDefault();
            
            const customerData = {
                first_name: document.getElementById('customerFirstName').value,
                last_name: document.getElementById('customerLastName').value,
                email: document.getElementById('customerEmail').value,
                phone_number: document.getElementById('customerPhone').value,
                drivers_license_number: document.getElementById('customerLicense').value,
                date_of_birth: document.getElementById('customerDOB').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/customers/`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(customerData)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    showSuccess('Customer added successfully!');
                    closeAddCustomerModal();
                    document.getElementById('addCustomerForm').reset();
                    loadCustomers(); // Reload the customer list
                    loadDashboardData(); // Update dashboard stats
                } else {
                    const error = await response.json();
                    showError('Error adding customer: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error adding customer:', error);
                showError('Error adding customer: ' + error.message);
            }
        }

        // Utility functions
        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'success';
            successDiv.textContent = message;
            document.querySelector('.content').prepend(successDiv);
            setTimeout(() => successDiv.remove(), 3000);
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.textContent = message;
            document.querySelector('.content').prepend(errorDiv);
            setTimeout(() => errorDiv.remove(), 5000);
        }

        // Placeholder functions for other actions
        function editVehicle(id) {
            showError('Edit vehicle functionality will be implemented with backend integration.');
        }

        function scheduleMaintenanceVehicle(id) {
            showError('Maintenance scheduling functionality will be implemented with backend integration.');
        }

        function archiveVehicle(id) {
            showError('Archive vehicle functionality will be implemented with backend integration.');
        }

        function editCustomer(id) {
            showError('Edit customer functionality will be implemented with backend integration.');
        }

        function viewCustomerHistory(id) {
            showError('Customer history functionality will be implemented with backend integration.');
        }

        function deactivateCustomer(id) {
            showError('Deactivate customer functionality will be implemented with backend integration.');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const vehicleModal = document.getElementById('addVehicleModal');
            const customerModal = document.getElementById('addCustomerModal');
            
            if (event.target === vehicleModal) {
                closeAddVehicleModal();
            }
            if (event.target === customerModal) {
                closeAddCustomerModal();
            }
        }
    </script>
</body>
</html>

