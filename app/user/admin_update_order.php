<?php
include 'employees_sidebar.php';


// 1. Handle Order Status Update
if (is_post() && isset($_POST['update_status'])) {
   $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
   $new_status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
   $admin_id = $_SESSION['user_id'];

   // ---  Step 1: Check Payment Method ---
   $check_method = $conn->prepare("SELECT payment_method FROM orders WHERE order_id = ?");
   $check_method->execute([$order_id]);
   $current_order = $check_method->fetch(PDO::FETCH_ASSOC);
   $method = $current_order['payment_method'] ?? 'cod'; 

   // ---  Step 2: Determine Payment Status ---
   $new_payment_status = 'pending'; // Default value

   if ($new_status == 'cancelled') {
       // 1. If Cancelled: Set payment status to Cancelled
       $new_payment_status = 'cancelled';
   
   } else if ($new_status == 'completed') {
       // 2. If Completed: Set payment status to Paid
       $new_payment_status = 'paid';
   
   } else if ($new_status == 'pending') {
       // 3.  Special Logic when reverting to Pending 
       if ($method == 'stripe') {
           // Stripe (Online): Money already paid, keep as Paid
           $new_payment_status = 'paid';
       } else {
           // Cash (COD): Not delivered yet, revert to Pending
           $new_payment_status = 'pending';
       }
   }

   // ---  Step 3: Execute Update ---
   $update_order = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE order_id = ?");
   $update_order->execute([$new_status, $new_payment_status, $order_id]);
   
   // Log action
   log_user_action($conn, $admin_id, "Update Order", "Updated order ID: " . $order_id . " to status: " . $new_status . " (Payment: " . $new_payment_status . ")");

   temp('info', 'Order status updated successfully!');
   redirect($_SERVER['REQUEST_URI']); 
}

// --- FETCH CUSTOMER ORDERS ---
$customer_id = filter_var($_GET['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

$fetch_orders = $conn->prepare("
    SELECT o.order_id, o.address, o.order_date, o.total_amount, o.status, o.payment_status, o.payment_method,
           u.username, u.gmail
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$fetch_orders->execute([$customer_id]);
$orders = $fetch_orders->fetchAll(PDO::FETCH_ASSOC);

$get_customer = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$get_customer->execute([$customer_id]);
$customer = $get_customer->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Update Orders</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/crud_product.css">
</head>

<body>
   <section class="side-section">
      <div class="section">
         
         <?php if ($msg = temp('info')): ?>
            <div class="message-box info">
                <i class="fa-solid fa-circle-info"></i> <?= $msg ?>
            </div>
         <?php endif; ?>

         <div class="product-header">
            <h3><?= htmlspecialchars($customer['username'] ?? 'Unknown'); ?> Orders</h3>
            <a href="admin_crud_customer.php" class="popup-trigger" style="background:#333;">Go Back</a>
         </div>

         <?php if (count($orders) === 0): ?>
            <p class="empty">No orders found for this customer.</p>
         <?php else: ?>
            <table>
               <thead>
                  <tr>
                     <th>Order ID</th>
                     <th>Address</th>
                     <th>Order Date</th>
                     <th>Total Amount</th>
                     <th>Method</th>
                     <th>Payment</th> 
                     <th>Status</th>
                     <th>Update</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($orders as $order): ?>
                     <tr>
                        <td><?= $order['order_id']; ?></td>
                        <td><?= htmlspecialchars($order['address']); ?></td>
                        <td><?= date('Y-m-d', strtotime($order['order_date'])); ?></td>
                        <td>RM<?= number_format($order['total_amount'], 2); ?></td>
                        
                        <td style="font-weight:bold; color:#555;">
                            <?php 
                                if($order['payment_method'] == 'stripe') {
                                    echo '<i class="fa-brands fa-stripe"></i> Online';
                                } else {
                                    echo '<i class="fa-solid fa-money-bill-wave"></i> Cash';
                                }
                            ?>
                        </td>

                        <td style="font-weight:bold; color: 
                            <?php 
                                if ($order['payment_status'] == 'paid') {
                                    echo 'green'; 
                                } else if ($order['payment_status'] == 'cancelled') {
                                    echo '#e74c3c';
                                } else {
                                    echo 'orange'; 
                                }
                            ?>;">
                            <?= ucfirst($order['payment_status']); ?>
                        </td>

                        <td><?= ucfirst($order['status']); ?></td>
                        
                        <td>
                           <form action="" method="POST">
                              <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                              <select name="status">
                                 <?php
                                 $statuses = ['pending', 'completed', 'cancelled'];
                                 foreach ($statuses as $s):
                                    ?>
                                    <option value="<?= $s; ?>" <?= $order['status'] === $s ? 'selected' : ''; ?>>
                                       <?= ucfirst($s); ?>
                                    </option>
                                 <?php endforeach; ?>
                              </select>
                              <input type="submit" class="btn edit" name="update_status" value="Update">
                           </form>
                        </td>
                     </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         <?php endif; ?>
      </div>
   </section>
</body>

</html>