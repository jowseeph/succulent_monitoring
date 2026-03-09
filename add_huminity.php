<?php
require 'config.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $humidity = filter_var($_POST['humidity'], FILTER_VALIDATE_FLOAT);
    $status = trim($_POST['status']);

    if ($humidity === false || $humidity < 0 || $humidity > 100) {
        $error = "Humidity must be a number between 0 and 100.";
    } elseif (empty($status)) {
        $error = "Status is required.";
    } else {
        // Insert into humidity
        $stmt = $pdo->prepare("INSERT INTO humidity (humidity_percent, status) VALUES (?, ?)");
        $stmt->execute([$humidity, $status]);

        $humidity_id = $pdo->lastInsertId();

        // Insert into user_logs linking current user to humidity
        $stmt2 = $pdo->prepare("INSERT INTO user_logs (user_id, humidity_id) VALUES (?, ?)");
        $stmt2->execute([$_SESSION['user_id'], $humidity_id]);

        $success = "Humidity data added successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Humidity Data - Succulent Monitoring</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fcfcfc; padding: 20px; }
        .container { max-width: 400px; margin: 50px auto; background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 10px #ccc; }
        input[type=number], input[type=text] { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { background-color: #008CBA; color: white; padding: 10px; border: none; width: 100%; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #007BA7; }
        .error { color: red; }
        .success { color: green; }
        a { display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Add Humidity Data</h2>
    <?php if ($error): ?>
        <p class="error"><?=htmlspecialchars($error)?></p>
    <?php elseif ($success): ?>
        <p class="success"><?=htmlspecialchars($success)?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label>Humidity (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="humidity" required />
        <label>Status</label>
        <input type="text" name="status" placeholder="e.g., Normal, High, Low" required />
        <button type="submit">Add</button>
    </form>
    <a href="dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>