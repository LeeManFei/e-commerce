<?php
ob_start();

include '../header.php';

// --- FETCH ALL CATEGORIES FOR FILTER ---
$categories_stmt = $conn->prepare("SELECT DISTINCT category FROM products WHERE status='active'");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// --- HANDLE SEARCH, CATEGORY & PRICE FILTER ---
$search_query = $_GET['search'] ?? '';
$selected_category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// --- BUILD SQL DYNAMICALLY ---
$sql = "SELECT * FROM products WHERE status='active'";
$params = [];

if ($search_query !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = '%' . $search_query . '%';
}

if ($selected_category !== '' && $selected_category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $selected_category;
}

if ($min_price !== '' && is_numeric($min_price)) {
    $sql .= " AND price >= ?";
    $params[] = $min_price;
}

if ($max_price !== '' && is_numeric($max_price)) {
    $sql .= " AND price <= ?";
    $params[] = $max_price;
}

$select_products = $conn->prepare($sql);
$select_products->execute($params);

// --- HANDLE ADD TO CART ---
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Not logged in: Set warning message and refresh page
        temp('warning', 'Please log in to add items to your cart.');
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {

        $user_id = $_SESSION['user_id'];
        $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_STRING);
        $qty = filter_var($_POST['qty'], FILTER_SANITIZE_STRING);

        // Step 1: Check if user already has a cart
        $select_cart = $conn->prepare("SELECT * FROM carts WHERE user_id = ? LIMIT 1");
        $select_cart->execute([$user_id]);

        if ($select_cart->rowCount() > 0) {
            $fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC);
            $cart_id = $fetch_cart['cart_id'];
        } else {
            $create_cart = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $create_cart->execute([$user_id]);
            $cart_id = $conn->lastInsertId();
        }

        // Step 2: Check if item already in cart
        $verify_item = $conn->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $verify_item->execute([$cart_id, $product_id]);

        if ($verify_item->rowCount() > 0) {
            //  Case 1: Already in cart (Warning)
            temp('warning', 'Product is already in your cart!');
        } else {
            //  Case 2: Added successfully (Success)
            $insert_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert_item->execute([$cart_id, $product_id, $qty]);
            
            temp('success', 'Product added to cart successfully!');
        }
        
        //  Refresh page (With ob_start, this line won't error now)
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/product.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/search.css">
</head>

<body>

    <section class="products">
        <div class="heading">
            <h1><span>Products</span></h1>
        </div>

        <form class="search-bar" method="GET" action="">
            <?= html_search('search', $search_query ?? '', "placeholder='Search products...' id='search-input'") ?>
            <?= html_select( 'category', array_combine($categories, $categories), 'All', "id='category-select'", $selected_category ?? '') ?>
            <?= html_number('min_price', $min_price ?? '', 0, '', 1, "placeholder='Min Price' id='min-price'") ?>
            <?= html_number('max_price', $max_price ?? '', 0, '', 1, "placeholder='Max Price' id='max-price'") ?>
            <button type="submit">Search</button>
        </form>

        <div class="box-container">
            <?php

            if ($select_products->rowCount() > 0) {
                while ($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <form action="" method="POST" class="box">
                        <a href="productDESC.php?product_id=<?= htmlspecialchars($fetch_product['product_id']); ?>" class="image-container">
                            <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($fetch_product['product_image']); ?>" class="image" alt="">
                        </a>
                        
                        <div class="product-info">
                            <a href="productDESC.php?product_id=<?= htmlspecialchars($fetch_product['product_id']); ?>" style="text-decoration:none; color:inherit;">
                                <h3 class="name"><?= htmlspecialchars($fetch_product['name']); ?></h3>
                            </a>
                        </div>

                        <div class="price-container">
                            <p class="price">RM <?= htmlspecialchars($fetch_product['price']); ?></p>
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($fetch_product['product_id']); ?>">
                            <input type="hidden" name="qty" value="1">
                        </div>
                        <input type="submit" name="add_to_cart" value="ADD TO CART" class="cart-btn">
                    </form>
                    <?php
                }
            } else {
                echo '<p class="empty">No products found!</p>';
            }
            ?>
        </div>
    </section>

    <?php include '../footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="<?= BASE_URL ?>js/product-search.js"></script>

    <?php if ($msg = temp('success')): ?>
        <script>
            swal({
                title: "Success",
                text: "<?= $msg ?>",
                icon: "success",
                button: "OK",
            });
        </script>
    <?php endif; ?>

    <?php if ($msg = temp('warning')): ?>
        <script>
            swal({
                title: "Warning",
                text: "<?= $msg ?>",
                icon: "warning",
                button: "OK",
            });
        </script>
    <?php endif; ?>

</body>
</html>

<?php 
ob_end_flush(); 
?>