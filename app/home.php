<?php
include 'header.php';

// Fetch all active products
$products = [];
$stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active'");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['add_to_cart'])) {
   // If not logged in
   if (!isset($_SESSION['user_id'])) {
      header("Location: login_form.php?message=login_required");
      exit();
   }

   $user_id = $_SESSION['user_id'];
   $product_id = $_POST['product_id'];
   $qty = $_POST['qty'];

   // Get product price
   $get_product = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
   $get_product->execute([$product_id]);
   $product = $get_product->fetch(PDO::FETCH_ASSOC);

   // Check if cart exists for this user
   $check_cart = $conn->prepare("SELECT * FROM carts WHERE user_id = ?");
   $check_cart->execute([$user_id]);

   if ($check_cart->rowCount() == 0) {
      $create_cart = $conn->prepare("INSERT INTO carts (user_id, created_at) VALUES (?, NOW())");
      $create_cart->execute([$user_id]);
      $cart_id = $conn->lastInsertId();
   } else {
      $cart = $check_cart->fetch(PDO::FETCH_ASSOC);
      $cart_id = $cart['cart_id'];
   }

   // Check if item already in cart
   $check_item = $conn->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
   $check_item->execute([$cart_id, $product_id]);

   if ($check_item->rowCount() > 0) {
      $warning_msg[] = 'Product already in cart!';
   } else {
      $insert_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
      $insert_item->execute([$cart_id, $product_id, $qty, $product['price']]);
      $success_msg[] = 'Product added to cart!';
   }
}

try {
   // Fetch only active ("on") banners
   $stmt = $conn->prepare("SELECT * FROM banners WHERE status = 'on' ORDER BY created_at DESC");
   $stmt->execute();

   $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
   echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
   $banners = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Customer Home</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
   <link rel="stylesheet" href="<?= BASE_URL ?>css/home.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/contact_us.css">
</head>

<body>
   <?php if (!empty($banners)): ?>
      <section>
         <div class="wrapper-swiper swiper">
            <div class="wrapper-inner swiper-wrapper">
               <?php foreach ($banners as $banner): ?>
                  <div class="slide swiper-slide">
                     <img src="<?= BASE_URL ?>images/banners/<?= htmlspecialchars($banner['image_path']); ?>"
                        alt="<?= htmlspecialchars($banner['title']); ?>" class="image">
                     <div class="bg-info">
                         <p></p>
                        <h1><?= htmlspecialchars($banner['title'] ?? 'NaBeGrad'); ?></h1>
                        <a href="<?= BASE_URL ?>product/view_products.php"><button>Shop Now</button></a>
                     </div>
                  </div>
               <?php endforeach; ?>
            </div>

            <!-- Swiper Controls -->
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
         </div>
      </section>
   <?php endif; ?>

   <section id="home-contact-section">
      <?php include 'contact_us.php'; ?>

   </section>
   
   <?php include 'footer.php'; ?>

   <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
   <script>
      const swiper = new Swiper('.swiper', {
         loop: true,

         // Auto swipe
         autoplay: {
            delay: 3000, // time between slides in ms (3000ms = 3 seconds)
            disableOnInteraction: false, // keeps autoplay running even after user interaction
         },

         // If we need pagination
         pagination: {
            el: '.swiper-pagination',
            clickable: true
         },

         // Navigation arrows
         navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
         },
      });
   </script>
</body>

</html>