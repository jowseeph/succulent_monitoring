<?php
require 'config.php';

if (is_logged_in()) {
    // Redirect to dashboard based on role
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Succulent Monitoring - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef6f9; }
        .container { max-width: 400px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        input[type=text], input[type=password] { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { background-color: #4CAF50; color: white; padding: 10px; border: none; width: 100%; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #45a049; }
        .error { color: red; }
        h2 { text-align: center; }
        .register-link { font-size: 0.9em; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login to Succulent Monitoring</h2>
    <?php if ($error): ?>
        <p class="error"><?=htmlspecialchars($error)?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required autofocus />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
    </form>
    <div class="register-link">
        <a href="add_humidity.php">Simulate Insert Humidity</a><br />
        <!-- Could add registration here if desired -->
    </div>
</div>
</body>
</html>