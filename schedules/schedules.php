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
    
    <style>
        /* ===== General Body & Typography ===== */
body {
    font-family: 'Inter', sans-serif;
    background-color: #f8f9fa;
    color: #343a40;
    margin: 0;
    line-height: 1.6;
}

h1, h3 {
    font-weight: 700;
    color: #212529;
}

a {
    color: #007bff;
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
    color: #dc3545;
}

.main-content {
    padding: 40px;
    max-width: 900px;
    margin: 0 auto;
}

.management-container {
    width: 100%;
}

.page-header {
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.page-header h1 {
    margin: 0;
    font-size: 2.25rem;
}

/* ===== Schedule List Styles ===== */
.management-list {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    overflow: hidden;
}

.item-row {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s ease;
}

.item-row:last-child {
    border-bottom: none;
}

.item-row:hover {
    background-color: #f8f9fa;
}

.item-icon {
    font-size: 20px;
    color: #007bff;
}

.schedule-item-details {
    flex-grow: 1;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.schedule-item-details strong {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.schedule-action-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #fff;
    text-transform: uppercase;
}

.schedule-action-badge.action-on {
    background-color: #28a745; /* Green */
}

.schedule-action-badge.action-off {
    background-color: #dc3545; /* Red */
}

.schedule-time {
    color: #6c757d;
    font-size: 0.95rem;
    margin-left: auto; /* Pushes time to the right */
}

.schedule-time i {
    margin-right: 5px;
}

.item-actions .delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #6c757d;
    font-size: 1rem;
    transition: color 0.2s ease;
}

.item-actions .delete-btn:hover {
    color: #dc3545;
}

/* ===== Add Schedule Form ===== */
.add-item-form {
    margin-top: 40px;
    padding: 30px;
    border-radius: 12px;
    background-color: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}

.add-item-form h3 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 1.5rem;
}

.add-item-form .form-inline {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    align-items: flex-end;
}

.add-item-form .input-group {
    display: flex;
    flex-direction: column;
}

.add-item-form label {
    margin-bottom: 8px;
    font-weight: 500;
    font-size: 0.9rem;
}

.add-item-form select,
.add-item-form input[type="time"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    box-sizing: border-box;
    background-color: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.add-item-form select:focus,
.add-item-form input[type="time"]:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
}

.btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    padding: 12px 22px;
    border-radius: 8px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.3s;
    height: 48px; /* Align with inputs */
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 15px;
    display: block;
    color: #ced4da;
}

.empty-state h2 {
    margin-bottom: 10px;
}

.empty-state p {
    margin: 0;
    font-size: 1.1rem;
}


    </style>

</head>
<body>

    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="../dashboard.php">Dashboard</a>
            <a href="../rooms/rooms.php">Rooms</a>
            <a href="schedules.php" class="active">Schedules</a>
            <a href="../scenes/manage_scenes.php">Scenes</a>
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

