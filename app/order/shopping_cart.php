<?php
include_once '../_base.php';

if (isset($_POST['update_cart'])) {
   $cart_item_id = $_POST['cart_item_id'];
   $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

   $update_qty = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND cart_id = ?");
   $update_qty->execute([$qty, $cart_item_id, $cart_id]);

   temp('info', 'Cart quantity updated!');
}

if (isset($_POST['delete_item'])) {
   $cart_id = $_POST['cart_id'];
   $cart_item_id = $_POST['cart_item_id'];

   // Sanitize inputs
   $cart_id = filter_var($cart_id, FILTER_SANITIZE_NUMBER_INT);
   $cart_item_id = filter_var($cart_item_id, FILTER_SANITIZE_NUMBER_INT);

   // Verify if the item exists in cart_items
   $verify_item = $conn->prepare("SELECT * FROM cart_items WHERE cart_item_id = ? AND cart_id = ?");
   $verify_item->execute([$cart_item_id, $cart_id]);

   if ($verify_item->rowCount() > 0) {
      // Delete from cart_items table
      $delete_item = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id = ?");
      $delete_item->execute([$cart_item_id, $cart_id]);
      temp('info', 'Cart item deleted!');
   } else {
      temp('info', 'Cart item already deleted or not found!');
   }
}

if (isset($_POST['empty_cart'])) {
   if (isset($_SESSION['user_id'])) {
      $user_id = $_SESSION['user_id'];

      // Check if the user has a cart
      $verify_cart = $conn->prepare("SELECT * FROM carts WHERE user_id = ?");
      $verify_cart->execute([$user_id]);

      if ($verify_cart->rowCount() > 0) {
         // Get the user's cart_id
         $cart = $verify_cart->fetch(PDO::FETCH_ASSOC);
         $cart_id = $cart['cart_id'];

         // Check if cart_items exist for that cart
         $verify_items = $conn->prepare("SELECT * FROM cart_items WHERE cart_id = ?");
         $verify_items->execute([$cart_id]);

         if ($verify_items->rowCount() > 0) {
            // Delete all items from cart_items
            $delete_all = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $delete_all->execute([$cart_id]);
            temp('info', 'Cart emptied!');
         } else {
            temp('info', 'Cart already emptied!');
         }
      } else {
         temp('info', 'No cart found for this user!');
      }
   } else {
      temp('info', 'You must be logged in to empty your cart!');
   }
}

if (isset($_POST['qty'], $_POST['cart_item_id']) && is_numeric($_POST['qty'])) {
   $cart_item_id = $_POST['cart_item_id'];
   $qty = (int) $_POST['qty'];

   if ($qty >= 1 && $qty <= 99) {
      // Update quantity in cart_items table
      $update_qty = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
      $update_qty->execute([$qty, $cart_item_id]);
   }
}

