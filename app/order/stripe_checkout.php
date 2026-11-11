<?php
include_once '../_base.php';
// Include Stripe library
require_once __DIR__ . '/../lib/vendor/autoload.php'; 

auth("customer", "admin");

// 1. Check if Order ID is provided
if (!isset($_GET['order_id'])) {
    redirect(BASE_URL);
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// 2. Fetch order amount
$stmt = $conn->prepare("SELECT total_amount FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    temp('error', 'Order not found.');
    redirect(BASE_URL . 'order/checkout.php');
}

// 3. Configure Stripe API Key
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY') ?: '');

// 4. Amount conversion (Stripe unit is cents)
$amount_cents = intval($order['total_amount'] * 100); 

try {
    // 5. Create Stripe Session
    $session = \Stripe\Checkout\Session::create([
        // Support Card and GrabPay
        'payment_method_types' => ['card', 'grabpay'], 
        
        'line_items' => [[
            'price_data' => [
                'currency' => 'myr',
                'product_data' => ['name' => 'Order #' . $order_id],
                'unit_amount' => $amount_cents,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        
        // GrabPay requires this parameter
        'submit_type' => 'pay',
        // Success callback URL (Process in payment_success.php, then redirect to my_orders.php)
        'success_url' => BASE_URL . 'order/payment_success.php?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order_id,
        
        //  Cancel/Failure callback URL (Redirect to payment_cancel.php)
        // Added ?order_id=... so the system knows which order to mark as Failed/Cancelled
        'cancel_url' => BASE_URL . 'order/payment_cancel.php?order_id=' . $order_id,
    ]);

    // 6. Redirect to Stripe
    header("Location: " . $session->url);
    exit;
} catch (Exception $e) {
    temp('error', 'Stripe Error: ' . $e->getMessage());
    // If error occurs before redirecting to Stripe, return to Checkout page
    redirect(BASE_URL . 'order/checkout.php');
}
