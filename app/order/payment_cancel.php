<?php
include_once '../_base.php';

// 1. Set failure message (Red)
temp('error', 'Payment was cancelled or failed.');

// 2. Redirect back to My Orders page
// The user will see a red error box, and the order status remains Pending
redirect(BASE_URL . 'order/orders.php');
?>