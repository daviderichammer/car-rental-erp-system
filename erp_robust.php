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

        .header h1::before {
            content: '🚗 ';
            font-size: 0.8em;
        }

        .nav-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-tab {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-tab.active {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
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
        }

        .dashboard-stats {
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid #667eea;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .card p {
            margin-bottom: 8px;
            color: #555;
        }

        .card-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9em;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-container input {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
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
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #e74c3c;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
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

        .status-maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-scheduled {
            background: #fff3cd;
            color: #856404;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .last-updated {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9em;
        }

        /* Toast notifications for better error handling */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
            max-width: 350px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            border-left-color: #e74c3c;
        }

        .toast.success {
            border-left-color: #27ae60;
        }

        .toast.warning {
            border-left-color: #f39c12;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .nav-container {
                gap: 5px;
            }
            
            .nav-tab {
                padding: 10px 15px;
                font-size: 0.9em;
            }
            
            .card-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Car Rental ERP System</h1>
            <div class="nav-container">
                <button class="nav-tab active" onclick="showModule('dashboard')">📊 Dashboard</button>
                <button class="nav-tab" onclick="showModule('vehicles')">🚗 Vehicles</button>
                <button class="nav-tab" onclick="showModule('customers')">👥 Customers</button>
                <button class="nav-tab" onclick="showModule('reservations')">📅 Reservations</button>
                <button class="nav-tab" onclick="showModule('maintenance')">🔧 Maintenance</button>
                <button class="nav-tab" onclick="showModule('financial')">📈 Financial</button>
                <button class="nav-tab" onclick="showModule('reports')">📋 Reports</button>
            </div>
        </div>

        <div class="main-content">
            <!-- Dashboard Module -->
            <div id="dashboard" class="module active">
                <h2>📊 Dashboard Overview</h2>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalVehicles">8</div>
                        <div class="stat-label">Total Vehicles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="availableVehicles">5</div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="rentedVehicles">3</div>
                        <div class="stat-label">Rented</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalCustomers">4</div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalReservations">3</div>
                        <div class="stat-label">Active Reservations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="monthlyRevenue">$12,450</div>
                        <div class="stat-label">Monthly Revenue</div>
                    </div>
                </div>
                <div class="last-updated">
                    <strong>Last Updated:</strong> <span id="lastUpdated">Loading...</span>
                </div>
            </div>


            <!-- Vehicle Management Module -->
            <div id="vehicles" class="module">
                <h2>🚗 Vehicle Management</h2>
                <div class="search-container">
                    <button class="btn btn-success" onclick="safeCall(openAddVehicleModal)">+ Add Vehicle</button>
                    <input type="text" placeholder="Search vehicles..." onkeyup="safeCall(() => searchVehicles(this.value))">
                </div>
                <div id="vehiclesList">
                    <div class="loading">Loading vehicles...</div>
                </div>
            </div>

            <!-- Customer Management Module -->
            <div id="customers" class="module">
                <h2>👥 Customer Management</h2>
                <div class="search-container">
                    <button class="btn btn-success" onclick="safeCall(openAddCustomerModal)">+ Add Customer</button>
                    <input type="text" placeholder="Search customers..." onkeyup="safeCall(() => searchCustomers(this.value))">
                </div>
                <div id="customersList">
                    <div class="loading">Loading customers...</div>
                </div>
            </div>

            <!-- Reservation Management Module -->
            <div id="reservations" class="module">
                <h2>📅 Reservation Management</h2>
                <div class="search-container">
                    <button class="btn btn-success" onclick="safeCall(openAddReservationModal)">+ New Reservation</button>
                    <input type="text" placeholder="Search reservations..." onkeyup="safeCall(() => searchReservations(this.value))">
                </div>
                <div id="reservationsList">
                    <div class="loading">Loading reservations...</div>
                </div>
            </div>

            <!-- Maintenance Management Module -->
            <div id="maintenance" class="module">
                <h2>🔧 Maintenance Management</h2>
                <div class="search-container">
                    <button class="btn btn-success" onclick="safeCall(openAddMaintenanceModal)">+ Schedule Maintenance</button>
                    <input type="text" placeholder="Search maintenance records..." onkeyup="safeCall(() => searchMaintenance(this.value))">
                </div>
                <div id="maintenanceList">
                    <div class="loading">Loading maintenance records...</div>
                </div>
            </div>

            <!-- Financial Management Module -->
            <div id="financial" class="module">
                <h2>📈 Financial Management</h2>
                <div class="search-container">
                    <button class="btn btn-success" onclick="safeCall(openAddTransactionModal)">+ Add Transaction</button>
                    <input type="text" placeholder="Search transactions..." onkeyup="safeCall(() => searchTransactions(this.value))">
                </div>
                <div id="transactionsList">
                    <div class="loading">Loading transactions...</div>
                </div>
            </div>

            <!-- Reports Module -->
            <div id="reports" class="module">
                <h2>📋 Reports & Analytics</h2>
                <div class="search-container">
                    <h3>📊 Quick Reports</h3>
                    <button class="btn btn-primary" onclick="safeCall(generateVehicleReport)">Vehicle Report</button>
                    <button class="btn btn-primary" onclick="safeCall(generateRevenueReport)">Revenue Report</button>
                    <button class="btn btn-primary" onclick="safeCall(generateCustomerReport)">Customer Report</button>
                    <button class="btn btn-primary" onclick="safeCall(generateMaintenanceReport)">Maintenance Report</button>
                </div>
                <div id="reportsContent">
                    <p>Select a report type above to generate detailed analytics.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="addVehicleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="safeCall(closeAddVehicleModal)">&times;</span>
            <h2>Add New Vehicle</h2>
            <form id="addVehicleForm" onsubmit="safeCall((e) => addVehicle(e))">
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
                    <button type="button" class="btn btn-danger" onclick="safeCall(closeAddVehicleModal)">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="safeCall(closeAddCustomerModal)">&times;</span>
            <h2>Add New Customer</h2>
            <form id="addCustomerForm" onsubmit="safeCall((e) => addCustomer(e))">
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
                    <label>Date of Birth</label>
                    <input type="date" id="customerDOB" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger" onclick="safeCall(closeAddCustomerModal)">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Reservation Modal -->
    <div id="addReservationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="safeCall(closeAddReservationModal)">&times;</span>
            <h2>New Reservation</h2>
            <form id="addReservationForm" onsubmit="safeCall((e) => addReservation(e))">
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
                    <label>Pickup Date & Time</label>
                    <input type="datetime-local" id="reservationPickupDate" required>
                </div>
                <div class="form-group">
                    <label>Return Date & Time</label>
                    <input type="datetime-local" id="reservationReturnDate" required>
                </div>
                <div class="form-group">
                    <label>Total Amount ($)</label>
                    <input type="number" id="reservationAmount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger" onclick="safeCall(closeAddReservationModal)">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Reservation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Maintenance Modal -->
    <div id="addMaintenanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="safeCall(closeAddMaintenanceModal)">&times;</span>
            <h2>Schedule Maintenance</h2>
            <form id="addMaintenanceForm" onsubmit="safeCall((e) => addMaintenance(e))">
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
                        <option value="engine_service">Engine Service</option>
                        <option value="transmission_service">Transmission Service</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="maintenanceDescription" placeholder="Brief description of maintenance needed">
                </div>
                <div class="form-group">
                    <label>Scheduled Date</label>
                    <input type="date" id="maintenanceDate" required>
                </div>
                <div class="form-group">
                    <label>Estimated Cost ($)</label>
                    <input type="number" id="maintenanceCost" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger" onclick="safeCall(closeAddMaintenanceModal)">Cancel</button>
                    <button type="submit" class="btn btn-success">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div id="addTransactionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="safeCall(closeAddTransactionModal)">&times;</span>
            <h2>Add Transaction</h2>
            <form id="addTransactionForm" onsubmit="safeCall((e) => addTransaction(e))">
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
                    <input type="date" id="transactionDate" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger" onclick="safeCall(closeAddTransactionModal)">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Enhanced Error Handling System
        const ErrorHandler = {
            // Central error logging
            logError: function(error, context = '') {
                const timestamp = new Date().toISOString();
                const errorInfo = {
                    timestamp,
                    context,
                    message: error.message || error,
                    stack: error.stack || 'No stack trace available'
                };
                console.error('ERP System Error:', errorInfo);
                
                // Store error in localStorage for debugging
                try {
                    const errors = JSON.parse(localStorage.getItem('erp_errors') || '[]');
                    errors.push(errorInfo);
                    // Keep only last 50 errors
                    if (errors.length > 50) errors.splice(0, errors.length - 50);
                    localStorage.setItem('erp_errors', JSON.stringify(errors));
                } catch (e) {
                    console.warn('Could not store error in localStorage:', e);
                }
            },

            // Show user-friendly error messages
            showUserError: function(message, type = 'error') {
                this.showToast(message, type);
            },

            // Toast notification system
            showToast: function(message, type = 'info', duration = 5000) {
                try {
                    const toast = document.createElement('div');
                    toast.className = `toast ${type}`;
                    toast.innerHTML = `
                        <div style="font-weight: 600; margin-bottom: 5px;">
                            ${type === 'error' ? '⚠️ Error' : type === 'success' ? '✅ Success' : type === 'warning' ? '⚠️ Warning' : 'ℹ️ Info'}
                        </div>
                        <div>${message}</div>
                    `;
                    
                    document.body.appendChild(toast);
                    
                    // Show toast
                    setTimeout(() => toast.classList.add('show'), 100);
                    
                    // Hide toast
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }, duration);
                } catch (e) {
                    console.error('Error showing toast:', e);
                }
            },

            // Retry mechanism for failed operations
            retry: async function(operation, maxRetries = 3, delay = 1000) {
                for (let i = 0; i < maxRetries; i++) {
                    try {
                        return await operation();
                    } catch (error) {
                        if (i === maxRetries - 1) throw error;
                        await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
                    }
                }
            }
        };

        // Safe function wrapper to prevent system crashes
        function safeCall(fn, context = '') {
            return function(...args) {
                try {
                    const result = fn.apply(this, args);
                    // Handle promises
                    if (result && typeof result.catch === 'function') {
                        result.catch(error => {
                            ErrorHandler.logError(error, context || fn.name);
                            ErrorHandler.showUserError(`Operation failed: ${error.message || 'Unknown error'}`);
                        });
                    }
                    return result;
                } catch (error) {
                    ErrorHandler.logError(error, context || fn.name);
                    ErrorHandler.showUserError(`Operation failed: ${error.message || 'Unknown error'}`);
                    return null;
                }
            };
        }

        // Enhanced API call wrapper with retry logic
        async function safeApiCall(url, options = {}, retries = 2) {
            return ErrorHandler.retry(async () => {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return await response.json();
            }, retries);
        }

        // Global variables with error checking
        const API_BASE = '/api';
        let vehicles = [];
        let customers = [];
        let reservations = [];
        let maintenanceRecords = [];
        let transactions = [];

        // Initialize the application with comprehensive error handling
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Initialize all modules with error handling
                safeCall(loadDashboardData, 'DOMContentLoaded - loadDashboardData')();
                safeCall(loadVehicles, 'DOMContentLoaded - loadVehicles')();
                safeCall(loadCustomers, 'DOMContentLoaded - loadCustomers')();
                safeCall(loadReservations, 'DOMContentLoaded - loadReservations')();
                safeCall(loadMaintenance, 'DOMContentLoaded - loadMaintenance')();
                safeCall(loadTransactions, 'DOMContentLoaded - loadTransactions')();
                
                ErrorHandler.showToast('ERP System initialized successfully', 'success', 3000);
            } catch (error) {
                ErrorHandler.logError(error, 'DOMContentLoaded');
                ErrorHandler.showUserError('Failed to initialize ERP system');
            }
        });

        // Enhanced Navigation functions with error handling
        function showModule(moduleId) {
            try {
                // Hide all modules
                const modules = document.querySelectorAll('.module');
                modules.forEach(module => {
                    try {
                        module.classList.remove('active');
                    } catch (e) {
                        ErrorHandler.logError(e, `showModule - hiding module ${module.id}`);
                    }
                });
                
                // Remove active class from all tabs
                const tabs = document.querySelectorAll('.nav-tab');
                tabs.forEach(tab => {
                    try {
                        tab.classList.remove('active');
                    } catch (e) {
                        ErrorHandler.logError(e, 'showModule - removing active tab');
                    }
                });
                
                // Show selected module
                const targetModule = document.getElementById(moduleId);
                if (targetModule) {
                    targetModule.classList.add('active');
                } else {
                    throw new Error(`Module ${moduleId} not found`);
                }
                
                // Add active class to clicked tab
                if (event && event.target) {
                    event.target.classList.add('active');
                }

                // Load module-specific data
                if (moduleId === 'reports') {
                    safeCall(loadReportsData, 'showModule - loadReportsData')();
                }
            } catch (error) {
                ErrorHandler.logError(error, `showModule - ${moduleId}`);
                ErrorHandler.showUserError(`Failed to switch to ${moduleId} module`);
            }
        }

        // Enhanced Dashboard functions with comprehensive error handling
        async function loadDashboardData() {
            try {
                const loadingPromises = [];
                
                // Load vehicles data with error handling
                loadingPromises.push(
                    safeApiCall(`${API_BASE}/vehicles/`)
                        .then(vehiclesData => {
                            if (vehiclesData && vehiclesData.vehicles) {
                                const totalVehicles = vehiclesData.vehicles.length;
                                const availableVehicles = vehiclesData.vehicles.filter(v => v.status === 'available').length;
                                const rentedVehicles = totalVehicles - availableVehicles;
                                
                                safeCall(() => {
                                    document.getElementById('totalVehicles').textContent = totalVehicles;
                                    document.getElementById('availableVehicles').textContent = availableVehicles;
                                    document.getElementById('rentedVehicles').textContent = rentedVehicles;
                                }, 'loadDashboardData - update vehicle stats')();
                            }
                        })
                        .catch(error => {
                            ErrorHandler.logError(error, 'loadDashboardData - vehicles');
                            safeCall(() => {
                                document.getElementById('totalVehicles').textContent = 'N/A';
                                document.getElementById('availableVehicles').textContent = 'N/A';
                                document.getElementById('rentedVehicles').textContent = 'N/A';
                            })();
                        })
                );

                // Load customers data with error handling
                loadingPromises.push(
                    safeApiCall(`${API_BASE}/customers/`)
                        .then(customersData => {
                            const count = customersData && customersData.customers ? customersData.customers.length : 0;
                            safeCall(() => {
                                document.getElementById('totalCustomers').textContent = count;
                            }, 'loadDashboardData - update customer count')();
                        })
                        .catch(error => {
                            ErrorHandler.logError(error, 'loadDashboardData - customers');
                            safeCall(() => {
                                document.getElementById('totalCustomers').textContent = 'N/A';
                            })();
                        })
                );

                // Load reservations data with error handling
                loadingPromises.push(
                    safeApiCall(`${API_BASE}/reservations/`)
                        .then(reservationsData => {
                            const count = reservationsData && reservationsData.reservations ? reservationsData.reservations.length : 0;
                            safeCall(() => {
                                document.getElementById('totalReservations').textContent = count;
                            }, 'loadDashboardData - update reservations count')();
                        })
                        .catch(error => {
                            ErrorHandler.logError(error, 'loadDashboardData - reservations');
                            safeCall(() => {
                                document.getElementById('totalReservations').textContent = 'N/A';
                            })();
                        })
                );

                // Wait for all data to load (but don't fail if some fail)
                await Promise.allSettled(loadingPromises);
                
                // Update last updated time
                safeCall(() => {
                    document.getElementById('lastUpdated').textContent = new Date().toLocaleString();
                }, 'loadDashboardData - update timestamp')();
                
            } catch (error) {
                ErrorHandler.logError(error, 'loadDashboardData - general');
                ErrorHandler.showUserError('Failed to load dashboard data');
            }
        }


        // Enhanced Vehicle functions with comprehensive error handling
        async function loadVehicles() {
            try {
                const container = document.getElementById('vehiclesList');
                if (container) {
                    container.innerHTML = '<div class="loading">Loading vehicles...</div>';
                }
                
                const data = await safeApiCall(`${API_BASE}/vehicles/`);
                
                if (data && data.vehicles) {
                    vehicles = data.vehicles;
                    safeCall(displayVehicles, 'loadVehicles - displayVehicles')(vehicles);
                    safeCall(populateVehicleSelects, 'loadVehicles - populateVehicleSelects')();
                } else {
                    throw new Error('Invalid vehicle data received');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'loadVehicles');
                const container = document.getElementById('vehiclesList');
                if (container) {
                    container.innerHTML = `
                        <div class="error">
                            Failed to load vehicles. 
                            <button class="btn btn-primary" onclick="safeCall(loadVehicles, 'retry loadVehicles')()">Retry</button>
                        </div>
                    `;
                }
                ErrorHandler.showUserError('Failed to load vehicles');
            }
        }

        function displayVehicles(vehicleList) {
            try {
                const container = document.getElementById('vehiclesList');
                if (!container) {
                    throw new Error('Vehicle list container not found');
                }
                
                if (!Array.isArray(vehicleList) || vehicleList.length === 0) {
                    container.innerHTML = `
                        <p>No vehicles found. 
                        <button class="btn btn-success" onclick="safeCall(openAddVehicleModal, 'displayVehicles - add first vehicle')()">Add your first vehicle</button>
                        </p>
                    `;
                    return;
                }
                
                const vehicleCards = vehicleList.map(vehicle => {
                    try {
                        return `
                            <div class="card">
                                <h4>${vehicle.make || 'Unknown'} ${vehicle.model || 'Model'} (${vehicle.year || 'N/A'})</h4>
                                <p><strong>License:</strong> ${vehicle.license_plate || 'N/A'}</p>
                                <p><strong>Vehicle #:</strong> ${vehicle.vehicle_number || 'N/A'}</p>
                                <p><strong>Category:</strong> ${vehicle.category || 'N/A'}</p>
                                <p><strong>Daily Rate:</strong> $${vehicle.daily_rate || 'N/A'}</p>
                                <p><strong>Status:</strong> <span class="status-badge status-${vehicle.status || 'unknown'}">${(vehicle.status || 'Unknown').toUpperCase()}</span></p>
                                <div class="card-actions">
                                    <button class="btn btn-info" onclick="safeCall(() => editVehicle('${vehicle.vehicle_id || ''}'), 'displayVehicles - edit')()">Edit</button>
                                    <button class="btn btn-warning" onclick="safeCall(() => scheduleMaintenanceVehicle('${vehicle.vehicle_id || ''}'), 'displayVehicles - maintenance')()">Maintenance</button>
                                    <button class="btn btn-danger" onclick="safeCall(() => archiveVehicle('${vehicle.vehicle_id || ''}'), 'displayVehicles - archive')()">Archive</button>
                                </div>
                            </div>
                        `;
                    } catch (cardError) {
                        ErrorHandler.logError(cardError, `displayVehicles - vehicle card ${vehicle.vehicle_id}`);
                        return `
                            <div class="card error">
                                <h4>Error displaying vehicle</h4>
                                <p>Vehicle ID: ${vehicle.vehicle_id || 'Unknown'}</p>
                            </div>
                        `;
                    }
                }).join('');
                
                container.innerHTML = `<div class="card-grid">${vehicleCards}</div>`;
            } catch (error) {
                ErrorHandler.logError(error, 'displayVehicles');
                const container = document.getElementById('vehiclesList');
                if (container) {
                    container.innerHTML = '<div class="error">Error displaying vehicles</div>';
                }
            }
        }

        function populateVehicleSelects() {
            try {
                const selects = ['reservationVehicle', 'maintenanceVehicle'];
                
                selects.forEach(selectId => {
                    try {
                        const select = document.getElementById(selectId);
                        if (select && Array.isArray(vehicles)) {
                            select.innerHTML = '<option value="">Select Vehicle</option>' +
                                vehicles.map(vehicle => {
                                    try {
                                        return `<option value="${vehicle.vehicle_id || ''}">${vehicle.make || 'Unknown'} ${vehicle.model || 'Model'} - ${vehicle.license_plate || 'N/A'}</option>`;
                                    } catch (optionError) {
                                        ErrorHandler.logError(optionError, `populateVehicleSelects - option ${vehicle.vehicle_id}`);
                                        return '';
                                    }
                                }).join('');
                        }
                    } catch (selectError) {
                        ErrorHandler.logError(selectError, `populateVehicleSelects - ${selectId}`);
                    }
                });
            } catch (error) {
                ErrorHandler.logError(error, 'populateVehicleSelects');
            }
        }

        function searchVehicles(query) {
            try {
                if (!Array.isArray(vehicles)) {
                    ErrorHandler.showUserError('Vehicle data not available for search');
                    return;
                }
                
                const filtered = vehicles.filter(vehicle => {
                    try {
                        const searchText = `${vehicle.make || ''} ${vehicle.model || ''} ${vehicle.license_plate || ''} ${vehicle.vehicle_number || ''}`.toLowerCase();
                        return searchText.includes((query || '').toLowerCase());
                    } catch (filterError) {
                        ErrorHandler.logError(filterError, `searchVehicles - filter ${vehicle.vehicle_id}`);
                        return false;
                    }
                });
                
                safeCall(displayVehicles, 'searchVehicles - displayVehicles')(filtered);
            } catch (error) {
                ErrorHandler.logError(error, 'searchVehicles');
                ErrorHandler.showUserError('Error searching vehicles');
            }
        }

        function openAddVehicleModal() {
            try {
                const modal = document.getElementById('addVehicleModal');
                if (modal) {
                    modal.style.display = 'block';
                } else {
                    throw new Error('Add vehicle modal not found');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'openAddVehicleModal');
                ErrorHandler.showUserError('Cannot open add vehicle form');
            }
        }

        function closeAddVehicleModal() {
            try {
                const modal = document.getElementById('addVehicleModal');
                const form = document.getElementById('addVehicleForm');
                
                if (modal) {
                    modal.style.display = 'none';
                }
                if (form) {
                    form.reset();
                }
            } catch (error) {
                ErrorHandler.logError(error, 'closeAddVehicleModal');
            }
        }

        async function addVehicle(event) {
            try {
                if (event) {
                    event.preventDefault();
                }
                
                const vehicleData = {
                    make: document.getElementById('vehicleMake')?.value || '',
                    model: document.getElementById('vehicleModel')?.value || '',
                    year: parseInt(document.getElementById('vehicleYear')?.value) || new Date().getFullYear(),
                    license_plate: document.getElementById('vehicleLicense')?.value || '',
                    daily_rate: parseFloat(document.getElementById('vehicleRate')?.value) || 0
                };
                
                // Validate required fields
                if (!vehicleData.make || !vehicleData.model || !vehicleData.license_plate) {
                    throw new Error('Please fill in all required fields');
                }
                
                const response = await safeApiCall(`${API_BASE}/vehicles/`, {
                    method: 'POST',
                    body: JSON.stringify(vehicleData)
                });
                
                if (response) {
                    ErrorHandler.showToast('Vehicle added successfully!', 'success');
                    safeCall(closeAddVehicleModal, 'addVehicle - closeModal')();
                    safeCall(loadVehicles, 'addVehicle - reload')();
                    safeCall(loadDashboardData, 'addVehicle - updateDashboard')();
                } else {
                    throw new Error('Failed to add vehicle');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'addVehicle');
                ErrorHandler.showUserError(`Failed to add vehicle: ${error.message}`);
            }
        }

        // Enhanced Customer functions with comprehensive error handling
        async function loadCustomers() {
            try {
                const container = document.getElementById('customersList');
                if (container) {
                    container.innerHTML = '<div class="loading">Loading customers...</div>';
                }
                
                const data = await safeApiCall(`${API_BASE}/customers/`);
                
                if (data && data.customers) {
                    customers = data.customers;
                    safeCall(displayCustomers, 'loadCustomers - displayCustomers')(customers);
                    safeCall(populateCustomerSelects, 'loadCustomers - populateCustomerSelects')();
                } else {
                    throw new Error('Invalid customer data received');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'loadCustomers');
                const container = document.getElementById('customersList');
                if (container) {
                    container.innerHTML = `
                        <div class="error">
                            Failed to load customers. 
                            <button class="btn btn-primary" onclick="safeCall(loadCustomers, 'retry loadCustomers')()">Retry</button>
                        </div>
                    `;
                }
                ErrorHandler.showUserError('Failed to load customers');
            }
        }

        function displayCustomers(customerList) {
            try {
                const container = document.getElementById('customersList');
                if (!container) {
                    throw new Error('Customer list container not found');
                }
                
                if (!Array.isArray(customerList) || customerList.length === 0) {
                    container.innerHTML = `
                        <p>No customers found. 
                        <button class="btn btn-success" onclick="safeCall(openAddCustomerModal, 'displayCustomers - add first customer')()">Add your first customer</button>
                        </p>
                    `;
                    return;
                }
                
                const customerCards = customerList.map(customer => {
                    try {
                        // Handle nested user object structure
                        const user = customer.user || {};
                        const firstName = user.first_name || customer.first_name || 'Unknown';
                        const lastName = user.last_name || customer.last_name || '';
                        const email = user.email || customer.email || 'N/A';
                        const phone = user.phone_number || customer.phone || 'N/A';
                        const dob = user.date_of_birth || customer.date_of_birth || 'N/A';
                        const status = customer.status || 'active';
                        
                        return `
                            <div class="card">
                                <h4>${firstName} ${lastName}</h4>
                                <p><strong>Email:</strong> ${email}</p>
                                <p><strong>Phone:</strong> ${phone}</p>
                                <p><strong>License:</strong> ${customer.license_number || 'null'}</p>
                                <p><strong>Date of Birth:</strong> ${new Date(dob).toLocaleDateString() || 'N/A'}</p>
                                <p><strong>Status:</strong> <span class="status-badge status-${status}">${status.toUpperCase()}</span></p>
                                <div class="card-actions">
                                    <button class="btn btn-info" onclick="safeCall(() => editCustomer('${customer.customer_id || ''}'), 'displayCustomers - edit')()">Edit</button>
                                    <button class="btn btn-warning" onclick="safeCall(() => viewCustomerHistory('${customer.customer_id || ''}'), 'displayCustomers - history')()">View History</button>
                                    <button class="btn btn-danger" onclick="safeCall(() => deactivateCustomer('${customer.customer_id || ''}'), 'displayCustomers - deactivate')()">Deactivate</button>
                                </div>
                            </div>
                        `;
                    } catch (cardError) {
                        ErrorHandler.logError(cardError, `displayCustomers - customer card ${customer.customer_id}`);
                        return `
                            <div class="card error">
                                <h4>Error displaying customer</h4>
                                <p>Customer ID: ${customer.customer_id || 'Unknown'}</p>
                            </div>
                        `;
                    }
                }).join('');
                
                container.innerHTML = `<div class="card-grid">${customerCards}</div>`;
            } catch (error) {
                ErrorHandler.logError(error, 'displayCustomers');
                const container = document.getElementById('customersList');
                if (container) {
                    container.innerHTML = '<div class="error">Error displaying customers</div>';
                }
            }
        }

        function populateCustomerSelects() {
            try {
                const select = document.getElementById('reservationCustomer');
                if (select && Array.isArray(customers)) {
                    select.innerHTML = '<option value="">Select Customer</option>' +
                        customers.map(customer => {
                            try {
                                const user = customer.user || {};
                                const firstName = user.first_name || customer.first_name || 'Unknown';
                                const lastName = user.last_name || customer.last_name || '';
                                return `<option value="${customer.customer_id || ''}">${firstName} ${lastName}</option>`;
                            } catch (optionError) {
                                ErrorHandler.logError(optionError, `populateCustomerSelects - option ${customer.customer_id}`);
                                return '';
                            }
                        }).join('');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'populateCustomerSelects');
            }
        }

        function searchCustomers(query) {
            try {
                if (!Array.isArray(customers)) {
                    ErrorHandler.showUserError('Customer data not available for search');
                    return;
                }
                
                const filtered = customers.filter(customer => {
                    try {
                        const user = customer.user || {};
                        const searchText = `${user.first_name || ''} ${user.last_name || ''} ${user.email || ''}`.toLowerCase();
                        return searchText.includes((query || '').toLowerCase());
                    } catch (filterError) {
                        ErrorHandler.logError(filterError, `searchCustomers - filter ${customer.customer_id}`);
                        return false;
                    }
                });
                
                safeCall(displayCustomers, 'searchCustomers - displayCustomers')(filtered);
            } catch (error) {
                ErrorHandler.logError(error, 'searchCustomers');
                ErrorHandler.showUserError('Error searching customers');
            }
        }

        function openAddCustomerModal() {
            try {
                const modal = document.getElementById('addCustomerModal');
                if (modal) {
                    modal.style.display = 'block';
                } else {
                    throw new Error('Add customer modal not found');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'openAddCustomerModal');
                ErrorHandler.showUserError('Cannot open add customer form');
            }
        }

        function closeAddCustomerModal() {
            try {
                const modal = document.getElementById('addCustomerModal');
                const form = document.getElementById('addCustomerForm');
                
                if (modal) {
                    modal.style.display = 'none';
                }
                if (form) {
                    form.reset();
                }
            } catch (error) {
                ErrorHandler.logError(error, 'closeAddCustomerModal');
            }
        }

        async function addCustomer(event) {
            try {
                if (event) {
                    event.preventDefault();
                }
                
                const customerData = {
                    first_name: document.getElementById('customerFirstName')?.value || '',
                    last_name: document.getElementById('customerLastName')?.value || '',
                    email: document.getElementById('customerEmail')?.value || '',
                    phone_number: document.getElementById('customerPhone')?.value || '',
                    date_of_birth: document.getElementById('customerDOB')?.value || ''
                };
                
                // Validate required fields
                if (!customerData.first_name || !customerData.last_name || !customerData.email) {
                    throw new Error('Please fill in all required fields');
                }
                
                // Validate email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(customerData.email)) {
                    throw new Error('Please enter a valid email address');
                }
                
                const response = await safeApiCall(`${API_BASE}/customers/`, {
                    method: 'POST',
                    body: JSON.stringify(customerData)
                });
                
                if (response) {
                    ErrorHandler.showToast('Customer added successfully!', 'success');
                    safeCall(closeAddCustomerModal, 'addCustomer - closeModal')();
                    safeCall(loadCustomers, 'addCustomer - reload')();
                    safeCall(loadDashboardData, 'addCustomer - updateDashboard')();
                } else {
                    throw new Error('Failed to add customer');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'addCustomer');
                ErrorHandler.showUserError(`Failed to add customer: ${error.message}`);
            }
        }


        // Enhanced Reservation functions with comprehensive error handling
        async function loadReservations() {
            try {
                const container = document.getElementById('reservationsList');
                if (container) {
                    container.innerHTML = '<div class="loading">Loading reservations...</div>';
                }
                
                const data = await safeApiCall(`${API_BASE}/reservations/`);
                
                if (data && data.reservations) {
                    reservations = data.reservations;
                    safeCall(displayReservations, 'loadReservations - displayReservations')(reservations);
                } else {
                    throw new Error('Invalid reservation data received');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'loadReservations');
                const container = document.getElementById('reservationsList');
                if (container) {
                    container.innerHTML = `
                        <div class="error">
                            Failed to load reservations. 
                            <button class="btn btn-primary" onclick="safeCall(loadReservations, 'retry loadReservations')()">Retry</button>
                        </div>
                    `;
                }
                ErrorHandler.showUserError('Failed to load reservations');
            }
        }

        function displayReservations(reservationList) {
            try {
                const container = document.getElementById('reservationsList');
                if (!container) {
                    throw new Error('Reservation list container not found');
                }
                
                if (!Array.isArray(reservationList) || reservationList.length === 0) {
                    container.innerHTML = `
                        <p>No reservations found. 
                        <button class="btn btn-success" onclick="safeCall(openAddReservationModal, 'displayReservations - add first reservation')()">Create your first reservation</button>
                        </p>
                    `;
                    return;
                }
                
                const reservationCards = reservationList.map(reservation => {
                    try {
                        // Handle nested customer object structure
                        const customer = reservation.customer || {};
                        const user = customer.user || {};
                        const customerName = `${user.first_name || 'Unknown'} ${user.last_name || ''}`.trim();
                        
                        // Handle vehicle category
                        const vehicleCategory = reservation.vehicle_category || {};
                        const vehicleName = vehicleCategory.category_name || 'N/A';
                        
                        // Handle dates
                        const startDate = reservation.pickup_datetime ? new Date(reservation.pickup_datetime).toLocaleDateString() : 'N/A';
                        const endDate = reservation.return_datetime ? new Date(reservation.return_datetime).toLocaleDateString() : 'N/A';
                        
                        // Handle amount
                        const amount = reservation.total_estimated_cost || 'N/A';
                        const status = reservation.status || 'pending';
                        
                        return `
                            <div class="card">
                                <h4>Reservation #${reservation.reservation_number || 'N/A'}</h4>
                                <p><strong>Customer:</strong> ${customerName}</p>
                                <p><strong>Vehicle:</strong> ${vehicleName}</p>
                                <p><strong>Start Date:</strong> ${startDate}</p>
                                <p><strong>End Date:</strong> ${endDate}</p>
                                <p><strong>Total Amount:</strong> $${amount}</p>
                                <p><strong>Status:</strong> <span class="status-badge status-${status}">${status.toUpperCase()}</span></p>
                                <div class="card-actions">
                                    <button class="btn btn-info" onclick="safeCall(() => editReservation('${reservation.reservation_id || ''}'), 'displayReservations - edit')()">Edit</button>
                                    <button class="btn btn-success" onclick="safeCall(() => checkInReservation('${reservation.reservation_id || ''}'), 'displayReservations - checkin')()">Check In</button>
                                    <button class="btn btn-warning" onclick="safeCall(() => checkOutReservation('${reservation.reservation_id || ''}'), 'displayReservations - checkout')()">Check Out</button>
                                    <button class="btn btn-danger" onclick="safeCall(() => cancelReservation('${reservation.reservation_id || ''}'), 'displayReservations - cancel')()">Cancel</button>
                                </div>
                            </div>
                        `;
                    } catch (cardError) {
                        ErrorHandler.logError(cardError, `displayReservations - reservation card ${reservation.reservation_id}`);
                        return `
                            <div class="card error">
                                <h4>Error displaying reservation</h4>
                                <p>Reservation ID: ${reservation.reservation_id || 'Unknown'}</p>
                            </div>
                        `;
                    }
                }).join('');
                
                container.innerHTML = `<div class="card-grid">${reservationCards}</div>`;
            } catch (error) {
                ErrorHandler.logError(error, 'displayReservations');
                const container = document.getElementById('reservationsList');
                if (container) {
                    container.innerHTML = '<div class="error">Error displaying reservations</div>';
                }
            }
        }

        function searchReservations(query) {
            try {
                if (!Array.isArray(reservations)) {
                    ErrorHandler.showUserError('Reservation data not available for search');
                    return;
                }
                
                const filtered = reservations.filter(reservation => {
                    try {
                        const customer = reservation.customer || {};
                        const user = customer.user || {};
                        const searchText = `${user.first_name || ''} ${user.last_name || ''} ${reservation.reservation_number || ''}`.toLowerCase();
                        return searchText.includes((query || '').toLowerCase());
                    } catch (filterError) {
                        ErrorHandler.logError(filterError, `searchReservations - filter ${reservation.reservation_id}`);
                        return false;
                    }
                });
                
                safeCall(displayReservations, 'searchReservations - displayReservations')(filtered);
            } catch (error) {
                ErrorHandler.logError(error, 'searchReservations');
                ErrorHandler.showUserError('Error searching reservations');
            }
        }

        function openAddReservationModal() {
            try {
                const modal = document.getElementById('addReservationModal');
                if (modal) {
                    modal.style.display = 'block';
                    // Populate dropdowns
                    safeCall(populateCustomerSelects, 'openAddReservationModal - populateCustomers')();
                    safeCall(populateVehicleSelects, 'openAddReservationModal - populateVehicles')();
                } else {
                    throw new Error('Add reservation modal not found');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'openAddReservationModal');
                ErrorHandler.showUserError('Cannot open add reservation form');
            }
        }

        function closeAddReservationModal() {
            try {
                const modal = document.getElementById('addReservationModal');
                const form = document.getElementById('addReservationForm');
                
                if (modal) {
                    modal.style.display = 'none';
                }
                if (form) {
                    form.reset();
                }
            } catch (error) {
                ErrorHandler.logError(error, 'closeAddReservationModal');
            }
        }

        async function addReservation(event) {
            try {
                if (event) {
                    event.preventDefault();
                }
                
                const customerId = document.getElementById('reservationCustomer')?.value;
                const vehicleId = document.getElementById('reservationVehicle')?.value;
                const pickupDate = document.getElementById('reservationPickupDate')?.value;
                const returnDate = document.getElementById('reservationReturnDate')?.value;
                const amount = document.getElementById('reservationAmount')?.value;
                
                // Validate required fields
                if (!customerId || !vehicleId || !pickupDate || !returnDate || !amount) {
                    throw new Error('Please fill in all required fields');
                }
                
                // Validate dates
                const pickup = new Date(pickupDate);
                const returnDateTime = new Date(returnDate);
                if (pickup >= returnDateTime) {
                    throw new Error('Return date must be after pickup date');
                }
                
                const reservationData = {
                    customer_id: customerId,
                    vehicle_id: vehicleId,
                    vehicle_category_id: "1", // Default category
                    pickup_location_id: "1", // Default location
                    return_location_id: "1", // Default location
                    pickup_datetime: pickupDate,
                    return_datetime: returnDate,
                    total_amount: parseFloat(amount)
                };
                
                const response = await safeApiCall(`${API_BASE}/reservations/`, {
                    method: 'POST',
                    body: JSON.stringify(reservationData)
                });
                
                if (response) {
                    ErrorHandler.showToast('Reservation created successfully!', 'success');
                    safeCall(closeAddReservationModal, 'addReservation - closeModal')();
                    safeCall(loadReservations, 'addReservation - reload')();
                    safeCall(loadDashboardData, 'addReservation - updateDashboard')();
                } else {
                    throw new Error('Failed to create reservation');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'addReservation');
                ErrorHandler.showUserError(`Failed to create reservation: ${error.message}`);
            }
        }

        // Enhanced Maintenance functions with comprehensive error handling
        async function loadMaintenance() {
            try {
                const container = document.getElementById('maintenanceList');
                if (container) {
                    container.innerHTML = '<div class="loading">Loading maintenance records...</div>';
                }
                
                const data = await safeApiCall(`${API_BASE}/maintenance/schedules`);
                
                if (data && data.schedules) {
                    maintenanceRecords = data.schedules;
                    safeCall(displayMaintenance, 'loadMaintenance - displayMaintenance')(maintenanceRecords);
                } else {
                    throw new Error('Invalid maintenance data received');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'loadMaintenance');
                const container = document.getElementById('maintenanceList');
                if (container) {
                    container.innerHTML = `
                        <div class="error">
                            Failed to load maintenance records. 
                            <button class="btn btn-primary" onclick="safeCall(loadMaintenance, 'retry loadMaintenance')()">Retry</button>
                        </div>
                    `;
                }
                ErrorHandler.showUserError('Failed to load maintenance records');
            }
        }

        function displayMaintenance(maintenanceList) {
            try {
                const container = document.getElementById('maintenanceList');
                if (!container) {
                    throw new Error('Maintenance list container not found');
                }
                
                if (!Array.isArray(maintenanceList) || maintenanceList.length === 0) {
                    container.innerHTML = `
                        <p>No maintenance records found. 
                        <button class="btn btn-success" onclick="safeCall(openAddMaintenanceModal, 'displayMaintenance - add first maintenance')()">Schedule your first maintenance</button>
                        </p>
                    `;
                    return;
                }
                
                const maintenanceCards = maintenanceList.map(maintenance => {
                    try {
                        // Handle vehicle information
                        const vehicle = maintenance.vehicle || {};
                        const vehicleName = `${vehicle.make || 'Unknown'} ${vehicle.model || 'Vehicle'}`;
                        
                        // Handle maintenance details with correct field mapping
                        const serviceType = maintenance.service_type || 'N/A';
                        const description = maintenance.service_notes || 'N/A';
                        const scheduledDate = maintenance.scheduled_date ? new Date(maintenance.scheduled_date).toLocaleDateString() : 'N/A';
                        const cost = maintenance.estimated_cost ? `$${maintenance.estimated_cost}` : '$N/A';
                        const status = maintenance.status || 'scheduled';
                        
                        return `
                            <div class="card">
                                <h4>${serviceType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</h4>
                                <p><strong>Vehicle:</strong> ${vehicleName}</p>
                                <p><strong>Description:</strong> ${description}</p>
                                <p><strong>Scheduled Date:</strong> ${scheduledDate}</p>
                                <p><strong>Cost:</strong> ${cost}</p>
                                <p><strong>Status:</strong> <span class="status-badge status-${status}">${status.toUpperCase()}</span></p>
                                <div class="card-actions">
                                    <button class="btn btn-success" onclick="safeCall(() => completeMaintenance('${maintenance.schedule_id || ''}'), 'displayMaintenance - complete')()">Complete</button>
                                    <button class="btn btn-warning" onclick="safeCall(() => rescheduleMaintenance('${maintenance.schedule_id || ''}'), 'displayMaintenance - reschedule')()">Reschedule</button>
                                    <button class="btn btn-danger" onclick="safeCall(() => cancelMaintenance('${maintenance.schedule_id || ''}'), 'displayMaintenance - cancel')()">Cancel</button>
                                </div>
                            </div>
                        `;
                    } catch (cardError) {
                        ErrorHandler.logError(cardError, `displayMaintenance - maintenance card ${maintenance.schedule_id}`);
                        return `
                            <div class="card error">
                                <h4>Error displaying maintenance record</h4>
                                <p>Schedule ID: ${maintenance.schedule_id || 'Unknown'}</p>
                            </div>
                        `;
                    }
                }).join('');
                
                container.innerHTML = `<div class="card-grid">${maintenanceCards}</div>`;
            } catch (error) {
                ErrorHandler.logError(error, 'displayMaintenance');
                const container = document.getElementById('maintenanceList');
                if (container) {
                    container.innerHTML = '<div class="error">Error displaying maintenance records</div>';
                }
            }
        }

        function searchMaintenance(query) {
            try {
                if (!Array.isArray(maintenanceRecords)) {
                    ErrorHandler.showUserError('Maintenance data not available for search');
                    return;
                }
                
                const filtered = maintenanceRecords.filter(maintenance => {
                    try {
                        const vehicle = maintenance.vehicle || {};
                        const searchText = `${vehicle.make || ''} ${vehicle.model || ''} ${maintenance.service_type || ''}`.toLowerCase();
                        return searchText.includes((query || '').toLowerCase());
                    } catch (filterError) {
                        ErrorHandler.logError(filterError, `searchMaintenance - filter ${maintenance.schedule_id}`);
                        return false;
                    }
                });
                
                safeCall(displayMaintenance, 'searchMaintenance - displayMaintenance')(filtered);
            } catch (error) {
                ErrorHandler.logError(error, 'searchMaintenance');
                ErrorHandler.showUserError('Error searching maintenance records');
            }
        }

        function openAddMaintenanceModal() {
            try {
                const modal = document.getElementById('addMaintenanceModal');
                if (modal) {
                    modal.style.display = 'block';
                    // Populate vehicle dropdown
                    safeCall(populateVehicleSelects, 'openAddMaintenanceModal - populateVehicles')();
                } else {
                    throw new Error('Add maintenance modal not found');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'openAddMaintenanceModal');
                ErrorHandler.showUserError('Cannot open add maintenance form');
            }
        }

        function closeAddMaintenanceModal() {
            try {
                const modal = document.getElementById('addMaintenanceModal');
                const form = document.getElementById('addMaintenanceForm');
                
                if (modal) {
                    modal.style.display = 'none';
                }
                if (form) {
                    form.reset();
                }
            } catch (error) {
                ErrorHandler.logError(error, 'closeAddMaintenanceModal');
            }
        }

        async function addMaintenance(event) {
            try {
                if (event) {
                    event.preventDefault();
                }
                
                const vehicleId = document.getElementById('maintenanceVehicle')?.value;
                const serviceType = document.getElementById('maintenanceType')?.value;
                const description = document.getElementById('maintenanceDescription')?.value || '';
                const scheduledDate = document.getElementById('maintenanceDate')?.value;
                const estimatedCost = document.getElementById('maintenanceCost')?.value;
                
                // Validate required fields
                if (!vehicleId || !serviceType || !scheduledDate) {
                    throw new Error('Please fill in all required fields');
                }
                
                // Validate date is not in the past
                const selectedDate = new Date(scheduledDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    throw new Error('Scheduled date cannot be in the past');
                }
                
                const maintenanceData = {
                    vehicle_id: vehicleId,
                    service_type: serviceType,
                    service_notes: description,
                    scheduled_date: scheduledDate,
                    estimated_cost: estimatedCost ? parseFloat(estimatedCost) : null
                };
                
                const response = await safeApiCall(`${API_BASE}/maintenance/schedules`, {
                    method: 'POST',
                    body: JSON.stringify(maintenanceData)
                });
                
                if (response) {
                    ErrorHandler.showToast('Maintenance scheduled successfully!', 'success');
                    safeCall(closeAddMaintenanceModal, 'addMaintenance - closeModal')();
                    safeCall(loadMaintenance, 'addMaintenance - reload')();
                } else {
                    throw new Error('Failed to schedule maintenance');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'addMaintenance');
                ErrorHandler.showUserError(`Failed to schedule maintenance: ${error.message}`);
            }
        }


        // Enhanced Financial functions with comprehensive error handling
        async function loadTransactions() {
            try {
                const container = document.getElementById('transactionsList');
                if (container) {
                    container.innerHTML = '<div class="loading">Loading transactions...</div>';
                }
                
                const data = await safeApiCall(`${API_BASE}/financial/payments`);
                
                if (data && data.payments) {
                    transactions = data.payments;
                    safeCall(displayTransactions, 'loadTransactions - displayTransactions')(transactions);
                } else {
                    throw new Error('Invalid transaction data received');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'loadTransactions');
                const container = document.getElementById('transactionsList');
                if (container) {
                    container.innerHTML = `
                        <div class="error">
                            Failed to load transactions. 
                            <button class="btn btn-primary" onclick="safeCall(loadTransactions, 'retry loadTransactions')()">Retry</button>
                        </div>
                    `;
                }
                ErrorHandler.showUserError('Failed to load transactions');
            }
        }

        function displayTransactions(transactionList) {
            try {
                const container = document.getElementById('transactionsList');
                if (!container) {
                    throw new Error('Transaction list container not found');
                }
                
                if (!Array.isArray(transactionList) || transactionList.length === 0) {
                    container.innerHTML = `
                        <p>No transactions found. 
                        <button class="btn btn-success" onclick="safeCall(openAddTransactionModal, 'displayTransactions - add first transaction')()">Add your first transaction</button>
                        </p>
                    `;
                    return;
                }
                
                const transactionCards = transactionList.map(transaction => {
                    try {
                        const amount = transaction.amount || 'N/A';
                        const description = transaction.description || 'N/A';
                        const date = transaction.transaction_date ? new Date(transaction.transaction_date).toLocaleDateString() : 'Invalid Date';
                        const status = transaction.status || 'pending';
                        const type = transaction.payment_type || 'Transaction';
                        
                        return `
                            <div class="card">
                                <h4>${type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</h4>
                                <p><strong>Amount:</strong> $${amount}</p>
                                <p><strong>Description:</strong> ${description}</p>
                                <p><strong>Date:</strong> ${date}</p>
                                <p><strong>Status:</strong> <span class="status-badge status-${status}">${status.toUpperCase()}</span></p>
                                <div class="card-actions">
                                    <button class="btn btn-info" onclick="safeCall(() => editTransaction('${transaction.payment_id || ''}'), 'displayTransactions - edit')()">Edit</button>
                                    <button class="btn btn-danger" onclick="safeCall(() => deleteTransaction('${transaction.payment_id || ''}'), 'displayTransactions - delete')()">Delete</button>
                                </div>
                            </div>
                        `;
                    } catch (cardError) {
                        ErrorHandler.logError(cardError, `displayTransactions - transaction card ${transaction.payment_id}`);
                        return `
                            <div class="card error">
                                <h4>Error displaying transaction</h4>
                                <p>Transaction ID: ${transaction.payment_id || 'Unknown'}</p>
                            </div>
                        `;
                    }
                }).join('');
                
                container.innerHTML = `<div class="card-grid">${transactionCards}</div>`;
            } catch (error) {
                ErrorHandler.logError(error, 'displayTransactions');
                const container = document.getElementById('transactionsList');
                if (container) {
                    container.innerHTML = '<div class="error">Error displaying transactions</div>';
                }
            }
        }

        function searchTransactions(query) {
            try {
                if (!Array.isArray(transactions)) {
                    ErrorHandler.showUserError('Transaction data not available for search');
                    return;
                }
                
                const filtered = transactions.filter(transaction => {
                    try {
                        const searchText = `${transaction.payment_type || ''} ${transaction.description || ''}`.toLowerCase();
                        return searchText.includes((query || '').toLowerCase());
                    } catch (filterError) {
                        ErrorHandler.logError(filterError, `searchTransactions - filter ${transaction.payment_id}`);
                        return false;
                    }
                });
                
                safeCall(displayTransactions, 'searchTransactions - displayTransactions')(filtered);
            } catch (error) {
                ErrorHandler.logError(error, 'searchTransactions');
                ErrorHandler.showUserError('Error searching transactions');
            }
        }

        function openAddTransactionModal() {
            try {
                const modal = document.getElementById('addTransactionModal');
                if (modal) {
                    modal.style.display = 'block';
                } else {
                    throw new Error('Add transaction modal not found');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'openAddTransactionModal');
                ErrorHandler.showUserError('Cannot open add transaction form');
            }
        }

        function closeAddTransactionModal() {
            try {
                const modal = document.getElementById('addTransactionModal');
                const form = document.getElementById('addTransactionForm');
                
                if (modal) {
                    modal.style.display = 'none';
                }
                if (form) {
                    form.reset();
                }
            } catch (error) {
                ErrorHandler.logError(error, 'closeAddTransactionModal');
            }
        }

        async function addTransaction(event) {
            try {
                if (event) {
                    event.preventDefault();
                }
                
                const paymentType = document.getElementById('transactionType')?.value;
                const amount = document.getElementById('transactionAmount')?.value;
                const description = document.getElementById('transactionDescription')?.value;
                const transactionDate = document.getElementById('transactionDate')?.value;
                
                // Validate required fields
                if (!paymentType || !amount || !description || !transactionDate) {
                    throw new Error('Please fill in all required fields');
                }
                
                // Validate amount
                const parsedAmount = parseFloat(amount);
                if (isNaN(parsedAmount) || parsedAmount <= 0) {
                    throw new Error('Please enter a valid amount greater than 0');
                }
                
                const transactionData = {
                    payment_type: paymentType,
                    customer_id: "1227b678-7aa0-4628-bfdf-9fcd362dddb7", // Default customer for now
                    amount: parsedAmount,
                    description: description,
                    transaction_date: transactionDate,
                    payment_method: "cash"
                };
                
                const response = await safeApiCall(`${API_BASE}/financial/payments`, {
                    method: 'POST',
                    body: JSON.stringify(transactionData)
                });
                
                if (response) {
                    ErrorHandler.showToast('Transaction added successfully!', 'success');
                    safeCall(closeAddTransactionModal, 'addTransaction - closeModal')();
                    safeCall(loadTransactions, 'addTransaction - reload')();
                } else {
                    throw new Error('Failed to add transaction');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'addTransaction');
                ErrorHandler.showUserError(`Failed to add transaction: ${error.message}`);
            }
        }

        // Enhanced Reports functions with error handling
        function loadReportsData() {
            try {
                // Calculate and display report statistics with error handling
                let totalRevenue = 0;
                let vehicleUtilization = 0;
                let maintenanceCosts = 0;
                
                try {
                    if (Array.isArray(transactions)) {
                        totalRevenue = transactions
                            .filter(t => t.payment_type && t.payment_type.includes('payment'))
                            .reduce((sum, t) => sum + (parseFloat(t.amount) || 0), 0);
                    }
                } catch (revenueError) {
                    ErrorHandler.logError(revenueError, 'loadReportsData - totalRevenue');
                }
                
                try {
                    if (Array.isArray(vehicles) && vehicles.length > 0) {
                        const rentedCount = vehicles.filter(v => v.status === 'rented').length;
                        vehicleUtilization = Math.round((rentedCount / vehicles.length) * 100);
                    }
                } catch (utilizationError) {
                    ErrorHandler.logError(utilizationError, 'loadReportsData - vehicleUtilization');
                }
                
                try {
                    if (Array.isArray(transactions)) {
                        maintenanceCosts = transactions
                            .filter(t => t.payment_type && t.payment_type.includes('maintenance'))
                            .reduce((sum, t) => sum + (parseFloat(t.amount) || 0), 0);
                    }
                } catch (maintenanceError) {
                    ErrorHandler.logError(maintenanceError, 'loadReportsData - maintenanceCosts');
                }
                
                // Update report display
                const reportsContent = document.getElementById('reportsContent');
                if (reportsContent) {
                    reportsContent.innerHTML = `
                        <div class="dashboard-stats">
                            <div class="stat-card">
                                <div class="stat-number">$${totalRevenue.toFixed(2)}</div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">${vehicleUtilization}%</div>
                                <div class="stat-label">Vehicle Utilization</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">$${maintenanceCosts.toFixed(2)}</div>
                                <div class="stat-label">Maintenance Costs</div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                ErrorHandler.logError(error, 'loadReportsData');
                const reportsContent = document.getElementById('reportsContent');
                if (reportsContent) {
                    reportsContent.innerHTML = '<div class="error">Error loading report data</div>';
                }
            }
        }

        // Enhanced Report generation functions with error handling
        function generateVehicleReport() {
            try {
                if (!Array.isArray(vehicles) || vehicles.length === 0) {
                    ErrorHandler.showUserError('No vehicle data available for report generation');
                    return;
                }
                
                ErrorHandler.showToast('Vehicle report generated successfully', 'success');
                // Report generation logic would go here
            } catch (error) {
                ErrorHandler.logError(error, 'generateVehicleReport');
                ErrorHandler.showUserError('Failed to generate vehicle report');
            }
        }

        function generateRevenueReport() {
            try {
                if (!Array.isArray(transactions) || transactions.length === 0) {
                    ErrorHandler.showUserError('No transaction data available for report generation');
                    return;
                }
                
                ErrorHandler.showToast('Revenue report generated successfully', 'success');
                // Report generation logic would go here
            } catch (error) {
                ErrorHandler.logError(error, 'generateRevenueReport');
                ErrorHandler.showUserError('Failed to generate revenue report');
            }
        }

        function generateCustomerReport() {
            try {
                if (!Array.isArray(customers) || customers.length === 0) {
                    ErrorHandler.showUserError('No customer data available for report generation');
                    return;
                }
                
                ErrorHandler.showToast('Customer report generated successfully', 'success');
                // Report generation logic would go here
            } catch (error) {
                ErrorHandler.logError(error, 'generateCustomerReport');
                ErrorHandler.showUserError('Failed to generate customer report');
            }
        }

        function generateMaintenanceReport() {
            try {
                if (!Array.isArray(maintenanceRecords) || maintenanceRecords.length === 0) {
                    ErrorHandler.showUserError('No maintenance data available for report generation');
                    return;
                }
                
                ErrorHandler.showToast('Maintenance report generated successfully', 'success');
                // Report generation logic would go here
            } catch (error) {
                ErrorHandler.logError(error, 'generateMaintenanceReport');
                ErrorHandler.showUserError('Failed to generate maintenance report');
            }
        }

        // Enhanced Utility functions with error handling
        function showSuccess(message) {
            try {
                ErrorHandler.showToast(message, 'success');
            } catch (error) {
                ErrorHandler.logError(error, 'showSuccess');
                console.log('Success:', message); // Fallback
            }
        }

        function showError(message) {
            try {
                ErrorHandler.showUserError(message);
            } catch (error) {
                ErrorHandler.logError(error, 'showError');
                console.error('Error:', message); // Fallback
            }
        }

        // Enhanced Placeholder functions with error handling and user feedback
        function editVehicle(id) {
            try {
                if (!id) {
                    throw new Error('Vehicle ID is required');
                }
                ErrorHandler.showToast('Edit vehicle functionality will be implemented soon', 'warning');
            } catch (error) {
                ErrorHandler.logError(error, 'editVehicle');
                ErrorHandler.showUserError('Cannot edit vehicle at this time');
            }
        }

        function scheduleMaintenanceVehicle(id) {
            try {
                if (!id) {
                    throw new Error('Vehicle ID is required');
                }
                // Open maintenance modal with pre-selected vehicle
                safeCall(openAddMaintenanceModal, 'scheduleMaintenanceVehicle')();
                // Pre-select the vehicle if possible
                setTimeout(() => {
                    const vehicleSelect = document.getElementById('maintenanceVehicle');
                    if (vehicleSelect) {
                        vehicleSelect.value = id;
                    }
                }, 100);
            } catch (error) {
                ErrorHandler.logError(error, 'scheduleMaintenanceVehicle');
                ErrorHandler.showUserError('Cannot schedule maintenance at this time');
            }
        }

        function archiveVehicle(id) {
            try {
                if (!id) {
                    throw new Error('Vehicle ID is required');
                }
                if (confirm('Are you sure you want to archive this vehicle?')) {
                    ErrorHandler.showToast('Archive vehicle functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'archiveVehicle');
                ErrorHandler.showUserError('Cannot archive vehicle at this time');
            }
        }

        function editCustomer(id) {
            try {
                if (!id) {
                    throw new Error('Customer ID is required');
                }
                ErrorHandler.showToast('Edit customer functionality will be implemented soon', 'warning');
            } catch (error) {
                ErrorHandler.logError(error, 'editCustomer');
                ErrorHandler.showUserError('Cannot edit customer at this time');
            }
        }

        function viewCustomerHistory(id) {
            try {
                if (!id) {
                    throw new Error('Customer ID is required');
                }
                ErrorHandler.showToast('Customer history functionality will be implemented soon', 'warning');
            } catch (error) {
                ErrorHandler.logError(error, 'viewCustomerHistory');
                ErrorHandler.showUserError('Cannot view customer history at this time');
            }
        }

        function deactivateCustomer(id) {
            try {
                if (!id) {
                    throw new Error('Customer ID is required');
                }
                if (confirm('Are you sure you want to deactivate this customer?')) {
                    ErrorHandler.showToast('Deactivate customer functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'deactivateCustomer');
                ErrorHandler.showUserError('Cannot deactivate customer at this time');
            }
        }

        function editReservation(id) {
            try {
                if (!id) {
                    throw new Error('Reservation ID is required');
                }
                ErrorHandler.showToast('Edit reservation functionality will be implemented soon', 'warning');
            } catch (error) {
                ErrorHandler.logError(error, 'editReservation');
                ErrorHandler.showUserError('Cannot edit reservation at this time');
            }
        }

        function checkInReservation(id) {
            try {
                if (!id) {
                    throw new Error('Reservation ID is required');
                }
                if (confirm('Confirm check-in for this reservation?')) {
                    ErrorHandler.showToast('Check-in functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'checkInReservation');
                ErrorHandler.showUserError('Cannot process check-in at this time');
            }
        }

        function checkOutReservation(id) {
            try {
                if (!id) {
                    throw new Error('Reservation ID is required');
                }
                if (confirm('Confirm check-out for this reservation?')) {
                    ErrorHandler.showToast('Check-out functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'checkOutReservation');
                ErrorHandler.showUserError('Cannot process check-out at this time');
            }
        }

        function cancelReservation(id) {
            try {
                if (!id) {
                    throw new Error('Reservation ID is required');
                }
                if (confirm('Are you sure you want to cancel this reservation?')) {
                    ErrorHandler.showToast('Cancel reservation functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'cancelReservation');
                ErrorHandler.showUserError('Cannot cancel reservation at this time');
            }
        }

        function completeMaintenance(id) {
            try {
                if (!id) {
                    throw new Error('Maintenance ID is required');
                }
                if (confirm('Mark this maintenance as completed?')) {
                    ErrorHandler.showToast('Complete maintenance functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'completeMaintenance');
                ErrorHandler.showUserError('Cannot complete maintenance at this time');
            }
        }

        function rescheduleMaintenance(id) {
            try {
                if (!id) {
                    throw new Error('Maintenance ID is required');
                }
                ErrorHandler.showToast('Reschedule maintenance functionality will be implemented soon', 'warning');
            } catch (error) {
                ErrorHandler.logError(error, 'rescheduleMaintenance');
                ErrorHandler.showUserError('Cannot reschedule maintenance at this time');
            }
        }

        function cancelMaintenance(id) {
            try {
                if (!id) {
                    throw new Error('Maintenance ID is required');
                }
                if (confirm('Are you sure you want to cancel this maintenance?')) {
                    ErrorHandler.showToast('Cancel maintenance functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'cancelMaintenance');
                ErrorHandler.showUserError('Cannot cancel maintenance at this time');
            }
        }

        function editTransaction(id) {
            try {
                if (!id) {
                    throw new Error('Transaction ID is required');
                }
                ErrorHandler.showToast('Edit transaction functionality will be implemented soon', 'warning');
            } catch (error) {
                ErrorHandler.logError(error, 'editTransaction');
                ErrorHandler.showUserError('Cannot edit transaction at this time');
            }
        }

        function deleteTransaction(id) {
            try {
                if (!id) {
                    throw new Error('Transaction ID is required');
                }
                if (confirm('Are you sure you want to delete this transaction?')) {
                    ErrorHandler.showToast('Delete transaction functionality will be implemented soon', 'warning');
                }
            } catch (error) {
                ErrorHandler.logError(error, 'deleteTransaction');
                ErrorHandler.showUserError('Cannot delete transaction at this time');
            }
        }

        // Enhanced modal click-outside handling with error protection
        window.onclick = function(event) {
            try {
                const modals = ['addVehicleModal', 'addCustomerModal', 'addReservationModal', 'addMaintenanceModal', 'addTransactionModal'];
                modals.forEach(modalId => {
                    try {
                        const modal = document.getElementById(modalId);
                        if (modal && event.target === modal) {
                            modal.style.display = 'none';
                        }
                    } catch (modalError) {
                        ErrorHandler.logError(modalError, `window.onclick - ${modalId}`);
                    }
                });
            } catch (error) {
                ErrorHandler.logError(error, 'window.onclick');
            }
        }

        // Global error handler for unhandled errors
        window.addEventListener('error', function(event) {
            ErrorHandler.logError(event.error || event.message, 'Global error handler');
            ErrorHandler.showUserError('An unexpected error occurred. The system will continue to function.');
        });

        // Global promise rejection handler
        window.addEventListener('unhandledrejection', function(event) {
            ErrorHandler.logError(event.reason, 'Unhandled promise rejection');
            ErrorHandler.showUserError('An operation failed unexpectedly. Please try again.');
            event.preventDefault(); // Prevent the default console error
        });

    </script>
</body>
</html>

