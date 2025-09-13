<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit();
}

require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

try {
    // Fetch all user's scenes
    $stmt_scenes = $pdo->prepare("SELECT * FROM scenes WHERE user_id = ? ORDER BY name ASC");
    $stmt_scenes->execute([$user_id]);
    $scenes = $stmt_scenes->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all user's devices for the dropdown
    $stmt_devices = $pdo->prepare("SELECT id, name FROM devices WHERE user_id = ? ORDER BY name ASC");
    $stmt_devices->execute([$user_id]);
    $devices = $stmt_devices->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all actions and group them by scene_id
    $stmt_actions = $pdo->prepare(
        "SELECT sa.id, sa.scene_id, sa.action, d.name as device_name 
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Link to the main external stylesheet -->
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="../dashboard.php">Dashboard</a>
            <a href="../rooms/rooms.php">Rooms</a>
            <a href="../schedules/schedules.php">Schedules</a>
            <a href="manage_scenes.php" class="active">Scenes</a>
        </nav>
        <div class="user-menu"><a href="../logout.php">Logout</a></div>
    </header>

    <main class="main-content">
        <div class="management-container">
            <div class="page-header">
                <h1>Manage Scenes</h1>
            </div>

            <!-- This container will be populated by JavaScript with any success/error messages -->
            <div id="message-container"></div>

            <div class="add-item-form">
                <h3>Create a New Scene</h3>
                <form action="add_scene.php" method="POST" class="form-grid">
                    <div class="input-group">
                        <label for="scene_name">Scene Name</label>
                        <input type="text" id="scene_name" name="scene_name" placeholder="e.g., Movie Time" required>
                    </div>
                    <div class="input-group">
                        <label for="icon">Font Awesome Icon Class</label>
                        <input type="text" id="icon" name="icon" placeholder="e.g., fas fa-film" required>
                    </div>
                    <button type="submit" class="btn-primary">Create Scene</button>
                </form>
            </div>

            <div class="scenes-list-container">
                <h2>Your Scenes</h2>
                <?php if (empty($scenes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <p>You haven't created any scenes yet. Use the form above to add your first one!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scenes as $scene): ?>
                        <div class="scene-management-card">
                            <div class="scene-card-header">
                                <h3><i class="<?php echo htmlspecialchars($scene['icon']); ?>"></i> <?php echo htmlspecialchars($scene['name']); ?></h3>
                                <form action="delete_scene.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this entire scene?');">
                                    <input type="hidden" name="id" value="<?php echo $scene['id']; ?>">
                                    <button type="submit" class="delete-btn" title="Delete Scene"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                            <div class="scene-card-body">
                                <h4>Actions in this Scene</h4>
                                <?php if (!empty($actions_by_scene[$scene['id']])): ?>
                                    <?php foreach ($actions_by_scene[$scene['id']] as $action): ?>
                                        <div class="scene-action-row">
                                            <div class="item-details">
                                                <?php echo htmlspecialchars($action['device_name']); ?>
                                                <span class="action-badge action-<?php echo htmlspecialchars($action['action']); ?>">
                                                    Turn <?php echo htmlspecialchars($action['action']); ?>
                                                </span>
                                            </div>
                                            <form action="delete_scene_action.php" method="POST">
                                                <input type="hidden" name="id" value="<?php echo $action['id']; ?>">
                                                <button type="submit" class="delete-btn" title="Remove Action"><i class="fas fa-times"></i></button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="empty-state minimal">No actions have been added to this scene yet.</p>
                                <?php endif; ?>

                                <form action="add_scene_action.php" method="POST" class="add-action-form">
                                    <h4>Add New Action</h4>
                                    <div class="form-inline">
                                        <input type="hidden" name="scene_id" value="<?php echo $scene['id']; ?>">
                                        <div class="input-group">
                                            <label>Device</label>
                                            <select name="device_id" required>
                                                <option value="" disabled selected>Select device...</option>
                                                <?php foreach ($devices as $device): ?>
                                                    <option value="<?php echo $device['id']; ?>"><?php echo htmlspecialchars($device['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="input-group">
                                            <label>Action</label>
                                            <select name="action" required>
                                                <option value="on">Turn On</option>
                                                <option value="off">Turn Off</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn-primary">Add Action</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // This script reads URL parameters to display success or error messages after a form submission.
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const messageContainer = document.getElementById('message-container');
            let message = '';
            let messageClass = '';

            if (params.has('error')) {
                messageClass = 'error-message';
                switch (params.get('error')) {
                    case 'invalid_input':
                        message = 'Invalid input. Please select a device and an action.';
                        break;
                    case 'emptyfields':
                        message = 'Please provide a name and icon for your new scene.';
                        break;
                    default:
                        message = 'An unknown error occurred. Please try again.';
                }
            } else if (params.has('status')) {
                messageClass = 'success-message';
                 switch (params.get('status')) {
                    case 'scene_added':
                        message = 'New scene created successfully!';
                        break;
                    case 'action_added':
                        message = 'Action added to the scene successfully!';
                        break;
                    case 'scene_deleted':
                        message = 'Scene deleted successfully.';
                        break;
                    case 'action_deleted':
                         message = 'Action removed from the scene.';
                        break;
                }
            }

            if (message) {
                messageContainer.innerHTML = `<div class="message-container ${messageClass}">${message}</div>`;
                // Clears the URL parameters so the message disappears on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>

