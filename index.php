<?php
// ============================================================
// STEP 5: LANDING PAGE (index.php)
// Public-facing homepage: hero, featured jerseys, stats, about
// ============================================================
require_once 'db.php';

// Fetch 6 featured jerseys from DB for the landing page
$featured = $conn->query("SELECT * FROM jerseys ORDER BY created_at DESC LIMIT 6");

// Get jersey counts for stats bar
$total_jerseys = $conn->query("SELECT COUNT(*) as c FROM jerseys")->fetch_assoc()['c'];
$club_count    = $conn->query("SELECT COUNT(*) as c FROM jerseys WHERE category='club'")->fetch_assoc()['c'];
$national_count = $conn->query("SELECT COUNT(*) as c FROM jerseys WHERE category='national'")->fetch_assoc()['c'];
$user_count    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jersey Club — Wear Your Passion</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts loaded via CSS @import -->
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        ⚽ JERSEY <span>CLUB</span>
    </a>
    <ul class="navbar-nav">
        <li><a href="index.php" class="nav-link active">Home</a></li>
        <li><a href="#jerseys" class="nav-link">Jerseys</a></li>
        <li><a href="#about" class="nav-link">About</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="admin/dashboard.php" class="nav-link">Admin Panel</a></li>
            <?php else: ?>
                <li><a href="user/shop.php" class="nav-link">Shop</a></li>
                <li><a href="user/cart.php" class="nav-link">
                    🛒 Cart
                    <?php
                        $cart_count = $conn->query("SELECT SUM(quantity) as c FROM cart WHERE user_id='{$_SESSION['user_id']}'")->fetch_assoc()['c'];
                        if ($cart_count > 0) echo "<span id='cart-count' style='background:var(--gold);color:var(--pitch-dark);padding:1px 7px;border-radius:50px;font-size:0.8rem;font-weight:700;margin-left:3px;'>$cart_count</span>";
                    ?>
                </a></li>
                <li><a href="user/dashboard.php" class="nav-link">My Account</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="nav-link btn-nav-cta">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php" class="nav-link">Login</a></li>
            <li><a href="register.php" class="nav-link btn-nav-cta">Register</a></li>
        <?php endif; ?>
    </ul>
    <div class="hamburger">
        <span></span><span></span><span></span>
    </div>
</nav>

<!-- ===================== HERO ===================== -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-eyebrow">🏆 Official Football Jerseys</span>
        <h1>Wear Your<br><span>Passion</span></h1>
        <p>Shop authentic club and national team jerseys. Represent your team with pride — on and off the pitch.</p>
        <div class="hero-buttons">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                <a href="user/shop.php" class="btn btn-primary">🛍️ Shop Now</a>
                <a href="#jerseys" class="btn btn-outline">View Featured</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">🛍️ Start Shopping</a>
                <a href="login.php" class="btn btn-outline">Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== STATS BAR ===================== -->
<div class="stats-bar">
    <div class="stat-item">
        <span class="number counter" data-target="<?= $total_jerseys ?>" data-suffix="+"><?= $total_jerseys ?>+</span>
        <span class="label">Jerseys Available</span>
    </div>
    <div class="stat-item">
        <span class="number counter" data-target="<?= $club_count ?>"><?= $club_count ?></span>
        <span class="label">Club Jerseys</span>
    </div>
    <div class="stat-item">
        <span class="number counter" data-target="<?= $national_count ?>"><?= $national_count ?></span>
        <span class="label">National Jerseys</span>
    </div>
    <div class="stat-item">
        <span class="number counter" data-target="<?= $user_count ?>" data-suffix="+"><?= $user_count ?>+</span>
        <span class="label">Happy Customers</span>
    </div>
    <div class="stat-item">
        <span class="number">3</span>
        <span class="label">Sizes: S / M / L</span>
    </div>
</div>

<!-- ===================== FEATURED JERSEYS ===================== -->
<section class="section section-white" id="jerseys">
    <div class="container">
        <div class="section-header">
            <h2>Featured Jerseys</h2>
            <div class="divider"></div>
            <p>Handpicked club and national jerseys — available in Small, Medium, and Large</p>
        </div>

        <div class="jerseys-grid">
            <?php while ($jersey = $featured->fetch_assoc()): ?>
            <div class="jersey-card">
                <div class="jersey-card-img">
                    <?php if (file_exists("uploads/" . $jersey['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($jersey['image']) ?>" alt="<?= htmlspecialchars($jersey['name']) ?>">
                    <?php else: ?>
                        <div class="img-placeholder">👕</div>
                    <?php endif; ?>
                    <span class="jersey-badge badge-<?= $jersey['category'] ?>">
                        <?= ucfirst($jersey['category']) ?>
                    </span>
                </div>
                <div class="jersey-card-body">
                    <h4><?= htmlspecialchars($jersey['name']) ?></h4>
                    <p class="team">🏟️ <?= htmlspecialchars($jersey['team']) ?></p>
                    <div class="jersey-sizes">
                        <span class="size-chip <?= $jersey['size_small']  > 0 ? 'available' : '' ?>">S</span>
                        <span class="size-chip <?= $jersey['size_medium'] > 0 ? 'available' : '' ?>">M</span>
                        <span class="size-chip <?= $jersey['size_large']  > 0 ? 'available' : '' ?>">L</span>
                    </div>
                    <div class="jersey-card-footer">
                        <span class="jersey-price"><?= formatPrice($jersey['price']) ?></span>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                            <a href="user/jersey_detail.php?id=<?= $jersey['id'] ?>" class="btn btn-green btn-sm">Buy Now</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-green btn-sm">Login to Buy</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="text-center mt-20">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user'): ?>
                <a href="user/shop.php" class="btn btn-primary">View All Jerseys →</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Register to See All →</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== ABOUT SECTION ===================== -->
<section class="section section-green" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">⚽</div>
            <div class="about-text">
                <h2 class="light">About Jersey Club</h2>
                <p>Jersey Club is Nepal's premier destination for authentic football jerseys. We bring you the best club and national team jerseys from around the world — straight to your door.</p>
                <p>From the streets of Kathmandu to the pitch, wear your allegiance with pride. Every jersey is carefully sourced and comes in your perfect size.</p>
                <div class="feature-list">
                    <div class="feature-item">Authentic Club Jerseys from Top Leagues</div>
                    <div class="feature-item">Official National Team Jerseys</div>
                    <div class="feature-item">Available in Small, Medium & Large</div>
                    <div class="feature-item">Fast & Secure Delivery Across Nepal</div>
                    <div class="feature-item">100% Satisfaction Guaranteed</div>
                </div>
                <div class="mt-20">
                    <a href="register.php" class="btn btn-primary">Join the Club →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="navbar-brand" style="font-size:1.5rem;">⚽ JERSEY <span>CLUB</span></div>
            <p class="footer-brand-text">Nepal's #1 destination for authentic football jerseys. Club kits, national team jerseys — all in one place.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="#jerseys">Jerseys</a></li>
                <li><a href="#about">About</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <ul>
                <li><a href="#">📍 Tripureshwor, Kathmandu</a></li>
                <li><a href="#">📞 +977-9745659693</a></li>
                <li><a href="#">✉️ himalcrestha4567@gmail.com</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> Jersey Club. All rights reserved.</p>
        <p>Made with ⚽ in Nepal</p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>