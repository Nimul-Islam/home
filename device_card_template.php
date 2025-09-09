<?php
// This is a template file. It expects a $device array to be available.
// We'll add a check to prevent errors if it's included incorrectly.
if (!isset($device)) {
    echo '<p class="error-message">Error: Device data is missing.</p>';
    return; // Stop execution of this script
}

// Determine the icon based on the device type
$icon_class = 'fas fa-toggle-on'; // Default icon
switch ($device['type']) {
    case 'light':
        $icon_class = 'fas fa-lightbulb';
        break;
    case 'fan':
        $icon_class = 'fas fa-fan';
        break;
    case 'switch':
        $icon_class = 'fas fa-plug';
        break;
    case 'smart_device':
        $icon_class = 'fas fa-robot';
        break;
}

// Determine the status text and card class
$status_text = ($device['status'] == 1) ? 'On' : 'Off';
$card_status_class = ($device['status'] == 1) ? 'status-on' : '';
?>

<div class="device-card <?php echo $card_status_class; ?>" id="device-card-<?php echo $device['id']; ?>">
    <div class="device-icon">
        <i class="<?php echo $icon_class; ?>"></i>
    </div>
    <div class="device-name"><?php echo htmlspecialchars($device['name']); ?></div>
    <div class="device-status" id="status-text-<?php echo $device['id']; ?>"><?php echo $status_text; ?></div>

    <label class="toggle-switch">
        <input
            type="checkbox"
            <?php echo ($device['status'] == 1) ? 'checked' : ''; ?>
            onclick="toggleDevice(<?php echo $device['id']; ?>, this.checked)">
        <span class="slider"></span>
    </label>

    <div class="device-actions">
        <a href="edit_device.php?id=<?php echo $device['id']; ?>" title="Edit Device"><i class="fas fa-pencil-alt"></i></a>
        <form action="delete_device.php" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this device?');">
            <input type="hidden" name="id" value="<?php echo $device['id']; ?>">
            <button type="submit" class="delete-btn" title="Delete Device"><i class="fas fa-trash-alt"></i></button>
        </form>
    </div>
</div>