<?php
// ============================================================
// STEP 15: ADMIN DASHBOARD (admin/dashboard.php)
// Overview of jerseys, users, orders + recent activity
// ============================================================
require_once '../db.php';
requireAdminLogin();

// Summary stats
$total_jerseys  = $conn->query("SELECT COUNT(*) as c FROM jerseys")->fetch_assoc()['c'];
$total_users    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_orders   = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE status != 'cancelled'")->fetch_assoc()['t'] ?? 0;
$club_jerseys   = $conn->query("SELECT COUNT(*) as c FROM jerseys WHERE category='club'")->fetch_assoc()['c'];
$national_jerseys = $conn->query("SELECT COUNT(*) as c FROM jerseys WHERE category='national'")->fetch_assoc()['c'];

// Recent 5 orders
$recent_orders = $conn->query("
    SELECT o.*, u.name as user_name, j.name as jersey_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN jerseys j ON o.jersey_id = j.id
    ORDER BY o.ordered_at DESC LIMIT 5
");

// Low stock alert (any size < 3)
$low_stock = $conn->query("SELECT name, size_small, size_medium, size_large FROM jerseys WHERE size_small < 3 OR size_medium < 3 OR size_large < 3 LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="../index.php" class="nav-link">← Website</a></li>
        <li><span style="color:var(--gold);font-weight:700;padding:8px 12px;">Admin Panel</span></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="panel-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            🛡️ Admin Panel
            <small><?= htmlspecialchars($_SESSION['user_name']) ?></small>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <div class="sidebar-nav-item"><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></div>
            <div class="sidebar-section-label">Jersey Management</div>
            <div class="sidebar-nav-item"><a href="jerseys.php"><span class="icon">👕</span> All Jerseys</a></div>
            <div class="sidebar-nav-item"><a href="add_jersey.php"><span class="icon">➕</span> Add Jersey</a></div>
            <div class="sidebar-section-label">User & Orders</div>
            <div class="sidebar-nav-item"><a href="users.php"><span class="icon">👥</span> Manage Users</a></div>
            <div class="sidebar-nav-item"><a href="orders.php"><span class="icon">📦</span> All Orders</a></div>
            <div class="sidebar-section-label">Account</div>
            <div class="sidebar-nav-item"><a href="../logout.php"><span class="icon">🚪</span> Logout</a></div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="panel-main">

        <div class="panel-header">
            <div>
                <h2>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h2>
                <p>Jersey Club Admin Dashboard — <?= date('l, F j, Y') ?></p>
            </div>
            <a href="add_jersey.php" class="btn btn-primary btn-sm">➕ Add Jersey</a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">👕</div>
                <div class="stat-info">
                    <span class="value"><?= $total_jerseys ?></span>
                    <span class="label">Total Jerseys</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold">👥</div>
                <div class="stat-info">
                    <span class="value"><?= $total_users ?></span>
                    <span class="label">Registered Users</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">📦</div>
                <div class="stat-info">
                    <span class="value"><?= $total_orders ?></span>
                    <span class="label">Total Orders</span>
                </div>
            </div>
            <div class="stat-card" style="border-left-color:var(--warning);">
                <div class="stat-icon" style="background:rgba(251,140,0,0.1);">⏳</div>
                <div class="stat-info">
                    <span class="value"><?= $pending_orders ?></span>
                    <span class="label">Pending Orders</span>
                </div>
            </div>
            <div class="stat-card" style="border-left-color:var(--success);">
                <div class="stat-icon" style="background:rgba(67,160,71,0.1);">💰</div>
                <div class="stat-info">
                    <span class="value">Rs.<?= number_format($total_revenue, 0) ?></span>
                    <span class="label">Total Revenue</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🏟️</div>
                <div class="stat-info">
                    <span class="value"><?= $club_jerseys ?> / <?= $national_jerseys ?></span>
                    <span class="label">Club / National</span>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <?php if ($low_stock->num_rows > 0): ?>
        <div class="alert alert-warning" style="margin-bottom:20px;">
            ⚠️ <strong>Low Stock Alert:</strong> Some jerseys are running low. <a href="jerseys.php" style="color:var(--warning);font-weight:700;">Manage Jerseys →</a>
        </div>
        <?php endif; ?>

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
                            <th>Customer</th>
                            <th>Jersey</th>
                            <th>Size</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['user_name']) ?></td>
                            <td><?= htmlspecialchars($order['jersey_name']) ?></td>
                            <td><span style="font-weight:700;text-transform:uppercase;"><?= $order['size'] ?></span></td>
                            <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
                            <td><span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                            <td><?= date('M d', strtotime($order['ordered_at'])) ?></td>
                            <td><a href="orders.php?update_id=<?= $order['id'] ?>" class="btn btn-green btn-sm">Update</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state"><span class="icon">📦</span><h3>No orders yet</h3></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:5px;">
            <div class="card">
                <div class="card-header"><h3>Quick Actions</h3></div>
                <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:10px;">
                    <a href="add_jersey.php" class="btn btn-primary btn-full">➕ Add New Jersey</a>
                    <a href="jerseys.php" class="btn btn-green btn-full">👕 Manage Jerseys</a>
                    <a href="users.php" class="btn btn-green btn-full">👥 Manage Users</a>
                    <a href="orders.php" class="btn btn-green btn-full">📦 View All Orders</a>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>Low Stock Jerseys</h3></div>
                <div class="card-body" style="padding:20px;">
                    <?php
                    $low_stock->data_seek(0);
                    if ($low_stock->num_rows > 0):
                        while ($j = $low_stock->fetch_assoc()):
                    ?>
                    <div style="padding:8px 0;border-bottom:1px solid var(--mid-gray);font-size:0.9rem;">
                        <strong><?= htmlspecialchars($j['name']) ?></strong><br>
                        <span style="color:var(--text-muted);">S:<?= $j['size_small'] ?> M:<?= $j['size_medium'] ?> L:<?= $j['size_large'] ?></span>
                    </div>
                    <?php endwhile; else: ?>
                    <p style="color:var(--success);">✅ All jerseys are well stocked!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>