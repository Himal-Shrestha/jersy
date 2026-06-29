<?php
// ============================================================
// STEP 8: LOGOUT (logout.php)
// Destroys the session and redirects to homepage
// ============================================================
require_once 'db.php';
session_destroy();
header("Location: login.php");
exit();
?>