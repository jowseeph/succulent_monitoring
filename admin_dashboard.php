<?php
require 'config.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

// Fetch users
$users = $pdo->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch humidity data and logs with user info
$sql = "SELECT h.humidity_id, h.humidity_percent, h.status, h.recorded_at, u.username
        FROM humidity h
        LEFT JOIN user_logs ul ON h.humidity_id = ul.humidity_id
        LEFT JOIN users u ON ul.user_id = u.user_id
        ORDER BY h.recorded_at DESC LIMIT 50";
$humidity_logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Succulent Monitoring</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f5f7; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        a.logout { float: right; color: #f44336; text-decoration: none; font-weight: bold; }
        a.logout:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <a href="logout.php" class="logout">Logout</a>
    <h1>Admin Dashboard</h1>

    <h2>Users</h2>
    <table>
        <thead><tr>
            <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created At</th><th>Actions</th>
        </tr></thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?=htmlspecialchars($user['user_id'])?></td>
                <td><?=htmlspecialchars($user['username'])?></td>
                <td><?=htmlspecialchars($user['email'])?></td>
                <td><?=htmlspecialchars($user['role'])?></td>
                <td><?=htmlspecialchars($user['created_at'])?></td>
                <td>
                    <a href="manage_users.php?edit=<?=intval($user['user_id'])?>">Edit</a> | 
                    <a href="delete_user.php?id=<?=intval($user['user_id'])?>" onclick="return confirm('Delete user?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Recent Humidity Logs</h2>
    <table>
        <thead><tr>
            <th>ID</th><th>Humidity (%)</th><th>Status</th><th>Recorded At</th><th>User</th>
        </tr></thead>
        <tbody>
            <?php foreach ($humidity_logs as $log): ?>
            <tr>
                <td><?=htmlspecialchars($log['humidity_id'])?></td>
                <td><?=htmlspecialchars($log['humidity_percent'])?></td>
                <td><?=htmlspecialchars($log['status'])?></td>
                <td><?=htmlspecialchars($log['recorded_at'])?></td>
                <td><?=htmlspecialchars($log['username'] ?? 'N/A')?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>