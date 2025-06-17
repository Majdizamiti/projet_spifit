<?php
session_start();
require_once 'database/db_connect.php';

// Vérification de l'admin (corrigé)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle product actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, description, price, image_url, category, stock)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['image_url'],
                        $_POST['category'],
                        $_POST['stock']
                    ]);
                    $success = "Product added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding product: " . $e->getMessage();
                }
                break;

            case 'edit':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET name = ?, description = ?, price = ?, image_url = ?, category = ?, stock = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['image_url'],
                        $_POST['category'],
                        $_POST['stock'],
                        $_POST['product_id']
                    ]);
                    $success = "Product updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating product: " . $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt->execute([$_POST['product_id']]);
                    $success = "Product deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting product: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all products
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching products: " . $e->getMessage();
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management - SPIFIT Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
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

        .add-product-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .add-product-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
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

        .alert-info {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .products-table {
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

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border-color);
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
        }

        .edit-btn {
            background-color: var(--secondary-color);
            color: white;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--secondary-color);
            outline: none;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .submit-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header h1 {
                font-size: 1.5rem;
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

            th, td {
                padding: 0.8rem;
            }

            .product-image {
                width: 50px;
                height: 50px;
            }
        }

        /* Animation pour les boutons d'action */
        .action-btn {
            position: relative;
            overflow: hidden;
        }

        .action-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .action-btn:active::after {
            width: 200%;
            height: 200%;
        }

        /* Style pour les statuts de stock */
        .stock-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-ok {
            background-color: #d4edda;
            color: #155724;
        }

        /* Style pour les catégories */
        .category-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            background-color: var(--light-bg);
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-store"></i> Store Management</h1>
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="admin-container">
        <div class="main-content">
            <div class="content-header">
                <h2><i class="fas fa-box"></i> Product Management</h2>
                <button class="add-product-btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
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

            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No products found. Add your first product!
                </div>
            <?php else: ?>
                <div class="products-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="product-image">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <div class="text-light" style="font-size: 0.9rem;">
                                            <?php echo substr(htmlspecialchars($product['description']), 0, 50) . '...'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($product['price'], 2); ?> Dt</strong>
                                    </td>
                                    <td>
                                        <span class="stock-status <?php echo $product['stock'] < 10 ? 'stock-low' : 'stock-ok'; ?>">
                                            <?php echo $product['stock']; ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i>
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

    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('addModal')">&times;</span>
            <h2>Add New Product</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (Dt)</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="Clothing">Clothing</option>
                        <option value="Nutrition">Nutrition</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Accessories">Accessories</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                <button type="submit" class="submit-btn">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Product</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label for="edit_name">Product Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price (Dt)</label>
                    <input type="number" id="edit_price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="edit_image_url">Image URL</label>
                    <input type="text" id="edit_image_url" name="image_url" required>
                </div>
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <option value="Clothing">Clothing</option>
                        <option value="Nutrition">Nutrition</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Accessories">Accessories</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_stock">Stock</label>
                    <input type="number" id="edit_stock" name="stock" required>
                </div>
                <button type="submit" class="submit-btn">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            const modal = document.getElementById('addModal');
            modal.style.display = 'block';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }

        function openEditModal(product) {
            const modal = document.getElementById('editModal');
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_image_url').value = product.image_url;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_stock').value = product.stock;
            modal.style.display = 'block';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function deleteProduct(productId) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <h2 style="margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i> Confirm Deletion</h2>
                    <p style="margin-bottom: 1.5rem;">Are you sure you want to delete this product? This action cannot be undone.</p>
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="confirmDelete(${productId})" style="flex: 1; background-color: var(--danger-color);" class="submit-btn">Delete</button>
                        <button onclick="this.closest('.modal').remove()" style="flex: 1; background-color: var(--text-light);" class="submit-btn">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.style.display = 'block';
        }

        function confirmDelete(productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="${productId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html> 