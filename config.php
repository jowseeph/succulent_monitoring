<?php
// config.php - DB Connection and session start
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'succulent_monitoring');
define('DB_USER', 'root'); // change to your db user
define('DB_PASS', '');     // change to your db password

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function for checking login & role
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
?>