<?php
require 'config.php';

if (!is_logged_in() || is_admin()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Show user's own humidity logs from user_logs + humidity table
$sql = "SELECT h.humidity_percent, h.status, h.recorded_at
        FROM humidity h
        JOIN user_logs ul ON h.humidity_id = ul.humidity_id
        WHERE ul.user_id = ?
        ORDER BY h.recorded_at DESC
        LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$humidity_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Dashboard - Succulent Monitoring</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f0fe; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #2196F3; color: white; }
        a.logout { float: right; color: #f44336; text-decoration: none; font-weight: bold; }
        a.logout:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <a href="logout.php" class="logout">Logout</a>
    <h1>Welcome, <?=htmlspecialchars($_SESSION['username'])?></h1>

    <h2>Your Humidity Logs</h2>
    <table>
        <thead><tr>
            <th>Humidity (%)</th><th>Status</th><th>Recorded At</th>
        </tr></thead>
        <tbody>
            <?php if (!$humidity_data): ?>
                <tr><td colspan="3">No humidity data found.</td></tr>
            <?php else: ?>
                <?php foreach ($humidity_data as $log): ?>
                <tr>
                    <td><?=htmlspecialchars($log['humidity_percent'])?></td>
                    <td><?=htmlspecialchars($log['status'])?></td>
                    <td><?=htmlspecialchars($log['recorded_at'])?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>