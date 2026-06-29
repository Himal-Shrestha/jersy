<?php
// ============================================================
// STEP 6: LOGIN PAGE (login.php)
// Handles both user and admin login with role detection
// ============================================================
require_once 'db.php';

// If already logged in, redirect to appropriate panel
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') header("Location: admin/dashboard.php");
    else header("Location: user/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Show message passed via URL (e.g. after registration or session timeout)
if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

// ---- PROCESS LOGIN FORM ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Find user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Please contact admin.';
            } else {
                // Set session variables
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role']      = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php#jerseys");
                }
                exit();
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Jersey Club</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Back to Home link -->
<div style="position:fixed;top:15px;left:20px;z-index:100;">
    <a href="index.php" style="color:rgba(255,255,255,0.7);font-size:0.9rem;font-weight:600;">← Back to Home</a>
</div>

<div class="auth-page">
    <div class="form-container">

        <!-- Logo -->
        <div style="text-align:center;margin-bottom:25px;">
            <div style="font-family:'Anton',sans-serif;font-size:2rem;color:var(--pitch-dark);">
                ⚽ JERSEY CLUB
            </div>
            <p style="color:var(--text-muted);font-size:0.85rem;margin-top:5px;">Sign in to your account</p>
        </div>

        <!-- Alerts -->
        <?php if ($error):   echo "<div class='alert alert-danger'>⚠️ $error</div>"; endif; ?>
        <?php if ($success): echo "<div class='alert alert-success'>✅ $success</div>"; endif; ?>

        <!-- Login Form -->
        <form method="POST" id="auth-form" novalidate>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:10px;">
                Login →
            </button>
        </form>

        <div class="form-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>

     
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>