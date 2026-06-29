<?php
// ============================================================
// STEP 14: USER ORDERS PAGE (user/orders.php)
// Shows all orders placed by the logged-in user
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid = $_SESSION['user_id'];
$msg = htmlspecialchars($_GET['msg'] ?? '');

// Filter by status
$status_filter = sanitize($conn, $_GET['status'] ?? '');
$where = "WHERE o.user_id = '$uid'";
if ($status_filter) $where .= " AND o.status = '$status_filter'";

$orders = $conn->query("
  SELECT
    o.*,
    j.name AS jersey_name,
    j.image,
    j.team,
    j.category,
    p.payment_status,
    p.payment_method,
    p.amount
FROM orders o
JOIN jerseys j
    ON o.jersey_id = j.id
LEFT JOIN payments p
    ON p.order_id = o.id
ORDER BY o.ordered_at DESC;

");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — Jersey Club</title>
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
        <li><a href="orders.php" class="nav-link active">My Orders</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="panel-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">📦 My Orders<small>Track your purchases</small></div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Navigate</div>
            <div class="sidebar-nav-item"><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></div>
            <div class="sidebar-nav-item"><a href="shop.php"><span class="icon">🛍️</span> Shop</a></div>
            <div class="sidebar-nav-item"><a href="cart.php"><span class="icon">🛒</span> Cart</a></div>
            <div class="sidebar-nav-item"><a href="orders.php"><span class="icon">📦</span> My Orders</a></div>
            <div class="sidebar-section-label">Filter by Status</div>
            <div class="sidebar-nav-item"><a href="orders.php" class="<?= !$status_filter ? 'active' : '' ?>"><span class="icon">⚽</span> All Orders</a></div>
            <div class="sidebar-nav-item"><a href="orders.php?status=pending" class="<?= $status_filter==='pending' ? 'active' : '' ?>"><span class="icon">⏳</span> Pending</a></div>
            <div class="sidebar-nav-item"><a href="orders.php?status=processing" class="<?= $status_filter==='processing' ? 'active' : '' ?>"><span class="icon">⚙️</span> Processing</a></div>
            <div class="sidebar-nav-item"><a href="orders.php?status=shipped" class="<?= $status_filter==='shipped' ? 'active' : '' ?>"><span class="icon">🚚</span> Shipped</a></div>
            <div class="sidebar-nav-item"><a href="orders.php?status=delivered" class="<?= $status_filter==='delivered' ? 'active' : '' ?>"><span class="icon">✅</span> Delivered</a></div>
        </nav>
    </aside>

    <main class="panel-main">
        <div class="panel-header">
            <div><h2>📦 My Orders</h2><p>Track all your jersey purchases</p></div>
            <a href="shop.php" class="btn btn-primary btn-sm">🛍️ Shop More</a>
        </div>

        <?php if ($msg): echo "<div class='alert alert-success'>✅ $msg</div>"; endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>
                    <?php
                    if ($status_filter) echo ucfirst($status_filter) . ' Orders';
                    else echo 'All Orders';
                    ?>
                </h3>
                <span style="color:var(--text-muted);font-size:0.9rem;"><?= $orders->num_rows ?> order(s)</span>
            </div>
            <div class="card-body table-wrapper">
                <?php if ($orders->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Jersey</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Booking Status</th>
                            <th>Payment Status</th>
                            <th>Ordered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php if (file_exists("../uploads/" . $order['image'])): ?>
                                        <img src="../uploads/<?= $order['image'] ?>" style="width:45px;height:45px;border-radius:8px;object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:45px;height:45px;background:var(--pitch-green);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;">👕</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($order['jersey_name']) ?></strong><br>
                                        <small style="color:var(--text-muted);">
                                            <?= htmlspecialchars($order['team']) ?> •
                                            <span class="jersey-badge badge-<?= $order['category'] ?>" style="position:static;display:inline;padding:2px 8px;font-size:0.7rem;"><?= ucfirst($order['category']) ?></span>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-weight:700;text-transform:uppercase;background:var(--light-gray);padding:3px 10px;border-radius:4px;"><?= $order['size'] ?></span></td>
                            <td><?= $order['quantity'] ?></td>
                            <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?php
                                    $icons = ['pending'=>'⏳','processing'=>'⚙️','shipped'=>'🚚','delivered'=>'✅','cancelled'=>'❌'];
                                    echo ($icons[$order['status']] ?? '') . ' ' . ucfirst($order['status']);
                                    ?>
                                </span>
                            </td>
                            <td><?= $order['payment_status'] ?></td>
                            <td><?= date('M d, Y', strtotime($order['ordered_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <span class="icon">📦</span>
                    <h3>No orders found</h3>
                    <p><?= $status_filter ? "No $status_filter orders." : "You haven't placed any orders yet." ?></p>
                    <a href="shop.php" class="btn btn-primary" style="margin-top:15px;">Start Shopping</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>