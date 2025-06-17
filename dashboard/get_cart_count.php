<?php
session_start();
require_once 'database/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'] ?? 0;
    
    echo json_encode(['success' => true, 'count' => $cart_count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?> 