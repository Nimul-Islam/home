<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

include '../db_connection.php';
$user_id = $_SESSION['user_id'];
$room_name = '';
$room_id = null;
$error_message = '';
$message = '';

// --- Handle POST request for updating the room name ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['room_id'], $_POST['room_name']) && !empty(trim($_POST['room_name']))) {
        $room_id = $_POST['room_id'];
        $new_room_name = trim($_POST['room_name']);

        try {
            // Update the room name, ensuring the room belongs to the logged-in user
            $stmt = $pdo->prepare("UPDATE rooms SET name = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$new_room_name, $room_id, $user_id]);
            
            // Redirect to the management page with a success status
            header('Location: rooms.php?status=updated');
            exit();
        } catch (PDOException $e) {
            $error_message = "An error occurred while updating the room. Please try again.";
            // error_log($e->getMessage()); // For debugging
        }
    } else {
        $error_message = "Please provide a new name for the room.";
        $room_id = $_POST['room_id'] ?? null; // Keep room_id for the form
    }
}

// --- Handle GET request to fetch room data and display the form ---
// This part runs if it's not a POST request or if the POST had an error
if (isset($_GET['id']) || $room_id !== null) {
    $current_id = $_GET['id'] ?? $room_id;

    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND user_id = ?");
    $stmt->execute([$current_id, $user_id]);
    $room = $stmt->fetch();

    if ($room) {
        $room_id = $room['id'];
        $room_name = $room['name'];
    } else {
        // If room not found or doesn't belong to the user, redirect.
        header('Location: rooms.php?status=notfound');
        exit();
    }
} else {
    // If no ID is provided in GET or POST, it's an invalid access.
    header('Location: rooms.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room - HomeSphere</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-header">
            <h1>Edit Room</h1>
            <p>Update the name of your room.</p>
        </div>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="edit_room.php" method="post" class="auth-form">
            <!-- Hidden input to pass the room ID during submission -->
            <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">

            <div class="input-group">
                <i class="fas fa-door-open"></i>
                <input type="text" name="room_name" placeholder="Room Name" value="<?php echo htmlspecialchars($room_name); ?>" required>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>

        <div class="auth-footer">
            <p><a href="rooms.php">Cancel and Go Back</a></p>
        </div>
    </div>
</div>
</body>
</html>
