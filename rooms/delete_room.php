<?php
session_start();

// 1. Authentication: Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

include '../db_connection.php';

// 2. Security: Only proceed if the request is a POST and a room ID is provided.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $room_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 3. Start a transaction to ensure both operations succeed or fail together.
        $pdo->beginTransaction();

        // First, unassign all devices from this room. 
        // This prevents orphaned records and maintains data integrity.
        $stmt_unassign = $pdo->prepare("UPDATE devices SET room_id = NULL WHERE room_id = ? AND user_id = ?");
        $stmt_unassign->execute([$room_id, $user_id]);

        // Second, delete the room itself, ensuring it belongs to the logged-in user.
        $stmt_delete = $pdo->prepare("DELETE FROM rooms WHERE id = ? AND user_id = ?");
        $stmt_delete->execute([$room_id, $user_id]);
        
        // 4. If both operations were successful, commit the changes.
        $pdo->commit();

        // 5. Redirect back with a success message.
        header('Location: rooms.php?status=deleted');
        exit();

    } catch (PDOException $e) {
        // 6. If any error occurred, roll back all changes.
        $pdo->rollBack();
        // For debugging, you could log the error: error_log($e->getMessage());
        header('Location: rooms.php?status=error');
        exit();
    }

} else {
    // If the script was accessed without a POST request, redirect away.
    header('Location: rooms.php');
    exit();
}
?>
