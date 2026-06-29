<?php
// ============================================================
// STEP 19: ADMIN USERS PAGE (admin/users.php)
// View, activate/deactivate, and delete users
// ============================================================
require_once '../db.php';
requireAdminLogin();

$msg = $error = '';

// ---- TOGGLE USER STATUS ----
if (isset($_GET['toggle'])) {
    $toggle_id = (int)$_GET['toggle'];
    // Don't allow admin to deactivate themselves
    if ($toggle_id === (int)$_SESSION['user_id']) {
        $error = 'You cannot change your own account status.';
    } else {
        $current = $conn->query("SELECT status FROM users WHERE id='$toggle_id' AND role='user'")->fetch_assoc();
        if ($current) {
            $new_status = $current['status'] === 'active' ? 'inactive' : 'active';
            $conn->query("UPDATE users SET status='$new_status' WHERE id='$toggle_id'");
            $msg = "User account has been " . ($new_status === 'active' ? 'activated' : 'deactivated') . ".";
        }
    }
}

// ---- DELETE USER ----
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE id='$del_id' AND role='user'")->fetch_assoc();
        if ($check) {
            $conn->query("DELETE FROM users WHERE id='$del_id'");
            $msg = 'User deleted successfully.';
        }
    }
}

// ---- FETCH USERS ----
$search = sanitize($conn, $_GET['search'] ?? '');
$status = sanitize($conn, $_GET['status'] ?? '');
$where  = "WHERE role='user'";
if ($search) $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
if ($status) $where .= " AND status='$status'";

$users = $conn->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as order_count,
        (SELECT SUM(total_price) FROM orders WHERE user_id=u.id) as total_spent
    FROM users u $where
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

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

    <main class="panel-main">
        <div class="panel-header">
            <div><h2>👥 Manage Users</h2><p><?= $users->num_rows ?> user(s) found</p></div>
        </div>

        <?php if ($msg):   echo "<div class='alert alert-success'>✅ $msg</div>"; endif; ?>
        <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <input type="text" id="table-search" name="search" placeholder="🔍 Search by name or email..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">All Status</option>
                <option value="active"   <?= $status==='active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status==='inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($search || $status): ?>
                <a href="users.php" class="btn btn-sm" style="background:var(--mid-gray);color:var(--text-dark);">Clear ✕</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="card-body table-wrapper">
                <?php if ($users->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:38px;height:38px;background:var(--pitch-green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.9rem;flex-shrink:0;">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone'] ?: '—') ?></td>
                            <td>
                                <span style="background:rgba(26,77,46,0.1);color:var(--pitch-green);padding:3px 10px;border-radius:50px;font-weight:700;font-size:0.85rem;">
                                    <?= $user['order_count'] ?>
                                </span>
                            </td>
                            <td><strong><?= formatPrice($user['total_spent'] ?? 0) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= $user['status'] ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <!-- Toggle Status -->
                                    <a href="users.php?toggle=<?= $user['id'] ?>"
                                       class="btn btn-sm <?= $user['status']==='active' ? 'btn-danger' : 'btn-green' ?>"
                                       title="<?= $user['status']==='active' ? 'Deactivate' : 'Activate' ?>">
                                        <?= $user['status']==='active' ? '🔒 Deactivate' : '🔓 Activate' ?>
                                    </a>
                                    <!-- Delete -->
                                    <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-danger btn-sm btn-confirm-delete" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <span class="icon">👥</span>
                    <h3>No users found</h3>
                    <p>No users have registered yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>