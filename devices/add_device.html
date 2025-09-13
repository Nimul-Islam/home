<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit();
}

require_once '../db_connection.php';
$user_id = $_SESSION['user_id'];

// Handle potential status messages
$message = '';
$message_type = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'added':
            $message = 'Room added successfully!';
            $message_type = 'success';
            break;
        case 'deleted':
            $message = 'Room deleted successfully.';
            $message_type = 'success';
            break;
        case 'error':
            $message = 'An error occurred. Please try again.';
            $message_type = 'error';
            break;
    }
}

try {
    // Fetch all rooms for the user
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE user_id = ? ORDER BY name ASC");
    $stmt->execute([$user_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching rooms: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - HomeSphere</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Link to the single, consolidated stylesheet -->
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <header class="app-header">
        <div class="logo">HomeSphere</div>
        <nav class="nav-links">
            <a href="../dashboard.php">Dashboard</a>
            <a href="rooms.php" class="active">Rooms</a>
            <a href="../schedules/schedules.php">Schedules</a>
            <a href="../scenes/manage_scenes.php">Scenes</a>
        </nav>
        <div class="user-menu">
            <a href="../logout.php">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <div class="management-container">
            <div class="page-header">
                <h1>Manage Rooms</h1>
            </div>

            <!-- Display success or error messages here -->
            <?php if ($message): ?>
                <div class="message-container <?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="management-list">
                <?php if (empty($rooms)): ?>
                    <div class="empty-state">
                        <i class="fas fa-door-open"></i>
                        <h2>No Rooms Found</h2>
                        <p>You haven't added any rooms yet. Create your first one below!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="item-row">
                            <div class="item-icon"><i class="fas fa-door-closed"></i></div>
                            <div class="item-details">
                                <strong><?php echo htmlspecialchars($room['name']); ?></strong>
                            </div>
                            <div class="item-actions">
                                <form action="delete_room.php" method="POST" onsubmit="return confirm('Deleting this room will unassign its devices. Are you sure?');">
                                    <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" class="delete-btn" title="Delete Room">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="add-item-form">
                <h3>Add a New Room</h3>
                <form action="add_room.php" method="POST" class="form-inline">
                    <div class="input-group">
                        <label for="room_name">Room Name</label>
                        <input type="text" id="room_name" name="room_name" placeholder="e.g., Living Room" required>
                    </div>
                    <button type="submit" class="btn-primary">Add Room</button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>

