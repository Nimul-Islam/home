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
    
    <style>
        /* ===== General Body & Typography ===== */
body {
    font-family: 'Inter', sans-serif;
    background-color: #f8f9fa; /* Lighter background for a cleaner look */
    color: #343a40;
    margin: 0;
    line-height: 1.6;
}

h1, h2, h3 {
    font-weight: 700; /* Bolder headings */
    color: #212529;
}

a {
    color: #007bff; /* Brighter blue for links */
    text-decoration: none;
    transition: color 0.3s ease;
}

a:hover {
    color: #0056b3;
}

/* ===== Main Application Layout ===== */
.app-header {
    background-color: #fff;
    padding: 16px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.app-header .logo {
    font-size: 24px;
    font-weight: 700;
    color: #007bff;
}

.app-header .nav-links a {
    margin: 0 15px;
    font-weight: 500;
    color: #495057;
}
.app-header .nav-links a.active {
    color: #007bff;
    border-bottom: 2px solid #007bff;
    padding-bottom: 4px;
}

.user-menu a {
    font-weight: 500;
    color: #dc3545; /* Red for logout */
}

.main-content {
    padding: 40px;
    max-width: 1400px; /* Wider layout for command center */
    margin: 0 auto;
}

/* ===== Dashboard Layout ===== */
.dashboard-layout {
    display: grid;
    grid-template-columns: 1fr 320px; /* Main content and sidebar */
    gap: 40px;
}
.main-column {
    display: flex;
    flex-direction: column;
    gap: 40px;
}
.sidebar-column {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

/* Page Header */
.page-header {
    margin-bottom: 0;
}
.page-header h1 {
    margin: 0;
    font-size: 2.5rem;
}
.page-header .subtitle {
    margin-top: 5px;
    color: #6c757d;
    font-size: 1.1rem;
}

.btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    padding: 12px 22px;
    border-radius: 8px;
    font-weight: 500;
    border: none;
    transition: transform 0.2s, box-shadow 0.3s;
}
.btn-primary:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

/* ===== Dashboard Sections ===== */
.dashboard-section {
    width: 100%;
}
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.section-header h2 {
    margin: 0;
    font-size: 1.75rem;
}

/* Scenes Grid */
.scenes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
}
.scene-card {
    background-color: #fff;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.scene-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
    color: #007bff;
    border-color: #007bff;
}
.scene-card i {
    font-size: 28px;
    margin-bottom: 5px;
}
.scene-add { color: #6c757d; background-color: #f8f9fa; }
.scene-add:hover { color: #212529; border-color: #ced4da; }

/* Rooms & Devices */
.room-container {
    background-color: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}
.room-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}
.device-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}
.device-card {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
}
.device-card.status-on {
    background-color: #e7f3ff;
    border: 1px solid #007bff;
}
.device-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
}
.device-icon {
    font-size: 36px;
    color: #007bff;
}
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    margin-top: 10px;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ced4da;
    transition: .4s;
    border-radius: 34px;
}
.slider:before {
    position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider { background-color: #007bff; }
input:checked + .slider:before { transform: translateX(26px); }

/* Sidebar */
.sidebar-widget {
    background-color: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}
.sidebar-widget h3 {
    margin-top: 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 10px;
}
.glance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    text-align: center;
}
.glance-item span {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
}
.schedule-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.schedule-list li {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}
.schedule-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
}
.schedule-icon.action-on { background-color: #28a745; }
.schedule-icon.action-off { background-color: #dc3545; }

/* Notification Toast */
.notification-toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(150%);
    background-color: #212529;
    color: #fff;
    padding: 12px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    font-weight: 500;
    transition: transform 0.5s ease-in-out;
    z-index: 2000;
}
.notification-toast.show {
    transform: translateX(-50%) translateY(0);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-layout {
        grid-template-columns: 1fr; /* Stack columns on smaller screens */
    }
    .sidebar-column {
        order: -1; /* Move sidebar to top on mobile */
    }
}
@media (max-width: 768px) {
    .app-header {
        flex-direction: column;
        padding: 16px 20px;
        gap: 10px;
    }
    .main-content {
        padding: 20px;
    }
}

</style>
</head>

<body>

    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="rooms/rooms.php">Rooms</a>
            <a href="schedules/schedules.php">Schedules</a>
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
            <a href="devices/add_device.html" class="btn-primary"><i class="fas fa-plus"></i> Add Device</a>
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
                                    <p>No devices in this room. <a href="devices/add_device.html">Add one!</a></p>
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