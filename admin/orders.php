<?php
// ============================================================
// STEP 20: ADMIN ORDERS PAGE (admin/orders.php)
// View all orders, update order status
// ============================================================
require_once '../db.php';
requireAdminLogin();

$msg = $error = '';

// ---- UPDATE ORDER STATUS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = sanitize($conn, $_POST['status']);
    $allowed    = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $conn->query("UPDATE orders SET status='$new_status' WHERE id='$order_id'");
        $msg = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
    }
}

// ---- FILTERS ----
$status_filter = sanitize($conn, $_GET['status'] ?? '');
$search        = sanitize($conn, $_GET['search'] ?? '');
$where = "WHERE 1=1";
if ($status_filter) $where .= " AND o.status='$status_filter'";
if ($search) $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR j.name LIKE '%$search%' OR o.id LIKE '%$search%')";

// Highlight specific order (when coming from dashboard)
$highlight_id = (int)($_GET['update_id'] ?? 0);

$orders = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
           j.name as jersey_name, j.team, j.image, j.category
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN jerseys j ON o.jersey_id = j.id
    $where
    ORDER BY o.ordered_at DESC
");

// Count by status for tabs
$counts = [];
foreach (['pending','processing','shipped','delivered','cancelled'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='$s'")->fetch_assoc()['c'];
}
$counts['all'] = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .status-tab {
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            border: 2px solid var(--mid-gray);
            background: var(--white);
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
        }
        .status-tab:hover, .status-tab.active {
            background: var(--pitch-dark);
            color: var(--gold);
            border-color: var(--pitch-dark);
        }
        .status-tab .count {
            background: var(--mid-gray);
            border-radius: 50px;
            padding: 1px 7px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .status-tab.active .count {
            background: var(--gold);
            color: var(--pitch-dark);
        }
        /* Inline status update form */
        .status-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .status-form select {
            padding: 6px 10px;
            border: 2px solid var(--mid-gray);
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: 'Roboto', sans-serif;
        }
        /* Highlight row */
        tr.highlighted {
            background: rgba(244,196,48,0.12) !important;
            box-shadow: inset 3px 0 0 var(--gold);
        }
        .order-detail-popup {
            background: var(--light-gray);
            padding: 10px 15px;
            border-radius: var(--radius);
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
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
        <div class="sidebar-brand">🛡️ Admin Panel<small><?= htmlspecialchars($_SESSION['user_name']) ?></small></div>
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
                <h2>📦 All Orders</h2>
                <p><?= $orders->num_rows ?> order(s)
                    <?= $status_filter ? "— filtered by <strong>" . ucfirst($status_filter) . "</strong>" : '' ?>
                </p>
            </div>
        </div>

        <?php if ($msg):   echo "<div class='alert alert-success'>✅ $msg</div>"; endif; ?>
        <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

        <!-- STATUS TABS -->
        <div class="status-tabs">
            <a href="orders.php" class="status-tab <?= !$status_filter ? 'active' : '' ?>">
                All <span class="count"><?= $counts['all'] ?></span>
            </a>
            <?php
            $tab_icons = ['pending'=>'⏳','processing'=>'⚙️','shipped'=>'🚚','delivered'=>'✅','cancelled'=>'❌'];
            foreach ($counts as $s => $c):
                if ($s === 'all') continue;
            ?>
            <a href="orders.php?status=<?= $s ?>" class="status-tab <?= $status_filter===$s ? 'active' : '' ?>">
                <?= $tab_icons[$s] ?> <?= ucfirst($s) ?> <span class="count"><?= $c ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- SEARCH BAR -->
        <form method="GET" class="filter-bar">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            <input type="text" name="search" placeholder="🔍 Search by order ID, customer, or jersey..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-green btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="orders.php?status=<?= $status_filter ?>" class="btn btn-sm" style="background:var(--mid-gray);color:var(--text-dark);">Clear ✕</a>
            <?php endif; ?>
        </form>

        <!-- ORDERS TABLE -->
        <div class="card">
            <div class="card-body table-wrapper">
                <?php if ($orders->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Jersey</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Shipping Address</th>
                            <th>Date</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr class="<?= ($highlight_id === $order['id']) ? 'highlighted' : '' ?>" id="order-<?= $order['id'] ?>">

                            <!-- Order ID + Status Badge -->
                            <td>
                                <strong>#<?= $order['id'] ?></strong><br>
                                <span class="status-badge status-<?= $order['status'] ?>" style="margin-top:4px;display:inline-block;">
                                    <?= ($tab_icons[$order['status']] ?? '') . ' ' . ucfirst($order['status']) ?>
                                </span>
                            </td>

                            <!-- Customer Info -->
                            <td>
                                <strong><?= htmlspecialchars($order['user_name']) ?></strong><br>
                                <small style="color:var(--text-muted);"><?= htmlspecialchars($order['user_email']) ?></small>
                                <?php if ($order['user_phone']): ?>
                                <br><small style="color:var(--text-muted);">📞 <?= htmlspecialchars($order['user_phone']) ?></small>
                                <?php endif; ?>
                            </td>

                            <!-- Jersey Info -->
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <?php if (file_exists("../uploads/" . $order['image'])): ?>
                                        <img src="../uploads/<?= $order['image'] ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                                    <?php else: ?>
                                        <div style="width:40px;height:40px;background:var(--pitch-green);border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;">👕</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong style="font-size:0.9rem;"><?= htmlspecialchars($order['jersey_name']) ?></strong><br>
                                        <small style="color:var(--text-muted);">
                                            <?= htmlspecialchars($order['team']) ?> •
                                            <span class="jersey-badge badge-<?= $order['category'] ?>" style="position:static;display:inline;padding:2px 6px;font-size:0.68rem;"><?= ucfirst($order['category']) ?></span>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- Size -->
                            <td>
                                <span style="font-weight:700;text-transform:uppercase;background:var(--light-gray);padding:3px 10px;border-radius:4px;">
                                    <?= $order['size'] ?>
                                </span>
                            </td>

                            <!-- Quantity -->
                            <td style="text-align:center;font-weight:700;"><?= $order['quantity'] ?></td>

                            <!-- Total -->
                            <td><strong><?= formatPrice($order['total_price']) ?></strong></td>

                            <!-- Shipping Address -->
                            <td>
                                <span style="font-size:0.85rem;color:var(--text-muted);max-width:150px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($order['shipping_address']) ?>">
                                    📍 <?= htmlspecialchars($order['shipping_address']) ?>
                                </span>
                            </td>

                            <!-- Date -->
                            <td style="white-space:nowrap;">
                                <?= date('M d, Y', strtotime($order['ordered_at'])) ?><br>
                                <small style="color:var(--text-muted);"><?= date('h:i A', strtotime($order['ordered_at'])) ?></small>
                            </td>

                            <!-- Update Status Form -->
                            <td>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status">
                                        <option value="pending"    <?= $order['status']==='pending'    ? 'selected' : '' ?>>⏳ Pending</option>
                                        <option value="processing" <?= $order['status']==='processing' ? 'selected' : '' ?>>⚙️ Processing</option>
                                        <option value="shipped"    <?= $order['status']==='shipped'    ? 'selected' : '' ?>>🚚 Shipped</option>
                                        <option value="delivered"  <?= $order['status']==='delivered'  ? 'selected' : '' ?>>✅ Delivered</option>
                                        <option value="cancelled"  <?= $order['status']==='cancelled'  ? 'selected' : '' ?>>❌ Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-green btn-sm">Save</button>
                                </form>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php else: ?>
                <div class="empty-state">
                    <span class="icon">📦</span>
                    <h3>No orders found</h3>
                    <p>
                        <?= $status_filter ? "No $status_filter orders at the moment." : "No orders have been placed yet." ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Stats Row -->
        <?php
        $revenue = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE status NOT IN ('cancelled')")->fetch_assoc()['t'] ?? 0;
        $avg     = $counts['all'] > 0 ? $revenue / max(1, ($counts['all'] - ($counts['cancelled'] ?? 0))) : 0;
        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:15px;margin-top:5px;">
            <div style="background:var(--white);padding:20px;border-radius:var(--radius-lg);box-shadow:var(--shadow);text-align:center;">
                <div style="font-family:'Anton',sans-serif;font-size:1.6rem;color:var(--pitch-green);"><?= $counts['all'] ?></div>
                <div style="font-size:0.85rem;color:var(--text-muted);">Total Orders</div>
            </div>
            <div style="background:var(--white);padding:20px;border-radius:var(--radius-lg);box-shadow:var(--shadow);text-align:center;">
                <div style="font-family:'Anton',sans-serif;font-size:1.6rem;color:var(--warning);"><?= $counts['pending'] ?></div>
                <div style="font-size:0.85rem;color:var(--text-muted);">Pending</div>
            </div>
            <div style="background:var(--white);padding:20px;border-radius:var(--radius-lg);box-shadow:var(--shadow);text-align:center;">
                <div style="font-family:'Anton',sans-serif;font-size:1.6rem;color:var(--success);"><?= $counts['delivered'] ?></div>
                <div style="font-size:0.85rem;color:var(--text-muted);">Delivered</div>
            </div>
            <div style="background:var(--white);padding:20px;border-radius:var(--radius-lg);box-shadow:var(--shadow);text-align:center;">
                <div style="font-family:'Anton',sans-serif;font-size:1.3rem;color:var(--pitch-dark);">Rs.<?= number_format($revenue, 0) ?></div>
                <div style="font-size:0.85rem;color:var(--text-muted);">Total Revenue</div>
            </div>
            <div style="background:var(--white);padding:20px;border-radius:var(--radius-lg);box-shadow:var(--shadow);text-align:center;">
                <div style="font-family:'Anton',sans-serif;font-size:1.3rem;color:var(--pitch-dark);">Rs.<?= number_format($avg, 0) ?></div>
                <div style="font-size:0.85rem;color:var(--text-muted);">Avg. Order Value</div>
            </div>
        </div>

    </main>
</div>

<script src="../assets/js/main.js"></script>
<script>
    // Auto-scroll to highlighted order
    const highlighted = document.querySelector('.highlighted');
    if (highlighted) {
        highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>
</body>
</html>