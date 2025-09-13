<?php
session_start();
header('Content-Type: application/json');

// 1. Authentication Check: Ensure a user is logged in.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

// 2. Database Connection
require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];

// 3. Get the scene ID from the incoming JSON request
$input = json_decode(file_get_contents('php://input'), true);
$scene_id = $input['scene_id'] ?? null;

if (!$scene_id) {
    echo json_encode(['success' => false, 'message' => 'Scene ID not provided.']);
    exit();
}

try {
    // 4. Start a database transaction.
    // This ensures that all device updates for the scene either succeed or fail together.
    $pdo->beginTransaction();

    // 5. Fetch all actions for the given scene, ensuring it belongs to the logged-in user.
    $stmt_actions = $pdo->prepare(
        "SELECT sa.device_id, sa.action 
         FROM scene_actions sa
         JOIN scenes s ON sa.scene_id = s.id
         WHERE sa.scene_id = ? AND s.user_id = ?"
    );
    $stmt_actions->execute([$scene_id, $user_id]);
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    // If the scene has no actions, it's not an error.
    if (empty($actions)) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Scene has no actions configured.']);
        exit();
    }

    // 6. Loop through and execute each action.
    foreach ($actions as $action) {
        // Convert 'on'/'off' string to a 1 or 0 for the database.
        $new_status = ($action['action'] === 'on') ? 1 : 0;
        
        // Prepare a statement to update the device's status.
        // The WHERE clause also checks for user_id for an extra layer of security.
        $stmt_update = $pdo->prepare("UPDATE devices SET status = ? WHERE id = ? AND user_id = ?");
        $stmt_update->execute([$new_status, $action['device_id'], $user_id]);
    }

    // 7. If all updates were successful, commit the transaction.
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Scene activated successfully!']);

} catch (PDOException $e) {
    // 8. If any error occurred, roll back the entire transaction.
    $pdo->rollBack();
    error_log("Scene trigger failed: " . $e->getMessage()); // Log the specific error for debugging.
    echo json_encode(['success' => false, 'message' => 'An error occurred while activating the scene.']);
}

