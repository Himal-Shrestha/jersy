<?php
require_once '../db.php';
requireUserLogin();

$uid    = $_SESSION['user_id'];
$amount = $_POST['total_amount']     ?? 0;
$address = $_POST['shipping_address'] ?? '';

// Fetch user details
$user = $conn->query("SELECT * FROM users WHERE id='$uid'")->fetch_assoc();

// Fetch cart items
$cart_items = $conn->query("
    SELECT c.*, j.name, j.team, j.price, j.image,
           j.size_small, j.size_medium, j.size_large
    FROM cart c
    JOIN jerseys j ON c.jersey_id = j.id
    WHERE c.user_id = '$uid'
");

$items_arr = [];
while ($item = $cart_items->fetch_assoc()) {
    $item['line_total'] = $item['price'] * $item['quantity'];
    $items_arr[] = $item;
}

// Store everything in session here
$_SESSION['pending_user_id'] = $uid;
$_SESSION['pending_amount']  = $amount;
$_SESSION['pending_address'] = $address;
$_SESSION['pending_user']    = [
    'id'    => $user['id'],
    'name'  => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? '',
];
$_SESSION['pending_items'] = $items_arr;

$transaction_uuid = uniqid('JC_', true);
$_SESSION['pending_uuid'] = $transaction_uuid;

$message   = "total_amount=$amount,transaction_uuid=$transaction_uuid,product_code=EPAYTEST";
$signature = base64_encode(hash_hmac('sha256', $message, '8gBm/:&EnhH.1/q', true));
?>
<body>
<form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
    <input type="hidden" name="amount"                   value="<?= $amount ?>">
    <input type="hidden" name="tax_amount"               value="0">
    <input type="hidden" name="total_amount"             value="<?= $amount ?>">
    <input type="hidden" name="transaction_uuid"         value="<?= $transaction_uuid ?>">
    <input type="hidden" name="product_code"             value="EPAYTEST">
    <input type="hidden" name="product_service_charge"   value="0">
    <input type="hidden" name="product_delivery_charge"  value="0">
    <input type="hidden" name="success_url"              value="http://localhost/jersey_club/payment/esewa_success.php">
    <input type="hidden" name="failure_url"              value="http://localhost/jersey_club/payment/esewa_failure.php">
    <input type="hidden" name="signed_field_names"       value="total_amount,transaction_uuid,product_code">
    <input type="hidden" name="signature"                value="<?= $signature ?>">
</form>
<script>document.querySelector('form').submit();</script>
</body>