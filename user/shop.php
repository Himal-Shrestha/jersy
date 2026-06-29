<?php
// ============================================================
// STEP 10: USER SHOP PAGE (user/shop.php)
// Browse all jerseys with category & size filters
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid = $_SESSION['user_id'];

// Build query with optional filters
$where = "WHERE 1=1";
$category = sanitize($conn, $_GET['category'] ?? '');
$size     = sanitize($conn, $_GET['size'] ?? '');
$search   = sanitize($conn, $_GET['search'] ?? '');

if ($category) $where .= " AND category = '$category'";
if ($size === 'small')  $where .= " AND size_small > 0";
if ($size === 'medium') $where .= " AND size_medium > 0";
if ($size === 'large')  $where .= " AND size_large > 0";
if ($search) $where .= " AND (name LIKE '%$search%' OR team LIKE '%$search%')";

$jerseys = $conn->query("SELECT * FROM jerseys $where ORDER BY created_at DESC");

// Cart count
$cart_count = $conn->query("SELECT SUM(quantity) as c FROM cart WHERE user_id='$uid'")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Jerseys — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="shop.php" class="nav-link active">Shop</a></li>
        <li><a href="cart.php" class="nav-link">
            🛒 Cart <?php if ($cart_count > 0): ?>
            <span id="cart-count" style="background:var(--gold);color:var(--pitch-dark);padding:1px 8px;border-radius:50px;font-size:0.8rem;font-weight:700;"><?= $cart_count ?></span>
            <?php endif; ?>
        </a></li>
        <li><a href="orders.php" class="nav-link">My Orders</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="panel-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">🛍️ Shop<small>Browse all jerseys</small></div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Navigate</div>
            <div class="sidebar-nav-item"><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></div>
            <div class="sidebar-nav-item"><a href="shop.php"><span class="icon">🛍️</span> Shop Jerseys</a></div>
            <div class="sidebar-nav-item"><a href="cart.php"><span class="icon">🛒</span> My Cart <?php if ($cart_count > 0) echo "($cart_count)"; ?></a></div>
            <div class="sidebar-nav-item"><a href="orders.php"><span class="icon">📦</span> My Orders</a></div>
            <div class="sidebar-section-label">Filter by Category</div>
            <div class="sidebar-nav-item"><a href="shop.php" class="<?= !$category ? 'active' : '' ?>"><span class="icon">⚽</span> All Jerseys</a></div>
            <div class="sidebar-nav-item"><a href="shop.php?category=club" class="<?= $category==='club' ? 'active' : '' ?>"><span class="icon">🏟️</span> Club Jerseys</a></div>
            <div class="sidebar-nav-item"><a href="shop.php?category=national" class="<?= $category==='national' ? 'active' : '' ?>"><span class="icon">🌍</span> National Jerseys</a></div>
            <div class="sidebar-section-label">Account</div>
            <div class="sidebar-nav-item"><a href="../logout.php"><span class="icon">🚪</span> Logout</a></div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="panel-main">

        <div class="panel-header">
            <div>
                <h2>
                    <?php
                    if ($category === 'club') echo '🏟️ Club Jerseys';
                    elseif ($category === 'national') echo '🌍 National Jerseys';
                    else echo '⚽ All Jerseys';
                    ?>
                </h2>
                <p><?= $jerseys->num_rows ?> jersey(s) found</p>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="GET" class="filter-bar">
            <input
                type="text"
                name="search"
                placeholder="🔍 Search jersey or team..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <select name="category">
                <option value="">All Categories</option>
                <option value="club"     <?= $category==='club'     ? 'selected' : '' ?>>Club</option>
                <option value="national" <?= $category==='national' ? 'selected' : '' ?>>National</option>
            </select>
            <select name="size">
                <option value="">All Sizes</option>
                <option value="small"  <?= $size==='small'  ? 'selected' : '' ?>>Small</option>
                <option value="medium" <?= $size==='medium' ? 'selected' : '' ?>>Medium</option>
                <option value="large"  <?= $size==='large'  ? 'selected' : '' ?>>Large</option>
            </select>
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($category || $size || $search): ?>
                <a href="shop.php" class="btn btn-sm" style="background:var(--mid-gray);color:var(--text-dark);">Clear ✕</a>
            <?php endif; ?>
        </form>

        <!-- JERSEY GRID -->
        <?php if ($jerseys->num_rows > 0): ?>
        <div class="jerseys-grid">
            <?php while ($jersey = $jerseys->fetch_assoc()):
                $sizes_available = [];
                if ($jersey['size_small']  > 0) $sizes_available[] = 'small';
                if ($jersey['size_medium'] > 0) $sizes_available[] = 'medium';
                if ($jersey['size_large']  > 0) $sizes_available[] = 'large';
                $in_stock = count($sizes_available) > 0;
            ?>
            <div class="jersey-card-wrapper">
                <div class="jersey-card"
                     data-category="<?= $jersey['category'] ?>"
                     data-sizes="<?= implode(',', $sizes_available) ?>">

                    <div class="jersey-card-img">
                        <?php if (file_exists("../uploads/" . $jersey['image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($jersey['image']) ?>" alt="<?= htmlspecialchars($jersey['name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder">👕</div>
                        <?php endif; ?>
                        <span class="jersey-badge badge-<?= $jersey['category'] ?>"><?= ucfirst($jersey['category']) ?></span>
                        <?php if (!$in_stock): ?>
                            <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;">
                                <span style="color:white;font-weight:700;font-size:1.1rem;background:var(--danger);padding:6px 16px;border-radius:6px;">OUT OF STOCK</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="jersey-card-body">
                        <h4><?= htmlspecialchars($jersey['name']) ?></h4>
                        <p class="team">🏟️ <?= htmlspecialchars($jersey['team']) ?></p>
                        <div class="jersey-sizes">
                            <span class="size-chip <?= $jersey['size_small']  > 0 ? 'available' : '' ?>">S (<?= $jersey['size_small'] ?>)</span>
                            <span class="size-chip <?= $jersey['size_medium'] > 0 ? 'available' : '' ?>">M (<?= $jersey['size_medium'] ?>)</span>
                            <span class="size-chip <?= $jersey['size_large']  > 0 ? 'available' : '' ?>">L (<?= $jersey['size_large'] ?>)</span>
                        </div>
                        <div class="jersey-card-footer">
                            <span class="jersey-price"><?= formatPrice($jersey['price']) ?></span>
                            <?php if ($in_stock): ?>
                                <a href="jersey_detail.php?id=<?= $jersey['id'] ?>" class="btn btn-green btn-sm">Buy Now</a>
                            <?php else: ?>
                                <span style="color:var(--danger);font-size:0.85rem;font-weight:700;">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php else: ?>
        <div class="empty-state" id="empty-state">
            <span class="icon">👕</span>
            <h3>No jerseys found</h3>
            <p>Try adjusting your search or filter settings.</p>
            <a href="shop.php" class="btn btn-primary" style="margin-top:15px;">View All Jerseys</a>
        </div>
        <?php endif; ?>

    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>