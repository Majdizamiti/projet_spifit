<?php
session_start();
require_once 'database/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$action = $data['action'] ?? '';

if (!$item_id || !in_array($action, ['increase', 'decrease'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    if ($action === 'increase') {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ? AND user_id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = GREATEST(quantity - 1, 1) WHERE id = ? AND user_id = ?");
    }
    
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 