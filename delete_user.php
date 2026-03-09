<?php
require 'config.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

$user_id = intval($_GET['id'] ?? 0);

if ($user_id) {
    // Prevent admin from deleting themselves
    if ($user_id === $_SESSION['user_id']) {
        die("You cannot delete your own account.");
    }
    $stmt = $pdo->