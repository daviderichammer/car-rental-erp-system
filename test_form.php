<?php
// Database connection
$host = 'localhost';
$dbname = 'car_rental_erp';
$username = 'root';
$password = 'SecureRootPass123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>Database connected successfully!</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
}

// Process form submission
if ($_POST) {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['action']) && $_POST['action'] == 'add_vehicle') {
        try {
            $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, license_plate, daily_rate, status) VALUES (?, ?, ?, ?, ?, 'AVAILABLE')");
            $result = $stmt->execute([
                $_POST['make'],
                $_POST['model'],
                $_POST['year'],
                $_POST['license_plate'],
                $_POST['daily_rate']
            ]);
            
            if ($result) {
                echo "<p style='color: green; font-weight: bold;'>SUCCESS: Vehicle added to database!</p>";
            } else {
                echo "<p style='color: red;'>FAILED: Could not add vehicle to database</p>";
            }
        } catch(PDOException $e) {
            echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Form Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; }
        input { padding: 8px; width: 200px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Simple Vehicle Form Test</h1>
    
    <form method="POST" action="">
        <input type="hidden" name="action" value="add_vehicle">
        
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
            <input type="number" name="year" required>
        </div>
        
        <div class="form-group">
            <label>License Plate:</label>
            <input type="text" name="license_plate" required>
        </div>
        
        <div class="form-group">
            <label>Daily Rate:</label>
            <input type="number" name="daily_rate" step="0.01" required>
        </div>
        
        <button type="submit">Add Vehicle (No JS)</button>
    </form>
    
    <hr>
    
    <h3>Current Vehicles in Database:</h3>
    <?php
    try {
        $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC LIMIT 5");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($vehicles) {
            echo "<ul>";
            foreach ($vehicles as $vehicle) {
                echo "<li>{$vehicle['make']} {$vehicle['model']} ({$vehicle['year']}) - {$vehicle['license_plate']} - \${$vehicle['daily_rate']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No vehicles found in database.</p>";
        }
    } catch(PDOException $e) {
        echo "<p style='color: red;'>Error fetching vehicles: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html>

