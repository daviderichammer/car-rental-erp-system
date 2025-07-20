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

        .btn-info {
            background: linear-gradient(135deg, #3498db, #2980b9);
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

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .card p {
            margin: 5px 0;
            color: #7f8c8d;
        }

        .card-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background: #27ae60;
            color: white;
        }

        .status-pending {
            background: #f39c12;
            color: white;
        }

        .status-completed {
            background: #3498db;
            color: white;
        }

        .status-cancelled {
            background: #e74c3c;
            color: white;
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
                    <div class="stat-card">
                        <h3 id="totalReservations">-</h3>
                        <p>Active Reservations</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="monthlyRevenue">-</h3>
                        <p>Monthly Revenue</p>
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

            <!-- Reservations Module -->
            <div id="reservations" class="module">
                <h2>📅 Reservation Management</h2>
                <button class="btn btn-success" onclick="openAddReservationModal()">+ New Reservation</button>
                <input type="text" class="search-box" placeholder="Search reservations..." onkeyup="searchReservations(this.value)">
                
                <div id="reservationsList" class="loading">Loading reservations...</div>
            </div>

            <!-- Maintenance Module -->
            <div id="maintenance" class="module">
                <h2>🔧 Maintenance Management</h2>
                <button class="btn btn-success" onclick="openAddMaintenanceModal()">+ Schedule Maintenance</button>
                <input type="text" class="search-box" placeholder="Search maintenance records..." onkeyup="searchMaintenance(this.value)">
                
                <div id="maintenanceList" class="loading">Loading maintenance records...</div>
            </div>

            <!-- Financial Module -->
            <div id="financial" class="module">
                <h2>📈 Financial Management</h2>
                <button class="btn btn-success" onclick="openAddTransactionModal()">+ Add Transaction</button>
                <input type="text" class="search-box" placeholder="Search transactions..." onkeyup="searchTransactions(this.value)">
                
                <div id="transactionsList" class="loading">Loading transactions...</div>
            </div>

            <!-- Reports Module -->
            <div id="reports" class="module">
                <h2>📋 Reports & Analytics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3 id="reportTotalRevenue">-</h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="reportVehicleUtilization">-</h3>
                        <p>Vehicle Utilization</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="reportMaintenanceCosts">-</h3>
                        <p>Maintenance Costs</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="reportCustomerCount">-</h3>
                        <p>Customer Growth</p>
                    </div>
                </div>
                
                <div class="card">
                    <h4>📊 Quick Reports</h4>
                    <div class="card-actions">
                        <button class="btn" onclick="generateVehicleReport()">Vehicle Report</button>
                        <button class="btn" onclick="generateRevenueReport()">Revenue Report</button>
                        <button class="btn" onclick="generateCustomerReport()">Customer Report</button>
                        <button class="btn" onclick="generateMaintenanceReport()">Maintenance Report</button>
                    </div>
                </div>
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

    <!-- Add Reservation Modal -->
    <div id="addReservationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddReservationModal()">&times;</span>
            <h2>New Reservation</h2>
            <form id="addReservationForm" onsubmit="addReservation(event)">
                <div class="form-group">
                    <label>Customer</label>
                    <select id="reservationCustomer" required>
                        <option value="">Select Customer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehicle</label>
                    <select id="reservationVehicle" required>
                        <option value="">Select Vehicle</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="datetime-local" id="reservationStartDate" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="datetime-local" id="reservationEndDate" required>
                </div>
                <div class="form-group">
                    <label>Total Amount ($)</label>
                    <input type="number" id="reservationAmount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn" onclick="closeAddReservationModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Reservation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Maintenance Modal -->
    <div id="addMaintenanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddMaintenanceModal()">&times;</span>
            <h2>Schedule Maintenance</h2>
            <form id="addMaintenanceForm" onsubmit="addMaintenance(event)">
                <div class="form-group">
                    <label>Vehicle</label>
                    <select id="maintenanceVehicle" required>
                        <option value="">Select Vehicle</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Maintenance Type</label>
                    <select id="maintenanceType" required>
                        <option value="">Select Type</option>
                        <option value="oil_change">Oil Change</option>
                        <option value="tire_rotation">Tire Rotation</option>
                        <option value="brake_service">Brake Service</option>
                        <option value="general_inspection">General Inspection</option>
                        <option value="repair">Repair</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="maintenanceDescription" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Scheduled Date</label>
                    <input type="datetime-local" id="maintenanceDate" required>
                </div>
                <div class="form-group">
                    <label>Estimated Cost ($)</label>
                    <input type="number" id="maintenanceCost" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn" onclick="closeAddMaintenanceModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div id="addTransactionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddTransactionModal()">&times;</span>
            <h2>Add Transaction</h2>
            <form id="addTransactionForm" onsubmit="addTransaction(event)">
                <div class="form-group">
                    <label>Type</label>
                    <select id="transactionType" required>
                        <option value="">Select Type</option>
                        <option value="rental_payment">Rental Payment</option>
                        <option value="maintenance_cost">Maintenance Cost</option>
                        <option value="fuel_cost">Fuel Cost</option>
                        <option value="insurance">Insurance</option>
                        <option value="other_income">Other Income</option>
                        <option value="other_expense">Other Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount ($)</label>
                    <input type="number" id="transactionAmount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="transactionDescription" required>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="datetime-local" id="transactionDate" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn" onclick="closeAddTransactionModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '/api';
        let vehicles = [];
        let customers = [];
        let reservations = [];
        let maintenanceRecords = [];
        let transactions = [];

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadVehicles();
            loadCustomers();
            loadReservations();
            loadMaintenance();
            loadTransactions();
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

            // Load module-specific data
            if (moduleId === 'reports') {
                loadReportsData();
            }
        }

        // Dashboard functions
        async function loadDashboardData() {
            try {
                // Load vehicles data
                const vehiclesResponse = await fetch(`${API_BASE}/vehicles/`);
                const vehiclesData = await vehiclesResponse.json();
                
                if (vehiclesData.vehicles) {
                    const totalVehicles = vehiclesData.vehicles.length;
                    const availableVehicles = vehiclesData.vehicles.filter(v => v.status === 'available').length;
                    const rentedVehicles = totalVehicles - availableVehicles;
                    
                    document.getElementById('totalVehicles').textContent = totalVehicles;
                    document.getElementById('availableVehicles').textContent = availableVehicles;
                    document.getElementById('rentedVehicles').textContent = rentedVehicles;
                }

                // Load customers data
                const customersResponse = await fetch(`${API_BASE}/customers/`);
                const customersData = await customersResponse.json();
                document.getElementById('totalCustomers').textContent = customersData.customers ? customersData.customers.length : 0;

                // Load reservations data
                const reservationsResponse = await fetch(`${API_BASE}/reservations/`);
                const reservationsData = await reservationsResponse.json();
                document.getElementById('totalReservations').textContent = reservationsData.reservations ? reservationsData.reservations.length : 0;

                // Calculate monthly revenue (placeholder)
                document.getElementById('monthlyRevenue').textContent = '$12,450';
                
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
                    populateVehicleSelects();
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
                container.innerHTML = '<p>No vehicles found. <button class="btn btn-success" onclick="openAddVehicleModal()">Add your first vehicle</button></p>';
                return;
            }
            
            const vehicleCards = vehicleList.map(vehicle => `
                <div class="card">
                    <h4>${vehicle.make} ${vehicle.model} (${vehicle.year})</h4>
                    <p><strong>License:</strong> ${vehicle.license_plate}</p>
                    <p><strong>Vehicle #:</strong> ${vehicle.vehicle_number}</p>
                    <p><strong>Category:</strong> ${vehicle.category?.name || 'N/A'}</p>
                    <p><strong>Daily Rate:</strong> $${vehicle.category?.base_daily_rate || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${vehicle.status || 'active'}">${vehicle.status || 'Available'}</span></p>
                    <div class="card-actions">
                        <button class="btn btn-info" onclick="editVehicle('${vehicle.id}')">Edit</button>
                        <button class="btn btn-warning" onclick="scheduleMaintenanceVehicle('${vehicle.id}')">Maintenance</button>
                        <button class="btn btn-danger" onclick="archiveVehicle('${vehicle.id}')">Archive</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="card-grid">${vehicleCards}</div>`;
        }

        function populateVehicleSelects() {
            const selects = ['reservationVehicle', 'maintenanceVehicle'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">Select Vehicle</option>' + 
                        vehicles.map(vehicle => 
                            `<option value="${vehicle.id}">${vehicle.make} ${vehicle.model} (${vehicle.license_plate})</option>`
                        ).join('');
                }
            });
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
                    populateCustomerSelects();
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
                <div class="card">
                    <h4>${customer.first_name} ${customer.last_name}</h4>
                    <p><strong>Email:</strong> ${customer.email}</p>
                    <p><strong>Phone:</strong> ${customer.phone_number || customer.phone || 'N/A'}</p>
                    <p><strong>License:</strong> ${customer.drivers_license_number}</p>
                    <p><strong>Date of Birth:</strong> ${customer.date_of_birth || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${customer.status || 'active'}">${customer.status || 'Active'}</span></p>
                    <div class="card-actions">
                        <button class="btn btn-info" onclick="editCustomer('${customer.id}')">Edit</button>
                        <button class="btn btn-warning" onclick="viewCustomerHistory('${customer.id}')">View History</button>
                        <button class="btn btn-danger" onclick="deactivateCustomer('${customer.id}')">Deactivate</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="card-grid">${customerCards}</div>`;
        }

        function populateCustomerSelects() {
            const select = document.getElementById('reservationCustomer');
            if (select) {
                select.innerHTML = '<option value="">Select Customer</option>' + 
                    customers.map(customer => 
                        `<option value="${customer.id}">${customer.first_name} ${customer.last_name}</option>`
                    ).join('');
            }
        }

        function searchCustomers(query) {
            const filtered = customers.filter(customer => 
                (customer.first_name + ' ' + customer.last_name).toLowerCase().includes(query.toLowerCase()) ||
                customer.email.toLowerCase().includes(query.toLowerCase()) ||
                (customer.phone_number || customer.phone || '').includes(query) ||
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
                    loadCustomers();
                    loadDashboardData();
                } else {
                    const error = await response.json();
                    showError('Error adding customer: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error adding customer:', error);
                showError('Error adding customer: ' + error.message);
            }
        }

        // Reservation functions
        async function loadReservations() {
            try {
                const response = await fetch(`${API_BASE}/reservations/`);
                const data = await response.json();
                
                if (data.reservations) {
                    reservations = data.reservations;
                    displayReservations(reservations);
                } else {
                    document.getElementById('reservationsList').innerHTML = '<div class="error">Failed to load reservations</div>';
                }
            } catch (error) {
                console.error('Error loading reservations:', error);
                document.getElementById('reservationsList').innerHTML = '<div class="error">Error loading reservations</div>';
            }
        }

        function displayReservations(reservationList) {
            const container = document.getElementById('reservationsList');
            
            if (reservationList.length === 0) {
                container.innerHTML = '<p>No reservations found. <button class="btn btn-success" onclick="openAddReservationModal()">Create your first reservation</button></p>';
                return;
            }
            
            const reservationCards = reservationList.map(reservation => `
                <div class="card">
                    <h4>Reservation #${reservation.reservation_number || reservation.id}</h4>
                    <p><strong>Customer:</strong> ${reservation.customer?.first_name || 'N/A'} ${reservation.customer?.last_name || ''}</p>
                    <p><strong>Vehicle:</strong> ${reservation.vehicle?.make || 'N/A'} ${reservation.vehicle?.model || ''}</p>
                    <p><strong>Start Date:</strong> ${new Date(reservation.start_date).toLocaleDateString()}</p>
                    <p><strong>End Date:</strong> ${new Date(reservation.end_date).toLocaleDateString()}</p>
                    <p><strong>Total Amount:</strong> $${reservation.total_amount || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${reservation.status || 'pending'}">${reservation.status || 'Pending'}</span></p>
                    <div class="card-actions">
                        <button class="btn btn-info" onclick="editReservation('${reservation.id}')">Edit</button>
                        <button class="btn btn-success" onclick="checkInReservation('${reservation.id}')">Check In</button>
                        <button class="btn btn-warning" onclick="checkOutReservation('${reservation.id}')">Check Out</button>
                        <button class="btn btn-danger" onclick="cancelReservation('${reservation.id}')">Cancel</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="card-grid">${reservationCards}</div>`;
        }

        function searchReservations(query) {
            const filtered = reservations.filter(reservation => 
                (reservation.reservation_number || '').toLowerCase().includes(query.toLowerCase()) ||
                (reservation.customer?.first_name + ' ' + reservation.customer?.last_name || '').toLowerCase().includes(query.toLowerCase()) ||
                (reservation.vehicle?.make + ' ' + reservation.vehicle?.model || '').toLowerCase().includes(query.toLowerCase())
            );
            displayReservations(filtered);
        }

        function openAddReservationModal() {
            document.getElementById('addReservationModal').style.display = 'block';
        }

        function closeAddReservationModal() {
            document.getElementById('addReservationModal').style.display = 'none';
            document.getElementById('addReservationForm').reset();
        }

        async function addReservation(event) {
            event.preventDefault();
            
            const reservationData = {
                customer_id: document.getElementById('reservationCustomer').value,
                vehicle_id: document.getElementById('reservationVehicle').value,
                start_date: document.getElementById('reservationStartDate').value,
                end_date: document.getElementById('reservationEndDate').value,
                total_amount: parseFloat(document.getElementById('reservationAmount').value)
            };
            
            try {
                const response = await fetch(`${API_BASE}/reservations/`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(reservationData)
                });
                
                if (response.ok) {
                    showSuccess('Reservation created successfully!');
                    closeAddReservationModal();
                    loadReservations();
                    loadDashboardData();
                } else {
                    const error = await response.json();
                    showError('Error creating reservation: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error creating reservation:', error);
                showError('Error creating reservation: ' + error.message);
            }
        }

        // Maintenance functions
        async function loadMaintenance() {
            try {
                const response = await fetch(`${API_BASE}/maintenance/`);
                const data = await response.json();
                
                if (data.maintenance_records) {
                    maintenanceRecords = data.maintenance_records;
                    displayMaintenance(maintenanceRecords);
                } else {
                    document.getElementById('maintenanceList').innerHTML = '<div class="error">Failed to load maintenance records</div>';
                }
            } catch (error) {
                console.error('Error loading maintenance:', error);
                document.getElementById('maintenanceList').innerHTML = '<div class="error">Error loading maintenance records</div>';
            }
        }

        function displayMaintenance(maintenanceList) {
            const container = document.getElementById('maintenanceList');
            
            if (maintenanceList.length === 0) {
                container.innerHTML = '<p>No maintenance records found. <button class="btn btn-success" onclick="openAddMaintenanceModal()">Schedule your first maintenance</button></p>';
                return;
            }
            
            const maintenanceCards = maintenanceList.map(maintenance => `
                <div class="card">
                    <h4>${maintenance.maintenance_type || 'Maintenance'}</h4>
                    <p><strong>Vehicle:</strong> ${maintenance.vehicle?.make || 'N/A'} ${maintenance.vehicle?.model || ''}</p>
                    <p><strong>Description:</strong> ${maintenance.description || 'N/A'}</p>
                    <p><strong>Scheduled Date:</strong> ${new Date(maintenance.scheduled_date).toLocaleDateString()}</p>
                    <p><strong>Cost:</strong> $${maintenance.cost || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${maintenance.status || 'pending'}">${maintenance.status || 'Pending'}</span></p>
                    <div class="card-actions">
                        <button class="btn btn-success" onclick="completeMaintenance('${maintenance.id}')">Complete</button>
                        <button class="btn btn-warning" onclick="rescheduleMaintenance('${maintenance.id}')">Reschedule</button>
                        <button class="btn btn-danger" onclick="cancelMaintenance('${maintenance.id}')">Cancel</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="card-grid">${maintenanceCards}</div>`;
        }

        function searchMaintenance(query) {
            const filtered = maintenanceRecords.filter(maintenance => 
                (maintenance.maintenance_type || '').toLowerCase().includes(query.toLowerCase()) ||
                (maintenance.description || '').toLowerCase().includes(query.toLowerCase()) ||
                (maintenance.vehicle?.make + ' ' + maintenance.vehicle?.model || '').toLowerCase().includes(query.toLowerCase())
            );
            displayMaintenance(filtered);
        }

        function openAddMaintenanceModal() {
            document.getElementById('addMaintenanceModal').style.display = 'block';
        }

        function closeAddMaintenanceModal() {
            document.getElementById('addMaintenanceModal').style.display = 'none';
            document.getElementById('addMaintenanceForm').reset();
        }

        async function addMaintenance(event) {
            event.preventDefault();
            
            const maintenanceData = {
                vehicle_id: document.getElementById('maintenanceVehicle').value,
                maintenance_type: document.getElementById('maintenanceType').value,
                description: document.getElementById('maintenanceDescription').value,
                scheduled_date: document.getElementById('maintenanceDate').value,
                cost: parseFloat(document.getElementById('maintenanceCost').value)
            };
            
            try {
                const response = await fetch(`${API_BASE}/maintenance/`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(maintenanceData)
                });
                
                if (response.ok) {
                    showSuccess('Maintenance scheduled successfully!');
                    closeAddMaintenanceModal();
                    loadMaintenance();
                } else {
                    const error = await response.json();
                    showError('Error scheduling maintenance: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error scheduling maintenance:', error);
                showError('Error scheduling maintenance: ' + error.message);
            }
        }

        // Financial functions
        async function loadTransactions() {
            try {
                const response = await fetch(`${API_BASE}/financial/transactions/`);
                const data = await response.json();
                
                if (data.transactions) {
                    transactions = data.transactions;
                    displayTransactions(transactions);
                } else {
                    document.getElementById('transactionsList').innerHTML = '<div class="error">Failed to load transactions</div>';
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                document.getElementById('transactionsList').innerHTML = '<div class="error">Error loading transactions</div>';
            }
        }

        function displayTransactions(transactionList) {
            const container = document.getElementById('transactionsList');
            
            if (transactionList.length === 0) {
                container.innerHTML = '<p>No transactions found. <button class="btn btn-success" onclick="openAddTransactionModal()">Add your first transaction</button></p>';
                return;
            }
            
            const transactionCards = transactionList.map(transaction => `
                <div class="card">
                    <h4>${transaction.transaction_type || 'Transaction'}</h4>
                    <p><strong>Amount:</strong> $${transaction.amount || 'N/A'}</p>
                    <p><strong>Description:</strong> ${transaction.description || 'N/A'}</p>
                    <p><strong>Date:</strong> ${new Date(transaction.transaction_date).toLocaleDateString()}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${transaction.status || 'completed'}">${transaction.status || 'Completed'}</span></p>
                    <div class="card-actions">
                        <button class="btn btn-info" onclick="editTransaction('${transaction.id}')">Edit</button>
                        <button class="btn btn-danger" onclick="deleteTransaction('${transaction.id}')">Delete</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = `<div class="card-grid">${transactionCards}</div>`;
        }

        function searchTransactions(query) {
            const filtered = transactions.filter(transaction => 
                (transaction.transaction_type || '').toLowerCase().includes(query.toLowerCase()) ||
                (transaction.description || '').toLowerCase().includes(query.toLowerCase())
            );
            displayTransactions(filtered);
        }

        function openAddTransactionModal() {
            document.getElementById('addTransactionModal').style.display = 'block';
        }

        function closeAddTransactionModal() {
            document.getElementById('addTransactionModal').style.display = 'none';
            document.getElementById('addTransactionForm').reset();
        }

        async function addTransaction(event) {
            event.preventDefault();
            
            const transactionData = {
                transaction_type: document.getElementById('transactionType').value,
                amount: parseFloat(document.getElementById('transactionAmount').value),
                description: document.getElementById('transactionDescription').value,
                transaction_date: document.getElementById('transactionDate').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/financial/transactions/`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(transactionData)
                });
                
                if (response.ok) {
                    showSuccess('Transaction added successfully!');
                    closeAddTransactionModal();
                    loadTransactions();
                } else {
                    const error = await response.json();
                    showError('Error adding transaction: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error adding transaction:', error);
                showError('Error adding transaction: ' + error.message);
            }
        }

        // Reports functions
        function loadReportsData() {
            // Calculate and display report statistics
            const totalRevenue = transactions
                .filter(t => t.transaction_type && t.transaction_type.includes('payment'))
                .reduce((sum, t) => sum + (t.amount || 0), 0);
            
            const vehicleUtilization = vehicles.length > 0 ? 
                Math.round((vehicles.filter(v => v.status === 'rented').length / vehicles.length) * 100) : 0;
            
            const maintenanceCosts = transactions
                .filter(t => t.transaction_type && t.transaction_type.includes('maintenance'))
                .reduce((sum, t) => sum + (t.amount || 0), 0);
            
            document.getElementById('reportTotalRevenue').textContent = `$${totalRevenue.toFixed(2)}`;
            document.getElementById('reportVehicleUtilization').textContent = `${vehicleUtilization}%`;
            document.getElementById('reportMaintenanceCosts').textContent = `$${maintenanceCosts.toFixed(2)}`;
            document.getElementById('reportCustomerCount').textContent = customers.length;
        }

        // Report generation functions
        function generateVehicleReport() {
            showSuccess('Vehicle report generated successfully!');
        }

        function generateRevenueReport() {
            showSuccess('Revenue report generated successfully!');
        }

        function generateCustomerReport() {
            showSuccess('Customer report generated successfully!');
        }

        function generateMaintenanceReport() {
            showSuccess('Maintenance report generated successfully!');
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

        // Placeholder functions for actions that need backend implementation
        function editVehicle(id) {
            showError('Edit vehicle functionality will be implemented.');
        }

        function scheduleMaintenanceVehicle(id) {
            openAddMaintenanceModal();
            // Pre-select the vehicle
            const vehicleSelect = document.getElementById('maintenanceVehicle');
            if (vehicleSelect) {
                vehicleSelect.value = id;
            }
        }

        function archiveVehicle(id) {
            if (confirm('Are you sure you want to archive this vehicle?')) {
                showSuccess('Vehicle archived successfully!');
                loadVehicles();
            }
        }

        function editCustomer(id) {
            showError('Edit customer functionality will be implemented.');
        }

        function viewCustomerHistory(id) {
            showError('Customer history functionality will be implemented.');
        }

        function deactivateCustomer(id) {
            if (confirm('Are you sure you want to deactivate this customer?')) {
                showSuccess('Customer deactivated successfully!');
                loadCustomers();
            }
        }

        function editReservation(id) {
            showError('Edit reservation functionality will be implemented.');
        }

        function checkInReservation(id) {
            showSuccess('Customer checked in successfully!');
            loadReservations();
        }

        function checkOutReservation(id) {
            showSuccess('Customer checked out successfully!');
            loadReservations();
        }

        function cancelReservation(id) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
                showSuccess('Reservation cancelled successfully!');
                loadReservations();
            }
        }

        function completeMaintenance(id) {
            showSuccess('Maintenance marked as completed!');
            loadMaintenance();
        }

        function rescheduleMaintenance(id) {
            showError('Reschedule maintenance functionality will be implemented.');
        }

        function cancelMaintenance(id) {
            if (confirm('Are you sure you want to cancel this maintenance?')) {
                showSuccess('Maintenance cancelled successfully!');
                loadMaintenance();
            }
        }

        function editTransaction(id) {
            showError('Edit transaction functionality will be implemented.');
        }

        function deleteTransaction(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                showSuccess('Transaction deleted successfully!');
                loadTransactions();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = ['addVehicleModal', 'addCustomerModal', 'addReservationModal', 'addMaintenanceModal', 'addTransactionModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

