<?php
include '../header.php';
auth("customer", "admin");

?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Orders</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/order.css">
</head>

<body>

   <section class="orders">

      <?php
      // Fetch any type of message 
      $msg = temp('error') ?? temp('success') ?? temp('info');
      ?>

      <div id="info"><?= $msg ?></div>
      <div class="heading">
         <h1><span>My Orders</span></h1>
      </div>

      <div class="box-container">

         <?php
         // Fetch orders by user
         $select_orders = $conn->prepare("
      SELECT * FROM orders 
      WHERE user_id = ? 
      ORDER BY order_date DESC
   ");
         $select_orders->execute([$user_id]);

         if ($select_orders->rowCount() > 0) {

            while ($fetch_order = $select_orders->fetch(PDO::FETCH_ASSOC)) {

               // Fetch first item of the order
               $first_item_stmt = $conn->prepare("
            SELECT p.name, p.product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.order_item_id ASC
            LIMIT 1
         ");
               $first_item_stmt->execute([$fetch_order['order_id']]);
               $first_item = $first_item_stmt->fetch(PDO::FETCH_ASSOC);

               // Count total items in the order
               $count_stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM order_items 
            WHERE order_id = ?
         ");
               $count_stmt->execute([$fetch_order['order_id']]);
               $item_count = $count_stmt->fetchColumn();
               ?>

               <div class="box">
                  <a href="view_order.php?id=<?= $fetch_order['order_id']; ?>">

                     <!-- Order Date -->
                     <p class="date">
                        <i class="fa fa-calendar"></i>
                        <span><?= htmlspecialchars($fetch_order['order_date']); ?></span>
                     </p>

                     <!-- Product Image -->
                     <div class="image-container">
                        <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($first_item['product_image']); ?>"
                           class="image" alt="">
                     </div>

                     <!-- Product Name -->
                     <h3 class="name">
                        <?= htmlspecialchars($first_item['name']); ?>
                        <?= ($item_count > 1) ? ' & more' : '' ?>
                     </h3>

                     <!-- Total Amount -->
                     <p class="total">
                        <strong>Total:</strong> RM <?= number_format($fetch_order['total_amount'], 2); ?>
                     </p>

                  </a>

                  <!-- Order Status -->
                  <p class="status" style="background-color:
            <?php
            if ($fetch_order['status'] === 'completed') {
               echo 'green';
            } elseif ($fetch_order['status'] === 'cancelled') {
               echo '#ff6b6b';
            } else {
               echo 'orange';
            }
            ?>">
                     <?= ucfirst($fetch_order['status']); ?>
                  </p>
               </div>

               <?php
            }
         } else {
            echo '<p class="empty">No orders found!</p>';
         }
         ?>

      </div>

   </section>

   <?php include '../footer.php'; ?>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
   <script src="js/script.js"></script>

</body>

</html>