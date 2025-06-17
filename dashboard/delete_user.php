<?php
// delete_user.php

// Database connection
$host = 'localhost';
$dbname = 'spifit';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$userId = $_GET['id'];

// Delete the user
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);

header('Location: admin_dashboard.php');
exit();
?> 