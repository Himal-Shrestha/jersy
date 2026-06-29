<?php
// ============================================================
// STEP 21: USER PROFILE PAGE (user/profile.php)
// Users can update name, phone, address and change password
// ============================================================
require_once '../db.php';
requireUserLogin();

$uid  = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();

$msg_info = $msg_pass = $err_info = $err_pass = '';

// ---- UPDATE PROFILE INFO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $name    = sanitize($conn, $_POST['name']    ?? '');
    $phone   = sanitize($conn, $_POST['phone']   ?? '');
    $address = sanitize($conn, $_POST['address'] ?? '');

    if (strlen($name) < 2) {
        $err_info = 'Name must be at least 2 characters.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $phone, $address, $uid);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $user['name']    = $name;
            $user['phone']   = $phone;
            $user['address'] = $address;
            $msg_info = 'Profile updated successfully!';
        } else {
            $err_info = 'Failed to update profile. Please try again.';
        }
    }
}

// ---- CHANGE PASSWORD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password']     ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_pass, $user['password'])) {
        $err_pass = 'Current password is incorrect.';
    } elseif (strlen($new_pass) < 6) {
        $err_pass = 'New password must be at least 6 characters.';
    } elseif ($new_pass !== $confirm_pass) {
        $err_pass = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $uid);
        if ($stmt->execute()) {
            $msg_pass = 'Password changed successfully!';
        } else {
            $err_pass = 'Failed to change password. Please try again.';
        }
    }
}

// Order stats for sidebar
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id='$uid'")->fetch_assoc()['c'];
$cart_count   = $conn->query("SELECT SUM(quantity) as c FROM cart WHERE user_id='$uid'")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — Jersey Club</title>
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
            <div class="sidebar-nav-item"><a href="cart.php"><span class="icon">🛒</span> My Cart <?php if ($cart_count > 0) echo "($cart_count)"; ?></a></div>
            <div class="sidebar-nav-item"><a href="orders.php"><span class="icon">📦</span> My Orders (<?= $total_orders ?>)</a></div>
            <div class="sidebar-section-label">Account</div>
            <div class="sidebar-nav-item"><a href="profile.php"><span class="icon">⚙️</span> My Profile</a></div>
            <div class="sidebar-nav-item"><a href="../logout.php"><span class="icon">🚪</span> Logout</a></div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="panel-main">

        <div class="panel-header">
            <div>
                <h2>⚙️ My Profile</h2>
                <p>Update your account information and password</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;align-items:start;">

            <!-- LEFT: Update Profile Info -->
            <div class="card">
                <div class="card-header"><h3>👤 Personal Information</h3></div>
                <div class="card-body" style="padding:25px;">

                    <?php if ($msg_info): echo "<div class='alert alert-success'>✅ $msg_info</div>"; endif; ?>
                    <?php if ($err_info): echo "<div class='alert alert-danger'>⚠️ $err_info</div>"; endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_info">

                        <div class="form-group">
                            <label>Full Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                style="background:var(--light-gray);cursor:not-allowed;opacity:0.7;">
                            <small style="color:var(--text-muted);font-size:0.8rem;">Email cannot be changed. Contact admin if needed.</small>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" placeholder="+977-98XXXXXXXX"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Delivery Address</label>
                            <textarea name="address" rows="3"
                                placeholder="Street, Ward, City, District..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">💾 Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT: Change Password -->
            <div class="card">
                <div class="card-header"><h3>🔒 Change Password</h3></div>
                <div class="card-body" style="padding:25px;">

                    <?php if ($msg_pass): echo "<div class='alert alert-success'>✅ $msg_pass</div>"; endif; ?>
                    <?php if ($err_pass): echo "<div class='alert alert-danger'>⚠️ $err_pass</div>"; endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label>Current Password <span style="color:var(--danger)">*</span></label>
                            <input type="password" name="current_password" placeholder="Enter current password" required>
                        </div>

                        <div class="form-group">
                            <label>New Password <span style="color:var(--danger)">*</span></label>
                            <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password <span style="color:var(--danger)">*</span></label>
                            <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>

                        <div style="background:rgba(26,77,46,0.06);padding:12px;border-radius:var(--radius);margin-bottom:20px;font-size:0.85rem;color:var(--text-muted);">
                            💡 Use at least 6 characters. Mix letters, numbers and symbols for stronger security.
                        </div>

                        <button type="submit" class="btn btn-green btn-full">🔑 Change Password</button>
                    </form>
                </div>
            </div>

        </div>

        <!-- Account Stats Summary -->
        <div class="card" style="margin-top:5px;">
            <div class="card-header"><h3>📊 Account Summary</h3></div>
            <div class="card-body" style="padding:25px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:20px;">
                    <div style="text-align:center;padding:15px;background:var(--light-gray);border-radius:var(--radius);">
                        <div style="font-family:'Anton',sans-serif;font-size:1.8rem;color:var(--pitch-green);"><?= $total_orders ?></div>
                        <div style="font-size:0.85rem;color:var(--text-muted);">Total Orders</div>
                    </div>
                    <div style="text-align:center;padding:15px;background:var(--light-gray);border-radius:var(--radius);">
                        <?php $delivered = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id='$uid' AND status='delivered'")->fetch_assoc()['c']; ?>
                        <div style="font-family:'Anton',sans-serif;font-size:1.8rem;color:var(--success);"><?= $delivered ?></div>
                        <div style="font-size:0.85rem;color:var(--text-muted);">Delivered</div>
                    </div>
                    <div style="text-align:center;padding:15px;background:var(--light-gray);border-radius:var(--radius);">
                        <?php $spent = $conn->query("SELECT SUM(total_price) as t FROM orders WHERE user_id='$uid'")->fetch_assoc()['t'] ?? 0; ?>
                        <div style="font-family:'Anton',sans-serif;font-size:1.5rem;color:var(--pitch-dark);">Rs.<?= number_format($spent, 0) ?></div>
                        <div style="font-size:0.85rem;color:var(--text-muted);">Total Spent</div>
                    </div>
                    <div style="text-align:center;padding:15px;background:var(--light-gray);border-radius:var(--radius);">
                        <div style="font-family:'Anton',sans-serif;font-size:1.2rem;color:var(--pitch-dark);"><?= date('M Y', strtotime($user['created_at'])) ?></div>
                        <div style="font-size:0.85rem;color:var(--text-muted);">Member Since</div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>