<?php
$message = "total_amount=2000,transaction_uuid=1761761769,product_code=EPAYTEST";
$signature = base64_encode(hash_hmac('sha256', $message, '8gBm/:&EnhH.1/q', true));
echo ($signature); 

?>



<body>
 <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
 <input type="text" id="amount" name="amount" value="2000" required>
 <input type="text" id="tax_amount" name="tax_amount" value ="0" required>
 <input type="text" id="total_amount" name="total_amount" value="2000" required>
 <input type="text" id="transaction_uuid" name="transaction_uuid" value="1761761769" required>
 <input type="text" id="product_code" name="product_code" value ="EPAYTEST" required>
 <input type="text" id="product_service_charge" name="product_service_charge" value="0" required>
 <input type="text" id="product_delivery_charge" name="product_delivery_charge" value="0" required>
 <input type="text" id="success_url" name="success_url" value="https://developer.esewa.com.np/success" required>
 <input type="text" id="failure_url" name="failure_url" value="https://developer.esewa.com.np/failure" required>
 <input type="text" id="signed_field_names" name="signed_field_names" value="total_amount,transaction_uuid,product_code" required>
 <input type="text" id="signature" name="signature" value="<?= $signature ?>" required>
 <input value="Submit" type="submit">
 </form>
</body>
 