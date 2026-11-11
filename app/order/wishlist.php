<?php
ob_start();

include '../header.php';

// Check login status
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            swal('Login Required', 'Please log in to view your wishlist.', 'warning')
            .then(() => { window.location.href='../user_login.php'; });
          </script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// --- HANDLE REMOVE ITEM ---
if (isset($_POST['remove_wishlist'])) {
    $product_id = $_POST['product_id'];

    $remove = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $remove->execute([$user_id, $product_id]);

    // 🔥 Set the message using temp()
    // Using 'info' here because your CSS targets #info
    temp('info', 'Item removed from wishlist successfully!');
    
    // Refresh the page to trigger the animation
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch wishlist items
$sql = "SELECT p.*
        FROM wishlist w
        JOIN products p ON w.product_id = p.product_id
        WHERE w.user_id = ? AND p.status='active'";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Wishlist</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
</head>

<body>

    <div id="info"><?php echo temp('info'); ?></div>

    <section class="products">
        
        <div class="heading">
            <h1><span>My Wishlist</span></h1>
        </div>

        <div class="box-container">
        <?php if ($stmt->rowCount() > 0): ?>
            <?php while ($product = $stmt->fetch(PDO::FETCH_ASSOC)): ?>

            <div class="box">
                <a href="<?= BASE_URL ?>product/productDESC.php?product_id=<?= $product['product_id'] ?>" style="text-decoration:none;">
                    <div class="image-container">
                        <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($product['product_image']); ?>" 
                             class="image" alt="">
                    </div>
                    <div class="product-info">
                        <h3 class="name"><?= htmlspecialchars($product['name']); ?></h3>
                    </div>
                    <div class="price-container">
                        <p class="price">RM <?= htmlspecialchars($product['price']); ?></p>
                    </div>
                </a>

                <form action="" method="POST">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <button type="submit" name="remove_wishlist" class="cart-btn" 
                            style="background-color:#c0392b; margin-top:10px;"
                            onclick="return confirm('Remove this item from wishlist?');">
                        Remove
                    </button>
                </form>
            </div>

            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">No items in your wishlist.</p>
        <?php endif; ?>
    </div>

    </section>

    <?php include '../footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html>

<?php 
ob_end_flush(); 
?>