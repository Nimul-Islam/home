<?php
session_start();
header('Content-Type: application/json');

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

require_once 'db_connection.php';
$user_id = $_SESSION['user_id'];

// 2. Get the scene ID from the incoming request
$input = json_decode(file_get_contents('php://input'), true);
$scene_id = $input['scene_id'] ?? null;

if (!$scene_id) {
    echo json_encode(['success' => false, 'message' => 'Scene ID not provided.']);
    exit();
}

try {
    // 3. Start a transaction
    $pdo->beginTransaction();

    // 4. Fetch all actions for the given scene, ensuring it belongs to the user
    $stmt_actions = $pdo->prepare(
        "SELECT sa.device_id, sa.action 
         FROM scene_actions sa
         JOIN scenes s ON sa.scene_id = s.id
         WHERE sa.scene_id = ? AND s.user_id = ?"
    );
    $stmt_actions->execute([$scene_id, $user_id]);
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    if (empty($actions)) {
        // If there are no actions, it's not an error, just nothing to do.
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Scene has no actions configured.']);
        exit();
    }

    // 5. Execute each action
    foreach ($actions as $action) {
        $new_status = ($action['action'] === 'on') ? 1 : 0;
        $stmt_update = $pdo->prepare("UPDATE devices SET status = ? WHERE id = ? AND user_id = ?");
        $stmt_update->execute([$new_status, $action['device_id'], $user_id]);
    }

    // 6. Commit the transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Scene activated successfully!']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Scene trigger failed: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'An error occurred while activating the scene.']);
}
