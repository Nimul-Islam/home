<?php
session_start();
include 'db_connection.php';

// 1. Authentication: Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Use POST for Security: Check if the request method is POST and an ID is provided.
// This prevents CSRF attacks where a malicious link could trigger the deletion.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $device_id = $_POST['id'];

    try {
        // 3. Prepare SQL Statement: The WHERE clause is critical.
        // It ensures users can only delete devices that they own (matching user_id).
        $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ? AND user_id = ?");

        // 4. Execute Deletion: Run the query with the device and user IDs.
        $stmt->execute([$device_id, $user_id]);

        // 5. Redirect: After deletion, send the user back to their dashboard with a success message.
        header('Location: dashboard.php?status=deleted_successfully');
        exit();
    } catch (PDOException $e) {
        // Handle any database errors during the process.
        // In a production app, you would log this error instead of showing it to the user.
        die("Error deleting device: " . $e->getMessage());
    }
} else {
    // If the script is accessed improperly (e.g., via GET), redirect to the dashboard.
    header('Location: dashboard.php');
    exit();
}
