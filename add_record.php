<?php
include 'config.php';
if (!is_logged_in()) { header('Location: index.php'); exit; }

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $humidity_input = floatval($_POST['humidity']);

    if ($humidity_input < 30) { $status = 'Dry'; }
    elseif ($humidity_input <= 50) { $status = 'Ideal'; }
    else { $status = 'Humid'; }

    $stmt = $conn->prepare("INSERT INTO humidity (humidity_percent, status) VALUES (?, ?)");
    $stmt->bind_param("ds", $humidity_input, $status);
    $stmt->execute();
    $humidity_id = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, humidity_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $humidity_id);
    $stmt->execute();
    $stmt->close();

    $message = "Record added successfully: $status";
}
?>

<div class="container">
<h2>Add Humidity Record</h2>
<form method="POST">
    <label>Humidity (%) :</label>
    <input type="number" name="humidity" min="0" max="100" step="0.01" required>
    <input type="submit" value="Add Record">
</form>
<?php if($message): ?>
<div class="message"><?php echo $message; ?></div>
<?php endif; ?>
<p><a href="dashboard.php">Back to Dashboard</a></p>
</div>