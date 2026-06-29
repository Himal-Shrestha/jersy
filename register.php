<?php
// ============================================================
// STEP 7: REGISTER PAGE (register.php)
// New user registration — only creates 'user' role accounts
// ============================================================
require_once 'db.php';

// Already logged in? Redirect
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$error = '';
$success = '';

// ---- PROCESS REGISTRATION FORM ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($conn, $_POST['name'] ?? '');
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $phone    = sanitize($conn, $_POST['phone'] ?? '');
    $address  = sanitize($conn, $_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'This email address is already registered. <a href="login.php">Login instead?</a>';
        } else {
            // Hash password and insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'active')");
            $stmt->bind_param("sssss", $name, $email, $phone, $address, $hashed);

            if ($stmt->execute()) {
                $success = 'Registration successful! Please login to continue.';
                // Redirect to login after 2 seconds via meta refresh
                header("refresh:2;url=login.php?msg=Registration successful! Please login.");
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Jersey Club</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Back to Home -->
<div style="position:fixed;top:15px;left:20px;z-index:100;">
    <a href="index.php" style="color:rgba(255,255,255,0.7);font-size:0.9rem;font-weight:600;">← Back to Home</a>
</div>

<div class="auth-page">
    <div class="form-container" style="max-width:520px;">

        <!-- Logo -->
        <div style="text-align:center;margin-bottom:25px;">
            <div style="font-family:'Anton',sans-serif;font-size:2rem;color:var(--pitch-dark);">
                ⚽ JERSEY CLUB
            </div>
            <p style="color:var(--text-muted);font-size:0.85rem;margin-top:5px;">Create your account to start shopping</p>
        </div>

        <!-- Alerts -->
        <?php if ($error):   echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>
        <?php if ($success): echo "<div class='alert alert-success'>✅ $success<br><small>Redirecting to login...</small></div>"; endif; ?>

        <?php if (!$success): ?>
        <!-- Registration Form -->
        <form method="POST" id="auth-form" novalidate>

            <div class="form-group">
                <label for="name">Full Name <span style="color:var(--danger)">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Your full name"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address <span style="color:var(--danger)">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="+977-98XXXXXXXX"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                >
            </div>

          

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span style="color:var(--danger)">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Min. 6 characters"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span style="color:var(--danger)">*</span></label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat password"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Create Account →
            </button>
        </form>
        <?php endif; ?>

        <div class="form-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>