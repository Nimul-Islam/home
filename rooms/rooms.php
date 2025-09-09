<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
include '../db_connection.php';
$user_id = $_SESSION['user_id'];

// Handle potential status messages from other pages (e.g., after adding a room)
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'added') {
        $message = '<p class="success-message">Room added successfully!</p>';
    } elseif ($_GET['status'] === 'deleted') {
        $message = '<p class="success-message">Room deleted successfully.</p>';
    } elseif ($_GET['status'] === 'error') {
        $message = '<p class="error-message">An error occurred. Please try again.</p>';
    }
}


// Fetch all rooms for the user
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - HomeSphere</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 600px;">
            <div class="auth-header">
                <h1>Manage Rooms</h1>
                <p>Add, edit, or remove your rooms.</p>
            </div>

            <?php echo $message; // Display status messages here 
            ?>

            <!-- Add Room Form -->
            <form action="add_room.php" method="post" class="auth-form" style="margin-bottom: 2rem;">
                <div class="input-group">
                    <i class="fas fa-door-open"></i>
                    <input type="text" name="room_name" placeholder="New Room Name" required>
                </div>
                <button type="submit" class="btn">Add Room</button>
            </form>

            <!-- List of Existing Rooms -->
            <h3 class="list-header">Your Rooms</h3>
            <div class="list-container">
                <?php if (empty($rooms)): ?>
                    <p style="text-align: center; color: #666;">You haven't added any rooms yet.</p>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="list-item">
                            <span><?php echo htmlspecialchars($room['name']); ?></span>
                            <div class="list-item-actions">
                                <!-- This form allows for secure deletion -->
                                <form action="delete_room.php" method="post" onsubmit="return confirm('Deleting this room will unassign its devices. Are you sure?');" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($room['id']); ?>">
                                    <button type="submit" class="device-action-delete" title="Delete Room">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="auth-footer">
                <p><a href="../dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>

</html>