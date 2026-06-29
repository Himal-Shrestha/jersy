<?php
// ============================================================
// STEP 1: DATABASE CONNECTION (db.php)
// This file connects to MySQL. Every other PHP file includes this.
// ============================================================
//My Name is suman Thapa
define('DB_HOST', 'localhost');
define('DB_USER', 'suman');        // Change to your MySQL username
define('DB_PASS', 'suman123');            // Change to your MySQL password
define('DB_NAME', 'jersey_club');

// Create connection using mysqli
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("
    <div style='font-family:sans-serif;padding:40px;text-align:center;'>
        <h2 style='color:red;'>Database Connection Failed</h2>
        <p>" . $conn->connect_error . "</p>
        <p>Please check your database settings in <b>db.php</b> and make sure MySQL is running.</p>
    </div>");
}

// Set character set to utf8
$conn->set_charset("utf8");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

// Sanitize input to prevent XSS attacks
function sanitize($conn, $input) {
    return $conn->real_escape_string(htmlspecialchars(trim($input)));
}

// Check if user is logged in (for user panel)
function requireUserLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        header("Location: ../login.php?msg=Please login first");
        exit();
    }
}

// Check if admin is logged in (for admin panel)
function requireAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php?msg=Admin access required");
        exit();
    }
}

// Redirect with message
function redirect($url, $msg = '') {
    if ($msg) {
        header("Location: $url?msg=" . urlencode($msg));
    } else {
        header("Location: $url");
    }
    exit();
}

// Format price to Nepali Rupees style
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}
?>









