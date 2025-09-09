<?php
session_start();
// Use require_once for robustness. It will cause a fatal error if the file is not found.
require_once 'db_connection.php'; // Use the same PDO connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 1. Basic Validation: Check for empty fields
    if (empty($username) || empty($password)) {
        header("Location: login.html?error=emptyfields");
        exit();
    }

    try {
        // 2. Prepare SQL to find user by username OR email for flexibility
        $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $username]);

        // 3. Check if a user was found
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Verify the password against the stored hash
            if (password_verify($password, $user['password'])) {
                // Password is correct, start the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Redirect to the main dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // Password does not match
                header("Location: login.html?error=wrongpassword");
                exit();
            }
        } else {
            // No user found with that username or email
            header("Location: login.html?error=nouser");
            exit();
        }
    } catch (PDOException $e) {
        // Handle potential database errors
        // For debugging: error_log("Login Error: " . $e->getMessage());
        header("Location: login.html?error=dberror");
        exit();
    }
} else {
    // If accessed directly without a POST request, redirect to login page
    header("Location: login.html");
    exit();
}
?>
