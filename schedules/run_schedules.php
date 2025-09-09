<?php
// This script is meant to be run automatically by a server task scheduler (like a cron job) every minute.
// It does not require a user session.

// Set the timezone for your server.
// Find your timezone here: https://www.php.net/manual/en/timezones.php
date_default_timezone_set('UTC'); 

include 'db_connection.php';

// Get the current time in 24-hour format (e.g., 17:30)
$currentTime = date('H:i');

echo "Cron job started at: " . date('Y-m-d H:i:s') . "\n";
echo "Checking for schedules set for: $currentTime\n";

try {
    // 1. Find all schedules that match the current time.
    $stmt = $pdo->prepare("SELECT device_id, action FROM schedules WHERE scheduled_time = ?");
    $stmt->execute([$currentTime . ':00']); // Match H:i:s format in DB
    $schedulesToRun = $stmt->fetchAll();

    if (empty($schedulesToRun)) {
        echo "No schedules to run at this time.\n";
        exit();
    }

    echo count($schedulesToRun) . " schedule(s) found. Processing...\n";

    // 2. Loop through each found schedule and update the corresponding device.
    foreach ($schedulesToRun as $schedule) {
        $device_id = $schedule['device_id'];
        $action = $schedule['action']; // 'ON' or 'OFF'

        // Prepare the update statement
        $updateStmt = $pdo->prepare("UPDATE devices SET status = ? WHERE id = ?");
        
        // Execute the update
        if ($updateStmt->execute([$action, $device_id])) {
            echo " - Successfully updated device ID #$device_id to '$action'.\n";
        } else {
            echo " - Failed to update device ID #$device_id.\n";
        }
    }

    echo "Processing complete.\n";

} catch (PDOException $e) {
    // Log any database errors. This is crucial for debugging a cron job.
    $errorMessage = "Error running schedules: " . $e->getMessage() . "\n";
    echo $errorMessage;
    // In a production environment, you would write this to a log file.
    // error_log($errorMessage, 3, '/var/log/homesphere_cron.log');
}
?>

