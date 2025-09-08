<?php
session_start();
include 'db_connection.php';

// 1. Authentication: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$device = null;
$error_message = '';
$device_id = null;

// 2. Handle POST request (form submission to update device)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required fields are present
    if (isset($_POST['device_id'], $_POST['device_name'], $_POST['device_type'])) {
        $device_id = $_POST['device_id'];
        $device_name = htmlspecialchars($_POST['device_name']);
        $device_type = htmlspecialchars($_POST['device_type']);

        try {
            // Prepare and execute the UPDATE statement
            $stmt = $pdo->prepare("UPDATE devices SET name = ?, type = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$device_name, $device_type, $device_id, $user_id]);

            // Redirect to dashboard after successful update
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $error_message = "Error updating device: " . $e->getMessage();
        }
    } else {
        $error_message = "All fields are required.";
    }
} 
// 3. Handle GET request (displaying the form to edit)
else if (isset($_GET['id'])) {
    $device_id = $_GET['id'];
    try {
        // Fetch the device details for the given ID and user
        $stmt = $pdo->prepare("SELECT id, name, type FROM devices WHERE id = ? AND user_id = ?");
        $stmt->execute([$device_id, $user_id]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no device is found, redirect to dashboard
        if (!$device) {
            header('Location: dashboard.php');
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching device data: " . $e->getMessage();
    }
} 
// 4. If no ID is provided in a GET request, redirect
else {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Device - HomeSphere</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                
                <h1>Edit Device</h1>
                <p>Update the details for your device below.</p>
            </div>

            <?php if ($error_message): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <?php if ($device): ?>
            <form action="edit_device.php" method="post" class="auth-form">
                <!-- Hidden input to pass the device ID during submission -->
                <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['id']); ?>">

                <div class="input-group">
                    <i class="fas fa-tag"></i>
                    <input type="text" id="device_name" name="device_name" value="<?php echo htmlspecialchars($device['name']); ?>" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-lightbulb"></i>
                    <select id="device_type" name="device_type" required>
                        <option value="light" <?php if ($device['type'] === 'light') echo 'selected'; ?>>Light</option>
                        <option value="fan" <?php if ($device['type'] === 'fan') echo 'selected'; ?>>Fan</option>
                        <option value="smart_device" <?php if ($device['type'] === 'smart_device') echo 'selected'; ?>>Smart Device</option>
                    </select>
                </div>

                <button type="submit" class="btn">Save Changes</button>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>
</html>
