<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit();
}

require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scene_id'])) {
    $scene_id = $_POST['scene_id'];

    try {
        // Use a transaction to ensure data integrity
        $pdo->beginTransaction();

        // 1. Delete all actions associated with this scene for this user
        $stmt_delete_actions = $pdo->prepare("DELETE FROM scene_actions WHERE scene_id = ? AND user_id = ?");
        $stmt_delete_actions->execute([$scene_id, $user_id]);

        // 2. Delete the scene itself for this user
        $stmt_delete_scene = $pdo->prepare("DELETE FROM scenes WHERE id = ? AND user_id = ?");
        $stmt_delete_scene->execute([$scene_id, $user_id]);

        $pdo->commit();
        header('Location: manage_scenes.php?status=scene_deleted');
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete scene error: " . $e->getMessage());
        header('Location: manage_scenes.php?error=database_error');
    }
    exit();
} else {
    header('Location: manage_scenes.php');
    exit();
}
?>
