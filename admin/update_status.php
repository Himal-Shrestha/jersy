<?php
require_once '../db.php';
requireAdminLogin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' ) {
    
    
    $order_id   = (int)$_POST['order_id'];
    $new_status = sanitize($conn, $_POST['status']);
    $allowed    = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $conn->query("UPDATE orders SET status='$new_status' WHERE id='$order_id'");
        $msg = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
        header('location:orders.php');
    }
}

?>