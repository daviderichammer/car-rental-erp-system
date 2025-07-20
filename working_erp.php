<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: /login.php');
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; text-align: center; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .nav-tabs { display: flex; background: white; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-tab { flex: 1; padding: 15px; text-align: center; cursor: pointer; border: none; background: none; font-size: 14px; transition: all 0.3s; }
        .nav-tab.active { background: #667eea; color: white; }
        .nav-tab:hover { background: #f0f0f0; }
        .nav-tab.active:hover { background: #5a6fd8; }
        .content { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); min-height: 500px; }
        .vehicle-list { display: grid; gap: 15px; }
        .vehicle-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9; }
        .vehicle-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .vehicle-title { font-weight: bold; color: #333; }
        .vehicle-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-available { background: #d4edda; color: #155724; }
        .status-rented { background: #f8d7da; color: #721c24; }
        .vehicle-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px; }
        .detail-item { font-size: 14px; }
        .detail-label { font-weight: bold; color: #666; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd8; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .loading { text-align: center; padding: 40px; color: #666; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 5% auto; padding: 20px; border-radius: 8px; max-width: 500px; position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: bold; }
        .close { font-size: 24px; cursor: pointer; color: #666; }
        .close:hover { color: #000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚗 Infinite Auto Rentals - ERP System</h1>
        <p>Complete Fleet Management Solution</p>
    </div>
    
    <div class="container">
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('dashboard')">📊 Dashboard</button>
            <button class="nav-tab" onclick="showTab('vehicles')">🚗 Vehicles</button>
            <button class="nav-tab" onclick="showTab('customers')">👥 Customers</button>
            <button class="nav-tab" onclick="showTab('reservations')">📅 Reservations</button>
            <button class="nav-tab" onclick="showTab('pricing')">💰 Pricing</button>
            <button class="nav-tab" onclick="showTab('maintenance')">🔧 Maintenance</button>
            <button class="nav-tab" onclick="showTab('financial')">📈 Financial</button>
            <button class="nav-tab" onclick="showTab('reports')">📋 Reports</button>
        </div>
        
        <div class="content">
            <div id="dashboard-content" class="tab-content">
                <h2>Dashboard Overview</h2>
                <div id="dashboard-stats" class="loading">Loading dashboard statistics...</div>
            </div>
            
            <div id="vehicles-content" class="tab-content" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Vehicle Management</h2>
                    <button class="btn btn-primary" onclick="showAddVehicleModal()">+ Add Vehicle</button>
                </div>
                <div id="vehicles-list" class="loading">Loading vehicles...</div>
            </div>
            
            <div id="customers-content" class="tab-content" style="display: none;">
                <h2>Customer Management</h2>
                <div id="customers-list" class="loading">Loading customers...</div>
            </div>
            
            <div id="reservations-content" class="tab-content" style="display: none;">
                <h2>Reservation Management</h2>
                <div id="reservations-list" class="loading">Loading reservations...</div>
            </div>
            
            <div id="pricing-content" class="tab-content" style="display: none;">
                <h2>Pricing Management</h2>
                <div id="pricing-list" class="loading">Loading pricing data...</div>
            </div>
            
            <div id="maintenance-content" class="tab-content" style="display: none;">
                <h2>Maintenance Management</h2>
                <div id="maintenance-list" class="loading">Loading maintenance data...</div>
            </div>
            
            <div id="financial-content" class="tab-content" style="display: none;">
                <h2>Financial Management</h2>
                <div id="financial-list" class="loading">Loading financial data...</div>
            </div>
            
            <div id="reports-content" class="tab-content" style="display: none;">
                <h2>Reports & Analytics</h2>
                <div id="reports-list" class="loading">Loading reports...</div>
            </div>
        </div>
    </div>
    
    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Vehicle</h3>
                <span class="close" onclick="closeAddVehicleModal()">&times;</span>
            </div>
            <form id="addVehicleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Make</label>
                        <input type="text" class="form-input" name="make" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-input" name="model" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-input" name="year" min="2000" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">License Plate</label>
                        <input type="text" class="form-input" name="license_plate" required>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeAddVehicleModal()" style="margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const API_BASE = '/api';
        let currentTab = 'dashboard';
        
        // Tab management
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-content').style.display = 'block';
            
            // Add active class to selected tab
            event.target.classList.add('active');
            
            currentTab = tabName;
            
            // Load data for the selected tab
            loadTabData(tabName);
        }
        
        // Load data based on current tab
        function loadTabData(tabName) {
            switch(tabName) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'vehicles':
                    loadVehicles();
                    break;
                case 'customers':
                    loadCustomers();
                    break;
                case 'reservations':
                    loadReservations();
                    break;
                case 'pricing':
                    loadPricing();
                    break;
                case 'maintenance':
                    loadMaintenance();
                    break;
                case 'financial':
                    loadFinancial();
                    break;
                case 'reports':
                    loadReports();
                    break;
            }
        }
        
        // Dashboard functions
        function loadDashboard() {
            fetch(API_BASE + '/vehicles/')
                .then(response => response.json())
                .then(data => {
                    const totalVehicles = data.pagination.total;
                    const availableVehicles = data.vehicles.filter(v => v.status === 'available').length;
                    const rentedVehicles = totalVehicles - availableVehicles;
                    
                    document.getElementById('dashboard-stats').innerHTML = `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                                <h3 style="color: #1976d2; margin-bottom: 10px;">Total Vehicles</h3>
                                <div style="font-size: 2em; font-weight: bold; color: #1976d2;">${totalVehicles}</div>
                            </div>
                            <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
                                <h3 style="color: #388e3c; margin-bottom: 10px;">Available</h3>
                                <div style="font-size: 2em; font-weight: bold; color: #388e3c;">${availableVehicles}</div>
                            </div>
                            <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center;">
                                <h3 style="color: #f57c00; margin-bottom: 10px;">Rented</h3>
                                <div style="font-size: 2em; font-weight: bold; color: #f57c00;">${rentedVehicles}</div>
                            </div>
                        </div>
                        <div style="margin-top: 30px;">
                            <h3>Recent Activity</h3>
                            <p style="color: #666; margin-top: 10px;">System is operational. All modules are functioning correctly.</p>
                        </div>
                    `;
                })
                .catch(error => {
                    document.getElementById('dashboard-stats').innerHTML = '<div class="error">Error loading dashboard: ' + error.message + '</div>';
                });
        }
        
        // Vehicle functions
        function loadVehicles() {
            fetch(API_BASE + '/vehicles/')
                .then(response => response.json())
                .then(data => {
                    if (data.vehicles && data.vehicles.length > 0) {
                        const vehiclesHtml = data.vehicles.map(vehicle => `
                            <div class="vehicle-card">
                                <div class="vehicle-header">
                                    <div class="vehicle-title">${vehicle.make} ${vehicle.model} (${vehicle.year})</div>
                                    <div class="vehicle-status ${vehicle.status === 'available' ? 'status-available' : 'status-rented'}">${vehicle.status}</div>
                                </div>
                                <div class="vehicle-details">
                                    <div class="detail-item">
                                        <span class="detail-label">License:</span> ${vehicle.license_plate}
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Vehicle #:</span> ${vehicle.vehicle_number}
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Category:</span> ${vehicle.category.category_name}
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Daily Rate:</span> $${vehicle.category.base_daily_rate}
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        
                        document.getElementById('vehicles-list').innerHTML = vehiclesHtml;
                    } else {
                        document.getElementById('vehicles-list').innerHTML = '<p>No vehicles found.</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('vehicles-list').innerHTML = '<div class="error">Error loading vehicles: ' + error.message + '</div>';
                });
        }
        
        // Add vehicle modal functions
        function showAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'block';
        }
        
        function closeAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'none';
            document.getElementById('addVehicleForm').reset();
        }
        
        // Add vehicle form submission
        document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const vehicleData = {
                make: formData.get('make'),
                model: formData.get('model'),
                year: parseInt(formData.get('year')),
                license_plate: formData.get('license_plate')
            };
            
            fetch(API_BASE + '/vehicles/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(vehicleData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.vehicle_id) {
                    closeAddVehicleModal();
                    loadVehicles(); // Reload the vehicles list
                    showMessage('Vehicle added successfully!', 'success');
                } else {
                    showMessage('Error adding vehicle: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showMessage('Error adding vehicle: ' + error.message, 'error');
            });
        });
        
        // Other module functions (placeholder implementations)
        function loadCustomers() {
            document.getElementById('customers-list').innerHTML = '<p>Customer management module - Coming soon</p>';
        }
        
        function loadReservations() {
            document.getElementById('reservations-list').innerHTML = '<p>Reservation management module - Coming soon</p>';
        }
        
        function loadPricing() {
            document.getElementById('pricing-list').innerHTML = '<p>Pricing management module - Coming soon</p>';
        }
        
        function loadMaintenance() {
            document.getElementById('maintenance-list').innerHTML = '<p>Maintenance management module - Coming soon</p>';
        }
        
        function loadFinancial() {
            document.getElementById('financial-list').innerHTML = '<p>Financial management module - Coming soon</p>';
        }
        
        function loadReports() {
            document.getElementById('reports-list').innerHTML = '<p>Reports & analytics module - Coming soon</p>';
        }
        
        // Utility functions
        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = type;
            messageDiv.textContent = message;
            
            const content = document.querySelector('.content');
            content.insertBefore(messageDiv, content.firstChild);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
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

