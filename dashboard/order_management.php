<?php
session_start();
require_once 'database/db_connect.php';

// Vérification de l'admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Gestion des actions sur les commandes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['order_id']]);
                $success = "Order status updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating order status: " . $e->getMessage();
            }
            break;
    }
}

// Récupération des paramètres de filtrage
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Construction de la requête SQL avec filtres
$query = "
    SELECT o.*, u.name, u.email,
    COUNT(oi.id) as total_items,
    SUM(oi.quantity) as total_quantity
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search_query) {
    $query .= " AND (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Calcul des statistiques
    $stats_query = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
            AVG(total_amount) as average_order_value
        FROM orders
        WHERE 1=1
    ";
    
    $stats_params = [];
    if ($date_from) {
        $stats_query .= " AND DATE(created_at) >= ?";
        $stats_params[] = $date_from;
    }
    if ($date_to) {
        $stats_query .= " AND DATE(created_at) <= ?";
        $stats_params[] = $date_to;
    }

    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($stats_params);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
    $orders = [];
    $stats = [];
}

// Gestion de l'export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, ['Order ID', 'Customer', 'Email', 'Date', 'Total', 'Status', 'Items']);
    
    // Données
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['name'],
            $order['email'],
            $order['created_at'],
            $order['total_amount'],
            $order['status'],
            $order['total_items']
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - SPIFIT Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
            --info-color: #3498db;
            --light-bg: #f8f9fa;
            --dark-bg: #2c3e50;
            --border-color: #e9ecef;
            --text-color: #2c3e50;
            --text-light: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f6fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header h1 i {
            font-size: 2rem;
            color: var(--warning-color);
        }

        .back-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .content-header h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .orders-table {
            overflow-x: auto;
            margin-top: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--primary-color);
            white-space: nowrap;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .view-details-btn {
            background-color: var(--info-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-details-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 800px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--danger-color);
        }

        .order-details {
            margin-top: 1.5rem;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background-color: var(--light-bg);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .info-card h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .info-card p {
            font-weight: 500;
            color: var(--text-color);
        }

        .order-items {
            margin-top: 2rem;
        }

        .order-items h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .item-list {
            list-style: none;
        }

        .item-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .item-list li:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .status-select {
            padding: 0.5rem;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: white;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .admin-container {
                padding: 1rem;
                margin: 1rem auto;
            }

            .main-content {
                padding: 1rem;
            }

            .content-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 1.5rem;
            }

            .order-info {
                grid-template-columns: 1fr;
            }
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--text-color);
        }

        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-card h3 {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-card .trend {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .trend-up {
            color: var(--success-color);
        }

        .trend-down {
            color: var(--danger-color);
        }

        .notification-badge {
            position: relative;
            display: inline-block;
        }

        .notification-badge::after {
            content: attr(data-count);
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            display: none;
        }

        .notification-badge.has-notifications::after {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-shopping-cart"></i> Order Management</h1>
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="admin-container">
        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" 
                               placeholder="Search orders..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" name="date_from" id="date_from" 
                               value="<?php echo $date_from; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" name="date_to" id="date_to" 
                               value="<?php echo $date_to; ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="view-details-btn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="?export=csv<?php echo $status_filter ? "&status=$status_filter" : ''; ?>" 
                       class="view-details-btn" style="background-color: var(--success-color);">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="value"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> Dt</div>
            </div>
            <div class="stat-card">
                <h3>Average Order Value</h3>
                <div class="value"><?php echo number_format($stats['average_order_value'] ?? 0, 2); ?> Dt</div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo number_format($stats['pending_orders'] ?? 0); ?></div>
            </div>
        </div>

        <div class="main-content">
            <div class="content-header">
                <h2><i class="fas fa-list"></i> All Orders</h2>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No orders found.
                </div>
            <?php else: ?>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['name']); ?></div>
                                        <div style="font-size: 0.9rem; color: var(--text-light);">
                                            <?php echo htmlspecialchars($order['email']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?> Dt</td>
                                    <td><?php echo $order['total_items']; ?> items</td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="view-details-btn" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('orderDetailsModal')">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            const modal = document.getElementById('orderDetailsModal');
            const content = document.getElementById('orderDetailsContent');
            
            // Show loading state
            content.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            modal.style.display = 'block';

            // Fetch order details
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    content.innerHTML = `
                        <div class="order-info">
                            <div class="info-card">
                                <h3>Order Information</h3>
                                <p>Order #${String(data.order.id).padStart(6, '0')}</p>
                                <p>Date: ${new Date(data.order.created_at).toLocaleString()}</p>
                                <p>Status: 
                                    <select class="status-select" onchange="updateOrderStatus(${data.order.id}, this.value)">
                                        <option value="pending" ${data.order.status === 'pending' ? 'selected' : ''}>Pending</option>
                                        <option value="processing" ${data.order.status === 'processing' ? 'selected' : ''}>Processing</option>
                                        <option value="shipped" ${data.order.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                                        <option value="delivered" ${data.order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                        <option value="cancelled" ${data.order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                    </select>
                                </p>
                            </div>
                            <div class="info-card">
                                <h3>Customer Information</h3>
                                <p>${data.order.name}</p>
                                <p>${data.order.email}</p>
                            </div>
                            <div class="info-card">
                                <h3>Shipping Address</h3>
                                <p>${data.order.shipping_address}</p>
                            </div>
                            <div class="info-card">
                                <h3>Payment Information</h3>
                                <p>Method: ${data.order.payment_method}</p>
                                <p>Total: ${parseFloat(data.order.total_amount).toFixed(2)} Dt</p>
                            </div>
                        </div>
                        <div class="order-items">
                            <h3>Order Items</h3>
                            <ul class="item-list">
                                ${data.items.map(item => `
                                    <li>
                                        <div class="item-info">
                                            <img src="${item.image_url}" alt="${item.name}" class="item-image">
                                            <div>
                                                <div>${item.name}</div>
                                                <div style="color: var(--text-light); font-size: 0.9rem;">
                                                    Quantity: ${item.quantity}
                                                </div>
                                            </div>
                                        </div>
                                        <div>${parseFloat(item.price).toFixed(2)} Dt</div>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `;
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-error">Error loading order details. Please try again.</div>';
                });
        }

        function updateOrderStatus(orderId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            fetch('order_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Reload the page to show updated status
                window.location.reload();
            })
            .catch(error => {
                alert('Error updating order status. Please try again.');
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        // Système de notifications
        function checkNewOrders() {
            fetch('check_new_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.new_orders > 0) {
                        const badge = document.querySelector('.notification-badge');
                        badge.classList.add('has-notifications');
                        badge.setAttribute('data-count', data.new_orders);
                        
                        // Notification du navigateur
                        if (Notification.permission === "granted") {
                            new Notification("New Orders", {
                                body: `You have ${data.new_orders} new order(s)!`,
                                icon: "/path/to/icon.png"
                            });
                        }
                    }
                });
        }

        // Demander la permission pour les notifications
        if (Notification.permission !== "granted" && Notification.permission !== "denied") {
            Notification.requestPermission();
        }

        // Vérifier les nouvelles commandes toutes les 30 secondes
        setInterval(checkNewOrders, 30000);
    </script>
</body>
</html> 