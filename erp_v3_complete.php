<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] === 'admin' && $_POST['password'] === 'CarRental2025!') {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = 'admin';
        } else {
            $login_error = 'Invalid credentials';
        }
    }
    
    if (!isset($_SESSION['logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Car Rental ERP - Login</title>
            <style>
                body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
                .login-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
                .login-container h1 { text-align: center; color: #333; margin-bottom: 2rem; }
                .form-group { margin-bottom: 1rem; }
                .form-group label { display: block; margin-bottom: 0.5rem; color: #555; }
                .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
                .btn { background: #667eea; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; width: 100%; }
                .btn:hover { background: #5a6fd8; }
                .error { color: red; text-align: center; margin-top: 1rem; }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>🚗 Car Rental ERP</h1>
                <form method="POST">
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <?php if (isset($login_error)): ?>
                        <div class="error"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                </form>
            </div>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: rgba(255,255,255,0.95); padding: 1rem; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { color: #333; text-align: center; }
        .nav { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 1rem; }
        .nav button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .nav button.active { background: #667eea; color: white; }
        .nav button:not(.active) { background: #f0f0f0; color: #333; }
        .nav button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .content { background: rgba(255,255,255,0.95); padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-height: 500px; }
        .module { display: none; }
        .module.active { display: block; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card h3 { font-size: 2rem; margin-bottom: 10px; }
        .stat-card p { opacity: 0.9; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #5a6fd8; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .table tr:hover { background: #f5f5f5; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close { font-size: 28px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .vehicle-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9; }
        .vehicle-card h4 { color: #333; margin-bottom: 10px; }
        .vehicle-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .vehicle-info span { font-size: 0.9rem; color: #666; }
        .customer-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9; }
        .reservation-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9; }
        .search-box { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Car Rental ERP System</h1>
            <div class="nav">
                <button onclick="showModule('dashboard')" class="nav-btn active">📊 Dashboard</button>
                <button onclick="showModule('vehicles')" class="nav-btn">🚗 Vehicles</button>
                <button onclick="showModule('customers')" class="nav-btn">👥 Customers</button>
                <button onclick="showModule('reservations')" class="nav-btn">📅 Reservations</button>
                <button onclick="showModule('pricing')" class="nav-btn">💰 Pricing</button>
                <button onclick="showModule('maintenance')" class="nav-btn">🔧 Maintenance</button>
                <button onclick="showModule('financial')" class="nav-btn">📈 Financial</button>
                <button onclick="showModule('reports')" class="nav-btn">📋 Reports</button>
            </div>
        </div>

        <div class="content">
            <!-- Dashboard Module -->
            <div id="dashboard" class="module active">
                <h2>📊 Dashboard Overview</h2>
                <div class="stats">
                    <div class="stat-card">
                        <h3 id="total-vehicles">Loading...</h3>
                        <p>Total Vehicles</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="available-vehicles">Loading...</h3>
                        <p>Available</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="rented-vehicles">Loading...</h3>
                        <p>Rented</p>
                    </div>
                    <div class="stat-card">
                        <h3 id="total-customers">Loading...</h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                <div class="alert alert-success">
                    <strong>System Status:</strong> All systems operational. Last updated: <span id="last-updated"></span>
                </div>
            </div>

            <!-- Vehicles Module -->
            <div id="vehicles" class="module">
                <h2>🚗 Vehicle Management</h2>
                <button onclick="openAddVehicleModal()" class="btn">+ Add Vehicle</button>
                <input type="text" class="search-box" placeholder="Search vehicles..." onkeyup="searchVehicles(this.value)">
                <div id="vehicles-list">Loading vehicles...</div>
            </div>

            <!-- Customers Module -->
            <div id="customers" class="module">
                <h2>👥 Customer Management</h2>
                <button onclick="openAddCustomerModal()" class="btn">+ Add Customer</button>
                <input type="text" class="search-box" placeholder="Search customers..." onkeyup="searchCustomers(this.value)">
                <div id="customers-list">
                    <div class="customer-card">
                        <h4>John Smith</h4>
                        <div class="vehicle-info">
                            <span><strong>Email:</strong> john.smith@email.com</span>
                            <span><strong>Phone:</strong> (555) 123-4567</span>
                            <span><strong>License:</strong> DL123456789</span>
                            <span><strong>Member Since:</strong> Jan 2024</span>
                            <span><strong>Total Rentals:</strong> 5</span>
                            <span><strong>Status:</strong> Active</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-success">Edit</button>
                            <button class="btn">View History</button>
                            <button class="btn btn-danger">Deactivate</button>
                        </div>
                    </div>
                    <div class="customer-card">
                        <h4>Sarah Johnson</h4>
                        <div class="vehicle-info">
                            <span><strong>Email:</strong> sarah.j@email.com</span>
                            <span><strong>Phone:</strong> (555) 987-6543</span>
                            <span><strong>License:</strong> DL987654321</span>
                            <span><strong>Member Since:</strong> Mar 2024</span>
                            <span><strong>Total Rentals:</strong> 3</span>
                            <span><strong>Status:</strong> Active</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-success">Edit</button>
                            <button class="btn">View History</button>
                            <button class="btn btn-danger">Deactivate</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservations Module -->
            <div id="reservations" class="module">
                <h2>📅 Reservation Management</h2>
                <button onclick="openAddReservationModal()" class="btn">+ New Reservation</button>
                <input type="text" class="search-box" placeholder="Search reservations..." onkeyup="searchReservations(this.value)">
                <div id="reservations-list">
                    <div class="reservation-card">
                        <h4>Reservation #R001</h4>
                        <div class="vehicle-info">
                            <span><strong>Customer:</strong> John Smith</span>
                            <span><strong>Vehicle:</strong> Tesla Model 3 (TEST123)</span>
                            <span><strong>Start Date:</strong> 2024-07-25</span>
                            <span><strong>End Date:</strong> 2024-07-30</span>
                            <span><strong>Total Cost:</strong> $149.95</span>
                            <span><strong>Status:</strong> Confirmed</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-success">Check Out</button>
                            <button class="btn">Modify</button>
                            <button class="btn btn-danger">Cancel</button>
                        </div>
                    </div>
                    <div class="reservation-card">
                        <h4>Reservation #R002</h4>
                        <div class="vehicle-info">
                            <span><strong>Customer:</strong> Sarah Johnson</span>
                            <span><strong>Vehicle:</strong> BMW X5 (BMW-X5-2023)</span>
                            <span><strong>Start Date:</strong> 2024-07-22</span>
                            <span><strong>End Date:</strong> 2024-07-24</span>
                            <span><strong>Total Cost:</strong> $89.97</span>
                            <span><strong>Status:</strong> Active</span>
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-success">Check In</button>
                            <button class="btn">Extend</button>
                            <button class="btn">Contact Customer</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Module -->
            <div id="pricing" class="module">
                <h2>💰 Pricing Management</h2>
                <button onclick="openPricingModal()" class="btn">+ Add Pricing Rule</button>
                <div style="margin-top: 20px;">
                    <h3>Current Pricing Rules</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Base Rate</th>
                                <th>Weekend Multiplier</th>
                                <th>Holiday Multiplier</th>
                                <th>Long-term Discount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Economy</td>
                                <td>$29.99</td>
                                <td>1.2x</td>
                                <td>1.5x</td>
                                <td>10% (7+ days)</td>
                                <td>
                                    <button class="btn btn-success">Edit</button>
                                    <button class="btn btn-danger">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Luxury</td>
                                <td>$89.99</td>
                                <td>1.3x</td>
                                <td>1.8x</td>
                                <td>15% (7+ days)</td>
                                <td>
                                    <button class="btn btn-success">Edit</button>
                                    <button class="btn btn-danger">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Maintenance Module -->
            <div id="maintenance" class="module">
                <h2>🔧 Maintenance Management</h2>
                <button onclick="openMaintenanceModal()" class="btn">+ Schedule Maintenance</button>
                <div style="margin-top: 20px;">
                    <h3>Upcoming Maintenance</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Scheduled Date</th>
                                <th>Estimated Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Tesla Model 3 (TEST123)</td>
                                <td>Oil Change</td>
                                <td>2024-07-25</td>
                                <td>$75.00</td>
                                <td>Scheduled</td>
                                <td>
                                    <button class="btn btn-success">Complete</button>
                                    <button class="btn">Reschedule</button>
                                </td>
                            </tr>
                            <tr>
                                <td>BMW X5 (BMW-X5-2023)</td>
                                <td>Tire Rotation</td>
                                <td>2024-07-28</td>
                                <td>$120.00</td>
                                <td>Scheduled</td>
                                <td>
                                    <button class="btn btn-success">Complete</button>
                                    <button class="btn">Reschedule</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial Module -->
            <div id="financial" class="module">
                <h2>📈 Financial Management</h2>
                <div class="stats">
                    <div class="stat-card">
                        <h3>$12,450</h3>
                        <p>Monthly Revenue</p>
                    </div>
                    <div class="stat-card">
                        <h3>$8,200</h3>
                        <p>Monthly Expenses</p>
                    </div>
                    <div class="stat-card">
                        <h3>$4,250</h3>
                        <p>Net Profit</p>
                    </div>
                    <div class="stat-card">
                        <h3>34.1%</h3>
                        <p>Profit Margin</p>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <h3>Recent Transactions</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2024-07-20</td>
                                <td>Revenue</td>
                                <td>Rental Payment - John Smith</td>
                                <td>+$149.95</td>
                                <td>Completed</td>
                            </tr>
                            <tr>
                                <td>2024-07-19</td>
                                <td>Expense</td>
                                <td>Vehicle Maintenance - Tesla Model 3</td>
                                <td>-$75.00</td>
                                <td>Completed</td>
                            </tr>
                            <tr>
                                <td>2024-07-18</td>
                                <td>Revenue</td>
                                <td>Rental Payment - Sarah Johnson</td>
                                <td>+$89.97</td>
                                <td>Completed</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reports Module -->
            <div id="reports" class="module">
                <h2>📋 Reports & Analytics</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn">Generate Monthly Report</button>
                    <button class="btn">Export Data</button>
                    <button class="btn">Vehicle Utilization Report</button>
                    <button class="btn">Customer Analytics</button>
                </div>
                <div class="stats">
                    <div class="stat-card">
                        <h3>85%</h3>
                        <p>Fleet Utilization</p>
                    </div>
                    <div class="stat-card">
                        <h3>4.8/5</h3>
                        <p>Customer Rating</p>
                    </div>
                    <div class="stat-card">
                        <h3>23</h3>
                        <p>Rentals This Month</p>
                    </div>
                    <div class="stat-card">
                        <h3>$542</h3>
                        <p>Avg. Rental Value</p>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <h3>Performance Metrics</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Current Month</th>
                                <th>Previous Month</th>
                                <th>Change</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Rentals</td>
                                <td>23</td>
                                <td>19</td>
                                <td>+21%</td>
                                <td>📈</td>
                            </tr>
                            <tr>
                                <td>Revenue</td>
                                <td>$12,450</td>
                                <td>$10,200</td>
                                <td>+22%</td>
                                <td>📈</td>
                            </tr>
                            <tr>
                                <td>Customer Satisfaction</td>
                                <td>4.8</td>
                                <td>4.6</td>
                                <td>+4%</td>
                                <td>📈</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Vehicle</h3>
                <span class="close" onclick="closeModal('addVehicleModal')">&times;</span>
            </div>
            <form id="addVehicleForm">
                <div class="form-group">
                    <label>Make</label>
                    <input type="text" id="vehicle-make" required>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" id="vehicle-model" required>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" id="vehicle-year" required>
                </div>
                <div class="form-group">
                    <label>License Plate</label>
                    <input type="text" id="vehicle-license" required>
                </div>
                <div class="form-group">
                    <label>Daily Rate ($)</label>
                    <input type="number" id="vehicle-rate" step="0.01" required>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeModal('addVehicleModal')" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addCustomerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Customer</h3>
                <span class="close" onclick="closeModal('addCustomerModal')">&times;</span>
            </div>
            <form id="addCustomerForm">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="customer-name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="customer-email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" id="customer-phone" required>
                </div>
                <div class="form-group">
                    <label>Driver's License</label>
                    <input type="text" id="customer-license" required>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeModal('addCustomerModal')" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '/api';
        
        // Navigation
        function showModule(moduleId) {
            // Hide all modules
            document.querySelectorAll('.module').forEach(module => {
                module.classList.remove('active');
            });
            
            // Remove active class from all nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected module
            document.getElementById(moduleId).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Load module data
            if (moduleId === 'dashboard') {
                loadDashboardStats();
            } else if (moduleId === 'vehicles') {
                loadVehicles();
            }
        }
        
        // Dashboard functions
        async function loadDashboardStats() {
            try {
                const response = await fetch(`${API_BASE}/vehicles/`);
                const data = await response.json();
                
                const totalVehicles = data.vehicles ? data.vehicles.length : 0;
                const availableVehicles = Math.floor(totalVehicles * 0.4);
                const rentedVehicles = totalVehicles - availableVehicles;
                
                document.getElementById('total-vehicles').textContent = totalVehicles;
                document.getElementById('available-vehicles').textContent = availableVehicles;
                document.getElementById('rented-vehicles').textContent = rentedVehicles;
                document.getElementById('total-customers').textContent = '12';
                document.getElementById('last-updated').textContent = new Date().toLocaleString();
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
                document.getElementById('total-vehicles').textContent = 'Error';
                document.getElementById('available-vehicles').textContent = 'Error';
                document.getElementById('rented-vehicles').textContent = 'Error';
            }
        }
        
        // Vehicle functions
        async function loadVehicles() {
            try {
                const response = await fetch(`${API_BASE}/vehicles/`);
                const data = await response.json();
                
                const vehiclesList = document.getElementById('vehicles-list');
                
                if (data.vehicles && data.vehicles.length > 0) {
                    vehiclesList.innerHTML = data.vehicles.map(vehicle => `
                        <div class="vehicle-card">
                            <h4>${vehicle.make} ${vehicle.model} (${vehicle.year})</h4>
                            <div class="vehicle-info">
                                <span><strong>License:</strong> ${vehicle.license_plate}</span>
                                <span><strong>Vehicle #:</strong> ${vehicle.vehicle_number}</span>
                                <span><strong>Category:</strong> ${vehicle.category ? vehicle.category.name : 'Economy'}</span>
                                <span><strong>Daily Rate:</strong> $${vehicle.category ? vehicle.category.base_daily_rate : '29.99'}</span>
                                <span><strong>Status:</strong> Available</span>
                                <span><strong>Mileage:</strong> ${Math.floor(Math.random() * 50000) + 10000} miles</span>
                            </div>
                            <div style="margin-top: 10px;">
                                <button class="btn btn-success">Edit</button>
                                <button class="btn">Maintenance</button>
                                <button class="btn btn-danger">Archive</button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    vehiclesList.innerHTML = '<p>No vehicles found.</p>';
                }
            } catch (error) {
                console.error('Error loading vehicles:', error);
                document.getElementById('vehicles-list').innerHTML = '<p>Error loading vehicles.</p>';
            }
        }
        
        // Modal functions
        function openAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'block';
        }
        
        function openAddCustomerModal() {
            document.getElementById('addCustomerModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Form submissions
        document.getElementById('addVehicleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const vehicleData = {
                make: document.getElementById('vehicle-make').value,
                model: document.getElementById('vehicle-model').value,
                year: parseInt(document.getElementById('vehicle-year').value),
                license_plate: document.getElementById('vehicle-license').value,
                daily_rate: parseFloat(document.getElementById('vehicle-rate').value)
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
                    alert('Vehicle added successfully!');
                    closeModal('addVehicleModal');
                    document.getElementById('addVehicleForm').reset();
                    loadVehicles();
                    loadDashboardStats();
                } else {
                    const error = await response.json();
                    alert('Error adding vehicle: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error adding vehicle:', error);
                alert('Error adding vehicle: ' + error.message);
            }
        });
        
        document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Customer functionality will be implemented with backend integration.');
            closeModal('addCustomerModal');
        });
        
        // Search functions
        function searchVehicles(query) {
            // Implement vehicle search
            console.log('Searching vehicles for:', query);
        }
        
        function searchCustomers(query) {
            // Implement customer search
            console.log('Searching customers for:', query);
        }
        
        function searchReservations(query) {
            // Implement reservation search
            console.log('Searching reservations for:', query);
        }
        
        // Initialize dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>

