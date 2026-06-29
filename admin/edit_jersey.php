<?php
// ============================================================
// STEP 18: EDIT JERSEY PAGE (admin/edit_jersey.php)
// Edit existing jersey details and image
// ============================================================
require_once '../db.php';
requireAdminLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: jerseys.php"); exit(); }

// Fetch existing jersey
$jersey = $conn->query("SELECT * FROM jerseys WHERE id='$id'")->fetch_assoc();
if (!$jersey) { header("Location: jerseys.php"); exit(); }

$error = $success = '';

// ---- PROCESS UPDATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($conn, $_POST['name'] ?? '');
    $team        = sanitize($conn, $_POST['team'] ?? '');
    $category    = sanitize($conn, $_POST['category'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $size_small  = (int)($_POST['size_small']  ?? 0);
    $size_medium = (int)($_POST['size_medium'] ?? 0);
    $size_large  = (int)($_POST['size_large']  ?? 0);

    if (empty($name) || empty($team) || empty($category) || $price <= 0) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($category, ['club', 'national'])) {
        $error = 'Invalid category.';
    } else {
        $image = $jersey['image']; // Keep existing image by default

        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($file_type, $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WEBP allowed.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_image = 'jersey_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/$new_image")) {
                    // Delete old image
                    if ($image !== 'default.jpg' && file_exists("../uploads/$image")) {
                        @unlink("../uploads/$image");
                    }
                    $image = $new_image;
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE jerseys SET name=?, team=?, category=?, description=?, price=?, image=?, size_small=?, size_medium=?, size_large=? WHERE id=?");
            $stmt->bind_param("ssssdsiiis", $name, $team, $category, $description, $price, $image, $size_small, $size_medium, $size_large, $id);
            if ($stmt->execute()) {
                // Refresh jersey data
                $jersey = $conn->query("SELECT * FROM jerseys WHERE id='$id'")->fetch_assoc();
                $success = 'Jersey updated successfully!';
            } else {
                $error = 'Failed to update jersey.';
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
    <title>Edit Jersey — Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">⚽ JERSEY <span>CLUB</span></a>
    <ul class="navbar-nav">
        <li><span style="color:var(--gold);font-weight:700;padding:8px 12px;">Admin Panel</span></li>
        <li><a href="../logout.php" class="nav-link btn-nav-cta">Logout</a></li>
    </ul>
</nav>

<div class="panel-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">🛡️ Admin Panel<small><?= htmlspecialchars($_SESSION['user_name']) ?></small></div>
        <nav class="sidebar-nav">
            <div class="sidebar-nav-item"><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></div>
            <div class="sidebar-nav-item"><a href="jerseys.php"><span class="icon">👕</span> All Jerseys</a></div>
            <div class="sidebar-nav-item"><a href="add_jersey.php"><span class="icon">➕</span> Add Jersey</a></div>
            <div class="sidebar-nav-item"><a href="users.php"><span class="icon">👥</span> Manage Users</a></div>
            <div class="sidebar-nav-item"><a href="orders.php"><span class="icon">📦</span> All Orders</a></div>
            <div class="sidebar-nav-item"><a href="../logout.php"><span class="icon">🚪</span> Logout</a></div>
        </nav>
    </aside>

    <main class="panel-main">
        <div class="panel-header">
            <div><h2>✏️ Edit Jersey</h2><p>Update jersey details and stock</p></div>
            <a href="jerseys.php" class="btn btn-green btn-sm">← Back to Jerseys</a>
        </div>

        <?php if ($error):   echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>
        <?php if ($success): echo "<div class='alert alert-success'>✅ $success</div>"; endif; ?>

        <div class="card">
            <div class="card-body" style="padding:30px;">
                <form method="POST" enctype="multipart/form-data">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Jersey Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="name" value="<?= htmlspecialchars($jersey['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Team Name <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="team" value="<?= htmlspecialchars($jersey['team']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Category <span style="color:var(--danger)">*</span></label>
                            <select name="category" required>
                                <option value="club"     <?= $jersey['category']==='club'     ? 'selected' : '' ?>>Club Jersey</option>
                                <option value="national" <?= $jersey['category']==='national' ? 'selected' : '' ?>>National Jersey</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.) <span style="color:var(--danger)">*</span></label>
                            <input type="number" name="price" step="0.01" min="1" value="<?= $jersey['price'] ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"><?= htmlspecialchars($jersey['description']) ?></textarea>
                    </div>

                    <!-- Stock Quantities -->
                    <div style="background:var(--light-gray);padding:20px;border-radius:var(--radius);margin-bottom:20px;">
                        <h4 style="margin-bottom:15px;color:var(--pitch-dark);">📦 Stock Quantities</h4>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Small (S)</label>
                                <input type="number" name="size_small" min="0" value="<?= $jersey['size_small'] ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Medium (M)</label>
                                <input type="number" name="size_medium" min="0" value="<?= $jersey['size_medium'] ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Large (L)</label>
                                <input type="number" name="size_large" min="0" value="<?= $jersey['size_large'] ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="form-group">
                        <label>Jersey Image</label>
                        <div style="display:flex;align-items:center;gap:20px;margin-bottom:10px;">
                            <?php if (file_exists("../uploads/" . $jersey['image'])): ?>
                                <div style="text-align:center;">
                                    <img src="../uploads/<?= $jersey['image'] ?>" style="width:80px;height:80px;object-fit:cover;border-radius:var(--radius);border:2px solid var(--mid-gray);">
                                    <br><small style="color:var(--text-muted);">Current image</small>
                                </div>
                            <?php else: ?>
                                <div style="width:80px;height:80px;background:var(--light-gray);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:2rem;">👕</div>
                            <?php endif; ?>
                            <div style="flex:1;">
                                <input type="file" id="jersey_image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                                <small style="color:var(--text-muted);display:block;margin-top:5px;">Leave blank to keep current image. Max 5MB.</small>
                            </div>
                        </div>
                        <div id="image_preview" class="img-preview" style="display:none;"></div>
                    </div>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;">
                        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                        <a href="jerseys.php" class="btn btn-green">Cancel</a>
                        <a href="jerseys.php?delete=<?= $jersey['id'] ?>" class="btn btn-danger btn-confirm-delete" style="margin-left:auto;">🗑️ Delete Jersey</a>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>