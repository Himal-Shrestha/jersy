<?php
// ============================================================
// STEP 11: JERSEY DETAIL PAGE (user/jersey_detail.php)
// Shows full jersey info, size selector, add-to-cart
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

if (!$id) { header("Location: shop.php"); exit(); }

// Fetch jersey
$stmt = $conn->prepare("SELECT * FROM jerseys WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$jersey = $stmt->get_result()->fetch_assoc();

if (!$jersey) { header("Location: shop.php"); exit(); }

$msg   = '';
$error = '';

// ---- HANDLE ADD TO CART ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_cart') {
    $size = sanitize($conn, $_POST['size'] ?? '');
    $qty  = max(1, min(10, (int)($_POST['quantity'] ?? 1)));

    if (!in_array($size, ['small', 'medium', 'large'])) {
        $error = 'Please select a size.';
    } else {
        // Check stock
        $stock_col = "size_$size";
        if ($jersey[$stock_col] < $qty) {
            $error = "Only {$jersey[$stock_col]} units available in " . ucfirst($size) . ".";
        } else {
            // Check if this size already in cart — update qty
            $existing = $conn->query("SELECT id, quantity FROM cart WHERE user_id='$uid' AND jersey_id='$id' AND size='$size'")->fetch_assoc();
            if ($existing) {
                $new_qty = min(10, $existing['quantity'] + $qty);
                $conn->query("UPDATE cart SET quantity='$new_qty' WHERE id='{$existing['id']}'");
            } else {
                $conn->query("INSERT INTO cart (user_id, jersey_id, size, quantity) VALUES ('$uid', '$id', '$size', '$qty')");
            }
            $msg = 'Jersey added to cart! <a href="cart.php" style="color:var(--gold);font-weight:700;">View Cart →</a>';
        }
    }
}

// Cart count
$cart_count = $conn->query("SELECT SUM(quantity) as c FROM cart WHERE user_id='$uid'")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($jersey['name']) ?> — Jersey Club</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
        <li><a href="shop.php" class="nav-link">Shop</a></li>
        <li><a href="cart.php" class="nav-link">🛒 Cart <?php if ($cart_count > 0) echo "($cart_count)"; ?></a></li>
        <li><a href="orders.php" class="nav-link">My Orders</a></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
    <div class="hamburger"><span></span><span></span><span></span></div>
</nav>

<div style="padding:30px 5%;max-width:1100px;margin:0 auto;">

    <!-- Breadcrumb -->
    <div style="margin-bottom:20px;font-size:0.9rem;color:var(--text-muted);">
        <a href="shop.php" style="color:var(--pitch-green);">Shop</a> → <?= htmlspecialchars($jersey['name']) ?>
    </div>

    <!-- Alerts -->
    <?php if ($msg):   echo "<div class='alert alert-success'>✅ $msg</div>"; endif; ?>
    <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

    <!-- JERSEY DETAIL -->
    <div class="jersey-detail">

        <!-- LEFT: Image -->
        <div class="jersey-detail-img">
            <?php if (file_exists("../uploads/" . $jersey['image'])): ?>
                <img src="../uploads/<?= htmlspecialchars($jersey['image']) ?>" alt="<?= htmlspecialchars($jersey['name']) ?>">
            <?php else: ?>
                <div class="img-placeholder" style="height:100%;font-size:8rem;">👕</div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Info + Add to Cart -->
        <div class="jersey-detail-info">

            <span class="jersey-badge badge-<?= $jersey['category'] ?>" style="position:static;display:inline-block;margin-bottom:15px;">
                <?= ucfirst($jersey['category']) ?> Jersey
            </span>

            <h2><?= htmlspecialchars($jersey['name']) ?></h2>
            <p style="color:var(--text-muted);margin-bottom:10px;">🏟️ <?= htmlspecialchars($jersey['team']) ?></p>

            <div class="price-big"><?= formatPrice($jersey['price']) ?></div>

            <p style="line-height:1.7;margin-bottom:25px;">
                <?= htmlspecialchars($jersey['description'] ?: 'Official jersey — authentic quality for true fans.') ?>
            </p>

            <!-- Stock Info -->
            <div style="background:var(--light-gray);padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:0.9rem;">
                <strong>Stock:</strong>
                S: <?= $jersey['size_small'] ?> &nbsp;|&nbsp;
                M: <?= $jersey['size_medium'] ?> &nbsp;|&nbsp;
                L: <?= $jersey['size_large'] ?>
            </div>

            <!-- Add to Cart Form -->
            <form method="POST">
                <input type="hidden" name="action" value="add_cart">
                <input type="hidden" name="size" id="selected_size" value="">

                <div style="margin-bottom:20px;">
                    <label style="font-weight:700;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:10px;">
                        Select Size
                    </label>
                    <div class="size-selector">
                        <button type="button" class="size-btn <?= $jersey['size_small']  == 0 ? 'out-of-stock' : '' ?>"
                            data-size="small" <?= $jersey['size_small'] == 0 ? 'disabled' : '' ?>>
                            S <?= $jersey['size_small'] == 0 ? '(Out)' : '' ?>
                        </button>
                        <button type="button" class="size-btn <?= $jersey['size_medium'] == 0 ? 'out-of-stock' : '' ?>"
                            data-size="medium" <?= $jersey['size_medium'] == 0 ? 'disabled' : '' ?>>
                            M <?= $jersey['size_medium'] == 0 ? '(Out)' : '' ?>
                        </button>
                        <button type="button" class="size-btn <?= $jersey['size_large']  == 0 ? 'out-of-stock' : '' ?>"
                            data-size="large" <?= $jersey['size_large'] == 0 ? 'disabled' : '' ?>>
                            L <?= $jersey['size_large'] == 0 ? '(Out)' : '' ?>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:25px;">
                    <label style="font-weight:700;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:10px;">
                        Quantity
                    </label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" data-action="dec">−</button>
                        <input type="number" name="quantity" class="qty-input" value="1" min="1" max="10">
                        <button type="button" class="qty-btn" data-action="inc">+</button>
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">🛒 Add to Cart</button>
                    <a href="shop.php" class="btn btn-outline" style="color:var(--pitch-dark);border-color:var(--mid-gray);">← Continue Shopping</a>
                </div>
            </form>

        </div>
    </div>

    <!-- Back link -->
    <div style="margin-top:40px;padding-top:20px;border-top:1px solid var(--mid-gray);">
        <a href="shop.php" style="color:var(--pitch-green);font-weight:600;">← Back to Shop</a>
    </div>

</div>

<script src="../assets/js/main.js"></script>
</body>
</html>