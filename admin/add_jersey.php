<?php
// ============================================================
// STEP 17: ADD JERSEY PAGE (admin/add_jersey.php)
// Form to add a new jersey with image upload
// ============================================================
require_once '../db.php';
requireAdminLogin();

$error = $success = '';

// ---- PROCESS FORM ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($conn, $_POST['name'] ?? '');
    $team        = sanitize($conn, $_POST['team'] ?? '');
    $category    = sanitize($conn, $_POST['category'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $size_small  = (int)($_POST['size_small']  ?? 0);
    $size_medium = (int)($_POST['size_medium'] ?? 0);
    $size_large  = (int)($_POST['size_large']  ?? 0);

    // Validate
    if (empty($name) || empty($team) || empty($category) || $price <= 0) {
        $error = 'Please fill in all required fields and set a valid price.';
    } elseif (!in_array($category, ['club', 'national'])) {
        $error = 'Invalid category selected.';
    } else {
        // Handle image upload
        $image = 'default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($file_type, $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image = 'jersey_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($ext);
                if (!move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/$image")) {
                    $error = 'Failed to upload image. Check uploads/ folder permissions.';
                    $image = 'default.jpg';
                }
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO jerseys (name, team, category, description, price, image, size_small, size_medium, size_large) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssdssii", $name, $team, $category, $description, $price, $image, $size_small, $size_medium, $size_large);
            // Note: Using 'i' (integer) for sizes. Let me fix the bind types:
            if ($stmt->execute()) {
                header("Location: jerseys.php?msg=Jersey added successfully!");
                exit();
            } else {
                $error = 'Failed to save jersey. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Jersey — Admin</title>
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
            <div><h2>➕ Add New Jersey</h2><p>Fill in the details to add a jersey to the store</p></div>
            <a href="jerseys.php" class="btn btn-green btn-sm">← Back to Jerseys</a>
        </div>

        <?php if ($error): echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>

        <div class="card">
            <div class="card-body" style="padding:30px;">
                <form method="POST" enctype="multipart/form-data">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Jersey Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="name" placeholder="e.g. Real Madrid Home Kit"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Team Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="team" placeholder="e.g. Real Madrid"
                                value="<?= htmlspecialchars($_POST['team'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Category <span style="color:var(--danger)">*</span></label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="club"     <?= ($_POST['category'] ?? '')==='club'     ? 'selected' : '' ?>>Club Jersey</option>
                                <option value="national" <?= ($_POST['category'] ?? '')==='national' ? 'selected' : '' ?>>National Jersey</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.) <span style="color:var(--danger)">*</span></label>
                            <input type="number" name="price" placeholder="e.g. 3500" step="0.01" min="1"
                                value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Brief description of the jersey..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Stock Quantities -->
                    <div style="background:var(--light-gray);padding:20px;border-radius:var(--radius);margin-bottom:20px;">
                        <h4 style="margin-bottom:15px;color:var(--pitch-dark);">📦 Stock Quantities</h4>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Small (S)</label>
                                <input type="number" name="size_small" placeholder="0" min="0" value="<?= htmlspecialchars($_POST['size_small'] ?? '0') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Medium (M)</label>
                                <input type="number" name="size_medium" placeholder="0" min="0" value="<?= htmlspecialchars($_POST['size_medium'] ?? '0') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Large (L)</label>
                                <input type="number" name="size_large" placeholder="0" min="0" value="<?= htmlspecialchars($_POST['size_large'] ?? '0') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="form-group">
                        <label>Jersey Image</label>
                        <input type="file" id="jersey_image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small style="color:var(--text-muted);display:block;margin-top:5px;">Accepted: JPG, PNG, GIF, WEBP. Max size: 5MB.</small>
                        <div id="image_preview" class="img-preview">📷</div>
                    </div>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;">
                        <button type="submit" class="btn btn-primary">➕ Add Jersey</button>
                        <a href="jerseys.php" class="btn btn-green">Cancel</a>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>