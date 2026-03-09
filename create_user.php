<?php
require 'config.php';

$username = 'rimond';
$password_plain = 'binayao';
$email = 'rimond@example.com'; // You can change this if you want
$role = 'user'; // or 'admin' if you want admin rights

// Hash the password securely
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password_hashed, $email, $role]);
    echo "User created: $username with password: $password_plain";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // duplicate entry
        echo "User '$username' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}