// Count total items in cart (sum of quantities) for the logged-in user
if (isset($_SESSION['user_id'])) {
   $user_id = $_SESSION['user_id'];

   // Fetch the user's cart_id
   $cart_stmt = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
   $cart_stmt->execute([$user_id]);
   $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);

   if ($cart) {
      $cart_id = $cart['cart_id'];

      // Get total quantity from cart_items
      $total_items_stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE cart_id = ?");
      $total_items_stmt->execute([$cart_id]);
      $total_items = $total_items_stmt->fetch(PDO::FETCH_ASSOC)['total_items'] ?? 0;
   } else {
      $total_items = 0;
   }
} else {
   $total_items = 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shopping Cart</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/shoppingcart.css">

</head>
<?php include '../header.php'; ?>

<body>
   <section>
      <?php
      $grand_total = 0;
      $cart_items = [];   // initialize to avoid undefined variable errors
      $total_items = 0;

      // Ensure the user is logged in
      if (isset($_SESSION['user_id'])) {
         $user_id = $_SESSION['user_id'];

         // Get the user's cart (create or fetch)
         $select_cart = $conn->prepare("SELECT * FROM carts WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         $cart = $select_cart->fetch(PDO::FETCH_ASSOC);

         if ($cart) {
            $cart_id = $cart['cart_id'];

            // Fetch all items inside this cart together with product details
            $select_items = $conn->prepare("
               SELECT ci.cart_item_id, ci.cart_id, ci.product_id, ci.quantity,
               p.product_id AS product_prodID, p.name, p.price, p.product_image
               FROM cart_items ci
               JOIN products p ON ci.product_id = p.product_id
               WHERE ci.cart_id = ?
            ");
            $select_items->execute([$cart_id]);
            $cart_items = $select_items->fetchAll(PDO::FETCH_ASSOC);

            // Calculate grand total and total item count
            foreach ($cart_items as $item) {
               $grand_total += ($item['price'] * $item['quantity']);
               $total_items += (int) $item['quantity'];
            }
         }
      }
      ?>

      <div class="cart-container">
         <!-- Left side: Shopping Cart Items -->
         <div class="cart-items">
            <h2 class="cart-heading">Shopping Cart</h2>

            <?php if (count($cart_items) > 0): ?>
               <div class="cart-labels">
                  <span class="label-product">Product Details</span>
                  <span class="label-qty">Quantity</span>
                  <span class="label-price">Price</span>
                  <span class="label-subtotal">Subtotal</span>
               </div>

               <?php foreach ($cart_items as $fetch_cart): ?>
                  <?php
                  // Map fields to the names you used previously
                  $cart_item_id = $fetch_cart['cart_item_id'];
                  $cart_id = $fetch_cart['cart_id'];
                  $prod_id = $fetch_cart['product_id']; // product PK
                  $product_name = $fetch_cart['name'];
                  $product_image = $fetch_cart['product_image'];
                  $price = (float) $fetch_cart['price'];
                  $qty = (int) $fetch_cart['quantity'];
                  $sub_total = $price * $qty;
                  ?>
                  <form method="POST" class="cart-box">
                     <input type="hidden" name="cart_id" value="<?= htmlspecialchars($cart_id); ?>">
                     <input type="hidden" name="cart_item_id" value="<?= htmlspecialchars($cart_item_id); ?>">

                     <div class="product-image">
                        <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($product_image); ?>"
                           alt="<?= htmlspecialchars($product_name); ?>">
                     </div>


                     <div class="product-details">
                        <h4><?= htmlspecialchars($product_name); ?></h4>
                        <!-- Delete submits the form with name="delete_item" -->
                        <button type="submit" name="delete_item" class="delete-btn"
                           onclick="return confirm('Are you sure you want to remove this item?')">Remove</button>
                     </div>

                     <div class="product-quantity">
                        <!-- We keep buttons that change the input and auto-submit the form -->
                        <button type="button" onclick="updateQty(this, -1)">−</button>
                        <input name="qty" min="1" max="99" value="<?= $qty; ?>" class="qty" />
                        <button type="button" onclick="updateQty(this, 1)">+</button>
                        <!-- Also provide an explicit update button if needed -->
                        <button type="submit" name="update_cart" style="display:none;">Update</button>
                     </div>

                     <div class="product-price">RM<?= number_format($price, 2); ?></div>
                     <div class="product-total">RM<?= number_format($sub_total, 2); ?></div>
                  </form>
               <?php endforeach; ?>

               <form method="POST" action="" style="justify-self: flex-end;">
                  <button type="submit" name="empty_cart" class="empty-cart-btn"
                     onclick="return confirm('Empty your cart?')">Empty Cart</button>
               </form>
            <?php else: ?>
               <p class="empty">Your cart is empty!</p>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>product/view_products.php" class="continue-shopping">
               <i class="fa-solid fa-arrow-left"></i> Continue Shopping
            </a>
         </div>

         <!-- Right side: Order Summary -->
         <?php if (count($cart_items) > 0): ?>
            <div class="order-summary">
               <h2 class="cart-heading">Order Summary</h2>
               <div class="summary-subcontainer">
                  <div class="summary-line total">
                     <span>Total Items</span>
                     <span><?= $total_items; ?></span>
                  </div>
                  <div class="summary-line total">
                     <span>Total Cost</span>
                     <span>RM<?= number_format($grand_total, 2); ?></span>
                  </div>
               </div>
               <a href="checkout.php" class="checkout-btn">Checkout</a>
            </div>
         <?php endif; ?>
      </div>
   </section>

   <?php include '../footer.php'; ?>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
   <script src="js/script.js"></script>
   <script>
      function updateQty(button, change) {
         const qtyInput = button.parentElement.querySelector(".qty");
         let currentQty = parseInt(qtyInput.value);
         currentQty += change;

         if (currentQty >= 1 && currentQty <= 99) {
            qtyInput.value = currentQty;
            qtyInput.form.submit(); // auto-submit the form after update
         }
      }
   </script>

</body>

</html>