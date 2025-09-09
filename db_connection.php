<?php
// Database configuration
$host = 'localhost';
$dbname = 'home_automation'; // Make sure this is your correct database name
$username = 'root';          // Your database username
$password = '';              // Your database password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If the connection fails, stop the script and show an error
    die("Database connection failed: " . $e->getMessage());
}
?>
