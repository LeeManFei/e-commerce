<?php
ob_start();

include '../header.php';

// 1. Validate Order ID
if (isset($_GET['id'])) {
   $get_id = $_GET['id'];
} else {
   header('location:orders.php');
   exit;
}

// 2. Handle Cancel Order Logic
if (isset($_POST['cancel'])) {
   
   // 🔥 Step A: Check current payment status first
   // We need to know if they already paid, so we can give the correct message
   $check_stmt = $conn->prepare("SELECT payment_status FROM orders WHERE order_id = ?");
   $check_stmt->execute([$get_id]);
   $current_order = $check_stmt->fetch(PDO::FETCH_ASSOC);

   // Default message
   $msg = 'Order cancelled successfully!';
   $new_payment_status = 'cancelled';

   // If they already paid, we should mark it as 'refund_pending' (if your DB allows) or just 'cancelled'
   // and tell them a refund is coming.
   if ($current_order['payment_status'] == 'paid') {
       $msg = 'Order cancelled. Refund process will be initiated.';
       // Note: If your database enum doesn't have 'refund_pending', keep it as 'cancelled'
       $new_payment_status = 'cancelled'; 
   }

   // 🔥 Step B: Update Status
   $update_order = $conn->prepare("UPDATE `orders` SET status = ?, payment_status = ? WHERE order_id = ?");
   $update_order->execute(['cancelled', $new_payment_status, $get_id]);
   
   // Step C: Set message and redirect
   temp('info', $msg); 
   
   header('Location: orders.php');
   exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Orders</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/viewOrders.css">
</head>

<body>
   <section class="order-details">
      
      <?php if ($msg = temp('info')): ?>
         <div class="message-box info">
             <i class="fa-solid fa-circle-info"></i> <?= $msg ?>
         </div>
      <?php endif; ?>

      <h1 class="heading">Order Details</h1>

      <?php
      $grand_total = 0;

      // Fetch order details
      $select_order = $conn->prepare("
             SELECT o.*, u.username, u.gmail AS email, u.phone
             FROM orders o
             JOIN users u ON o.user_id = u.user_id
             WHERE o.order_id = ?
             LIMIT 1
          ");
      $select_order->execute([$get_id]);

      if ($select_order->rowCount() > 0) {
         $order = $select_order->fetch(PDO::FETCH_ASSOC);

         // Fetch order items
         $select_items = $conn->prepare("
            SELECT oi.*, p.name AS product_name, p.product_image, p.price
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
         ");
         $select_items->execute([$get_id]);

         $items = $select_items->fetchAll(PDO::FETCH_ASSOC);
         $grand_total = array_sum(array_column($items, 'subtotal'));
         ?>
         <div class="order-card">
            <div class="order-summary">
               <p><strong>Order ID</strong><br>#<?= htmlspecialchars($order['order_id']); ?></p>
               <p><strong>Date</strong><br><?= date('d F, Y', strtotime($order['order_date'])); ?></p>
               <p><strong>Total Amount</strong><br>$<?= number_format($grand_total, 2); ?></p>
               
               <p><strong>Payment Status</strong><br>
                  <span class="status <?= strtolower($order['payment_status']); ?>">
                     <?= ucfirst($order['payment_status']); ?>
                  </span>
               </p>

               <p><strong>Order Status</strong><br>
                  <span class="status <?= strtolower($order['status']); ?>">
                     <?= ucfirst($order['status']); ?>
                  </span>
               </p>
            </div>

            <div class="order-products">
               <?php foreach ($items as $item): ?>
                  <div class="product-row">
                     <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($item['product_image']); ?>" alt="">
                     <div class="product-info">
                        <h3><?= htmlspecialchars($item['product_name']); ?></h3>
                        <p>RM<?= number_format($item['price'], 2); ?> (x<?= $item['quantity']; ?>)</p>
                        <div class="links">
                           <a href="view_products.php?id=<?= $item['product_id']; ?>">View Product</a>
                        </div>
                     </div>
                     <p class="product-price">RM<?= number_format($item['subtotal'], 2); ?></p>
                  </div>
               <?php endforeach; ?>

               <div class="order-actions">
                  <a href="orders.php" class="btn">Back to Orders</a>
                  <a href="invoice.php?id=<?= $order['order_id']; ?>" class="btn outline">View Invoice</a>

                  <?php 
                  if ($order['status'] == 'pending' || $order['status'] == 'processing'): 
                  ?>
                     <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this order? <?php if($order['payment_status']=='paid') echo 'Refund will be processed.'; ?>');">
                        <button type="submit" name="cancel" class="btn" style="background-color: #e74c3c; color: white; border:none;">
                           Cancel Order
                        </button>
                     </form>
                  <?php endif; ?>
               </div>
            </div>
         </div>
         <?php
      } else {
         echo '<p class="empty">No orders found!</p>';
      }
      ?>
   </section>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

   <?php include '../footer.php'; ?>
   
   <?php 
   ob_end_flush(); 
   ?>
</body>

</html>