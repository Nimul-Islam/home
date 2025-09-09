<?php
session_start();

// 1. Authentication: Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

include 'db_connection.php';

// 2. Security: Only proceed if the request is a POST and a schedule ID is provided.
//    Using POST is crucial for any action that deletes data.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $schedule_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // 3. Prepare and execute the delete statement.
        //    The WHERE clause is critical: it ensures users can only delete schedules
        //    that they own (matching both schedule ID and user ID).
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
        $stmt->execute([$schedule_id, $user_id]);
        
        // 4. Redirect on Success: Send the user back with a success message.
        header('Location: schedules.php?status=deleted');
        exit();

    } catch (PDOException $e) {
        // 5. Error Handling: If there's a database error, redirect with an error message.
        // For debugging, you could log the error: error_log($e->getMessage());
        header('Location: schedules.php?status=error');
        exit();
    }

} else {
    // 6. Invalid Access: If the script is accessed directly or without an ID, redirect.
    header('Location: schedules.php');
    exit();
}
?>
