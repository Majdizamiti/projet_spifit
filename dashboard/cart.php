<?php
session_start();
require_once 'database/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image_url 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - SPIFIT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="storecss.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 20px;
        }

        .cart-items {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 20px;
        }

        .item-details {
            flex: 1;
        }

        .item-price {
            font-weight: bold;
            color: var(--primary);
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-controls button {
            background: var(--primary);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cart-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .checkout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: #9a1a26;
        }

        .remove-item {
            color: #ff4444;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="cart-container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="store.html" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-price"><?php echo number_format($item['price'], 2); ?> Dt</p>
                            <div class="quantity-controls">
                                <button class="decrease-quantity">-</button>
                                <span class="quantity"><?php echo $item['quantity']; ?></span>
                                <button class="increase-quantity">+</button>
                            </div>
                        </div>
                        <button class="remove-item" title="Remove item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span><?php echo number_format($total, 2); ?> Dt</span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span><?php echo number_format($total, 2); ?> Dt</span>
                </div>
                <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                    Proceed to Checkout
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add cart functionality JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity controls
            const quantityControls = document.querySelectorAll('.quantity-controls');
            quantityControls.forEach(control => {
                const decreaseBtn = control.querySelector('.decrease-quantity');
                const increaseBtn = control.querySelector('.increase-quantity');
                const quantitySpan = control.querySelector('.quantity');
                const cartItem = control.closest('.cart-item');
                const itemId = cartItem.dataset.itemId;

                decreaseBtn.addEventListener('click', () => updateQuantity(itemId, 'decrease'));
                increaseBtn.addEventListener('click', () => updateQuantity(itemId, 'increase'));
            });

            // Remove item
            const removeButtons = document.querySelectorAll('.remove-item');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const itemId = cartItem.dataset.itemId;
                    removeItem(itemId);
                });
            });
        });

        async function updateQuantity(itemId, action) {
            try {
                const response = await fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        action: action
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating cart');
            }
        }

        async function removeItem(itemId) {
            if (!confirm('Are you sure you want to remove this item?')) return;

            try {
                const response = await fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error removing item');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error removing item');
            }
        }
    </script>
</body>
</html> 