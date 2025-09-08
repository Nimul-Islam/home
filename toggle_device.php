<?php
session_start();
header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

include 'db_connection.php';

// Ensure it's a POST request and device_id is set
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['device_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$device_id = $_POST['device_id'];

try {
    // Check if the device belongs to the current user and is connected
    $check_stmt = $pdo->prepare("SELECT status, connection_status FROM devices WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$device_id, $user_id]);
    $device = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($device) {
        if ($device['connection_status'] === 'disconnected') {
            echo json_encode(['status' => 'error', 'message' => 'Device is disconnected']);
            exit();
        }

        // Toggle the status
        $new_status = $device['status'] === 'on' ? 'off' : 'on';
        $update_stmt = $pdo->prepare("UPDATE devices SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $device_id]);

        echo json_encode(['status' => 'success', 'new_status' => $new_status]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Device not found or access denied']);
    }
} catch (PDOException $e) {
    // Log error to a file instead of echoing details to the user
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}

