<?php
session_start();
require_once 'database/db_connect.php';

// Vérification de l'admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

try {
    // Récupérer les détails de la commande
    $stmt = $pdo->prepare("
        SELECT o.*, u.name, u.email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$_GET['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    // Récupérer les articles de la commande
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image_url
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$_GET['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourner les données
    echo json_encode([
        'order' => $order,
        'items' => $items
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 