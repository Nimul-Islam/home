<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.html");
    exit;
}

include 'db_connection.php';
$user_id = $_SESSION['id'];

// Handle device state change
if (isset($_POST['toggle_device'])) {
    $device_id = $_POST['device_id'];
    $current_state = $_POST['is_on'];
    $new_state = $current_state ? 0 : 1;
    $sql = "UPDATE devices SET is_on = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $new_state, $device_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("location: dashboard.php");
    exit;
}


$sql = "SELECT id, device_name, device_type, status, is_on, value FROM devices WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

function getDeviceIcon($type)
{
    switch ($type) {
        case 'light':
            return 'fa-lightbulb';
        case 'fan':
            return 'fa-fan';
        case 'switch':
            return 'fa-toggle-on';
        case 'smart_device':
            return 'fa-robot';
        default:
            return 'fa-microchip';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Home Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Good Morning, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="device-grid">
            <?php while ($device = $result->fetch_assoc()): ?>
                <div class="device-card <?php echo $device['status']; ?> <?php echo $device['is_on'] ? 'on' : 'off'; ?>">
                    <div class="device-card-header">
                        <i class="fas <?php echo getDeviceIcon($device['device_type']); ?> device-icon"></i>
                        <form method="post" action="dashboard.php">
                            <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                            <input type="hidden" name="is_on" value="<?php echo $device['is_on']; ?>">
                            <?php if ($device['status'] === 'connected'): ?>
                                <button type="submit" name="toggle_device" class="btn-toggle"></button>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="device-card-body">
                        <h4><?php echo htmlspecialchars($device['device_name']); ?></h4>
                        <p class="device-status">
                            <?php echo $device['is_on'] ? ucfirst($device['device_type']) . ' is On' : 'Off'; ?>
                            <span class="status-indicator"></span> <?php echo ucfirst($device['status']); ?>
                        </p>
                    </div>
                    <?php if (($device['device_type'] === 'fan' || $device['device_type'] === 'light') && $device['status'] === 'connected' && $device['is_on']): ?>
                        <div class="slider-container">
                            <i class="fas fa-sun"></i>
                            <input type="range" min="0" max="<?php echo $device['device_type'] === 'light' ? '100' : '5'; ?>" value="<?php echo $device['value']; ?>" class="slider">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>

</html>
<?php
$stmt->close();
$conn->close();
?>