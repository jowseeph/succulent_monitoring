<?php
require 'config.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

$user_id = intval($_GET['edit'] ?? 0);
if (!$user_id) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$success = '';

// Fetch user details
$stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $role = $_POST['role'] ?? '';

    if (!$email) {
        $error = "Valid email is required.";
    } elseif (!in_array($role, ['admin', 'user'])) {
        $error = "Invalid role selected.";
    } else {
        $update = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE user_id = ?");
        $update->execute([$email, $role, $user_id]);
        $success = "User updated successfully.";
        // refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit User - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 20px; }
        .container { max-width: 400px; margin: 30px auto; background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 10px #ccc; }
        input[type=email], select { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { background-color: #4CAF50; color: white; padding: 10px; border: none; width: 100%; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #45a049; }
        .error { color: red; }
        .success { color: green; }
        a { display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit User: <?=htmlspecialchars($user['username'])?></h2>

    <?php if ($error): ?>
        <p class="error"><?=htmlspecialchars($error)?></p>
    <?php elseif ($success): ?>
        <p class="success"><?=htmlspecialchars($success)?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Email</label>
        <input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required />
        <label>Role</label>
        <select name="role" required>
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        <button type="submit">Update</button>
    </form>

    <a href="admin_dashboard.php">Back to Admin Dashboard</a>
</div>
</body>
</html>