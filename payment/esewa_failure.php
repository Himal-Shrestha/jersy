<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
requireUserLogin();

// eSewa v2 sends base64 encoded data even on failure
$data    = $_GET['data'] ?? '';
$decoded = !empty($data) ? json_decode(base64_decode($data), true) : [];

$transaction_uuid = $decoded['transaction_uuid'] ?? '';
$status           = $decoded['status']           ?? 'FAILED';
$esewa_amount     = $decoded['total_amount']     ?? 0;

// Read from session
$uid            = $_SESSION['pending_user_id'] ?? null;
$pending_amount = $_SESSION['pending_amount']  ?? $esewa_amount;
$user           = $_SESSION['pending_user']    ?? [];
$items          = $_SESSION['pending_items']   ?? [];

// Re-fetch user if session lost
if (empty($user) && $uid) {
    $user = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();
}

// Log failed payment in DB
if ($uid && $pending_amount) {
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, amount, transaction_uuid, payment_method, status, created_at)
        VALUES (?, ?, ?, 'esewa', 'failed', NOW())
    ");
    if ($stmt) {
        $stmt->bind_param("ids", $uid, $pending_amount, $transaction_uuid);
        $stmt->execute();
    }
}

// Keep session intact so user can retry without losing cart
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ── Payment Failed Page ── */
        .failure-wrapper {
            max-width: 580px;
            margin: 48px auto;
            padding: 0 16px 48px;
            font-family: inherit;
        }

        .pf-shake-icon {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 6px;
            margin-bottom: 16px;
            animation: pfShake 0.5s ease 0.2s both;
        }

        @keyframes pfShake {
            0%,100% { transform: translateX(0); }
            20%      { transform: translateX(-6px); }
            40%      { transform: translateX(6px); }
            60%      { transform: translateX(-4px); }
            80%      { transform: translateX(4px); }
        }

        /* Main card */
        .failure-card {
            background: #16162a;
            border: 1px solid #2a2a4a;
            border-radius: 16px;
            overflow: hidden;
            animation: pfSlideUp 0.4s ease both;
        }

        @keyframes pfSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        /* ── Header ── */
        .failure-header {
            background: #2a0a0a;
            border-bottom: 1px solid #4a1a1a;
            padding: 28px 24px;
            text-align: center;
        }

        .pf-x-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.5rem;
            animation: pfPopIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
        }

        @keyframes pfPopIn {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .failure-header h2 {
            margin: 0 0 6px;
            font-size: 1.3rem;
            font-weight: 600;
            color: #f87171;
        }

        .failure-header p {
            margin: 0;
            font-size: 0.88rem;
            color: #fca5a5;
            opacity: 0.85;
        }

        .pf-amount-pill {
            display: inline-block;
            background: rgba(220,38,38,0.2);
            border: 1px solid rgba(220,38,38,0.35);
            color: #f87171;
            padding: 5px 18px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 12px;
        }

        /* ── Body ── */
        .failure-body { padding: 20px 24px; }

        /* Status badge */
        .pf-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(220,38,38,0.12);
            color: #f87171;
            border: 1px solid rgba(220,38,38,0.3);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 12px;
        }

        /* Section label */
        .pf-section-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #6b7280;
            margin: 0 0 10px;
        }

        /* Transaction meta grid */
        .pf-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
            animation: pfSlideUp 0.35s ease 0.2s both;
        }

        .pf-meta-cell {
            background: #0f0f20;
            border: 1px solid #2a2a4a;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .pf-meta-cell.full { grid-column: 1 / -1; }

        .pf-meta-cell .mc-label {
            font-size: 0.72rem;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .pf-meta-cell .mc-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #e5e7eb;
            word-break: break-all;
        }

        /* Dashed divider */
        .pf-divider {
            border: none;
            border-top: 1px dashed #2a2a4a;
            margin: 20px 0;
        }

        /* Reasons box */
        .pf-reason-box {
            background: rgba(220,38,38,0.06);
            border: 1px solid rgba(220,38,38,0.2);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 16px;
            animation: pfSlideUp 0.35s ease 0.3s both;
        }

        .pf-reason-box .rb-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #f87171;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pf-reason-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pf-reason-list li {
            font-size: 0.83rem;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pf-reason-list li::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #dc2626;
            flex-shrink: 0;
            opacity: 0.6;
        }

        /* Cart safe note */
        .pf-note {
            background: #0f0f20;
            border: 1px solid #2a2a4a;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.83rem;
            color: #9ca3af;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: pfSlideUp 0.35s ease 0.35s both;
        }

        .pf-note-icon {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* Footer buttons */
        .pf-footer {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 16px 24px;
            border-top: 1px solid #2a2a4a;
            animation: pfSlideUp 0.35s ease 0.4s both;
        }

        .pf-footer a {
            text-align: center;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.15s;
        }

        .pf-footer a:hover { opacity: 0.85; }

        .pf-btn-primary {
            background: #dc2626;
            color: #fff;
        }

        .pf-btn-secondary {
            background: transparent;
            color: #e5e7eb;
            border: 1px solid #3a3a5a;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="../user/dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="../user/shop.php" class="nav-link">Shop</a></li>
        <li><a href="../user/cart.php" class="nav-link">🛒 Cart</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="failure-wrapper">

    <div class="pf-shake-icon">😞 ❌ 😞</div>

    <div class="failure-card">

        <!-- Header -->
        <div class="failure-header">
            <div class="pf-x-circle">✕</div>
            <h2>Payment failed</h2>
            <p>
                <?php if (!empty($user['name'])): ?>
                    Sorry, <?= htmlspecialchars($user['name']) ?>! Your payment could not be completed.
                <?php else: ?>
                    Your payment could not be completed.
                <?php endif; ?>
            </p>
            <?php if ($pending_amount): ?>
                <div class="pf-amount-pill">Rs. <?= number_format((float)$pending_amount, 2) ?></div>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="failure-body">

            <?php if ($transaction_uuid || $status): ?>

                <p class="pf-section-label">Transaction details</p>

                <div class="pf-meta-grid">
                    <div class="pf-meta-cell">
                        <div class="mc-label">Status</div>
                        <div class="mc-value">
                            <span class="pf-status-badge">✗ <?= htmlspecialchars($status) ?></span>
                        </div>
                    </div>
                    <div class="pf-meta-cell">
                        <div class="mc-label">Payment method</div>
                        <div class="mc-value">eSewa</div>
                    </div>
                    <?php if ($transaction_uuid): ?>
                    <div class="pf-meta-cell full">
                        <div class="mc-label">Transaction ID</div>
                        <div class="mc-value"><?= htmlspecialchars($transaction_uuid) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="pf-divider">

            <?php endif; ?>

            <!-- Possible reasons -->
            <div class="pf-reason-box">
                <div class="rb-title">⚠ This may have happened because:</div>
                <ul class="pf-reason-list">
                    <li>You cancelled the payment on eSewa</li>
                    <li>Insufficient balance in your eSewa account</li>
                    <li>Your eSewa session timed out</li>
                    <li>Network or connection issue</li>
                    <li>Payment was declined by eSewa</li>
                </ul>
            </div>

            <!-- Cart safe note -->
            <div class="pf-note">
                <span class="pf-note-icon">🛒</span>
                <span>Your cart is still saved. You can try again or switch to Cash on Delivery.</span>
            </div>

        </div><!-- /failure-body -->

        <!-- Footer actions -->
        <div class="pf-footer">
            <a href="../user/checkout.php" class="pf-btn-primary">🔄 Try again</a>
            <a href="../user/cart.php" class="pf-btn-secondary">🛒 Back to cart</a>
        </div>

    </div><!-- /failure-card -->

</div>

<script src="../assets/js/main.js"></script>
</body>
</html>