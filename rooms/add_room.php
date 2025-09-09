<?php
session_start();

// Ensure the user is logged in before proceeding.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Include the database connection file.
include '../db_connection.php';

// Check if the form was submitted via POST and if a room name was provided.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['room_name']))) {
    
    $user_id = $_SESSION['user_id'];
    $room_name = trim($_POST['room_name']);

    try {
        // Prepare an SQL statement to insert the new room into the database.
        $stmt = $pdo->prepare("INSERT INTO rooms (user_id, name) VALUES (?, ?)");
        
        // Execute the statement with the user's ID and the new room name.
        $stmt->execute([$user_id, $room_name]);

        // Redirect back to the rooms management page with a success message.
        header('Location: rooms.php?status=added');
        exit();

    } catch (PDOException $e) {
        // If a database error occurs, redirect back with an error message.
        // You could log the specific error for debugging: error_log($e->getMessage());
        header('Location: rooms.php?status=error');
        exit();
    }

} else {
    // If the form was accessed directly or with no data, redirect to the rooms page.
    header('Location: rooms.php');
    exit();
}
?>
