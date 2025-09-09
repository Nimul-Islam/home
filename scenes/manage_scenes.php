<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit();
}

require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];

try {
    // Fetch all scenes for the user
    $stmt_scenes = $pdo->prepare("SELECT * FROM scenes WHERE user_id = ? ORDER BY name ASC");
    $stmt_scenes->execute([$user_id]);
    $scenes = $stmt_scenes->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all scene actions and group them by scene_id for easy lookup
    $stmt_actions = $pdo->prepare(
        "SELECT sa.id, sa.scene_id, d.name as device_name, sa.action 
         FROM scene_actions sa
         JOIN devices d ON sa.device_id = d.id
         WHERE sa.user_id = ?"
    );
    $stmt_actions->execute([$user_id]);
    $all_actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    $actions_by_scene = [];
    foreach ($all_actions as $action) {
        $actions_by_scene[$action['scene_id']][] = $action;
    }

    // Fetch all user devices to populate dropdowns
    $stmt_devices = $pdo->prepare("SELECT id, name FROM devices WHERE user_id = ? ORDER BY name ASC");
    $stmt_devices->execute([$user_id]);
    $devices = $stmt_devices->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching scene data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scenes - HomeSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="../dashboard.php">Dashboard</a>
            <a href="../rooms.php">Rooms</a>
            <a href="../schedules.php">Schedules</a>
            <a href="manage_scenes.php" class="active">Scenes</a>
        </nav>
        <div class="user-menu"><a href="../logout.php">Logout</a></div>
    </header>

    <main class="main-content">
        <div class="management-container">
            <div class="page-header">
                <h1>Manage Scenes</h1>
                <p class="subtitle">Create and configure your one-click automations.</p>
            </div>

            <!-- Form to Add a New Scene -->
            <div class="add-item-form">
                <h3>Create a New Scene</h3>
                <form action="add_scene.php" method="POST" class="form-inline">
                    <div class="input-group"><label for="scene_name">Scene Name</label><input type="text" id="scene_name" name="scene_name" required></div>
                    <div class="input-group"><label for="icon">Icon (e.g., fas fa-sun)</label><input type="text" id="icon" name="icon" value="fas fa-star"></div>
                    <div class="input-group"><label for="custom_class">Custom Class (Optional)</label><input type="text" id="custom_class" name="custom_class" placeholder="e.g., scene-relax"></div>
                    <button type="submit" class="btn-primary">Create Scene</button>
                </form>
            </div>

            <!-- List of Existing Scenes -->
            <div class="scenes-management-grid">
                <?php if (empty($scenes)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-magic"></i>
                        <h2>No Scenes Created Yet</h2>
                        <p>Use the form above to create your first scene.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scenes as $scene): ?>
                        <div class="scene-management-card">
                            <div class="scene-card-header">
                                <div class="scene-info">
                                    <i class="<?php echo htmlspecialchars($scene['icon']); ?>"></i>
                                    <h3><?php echo htmlspecialchars($scene['name']); ?></h3>
                                </div>
                                <form action="delete_scene_action.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this entire scene and all its actions?');">
                                    <input type="hidden" name="scene_id" value="<?php echo $scene['id']; ?>">
                                    <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete Scene</button>
                                </form>
                            </div>

                            <div class="scene-actions-list">
                                <h4>Actions in this Scene:</h4>
                                <?php if (!empty($actions_by_scene[$scene['id']])): ?>
                                    <ul>
                                        <?php foreach ($actions_by_scene[$scene['id']] as $action): ?>
                                            <li>
                                                <span>Turn <strong><?php echo htmlspecialchars($action['device_name']); ?></strong> <?php echo htmlspecialchars($action['action']); ?></span>
                                                <form action="delete_scene_action.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action_id" value="<?php echo $action['id']; ?>">
                                                    <button type="submit" class="delete-action-btn">&times;</button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="no-actions">No actions defined for this scene yet.</p>
                                <?php endif; ?>
                            </div>

                            <div class="add-action-form">
                                <h4>Add Action:</h4>
                                <form action="add_scene_action.php" method="POST">
                                    <input type="hidden" name="scene_id" value="<?php echo $scene['id']; ?>">
                                    <select name="device_id" required>
                                        <option value="" disabled selected>Select Device</option><?php foreach ($devices as $device) {
                                                                                                        echo "<option value='{$device['id']}'>" . htmlspecialchars($device['name']) . "</option>";
                                                                                                    } ?>
                                    </select>
                                    <select name="action" required>
                                        <option value="on">Turn On</option>
                                        <option value="off">Turn Off</option>
                                    </select>
                                    <button type="submit" class="btn-secondary-outline">Add</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>