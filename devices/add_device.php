<?php
session_start();

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not, redirect to the login page
    header('Location: ../login.html');
    exit();
}

// 2. Check if the form was submitted via POST and if data is present
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['device_name']) && !empty($_POST['device_type'])) {
    
    // Include the database connection file
    include '../db_connection.php';

    // 3. Sanitize and retrieve form data
    $device_name = htmlspecialchars($_POST['device_name']);
    $device_type = htmlspecialchars($_POST['device_type']);
    $user_id = $_SESSION['user_id'];

    // Set default values for a new device
    $status = 'off'; // New devices are off by default
    $connection_status = 'connected'; // Assume connected when added

    try {
        // 4. Prepare and execute the SQL INSERT statement
        $sql = "INSERT INTO devices (user_id, name, type, status, connection_status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        // Execute the statement with the form data
        if ($stmt->execute([$user_id, $device_name, $device_type, $status, $connection_status])) {
            // 5. If successful, redirect to the dashboard
            header('Location: ../dashboard.php');
            exit();
        } else {
            // Handle potential execution errors
            echo "Error: Could not add the device.";
        }
    } catch (PDOException $e) {
        // Handle database connection or query errors
        // For a real application, you might log this error instead of displaying it
        die("Database error: " . $e->getMessage());
    }
} else {
    // If the form was not submitted correctly, redirect back to the add device page
    header('Location: add_device.html');
    exit();
}
?>
