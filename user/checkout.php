<?php
// ============================================================
// CHECKOUT PAGE (user/checkout.php)
// ============================================================
require_once '../db.php';
requireUserLogin();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uid  = $_SESSION['user_id'];
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
    $subtotal          += $item['line_total'];
    $items_arr[]        = $item;
}

// Redirect if cart is empty
if (count($items_arr) === 0) {
    header("Location: cart.php");
    exit();
}

$shipping    = 150;
$grand_total = $subtotal + $shipping;
$error       = '';

// ---- PROCESS ORDER ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address        = sanitize($conn, $_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';

    if (empty($address)) {
        $error = 'Please enter a shipping address.';

    } elseif ($payment_method === 'esewa') {
        // Store everything in session — used after eSewa redirects back
        $_SESSION['pending_address'] = $address;
        $_SESSION['pending_amount']  = $grand_total;
        $_SESSION['pending_user_id'] = $uid;
        $_SESSION['pending_user']    = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
        ];
        $_SESSION['pending_items'] = $items_arr;

        header("Location: ../payment/payment_esewa.php");
        exit();

    }
    
    else {
    // COD flow
    $conn->begin_transaction();

    try {

        foreach ($items_arr as $item) {

            $size_col = "size_{$item['size']}";

            // Check stock
            $stock_check = $conn->query("
                SELECT $size_col
                FROM jerseys
                WHERE id='{$item['jersey_id']}'
            ")->fetch_assoc();

            if ($stock_check[$size_col] < $item['quantity']) {
                throw new Exception(
                    "Sorry, '{$item['name']}' in size {$item['size']} is no longer available."
                );
            }

            $total_price = $item['price'] * $item['quantity'];

            // -------------------------
            // Insert Order
            // -------------------------
            $order_stmt = $conn->prepare("
                INSERT INTO orders
                (user_id, jersey_id, size, quantity, total_price, shipping_address)
                VALUES (?,?,?,?,?,?)
            ");

            $order_stmt->bind_param(
                "iisids",
                $uid,
                $item['jersey_id'],
                $item['size'],
                $item['quantity'],
                $total_price,
                $address
            );

            $order_stmt->execute();

            // Get Order ID
            $order_id = $conn->insert_id;

            // -------------------------
            // Insert Payment
            // -------------------------
            $payment_method = "cod";
            $payment_status = "unpaid";

            $payment_stmt = $conn->prepare("
                INSERT INTO payments
                (
                    order_id,
                    user_id,
                    amount,
                    shipping_address,
                    payment_method,
                    payment_status
                )
                VALUES (?,?,?,?,?,?)
            ");

            $payment_stmt->bind_param(
                "iidsss",
                $order_id,
                $uid,
                $total_price,
                $address,
                $payment_method,
                $payment_status
            );

            $payment_stmt->execute();

            // -------------------------
            // Update Stock
            // -------------------------
            $update = $conn->prepare("
                UPDATE jerseys
                SET $size_col = $size_col - ?
                WHERE id = ?
            ");

            $update->bind_param(
                "ii",
                $item['quantity'],
                $item['jersey_id']
            );

            $update->execute();
        }

        // Clear cart
        $delete = $conn->prepare("
            DELETE FROM cart
            WHERE user_id = ?
        ");

        $delete->bind_param("i", $uid);
        $delete->execute();

        // Commit everything
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
    <style>
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .payment-card {
            border: 2px solid var(--border, #ddd);
            border-radius: var(--radius, 8px);
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            overflow: hidden;
        }
        .payment-card input[type="radio"] {
            display: none;
        }
        .payment-card-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
        }
        .payment-icon {
            font-size: 1.6rem;
            line-height: 1;
        }
        .payment-name {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .payment-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .payment-check {
            margin-left: auto;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--border, #ddd);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: transparent;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .payment-card.selected {
            border-color: var(--gold, #f59e0b);
            background: rgba(245, 158, 11, 0.05);
        }
        .payment-card.selected .payment-check {
            background: var(--gold, #f59e0b);
            border-color: var(--gold, #f59e0b);
            color: #fff;
        }
    </style>
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

    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:25px;align-items:start;">

        <!-- LEFT: Order Review + Address -->
        <div>

            <!-- Order Items -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h3>Order Items</h3></div>
                <div class="card-body table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Jersey</th>
                                <th>Size</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
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

            <!-- Shipping + Payment Form -->
            <div class="card">
                <div class="card-header"><h3>Shipping Details</h3></div>
                <div class="card-body" style="padding:25px;">
                    <form method="POST" id="checkout-form">

                        <!-- Hidden fields -->
                        <input type="hidden" id="grand_total" value="<?= $grand_total ?>">
                        <input type="hidden" name="payment_method" id="payment_method_input" value="cod">

                        <!-- User info -->
                        <div style="margin-bottom:15px;padding:12px 16px;background:var(--light-gray);border-radius:var(--radius);font-size:0.9rem;">
                            <strong>Ordering as:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                        </div>

                        <!-- Shipping address -->
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

                        <!-- Payment Method -->
                        <div class="form-group" style="margin-top:20px;">
                            <label style="display:block;margin-bottom:12px;font-weight:600;">Payment Method</label>

                            <div class="payment-options">

                                <!-- COD -->
                                <label class="payment-card selected" id="label-cod">
                                    <input type="radio" name="payment_method_radio" value="cod" checked onchange="selectPayment('cod')">
                                    <div class="payment-card-inner">
                                        <span class="payment-icon">💵</span>
                                        <div>
                                            <div class="payment-name">Cash on Delivery</div>
                                            <div class="payment-sub">Pay when order arrives</div>
                                        </div>
                                        <span class="payment-check">✓</span>
                                    </div>
                                </label>

                                <!-- eSewa -->
                                <label class="payment-card" id="label-esewa">
                                    <input type="radio" name="payment_method_radio" value="esewa" onchange="selectPayment('esewa')">
                                    <div class="payment-card-inner">
                                        <img src="../assets/images/esewa.png" alt="eSewa" style="height:32px;object-fit:contain;">
                                        <div>
                                            <div class="payment-name">eSewa</div>
                                            <div class="payment-sub">Pay online instantly</div>
                                        </div>
                                        <span class="payment-check">✓</span>
                                    </div>
                                </label>


                                <!-- Add more payment options here later -->
                                <input type="hidden" id="grand_total" value="<?= $grand_total ?>">
                            </div>
                        </div>

                        <!-- COD info -->
                        <div class="alert alert-info" id="cod-info" style="font-size:0.9rem;margin-top:15px;">
                            ℹ️ Payment is <strong>Cash on Delivery (COD)</strong>. Pay when your order arrives.
                        </div>

                        <!-- eSewa info -->
                        <div class="alert alert-info" id="esewa-info" style="font-size:0.9rem;margin-top:15px;display:none;">
                            ℹ️ You will be redirected to <strong>eSewa</strong> to complete your payment securely.
                        </div>

                        

                        <!-- Submit button -->
                        <button type="submit" class="btn btn-primary btn-full" id="place-order-btn" style="margin-top:10px;">
                            Place Order (Cash on Delivery) →
                        </button>

                    </form>
                </div>
            </div>

        </div>

        <!-- RIGHT: Price Summary -->
        <div class="cart-total-box">
            <h3 style="color:var(--gold);font-family:'Anton',sans-serif;margin-bottom:20px;">Price Summary</h3>
            <div class="total-row">
                <span>Items (<?= count($items_arr) ?>)</span>
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
            <a href="cart.php" style="display:block;text-align:center;margin-top:20px;color:rgba(255,255,255,0.6);font-size:0.9rem;">← Edit Cart</a>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/main.js"></script>
<script>
    function selectPayment(method) {
        document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('label-' + method).classList.add('selected');
        document.getElementById('payment_method_input').value = method;

        const btn = document.getElementById('place-order-btn');
        if (method === 'esewa') {
            btn.textContent = 'Pay with eSewa →';
            document.getElementById('cod-info').style.display   = 'none';
            document.getElementById('esewa-info').style.display = 'block';
        } else {
            btn.textContent = 'Place Order (Cash on Delivery) →';
            document.getElementById('cod-info').style.display   = 'block';
            document.getElementById('esewa-info').style.display = 'none';
        }
    }

    // Set COD as selected on load
    document.addEventListener('DOMContentLoaded', () => selectPayment('cod'));

    // Form submit handler
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const method = document.getElementById('payment_method_input').value;

        if (method === 'esewa') {
            e.preventDefault();

            const address = document.getElementById('shipping_address').value.trim();
            if (!address) {
                alert('Please enter a shipping address.');
                return;
            }

            // Create and submit form directly to payment_esewa.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../payment/payment_esewa.php';

            const fields = {
                total_amount:     document.getElementById('grand_total').value,
                shipping_address: address
            };

            for (const [key, val] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = key;
                input.value = val;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }
        // COD — normal form submit, PHP handles it
    });
</script>
</body>
</html>