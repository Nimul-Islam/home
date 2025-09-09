<?php
session_start();
include 'db_connection.php'; // Ensures you use the same PDO connection as the rest of the app

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get data from the registration form
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Server-side Validation ---
    if (empty($username) || empty($email) || empty($password)) {
        // Redirect back with an error
        header('Location: register.html?error=emptyfields');
        exit();
    }
    if ($password !== $confirm_password) {
        // Redirect back with an error
        header('Location: register.html?error=passwordmismatch');
        exit();
    }
    
    // IMPORTANT: Hash the password for secure storage. Never store plain text passwords.
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Prepare the SQL statement to prevent SQL injection
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Execute the prepared statement with the user's data
        if ($stmt->execute([$username, $email, $password_hash])) {
            // Registration successful, redirect to the login page
            header("Location: login.html?status=success");
            exit();
        } else {
            // A generic error if execute fails for some reason
            header('Location: register.html?error=dberror');
            exit();
        }
    } catch (PDOException $e) {
        // Catch specific database errors, like a duplicate email or username
        if ($e->errorInfo[1] == 1062) {
            // Error code 1062 is for a duplicate entry
            header('Location: register.html?error=usertaken');
            exit();
        } else {
            // For any other database error, redirect with a generic message
            // You should log the actual error for debugging: error_log($e->getMessage());
            header('Location: register.html?error=dberror');
            exit();
        }
    }
} else {
    // If not a POST request, just redirect to the registration page
    header('Location: register.html');
    exit();
}
?>
