<?php
// ============================================================
// STEP 13: CHECKOUT PAGE (user/checkout.php)
// Review order, confirm address, place order
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();

// Fetch cart items
$cart_items = $conn->query("
    SELECT c.*, j.name, j.team, j.price, j.image,
           j.size_small, j.size_medium, j.size_large
    FROM cart c
    JOIN jerseys j ON c.jersey_id = j.id
    WHERE c.user_id = '$uid'
");

$items_arr = [];
$subtotal  = 0;
while ($item = $cart_items->fetch_assoc()) {
    $item['line_total'] = $item['price'] * $item['quantity'];
    $subtotal += $item['line_total'];
    $items_arr[] = $item;
}

// Redirect if cart is empty
if (count($items_arr) === 0) {
    header("Location: cart.php");
    exit();
}

$shipping    = 150;
$grand_total = $subtotal + $shipping;
$error = $success = '';

// ---- PROCESS ORDER ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($conn, $_POST['shipping_address'] ?? '');

    if (empty($address)) {
        $error = 'Please enter a shipping address.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            foreach ($items_arr as $item) {
                $size_col = "size_{$item['size']}";
                // Verify stock
                $stock_check = $conn->query("SELECT $size_col FROM jerseys WHERE id='{$item['jersey_id']}'")->fetch_assoc();
                if ($stock_check[$size_col] < $item['quantity']) {
                    throw new Exception("Sorry, '{$item['name']}' in size {$item['size']} is no longer available in the required quantity.");
                }
                // Insert order
                $total_price = $item['price'] * $item['quantity'];
                $stmt = $conn->prepare("INSERT INTO orders (user_id, jersey_id, size, quantity, total_price, shipping_address) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("iisids", $uid, $item['jersey_id'], $item['size'], $item['quantity'], $total_price, $address);
                $stmt->execute();
                // Deduct stock
                $conn->query("UPDATE jerseys SET $size_col = $size_col - {$item['quantity']} WHERE id='{$item['jersey_id']}'");
                $conn->commit();
            }
            // Clear cart
            $conn->query("DELETE FROM cart WHERE user_id='$uid'");
            $conn->commit();
            header("Location: orders.php?msg=Order placed successfully! We will process it shortly.");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="shop.php" class="nav-link">Shop</a></li>
        <li><a href="cart.php" class="nav-link">🛒 Cart</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div style="padding:30px 5%;max-width:1000px;margin:0 auto;">

    <div class="panel-header">
        <div><h2>✅ Checkout</h2><p>Review your order and confirm</p></div>
    </div>

    <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:25px;align-items:start;">

        <!-- LEFT: Order Review + Address -->
        <div>
            <!-- Order Items -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h3>Order Items</h3></div>
                <div class="card-body table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Jersey</th><th>Size</th><th>Qty</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items_arr as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($item['team']) ?></small>
                                </td>
                                <td><span style="font-weight:700;text-transform:uppercase;"><?= $item['size'] ?></span></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><strong><?= formatPrice($item['line_total']) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shipping Address Form -->
            <div class="card">
                <div class="card-header"><h3>Shipping Details</h3></div>
                <div class="card-body" style="padding:25px;">
                    <form method="POST" id="checkout-form">
                        <div style="margin-bottom:15px;padding:12px 16px;background:var(--light-gray);border-radius:var(--radius);font-size:0.9rem;">
                            <strong>Ordering as:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </div>
                        <div class="form-group">
                            <label for="shipping_address">Shipping Address <span style="color:var(--danger)">*</span></label>
                            <textarea
                                id="shipping_address"
                                name="shipping_address"
                                rows="3"
                                placeholder="House No., Street, Ward, City, District..."
                                required
                            ><?= htmlspecialchars($_POST['shipping_address'] ?? $user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="alert alert-info" style="font-size:0.9rem;">
                            ℹ️ Payment is <strong>Cash on Delivery (COD)</strong>. Pay when your order arrives.
                        </div>
                        <button type="submit" class="btn btn-primary btn-full" style="margin-top:10px;">
                            Place Order (COD) →
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: Summary -->
        <div class="cart-total-box">
            <h3 style="color:var(--gold);font-family:'Anton',sans-serif;margin-bottom:20px;">Price Summary</h3>
            <div class="total-row"><span>Items (<?= count($items_arr) ?>)</span><span><?= formatPrice($subtotal) ?></span></div>
            <div class="total-row"><span>Shipping</span><span><?= formatPrice($shipping) ?></span></div>
            <div class="total-row" style="border:none;padding-top:15px;margin-top:5px;">
                <span style="font-size:1.1rem;font-weight:700;">Grand Total</span>
                <span class="grand-total"><?= formatPrice($grand_total) ?></span>
            </div>
            <a href="cart.php" style="display:block;text-align:center;margin-top:20px;color:rgba(255,255,255,0.6);font-size:0.9rem;">← Edit Cart</a>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>