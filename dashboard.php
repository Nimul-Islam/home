<?php
session_start();

// Ensure the user is logged in before accessing the dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require_once 'db_connection.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

try {
    // Fetch rooms
    $stmt_rooms = $pdo->prepare("SELECT * FROM rooms WHERE user_id = ? ORDER BY name ASC");
    $stmt_rooms->execute([$user_id]);
    $rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all devices
    $stmt_devices = $pdo->prepare("SELECT * FROM devices WHERE user_id = ? ORDER BY name ASC");
    $stmt_devices->execute([$user_id]);
    $all_devices = $stmt_devices->fetchAll(PDO::FETCH_ASSOC);

    // Group devices by room_id for easy lookup, and handle unassigned devices
    $devices_by_room = [];
    $unassigned_devices = [];
    foreach ($all_devices as $device) {
        if ($device['room_id'] === null) {
            $unassigned_devices[] = $device;
        } else {
            $devices_by_room[$device['room_id']][] = $device;
        }
    }

    // Fetch dashboard stats
    $total_devices = count($all_devices);
    $devices_on = 0;
    foreach ($all_devices as $device) {
        if ($device['status'] == 1) {
            $devices_on++;
        }
    }

    // Fetch schedules count
    $stmt_schedules_count = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE user_id = ?");
    $stmt_schedules_count->execute([$user_id]);
    $schedule_count = $stmt_schedules_count->fetchColumn();

    // Fetch user's scenes from the database
    $stmt_scenes = $pdo->prepare("SELECT * FROM scenes WHERE user_id = ? ORDER BY id ASC");
    $stmt_scenes->execute([$user_id]);
    $scenes = $stmt_scenes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HomeSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="rooms.php">Rooms</a>
            <a href="schedules.php">Schedules</a>
            <a href="scenes/manage_scenes.php">Scenes</a>
        </nav>
        <div class="user-menu"><a href="logout.php">Logout</a></div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="subtitle">Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
            </div>
            <a href="add_device.html" class="btn-primary"><i class="fas fa-plus"></i> Add Device</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #e0f2fe;"><i class="fas fa-lightbulb" style="color: #0c6ba1;"></i></div>
                <h3><?php echo $total_devices; ?></h3>
                <p>Total Devices</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #d1fae5;"><i class="fas fa-power-off" style="color: #047857;"></i></div>
                <h3><?php echo $devices_on; ?></h3>
                <p>Devices On</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #ffedd5;"><i class="fas fa-clock" style="color: #c2410c;"></i></div>
                <h3><?php echo $schedule_count; ?></h3>
                <p>Active Schedules</p>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Quick Scenes</h2>
                <a href="scenes/manage_scenes.php" class="btn-secondary-outline">Manage Scenes</a>
            </div>
            <div class="scenes-grid">
                <?php foreach ($scenes as $scene): ?>
                    <div class="scene-card <?php echo htmlspecialchars($scene['custom_class']); ?>" onclick="triggerScene(<?php echo $scene['id']; ?>, '<?php echo htmlspecialchars($scene['name']); ?>')">
                        <i class="<?php echo htmlspecialchars($scene['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($scene['name']); ?></span>
                    </div>
                <?php endforeach; ?>
                <a href="scenes/manage_scenes.php" class="scene-card scene-add">
                    <i class="fas fa-plus"></i>
                    <span>Manage Scenes</span>
                </a>
            </div>
        </div>

        <div class="dashboard-section">
            <?php if (empty($rooms) && empty($all_devices)): ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <h2>Your Home is Empty</h2>
                    <p>Get started by adding a room and then adding your first device.</p>
                    <a href="rooms.php" class="btn-primary" style="margin-top:15px;">Manage Rooms</a>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="room-container">
                        <div class="room-header">
                            <h2><?php echo htmlspecialchars($room['name']); ?></h2>
                            <span class="device-count"><?php echo count($devices_by_room[$room['id']] ?? []); ?> Devices</span>
                        </div>
                        <div class="device-grid">
                            <?php if (!empty($devices_by_room[$room['id']])): ?>
                                <?php foreach ($devices_by_room[$room['id']] as $device): ?>
                                    <?php include 'device_card_template.php'; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state minimal">
                                    <p>No devices in this room. <a href="add_device.html">Add one!</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!empty($unassigned_devices)): ?>
                    <div class="room-container">
                        <div class="room-header">
                            <h2>Unassigned Devices</h2>
                        </div>
                        <div class="device-grid">
                            <?php foreach ($unassigned_devices as $device): ?>
                                <?php include 'device_card_template.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <div id="notification" class="notification"></div>

    <script>
        function triggerScene(sceneId, sceneName) {
            fetch('scenes/trigger_scene.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        scene_id: sceneId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`Scene "${sceneName}" triggered successfully!`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(`Error: ${data.message || 'Could not trigger scene.'}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    showNotification('An error occurred while connecting to the server.', 'error');
                });
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification';
            notification.classList.add(type === 'success' ? 'show-success' : 'show-error');
            setTimeout(() => notification.classList.remove('show-success', 'show-error'), 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-checkbox').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    const deviceId = this.dataset.deviceId;
                    const isChecked = this.checked;
                    const card = this.closest('.device-card');
                    const statusText = card.querySelector('.device-status');

                    fetch('toggle_device.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: deviceId,
                                status: isChecked ? 1 : 0
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                statusText.textContent = isChecked ? 'Status: On' : 'Status: Off';
                                card.classList.toggle('status-on', isChecked);
                            } else {
                                alert('Error: ' + data.message);
                                toggle.checked = !isChecked; // Revert the toggle on failure
                            }
                        }).catch(error => {
                            console.error('Toggle Error:', error);
                            alert('Could not update device status.');
                            toggle.checked = !isChecked; // Revert the toggle on failure
                        });
                });
            });
        });
    </script>
</body>

</html>