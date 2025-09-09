<?php
session_start();

// Set the response header to indicate JSON content
header('Content-Type: application/json');

// 1. Authentication Check: Ensure a user is logged in.
if (!isset($_SESSION['user_id'])) {
    // If not logged in, send an error response and exit.
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// 2. Include the database connection file.
// Using require_once ensures the script will stop if the connection file is missing.
require_once 'db_connection.php';
$user_id = $_SESSION['user_id'];

// 3. Get the data sent from the dashboard's JavaScript (AJAX call).
// The data is sent as a JSON string in the request body.
$input = json_decode(file_get_contents('php://input'), true);
$device_id = $input['id'] ?? null;
$new_status = $input['status'] ?? null;

// 4. Validate the received data.
// Ensure a device ID was provided and the status is either 0 (off) or 1 (on).
if ($device_id === null || !in_array($new_status, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input provided.']);
    exit();
}

try {
    // 5. Prepare the SQL statement to update the device status.
    // This is the most critical part for security. The WHERE clause ensures that
    // users can ONLY update devices that belong to them (matching user_id).
    $sql = "UPDATE devices SET status = ? WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);

    // 6. Execute the update.
    $stmt->execute([$new_status, $device_id, $user_id]);

    // 7. Check if the update was successful.
    // rowCount() returns the number of rows affected. If it's > 0, the update worked.
    if ($stmt->rowCount() > 0) {
        // Send a success response back to the JavaScript.
        echo json_encode(['success' => true, 'message' => 'Device status updated.']);
    } else {
        // This can happen if the device ID doesn't belong to the user.
        echo json_encode(['success' => false, 'message' => 'Permission denied or device not found.']);
    }
} catch (PDOException $e) {
    // 8. Handle any potential database errors.
    // It's good practice to log the error for debugging, not show it to the user.
    error_log("Toggle device error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
