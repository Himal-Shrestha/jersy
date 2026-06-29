<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../db.php';
requireUserLogin();

// eSewa v2 — decode the base64 data param
$data    = $_GET['data'] ?? '';
$decoded = json_decode(base64_decode($data), true);

// Extract eSewa response values
$ref_id           = $decoded['transaction_code'] ?? '';
$transaction_uuid = $decoded['transaction_uuid'] ?? '';
$status           = $decoded['status']           ?? '';
$esewa_amount     = $decoded['total_amount']     ?? 0;

// Read from session — fallback uid from transaction_uuid (we stored it with uid prefix)
$uid              = $_SESSION['pending_user_id'] ?? null;
$pending_amount   = $_SESSION['pending_amount']  ?? 0;
$shipping_address = $_SESSION['pending_address'] ?? '';
$user             = $_SESSION['pending_user']    ?? [];
$items            = $_SESSION['pending_items']   ?? [];

// If session lost, re-fetch from DB using uid embedded in transaction_uuid
// transaction_uuid format: JC_6a428f932e2e70.79092409
// We need uid — try to get it from session or fallback
if (!$uid && $transaction_uuid) {
    // Try to find pending payment by transaction_uuid
    $res = $conn->query("SELECT user_id FROM payments WHERE transaction_uuid='$transaction_uuid' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $uid = $res->fetch_assoc()['user_id'];
    }
}

// If still no uid, redirect to login
if (!$uid) {
    header("Location: ../user/orders.php?error=Session+expired.+Please+login+again.");
    exit();
}

