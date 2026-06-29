<?php
// ============================================================
// STEP 12: CART PAGE (user/cart.php)
// View, update quantity, remove items from cart
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid = $_SESSION['user_id'];
$msg = $error = '';

// ---- HANDLE ACTIONS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $cart_id  = (int)($_POST['cart_id'] ?? 0);

    // Verify cart item belongs to this user
    $verify = $conn->query("SELECT id FROM cart WHERE id='$cart_id' AND user_id='$uid'")->fetch_assoc();

    if ($action === 'remove' && $verify) {
        $conn->query("DELETE FROM cart WHERE id='$cart_id'");
        $msg = 'Item removed from cart.';
    }

    if ($action === 'update' && $verify) {
        $new_qty = max(1, min(10, (int)($_POST['quantity'] ?? 1)));
        $conn->query("UPDATE cart SET quantity='$new_qty' WHERE id='$cart_id'");
        $msg = 'Cart updated.';
    }

    if ($action === 'clear') {
        $conn->query("DELETE FROM cart WHERE user_id='$uid'");
        $msg = 'Cart cleared.';
    }
}

// Fetch cart items with jersey info
$cart_items = $conn->query("
    SELECT c.*, j.name, j.team, j.price, j.image, j.category,
           j.size_small, j.size_medium, j.size_large
    FROM cart c
    JOIN jerseys j ON c.jersey_id = j.id
    WHERE c.user_id = '$uid'
    ORDER BY c.added_at DESC
");

// Calculate totals
$subtotal = 0;
$items_arr = [];
while ($item = $cart_items->fetch_assoc()) {
    $item['line_total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['line_total'];
    $items_arr[] = $item;
}
$shipping = $subtotal > 0 ? 150 : 0;
$grand_total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="shop.php" class="nav-link">Shop</a></li>
        <li><a href="cart.php" class="nav-link active">🛒 Cart (<?= count($items_arr) ?>)</a></li>
        <li><a href="orders.php" class="nav-link">My Orders</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div style="padding:30px 5%;max-width:1100px;margin:0 auto;">

    <div class="panel-header">
        <div><h2>🛒 My Cart</h2><p><?= count($items_arr) ?> item(s) in your cart</p></div>
        <?php if (count($items_arr) > 0): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-danger btn-sm btn-confirm-delete">Clear Cart</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($msg):   echo "<div class='alert alert-success'>✅ $msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

    <?php if (count($items_arr) > 0): ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:25px;align-items:start;">

        <!-- CART ITEMS TABLE -->
        <div class="card">
            <div class="card-header"><h3>Cart Items</h3></div>
            <div class="card-body table-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Jersey</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_arr as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-item-info">
                                    <?php if (file_exists("../uploads/" . $item['image'])): ?>
                                        <img class="cart-item-img" src="../uploads/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                        <div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;background:var(--pitch-green);color:white;font-size:1.5rem;">👕</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= htmlspecialchars($item['team']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-weight:700;text-transform:uppercase;background:var(--light-gray);padding:3px 10px;border-radius:4px;"><?= $item['size'] ?></span></td>
                            <td><?= formatPrice($item['price']) ?></td>
                            <td>
                                <!-- Update qty form -->
                                <form method="POST" style="display:flex;gap:5px;align-items:center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="10"
                                        style="width:55px;padding:5px;border:2px solid var(--mid-gray);border-radius:6px;text-align:center;font-weight:700;"
                                        onchange="this.form.submit()">
                                </form>
                            </td>
                            <td><strong><?= formatPrice($item['line_total']) ?></strong></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm btn-confirm-delete" title="Remove">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ORDER SUMMARY -->
        <div>
            <div class="cart-total-box">
                <h3 style="color:var(--gold);font-family:'Anton',sans-serif;margin-bottom:20px;">Order Summary</h3>
                <div class="total-row">
                    <span>Subtotal</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping</span>
                    <span><?= formatPrice($shipping) ?></span>
                </div>
                <div class="total-row" style="border:none;padding-top:15px;margin-top:5px;">
                    <span style="font-size:1.1rem;font-weight:700;">Grand Total</span>
                    <span class="grand-total"><?= formatPrice($grand_total) ?></span>
                </div>
                <a href="checkout.php" class="btn btn-primary btn-full" style="margin-top:20px;">
                    Proceed to Checkout →
                </a>
                <a href="shop.php" class="btn btn-full" style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.8);border-color:rgba(255,255,255,0.2);margin-top:10px;">
                    ← Continue Shopping
                </a>
            </div>
        </div>

    </div>

    <?php else: ?>
    <div class="empty-state">
        <span class="icon">🛒</span>
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added any jerseys yet.</p>
        <a href="shop.php" class="btn btn-primary" style="margin-top:15px;">Shop Now</a>
    </div>
    <?php endif; ?>

</div>

<script src="../assets/js/main.js"></script>
</body>
</html>