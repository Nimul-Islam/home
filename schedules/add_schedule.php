<?php
session_start();

// 1. Authentication Check: Ensure a user is logged in before proceeding.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

include 'db_connection.php';

// 2. Security & Input Validation:
//    - Ensure the request is a POST request.
//    - Check that all required fields (device_id, action, scheduled_time) are present and not empty.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['device_id'], $_POST['action'], $_POST['scheduled_time']) &&
    !empty($_POST['device_id']) && !empty($_POST['scheduled_time'])) {

    $user_id = $_SESSION['user_id'];
    $device_id = $_POST['device_id'];
    $action = $_POST['action'];
    $scheduled_time = $_POST['scheduled_time'];

    // 3. Action Validation: Only allow 'ON' or 'OFF' as valid actions.
    if ($action !== 'ON' && $action !== 'OFF') {
        header('Location: schedules.php?status=error');
        exit();
    }

    try {
        // 4. Ownership Verification: Before inserting, verify the device belongs to the logged-in user.
        //    This prevents a user from scheduling actions for another user's devices.
        $verify_stmt = $pdo->prepare("SELECT id FROM devices WHERE id = ? AND user_id = ?");
        $verify_stmt->execute([$device_id, $user_id]);
        
        if ($verify_stmt->fetch()) {
            // 5. Database Insertion: If verification passes, insert the new schedule.
            $insert_stmt = $pdo->prepare(
                "INSERT INTO schedules (user_id, device_id, action, scheduled_time) VALUES (?, ?, ?, ?)"
            );
            $insert_stmt->execute([$user_id, $device_id, $action, $scheduled_time]);

            // 6. Redirect on Success: Send the user back to the schedules page with a success message.
            header('Location: schedules.php?status=added');
            exit();
        } else {
            // If device doesn't belong to the user, redirect with an error.
            throw new Exception("Device ownership verification failed.");
        }

    } catch (Exception $e) {
        // 7. Error Handling: Catch any database or verification errors and redirect.
        // For debugging, you might want to log the error: error_log($e->getMessage());
        header('Location: schedules.php?status=error');
        exit();
    }
} else {
    // 8. Invalid Access: If the script is accessed directly or with missing data, redirect.
    header('Location: schedules.php');
    exit();
}
?>
