<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit();
}

require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scene_id = $_POST['scene_id'] ?? null;
    $device_id = $_POST['device_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if (empty($scene_id) || empty($device_id) || !in_array($action, ['on', 'off'])) {
        header('Location: manage_scenes.php?error=invalid_input');
        exit();
    }

    try {
        // Security check: Ensure the scene and device both belong to the user
        $stmt_check = $pdo->prepare(
            "SELECT COUNT(*) FROM scenes s JOIN devices d ON d.id = ? 
             WHERE s.id = ? AND s.user_id = ? AND d.user_id = ?"
        );
        $stmt_check->execute([$device_id, $scene_id, $user_id, $user_id]);
        $count = $stmt_check->fetchColumn();

        if ($count == 1) {
            $sql = "INSERT INTO scene_actions (user_id, scene_id, device_id, action) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $scene_id, $device_id, $action]);
            header('Location: manage_scenes.php?status=action_added');
        } else {
            header('Location: manage_scenes.php?error=permission_denied');
        }
    } catch (PDOException $e) {
        error_log("Add scene action error: " . $e->getMessage());
        header('Location: manage_scenes.php?error=database_error');
    }
    exit();
} else {
    header('Location: manage_scenes.php');
    exit();
}
