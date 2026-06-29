<?php
// ============================================================
// STEP 16: ADMIN JERSEYS PAGE (admin/jerseys.php)
// List all jerseys with Delete action. Edit links to edit_jersey.php
// ============================================================
require_once '../db.php';
requireAdminLogin();

$msg = $error = '';

// ---- DELETE JERSEY ----
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Get image filename before deleting
    $img_row = $conn->query("SELECT image FROM jerseys WHERE id='$del_id'")->fetch_assoc();
    if ($img_row) {
        // Delete image file if exists
        $img_path = "../uploads/" . $img_row['image'];
        if (file_exists($img_path) && $img_row['image'] !== 'default.jpg') {
            @unlink($img_path);
        }
        $conn->query("DELETE FROM jerseys WHERE id='$del_id'");
        $msg = 'Jersey deleted successfully.';
    }
}

// ---- FETCH JERSEYS ----
$search   = sanitize($conn, $_GET['search'] ?? '');
$category = sanitize($conn, $_GET['category'] ?? '');
$where    = "WHERE 1=1";
if ($search)   $where .= " AND (name LIKE '%$search%' OR team LIKE '%$search%')";
if ($category) $where .= " AND category='$category'";

$jerseys = $conn->query("SELECT * FROM jerseys $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jerseys — Admin</title>
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
            <div><h2>👕 Manage Jerseys</h2><p><?= $jerseys->num_rows ?> jersey(s) found</p></div>
            <a href="add_jersey.php" class="btn btn-primary btn-sm">➕ Add New Jersey</a>
        </div>

        <?php if ($msg):   echo "<div class='alert alert-success'>✅ $msg</div>"; endif; ?>
        <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <input type="text" id="table-search" name="search" placeholder="🔍 Search by name or team..." value="<?= htmlspecialchars($search) ?>">
            <select name="category">
                <option value="">All Categories</option>
                <option value="club"     <?= $category==='club'     ? 'selected' : '' ?>>Club</option>
                <option value="national" <?= $category==='national' ? 'selected' : '' ?>>National</option>
            </select>
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($search || $category): ?>
                <a href="jerseys.php" class="btn btn-sm" style="background:var(--mid-gray);color:var(--text-dark);">Clear ✕</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <div class="card-body table-wrapper">
                <?php if ($jerseys->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Team</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock (S/M/L)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($j = $jerseys->fetch_assoc()): ?>
                        <tr>
                            <td><?= $j['id'] ?></td>
                            <td>
                                <?php if (file_exists("../uploads/" . $j['image'])): ?>
                                    <img src="../uploads/<?= $j['image'] ?>" style="width:50px;height:50px;border-radius:8px;object-fit:cover;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:var(--pitch-green);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.3rem;">👕</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($j['name']) ?></strong></td>
                            <td><?= htmlspecialchars($j['team']) ?></td>
                            <td><span class="jersey-badge badge-<?= $j['category'] ?>" style="position:static;display:inline-block;"><?= ucfirst($j['category']) ?></span></td>
                            <td><strong><?= formatPrice($j['price']) ?></strong></td>
                            <td>
                                <span title="Small"  style="color:<?= $j['size_small']  < 3 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700;"><?= $j['size_small'] ?></span> /
                                <span title="Medium" style="color:<?= $j['size_medium'] < 3 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700;"><?= $j['size_medium'] ?></span> /
                                <span title="Large"  style="color:<?= $j['size_large']  < 3 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700;"><?= $j['size_large'] ?></span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_jersey.php?id=<?= $j['id'] ?>" class="btn btn-green btn-sm" title="Edit">✏️ Edit</a>
                                    <a href="jerseys.php?delete=<?= $j['id'] ?>" class="btn btn-danger btn-sm btn-confirm-delete" title="Delete">🗑️ Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <span class="icon">👕</span>
                    <h3>No jerseys found</h3>
                    <p>Start by adding your first jersey.</p>
                    <a href="add_jersey.php" class="btn btn-primary" style="margin-top:15px;">Add Jersey</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>