<?php
// ============================================================
// STEP 22: ADMIN PROFILE PAGE (admin/profile.php)
// Admin can update their name and change password
// ============================================================
require_once '../db.php';
requireAdminLogin();

$uid   = $_SESSION['user_id'];
$admin = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();

$msg_info = $msg_pass = $err_info = $err_pass = '';

// ---- UPDATE NAME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $name  = sanitize($conn, $_POST['name'] ?? '');
    $phone = sanitize($conn, $_POST['phone'] ?? '');
    if (strlen($name) < 2) {
        $err_info = 'Name must be at least 2 characters.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $phone, $uid);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $admin['name']  = $name;
            $admin['phone'] = $phone;
            $msg_info = 'Profile updated successfully!';
        } else {
            $err_info = 'Update failed.';
        }
    }
}

// ---- CHANGE PASSWORD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new_p   = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $admin['password'])) {
        $err_pass = 'Current password is incorrect.';
    } elseif (strlen($new_p) < 6) {
        $err_pass = 'New password must be at least 6 characters.';
    } elseif ($new_p !== $confirm) {
        $err_pass = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_p, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $uid);
        $stmt->execute() ? $msg_pass = 'Password changed successfully!' : $err_pass = 'Failed to change password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile — Jersey Club</title>
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
        <div class="sidebar-brand">🛡️ Admin Panel<small><?= htmlspecialchars($admin['name']) ?></small></div>
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
            <div class="sidebar-nav-item"><a href="profile.php"><span class="icon">⚙️</span> My Profile</a></div>
            <div class="sidebar-nav-item"><a href="../logout.php"><span class="icon">🚪</span> Logout</a></div>
        </nav>
    </aside>

    <main class="panel-main">
        <div class="panel-header">
            <div><h2>⚙️ Admin Profile</h2><p>Update your admin account settings</p></div>
            <a href="dashboard.php" class="btn btn-green btn-sm">← Dashboard</a>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;align-items:start;">

            <!-- Update Info -->
            <div class="card">
                <div class="card-header"><h3>👤 Account Information</h3></div>
                <div class="card-body" style="padding:25px;">
                    <?php if ($msg_info): echo "<div class='alert alert-success'>✅ $msg_info</div>"; endif; ?>
                    <?php if ($err_info): echo "<div class='alert alert-danger'>⚠️ $err_info</div>"; endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_info">
                        <div class="form-group">
                            <label>Admin Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?= htmlspecialchars($admin['email']) ?>" disabled
                                style="background:var(--light-gray);cursor:not-allowed;opacity:0.7;">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>" placeholder="+977-98XXXXXXXX">
                        </div>
                        <div style="padding:12px;background:rgba(244,196,48,0.1);border-radius:var(--radius);margin-bottom:20px;font-size:0.85rem;">
                            <strong>Role:</strong> Administrator &nbsp;|&nbsp;
                            <strong>Since:</strong> <?= date('M Y', strtotime($admin['created_at'])) ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">💾 Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
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
                        <div style="background:rgba(229,57,53,0.06);padding:12px;border-radius:var(--radius);margin-bottom:20px;font-size:0.85rem;color:var(--danger);">
                            ⚠️ After changing your password, you will need to login again.
                        </div>
                        <button type="submit" class="btn btn-danger btn-full">🔑 Change Password</button>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>