// If items lost from session, re-fetch from cart
if (empty($items)) {
    $cart_items = $conn->query("
        SELECT c.*, j.name, j.team, j.price, j.image,
               j.size_small, j.size_medium, j.size_large
        FROM cart c
        JOIN jerseys j ON c.jersey_id = j.id
        WHERE c.user_id = '$uid'
    ");
    while ($item = $cart_items->fetch_assoc()) {
        $item['line_total'] = $item['price'] * $item['quantity'];
        $items[] = $item;
    }
}

// If still no items — cart was already cleared (duplicate request)
if (empty($items)) {
    // Just show success page with payment info
    $payment = $conn->query("SELECT * FROM payments WHERE transaction_uuid='$transaction_uuid' LIMIT 1")->fetch_assoc();
    if ($payment) {
        header("Location: ../user/orders.php?msg=Payment+already+processed+successfully.");
        exit();
    }
}

// Re-fetch user if lost from session
if (empty($user)) {
    $user = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();
}

// Verify payment is COMPLETE
if ($status !== 'COMPLETE') {
    header("Location: esewa_failure.php");
    exit();
}

$payment_id = null;
$error      = '';

$conn->begin_transaction();

try {

    foreach ($items as $item) {

        $size_col = "size_{$item['size']}";
        $total_price = $item['price'] * $item['quantity'];

        // Check stock
        $stock = $conn->query("
            SELECT $size_col
            FROM jerseys
            WHERE id='{$item['jersey_id']}'
        ")->fetch_assoc();

        if ($stock[$size_col] < $item['quantity']) {
            throw new Exception("'{$item['name']}' in size {$item['size']} is out of stock.");
        }

        // 1. Insert Order
        $order_stmt = $conn->prepare("
            INSERT INTO orders
            (user_id, jersey_id, size, quantity, total_price, shipping_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $order_stmt->bind_param(
            "iisids",
            $uid,
            $item['jersey_id'],
            $item['size'],
            $item['quantity'],
            $total_price,
            $shipping_address
        );

        $order_stmt->execute();

        // Get Order ID
        $order_id = $conn->insert_id;

        // 2. Insert Payment
        $payment_stmt = $conn->prepare("
            INSERT INTO payments
            (
                order_id,
                user_id,
                amount,
                ref_id,
                transaction_uuid,
                shipping_address,
                payment_method,
                payment_status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, 'esewa', 'success')
        ");

        $payment_stmt->bind_param(
            "iidsss",
            $order_id,
            $uid,
            $total_price,
            $ref_id,
            $transaction_uuid,
            $shipping_address
        );

        $payment_stmt->execute();

        // Save first payment id for display
        if ($payment_id === null) {
            $payment_id = $conn->insert_id;
        }

        // 3. Update Stock
        $conn->query("
            UPDATE jerseys
            SET $size_col = $size_col - {$item['quantity']}
            WHERE id='{$item['jersey_id']}'
        ");
    }

    // 4. Clear cart
    $conn->query("DELETE FROM cart WHERE user_id='$uid'");

    $conn->commit();

    unset(
        $_SESSION['pending_amount'],
        $_SESSION['pending_address'],
        $_SESSION['pending_user_id'],
        $_SESSION['pending_user'],
        $_SESSION['pending_items'],
        $_SESSION['pending_uuid']
    );

} catch (Exception $e) {

    $conn->rollback();
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ── Payment Success Page ── */
        .success-wrapper {
            max-width: 640px;
            margin: 48px auto;
            padding: 0 16px 48px;
            font-family: inherit;
        }

        /* Confetti row */
        .ps-confetti {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 6px;
            margin-bottom: 16px;
        }

        /* Main card */
        .ps-card {
            background: #16162a;
            border: 1px solid #2a2a4a;
            border-radius: 16px;
            overflow: hidden;
            animation: psSlideUp 0.4s ease both;
        }

        @keyframes psSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        /* ── Header ── */
        .ps-header {
            background: #0f2d1a;
            border-bottom: 1px solid #1a4a2a;
            padding: 28px 24px;
            text-align: center;
        }

        .ps-check-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.6rem;
            animation: psPopIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
        }

        @keyframes psPopIn {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .ps-header h2 {
            margin: 0 0 5px;
            font-size: 1.3rem;
            font-weight: 600;
            color: #4ade80;
        }

        .ps-header p {
            margin: 0;
            font-size: 0.88rem;
            color: #86efac;
            opacity: 0.85;
        }

        /* ── Body ── */
        .ps-body { padding: 20px 24px; }

        /* Amount hero block */
        .ps-amount-block {
            background: #0f0f20;
            border: 1px solid #2a2a4a;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            animation: psSlideUp 0.35s ease 0.15s both;
        }

        .ps-amount-label {
            font-size: 0.78rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .ps-amount-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #fbbf24;
        }

        .ps-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(22,163,74,0.15);
            color: #4ade80;
            border: 1px solid rgba(22,163,74,0.3);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 12px;
        }

        /* Section label */
        .ps-section-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #6b7280;
            margin: 0 0 10px;
        }

        /* Meta grid */
        .ps-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
            animation: psSlideUp 0.35s ease 0.25s both;
        }

        .ps-meta-cell {
            background: #0f0f20;
            border: 1px solid #2a2a4a;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .ps-meta-cell.full { grid-column: 1 / -1; }

        .ps-meta-cell .mc-label {
            font-size: 0.72rem;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .ps-meta-cell .mc-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #e5e7eb;
            word-break: break-all;
        }

        /* Dashed divider */
        .ps-divider {
            border: none;
            border-top: 1px dashed #2a2a4a;
            margin: 20px 0;
        }

        /* Items table */
        .ps-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            animation: psSlideUp 0.35s ease 0.3s both;
        }

        .ps-items-table th {
            text-align: left;
            padding: 6px 0;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            border-bottom: 1px solid #2a2a4a;
        }

        .ps-items-table th:last-child { text-align: right; }

        .ps-items-table td {
            padding: 12px 0;
            border-bottom: 1px solid #1e1e38;
            vertical-align: top;
        }

        .ps-items-table tr:last-child td { border-bottom: none; }
        .ps-items-table td:last-child { text-align: right; font-weight: 600; color: #e5e7eb; }

        .ps-item-name { font-weight: 600; color: #e5e7eb; }
        .ps-item-team { font-size: 0.78rem; color: #6b7280; margin-top: 2px; }

        .ps-size-pill {
            display: inline-block;
            background: #1a1a30;
            border: 1px solid #3a3a5a;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 7px;
            color: #9ca3af;
            text-transform: uppercase;
        }

        /* Grand total row */
        .ps-grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0 2px;
            border-top: 1px solid #2a2a4a;
            margin-top: 8px;
        }

        .ps-grand-total .gt-label { font-size: 0.88rem; color: #9ca3af; }
        .ps-grand-total .gt-value  { font-size: 1.15rem; font-weight: 700; color: #fbbf24; }

        /* ── Footer buttons ── */
        .ps-footer {
            display: flex;
            gap: 10px;
            padding: 16px 24px;
            border-top: 1px solid #2a2a4a;
        }

        .ps-footer a {
            flex: 1;
            text-align: center;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.15s;
        }

        .ps-footer a:hover { opacity: 0.85; }

        .ps-btn-primary {
            background: #16a34a;
            color: #fff;
        }

        .ps-btn-secondary {
            background: transparent;
            color: #e5e7eb;
            border: 1px solid #3a3a5a;
        }

        /* Error state */
        .ps-error-wrap {
            text-align: center;
            padding: 48px 24px;
            background: #16162a;
            border: 1px solid #2a2a4a;
            border-radius: 16px;
        }

        .ps-error-icon { font-size: 2.5rem; margin-bottom: 14px; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="../user/dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="../user/shop.php" class="nav-link">Shop</a></li>
        <li><a href="../user/orders.php" class="nav-link">My Orders</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="success-wrapper">
    <?php if ($error): ?>

        <div class="ps-error-wrap">
            <div class="ps-error-icon">⚠️</div>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <?= htmlspecialchars($error) ?>
            </div>
            <a href="../user/orders.php" class="btn btn-primary">Go to My Orders</a>
        </div>

    <?php else: ?>

        <div class="ps-confetti">🎽 ⚽ 🏆 ⚽ 🎽</div>

        <div class="ps-card">

            <!-- Header -->
            <div class="ps-header">
                <div class="ps-check-circle">✓</div>
                <h2>Payment confirmed!</h2>
                <p>Thank you, <?= htmlspecialchars($user['name'] ?? '') ?>! Your order has been placed.</p>
            </div>

            <!-- Body -->
            <div class="ps-body">

                <!-- Amount hero -->
                <div class="ps-amount-block">
                    <div>
                        <div class="ps-amount-label">Amount paid</div>
                        <div class="ps-amount-value">Rs. <?= number_format((float)$esewa_amount, 2) ?></div>
                    </div>
                    <span class="ps-status-badge">✓ Complete</span>
                </div>

                <!-- Transaction details -->
                <p class="ps-section-label">Transaction details</p>

                <div class="ps-meta-grid">
                    <div class="ps-meta-cell">
                        <div class="mc-label">Payment ID</div>
                        <div class="mc-value">#<?= $payment_id ?></div>
                    </div>
                    <div class="ps-meta-cell">
                        <div class="mc-label">Payment method</div>
                        <div class="mc-value">eSewa</div>
                    </div>
                    <div class="ps-meta-cell">
                        <div class="mc-label">eSewa code</div>
                        <div class="mc-value"><?= htmlspecialchars($ref_id) ?></div>
                    </div>
                    <div class="ps-meta-cell">
                        <div class="mc-label">Transaction ID</div>
                        <div class="mc-value"><?= htmlspecialchars($transaction_uuid) ?></div>
                    </div>
                    <?php if ($shipping_address): ?>
                    <div class="ps-meta-cell full">
                        <div class="mc-label">Shipping to</div>
                        <div class="mc-value"><?= htmlspecialchars($shipping_address) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="ps-meta-cell full">
                        <div class="mc-label">Email</div>
                        <div class="mc-value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                    </div>
                </div>

                <?php if (!empty($items)): ?>

                <hr class="ps-divider">

                <!-- Items ordered -->
                <p class="ps-section-label">Items ordered</p>

                <table class="ps-items-table">
                    <thead>
                        <tr>
                            <th>Jersey</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="ps-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="ps-item-team"><?= htmlspecialchars($item['team']) ?></div>
                            </td>
                            <td><span class="ps-size-pill"><?= htmlspecialchars($item['size']) ?></span></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td>Rs. <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="ps-grand-total">
                    <span class="gt-label">Grand total</span>
                    <span class="gt-value">Rs. <?= number_format((float)$esewa_amount, 2) ?></span>
                </div>

                <?php endif; ?>

            </div><!-- /ps-body -->

            <!-- Footer actions -->
            <div class="ps-footer">
                <a href="../user/orders.php" class="ps-btn-primary">View my orders</a>
                <a href="../user/shop.php"   class="ps-btn-secondary">Continue shopping</a>
            </div>

        </div><!-- /ps-card -->

    <?php endif; ?>
</div>

</body>
</html>