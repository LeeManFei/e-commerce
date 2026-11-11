<?php
include_once '../_base.php';

// 2. Verify login
auth("customer", "admin");
$user_id = $_SESSION['user_id'];

// 3. Check cart
$cart_id = get_cart_id($conn, $user_id);
$cart_items = get_cart_items($conn, $user_id); 

if (count($cart_items) == 0) {
    temp('info', 'Your cart is empty!');
    redirect(BASE_URL . 'product/view_products.php'); 
}

// 4. Get user default profile
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt_user->execute([$user_id]);
$user_profile = $stmt_user->fetch(PDO::FETCH_ASSOC);

$default_name = $user_profile['username'] ?? '';
$default_phone = $user_profile['phone'] ?? '';
$default_email = $user_profile['gmail'] ?? ''; 

// ==========================================
// 5. Handle submission
// ==========================================
if (is_post() && isset($_POST['place_order'])) {
    $name = req('name');
    $number = req('number');
    $email = req('email');
    $method = req('method'); 
    
    $address_parts = [
        req('flat'), req('street'), req('city'), 
        req('state'), req('country'), req('pin_code')
    ];
    $full_address = implode(", ", array_filter($address_parts));

    try {
        $conn->beginTransaction();

        // A. Calculate grand total
        $grand_total = 0;
        foreach ($cart_items as $item) {
            $grand_total += ($item['price'] * $item['quantity']);
        }
        
        // B. Create order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, name, number, email, address, total_amount, status, payment_method, payment_status, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $name, $number, $email, $full_address, $grand_total, $method]);
        $order_id = $conn->lastInsertId();

        // C. Insert items
        $stmt_item = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, subtotal) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $subtotal]);
        }

        // D. Clear cart
        $stmt_del = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt_del->execute([$cart_id]);
        
        // E. Log user action
        log_user_action($conn, $user_id, 'place_order', "Placed Order #$order_id via $method");

        $conn->commit();
        
        // F. Redirect logic
        if ($method == 'stripe') {
            header("Location: stripe_checkout.php?order_id=" . $order_id);
            exit;
        } else {
            // Cash success logic
            temp('success', 'Order placed successfully! Please pay cash upon delivery.');
            redirect(BASE_URL . 'order/orders.php');
        }

    } catch (Exception $e) {
        $conn->rollBack();
        temp('error', 'Error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/checkout.css">
</head>
<body>
    <?php include '../header.php'; ?>

    <section class="checkout">

        <div class="checkout-steps">
            <div class="step active">
                <div class="circle">1</div>
                <span>Cart</span>
            </div>
            <div class="step active">
                <div class="circle">2</div>
                <span>Checkout</span>
            </div>
            <div class="step">
                <div class="circle">3</div>
                <span>Finish</span>
            </div>
        </div>

        <h1 class="heading">Checkout Details</h1>

        <?php 
        // Automatically select a non-empty message
        $msg = temp('error') ?? temp('success') ?? temp('info');
        ?>
        <div id="info"><?= $msg ?></div>
        <div class="row">
            
            <form action="" method="POST">
                <h3>Billing Details</h3>
                
                <div class="flex">
                    <div class="box">
                        <p>Your Name <span>*</span></p>
                        <input type="text" name="name" required class="input" value="<?= htmlspecialchars($default_name) ?>">
                    </div>
                    <div class="box">
                        <p>Phone Number <span>*</span></p>
                        <input type="text" name="number" required class="input" value="<?= htmlspecialchars($default_phone) ?>">
                    </div>
                </div>

                <div class="flex">
                    <div class="box">
                        <p>Email Address <span>*</span></p>
                        <input type="email" name="email" required class="input" value="<?= htmlspecialchars($default_email) ?>">
                    </div>
                    <div class="box">
                        <p>Payment Method <span>*</span></p>
                        <select name="method" class="input" required>
                            <option value="cod">Cash on Delivery</option>
                            <option value="stripe">Online Payment (Card / GrabPay)</option>
                        </select>
                    </div>
                </div>

                <h3>Delivery Address</h3>
                
                <div class="flex">
                    <div class="box">
                        <p>Flat / House No. <span>*</span></p>
                        <input type="text" name="flat" class="input" required>
                    </div>
                    <div class="box">
                        <p>Street Name <span>*</span></p>
                        <input type="text" name="street" class="input" required>
                    </div>
                </div>

                <div class="flex">
                    <div class="box">
                        <p>City <span>*</span></p>
                        <input type="text" name="city" class="input" required>
                    </div>
                    <div class="box">
                        <p>State <span>*</span></p>
                        <input type="text" name="state" class="input" required>
                    </div>
                </div>

                <div class="flex">
                    <div class="box">
                        <p>Country <span>*</span></p>
                        <input type="text" name="country" class="input" required value="Malaysia">
                    </div>
                    <div class="box">
                        <p>Pin Code <span>*</span></p>
                        <input type="number" name="pin_code" class="input" required>
                    </div>
                </div>

                <button type="submit" name="place_order" class="btn">Place Order</button>
            </form>

            <div class="summary">
                <h3 class="title">Cart Items</h3>
                
                <?php 
                $grand_total = 0;
                if (count($cart_items) > 0) {
                    foreach ($cart_items as $item) {
                        $sub_total = $item['price'] * $item['quantity'];
                        $grand_total += $sub_total;
                ?>
                    <div class="flex">
                        <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($item['product_image']); ?>" class="image" alt="">
                        <div>
                            <h3 class="name"><?= htmlspecialchars($item['name']); ?></h3>
                            <p class="price">RM <?= number_format($item['price'], 2); ?> x <?= $item['quantity']; ?></p>
                        </div>
                    </div>
                <?php 
                    }
                } else {
                    echo '<p class="empty">Your cart is empty</p>';
                }
                ?>

                <div class="grand-total">
                    <span>Grand Total :</span>
                    <span>RM <?= number_format($grand_total, 2); ?></span>
                </div>
            </div>

        </div> 
    </section>

    <?php include '../footer.php'; ?>

</body>
</html>