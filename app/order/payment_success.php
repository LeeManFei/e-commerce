<?php
include_once '../_base.php';
require_once __DIR__ . '/../lib/vendor/autoload.php';

auth("customer", "admin");

// Secret Key
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY') ?: '');

$session_id = $_GET['session_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

if ($session_id && $order_id) {
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);

        if ($session->payment_status == 'paid') {
            // Update database to Paid
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'processing' WHERE order_id = ?");
            $stmt->execute([$order_id]);

            // 1. Set success message (Green)
            temp('success', 'Payment Successful! Thank you.');
            
            // 2. Redirect to My Orders page
            redirect(BASE_URL . 'order/orders.php'); 
        }
    } catch (Exception $e) {
        temp('error', 'Error: ' . $e->getMessage());
        redirect(BASE_URL . 'order/orders.php');
    }
} else {
    redirect(BASE_URL . 'order/orders.php');
}
