<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];

try {
    // Fetch existing schedules to display in the list
    // FIX: Changed s.time to s.schedule_time to match the database schema
    $stmt_schedules = $pdo->prepare(
        "SELECT s.id, d.name as device_name, s.action, s.schedule_time 
         FROM schedules s 
         JOIN devices d ON s.device_id = d.id 
         WHERE s.user_id = ? 
         ORDER BY s.schedule_time ASC"
    );
    $stmt_schedules->execute([$user_id]);
    $schedules = $stmt_schedules->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's devices to populate the "Add Schedule" form dropdown
    $stmt_devices = $pdo->prepare("SELECT id, name FROM devices WHERE user_id = ? ORDER BY name ASC");
    $stmt_devices->execute([$user_id]);
    $devices = $stmt_devices->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - HomeSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">

</head>
<body>

    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="../rooms/rooms.php">Rooms</a>
            <a href="schedules.php" class="active">Schedules</a>
        </nav>
        <div class="user-menu">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <div class="management-container">
            <div class="page-header">
                <h1>Manage Schedules</h1>
            </div>

            <div class="management-list">
                <?php if (empty($schedules)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <h2>No Schedules Found</h2>
                        <p>You haven't created any schedules yet. Add one below to get started!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <div class="item-row">
                            <div class="item-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="schedule-item-details">
                                <strong><?php echo htmlspecialchars($schedule['device_name']); ?></strong>
                                <span class="schedule-action-badge action-<?php echo htmlspecialchars($schedule['action']); ?>">
                                    Turn <?php echo htmlspecialchars($schedule['action']); ?>
                                </span>
                                <span class="schedule-time">
                                    <i class="fas fa-clock"></i> at <?php echo htmlspecialchars(date('g:i A', strtotime($schedule['schedule_time']))); ?>
                                </span>
                            </div>
                            <div class="item-actions">
                                <form action="delete_schedule.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                    <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="add-item-form">
                <h3>Add a New Schedule</h3>
                <form action="add_schedule.php" method="POST" class="form-inline">
                    <div class="input-group">
                        <label for="device_id">Device</label>
                        <select id="device_id" name="device_id" required>
                            <option value="" disabled selected>Select a device...</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo $device['id']; ?>">
                                    <?php echo htmlspecialchars($device['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="action">Action</label>
                        <select id="action" name="action" required>
                            <option value="on">Turn On</option>
                            <option value="off">Turn Off</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="time">Time</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Schedule</button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>

