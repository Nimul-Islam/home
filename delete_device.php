<?php
session_start();
include 'db_connection.php';

// 1. Authentication: Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page.
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Check for Device ID: Ensure a device ID was passed in the URL.
// For enhanced security, this action should ideally be triggered by a POST request
// from a confirmation form to prevent Cross-Site Request Forgery (CSRF).
if (isset($_GET['id'])) {
    $device_id = $_GET['id'];

    try {
        // 3. Prepare SQL Statement: The WHERE clause is critical.
        // It ensures users can only delete devices that they own (matching user_id).
        $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ? AND user_id = ?");
        
        // 4. Execute Deletion: Run the query with the device and user IDs.
        $stmt->execute([$device_id, $user_id]);

        // 5. Redirect: After deletion, send the user back to their dashboard.
        header('Location: dashboard.php?status=deleted');
        exit();

    } catch (PDOException $e) {
        // Handle any database errors during the process.
        // In a production app, you would log this error instead of showing it to the user.
        die("Error deleting device: " . $e->getMessage());
    }
} else {
    // If no device ID is provided in the URL, redirect to the dashboard without taking action.
    header('Location: dashboard.php');
    exit();
}
?>
