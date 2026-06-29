<?php
// ============================================================
// STEP 9: USER DASHBOARD (user/dashboard.php)
// Shows user's account overview: stats + recent orders
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid = $_SESSION['user_id'];

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();

// Get stats
$total_orders   = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id='$uid'")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id='$uid' AND status='pending'")->fetch_assoc()['c'];
$delivered      = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id='$uid' AND status='delivered'")->fetch_assoc()['c'];
$total_spent    = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE user_id='$uid'")->fetch_assoc()['t'] ?? 0;

// Get recent 5 orders
$recent_orders = $conn->query("
    SELECT o.*, j.name as jersey_name, j.image, j.team
    FROM orders o
    JOIN jerseys j ON o.jersey_id = j.id
    WHERE o.user_id = '$uid'
    ORDER BY o.ordered_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
        <li><a href="shop.php" class="nav-link">Shop</a></li>
        <li><a href="cart.php" class="nav-link">🛒 Cart</a></li>
        <li><a href="orders.php" class="nav-link">My Orders</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="panel-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            👤 <?= htmlspecialchars($user['name']) ?>
            <small><?= htmlspecialchars($user['email']) ?></small>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Menu</div>
            <div class="sidebar-nav-item"><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></div>
            <div class="sidebar-nav-item"><a href="shop.php"><span class="icon">🛍️</span> Shop Jerseys</a></div>
            <div class="sidebar-nav-item"><a href="cart.php"><span class="icon">🛒</span> My Cart</a></div>
            <div class="sidebar-nav-item"><a href="orders.php"><span class="icon">📦</span> My Orders</a></div>
            <div class="sidebar-section-label">Account</div>
            <div class="sidebar-nav-item"><a href="../logout.php"><span class="icon">🚪</span> Logout</a></div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="panel-main">

        <!-- Page Header -->
        <div class="panel-header">
            <div>
                <h2>Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h2>
                <p>Here's a summary of your account</p>
            </div>
            <a href="shop.php" class="btn btn-primary btn-sm">🛍️ Shop Now</a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">📦</div>
                <div class="stat-info">
                    <span class="value"><?= $total_orders ?></span>
                    <span class="label">Total Orders</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold">⏳</div>
                <div class="stat-info">
                    <span class="value"><?= $pending_orders ?></span>
                    <span class="label">Pending</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">✅</div>
                <div class="stat-info">
                    <span class="value"><?= $delivered ?></span>
                    <span class="label">Delivered</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">💰</div>
                <div class="stat-info">
                    <span class="value">Rs.<?= number_format($total_spent, 0) ?></span>
                    <span class="label">Total Spent</span>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Orders</h3>
                <a href="orders.php" class="btn btn-green btn-sm">View All</a>
            </div>
            <div class="card-body table-wrapper">
                <?php if ($recent_orders->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Jersey</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php if (file_exists("../uploads/" . $order['image'])): ?>
                                        <img src="../uploads/<?= $order['image'] ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px;height:40px;background:var(--pitch-green);border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;">👕</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($order['jersey_name']) ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= htmlspecialchars($order['team']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-weight:700;text-transform:uppercase;"><?= $order['size'] ?></span></td>
                            <td><?= $order['quantity'] ?></td>
                            <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
                            <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($order['ordered_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="icon">📦</span>
                        <h3>No orders yet</h3>
                        <p>You haven't placed any orders. Start shopping!</p>
                        <a href="shop.php" class="btn btn-primary" style="margin-top:15px;">Shop Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card">
            <div class="card-header"><h3>Account Information</h3></div>
            <div class="card-body" style="padding:25px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <label style="font-size:0.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Full Name</label>
                        <p style="color:var(--text-dark);font-weight:600;margin-top:4px;"><?= htmlspecialchars($user['name']) ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Email</label>
                        <p style="color:var(--text-dark);font-weight:600;margin-top:4px;"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Phone</label>
                        <p style="color:var(--text-dark);font-weight:600;margin-top:4px;"><?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Member Since</label>
                        <p style="color:var(--text-dark);font-weight:600;margin-top:4px;"><?= date('F Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>