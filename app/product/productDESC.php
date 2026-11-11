<?php
ob_start();

include '../header.php';

// 1. HANDLE ADD TO CART 
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Not logged in
        temp('warning', 'Please log in to add items to your cart.');
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $user_id = $_SESSION['user_id'];
        $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_STRING);
        $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

        // Step 1: Check/Create Cart
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

        // Step 2: Check Cart Items
        $verify_item = $conn->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $verify_item->execute([$cart_id, $product_id]);

        if ($verify_item->rowCount() > 0) {
            $existing = $verify_item->fetch(PDO::FETCH_ASSOC);
            $new_qty = $existing['quantity'] + $qty;
            $update_item = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
            $update_item->execute([$new_qty, $cart_id, $product_id]);

            temp('success', 'Cart updated successfully!');
        } else {
            $insert_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert_item->execute([$cart_id, $product_id, $qty]);

            temp('success', 'Added to cart successfully!');
        }

        // Refresh Page
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 2. HANDLE WISHLIST (AJAX - No Page Reload)
$user_id = $_SESSION['user_id'] ?? null;
$product_id = req('product_id');

if (isset($_POST['wishlist_action']) && $user_id) {
    // Clear buffer to ensure clean JSON response
    ob_clean();

    $action = $_POST['wishlist_action'];
    $msg = "";

    if ($action === 'add') {
        $check = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);
        if ($check->rowCount() == 0) {
            $insert = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert->execute([$user_id, $product_id]);
            $msg = "Added to wishlist!";
        }
    } elseif ($action === 'remove') {
        $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete->execute([$user_id, $product_id]);
        $msg = "Removed from wishlist!";
    }

    //  Return JSON response for JS to handle
    echo json_encode(['status' => 'success', 'message' => $msg]);
    exit;
}

// 3. FETCH PRODUCT DATA
$product = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$product->execute([$product_id]);
$product = $product->fetch();

$stmt = $conn->prepare("SELECT file_name FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($images)) {
    $images = [['file_name' => 'no_image.png']];
}

$in_wishlist = false;
if ($user_id) {
    $check_wl = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_wl->execute([$user_id, $product_id]);
    $in_wishlist = $check_wl->rowCount() > 0;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Description</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/prodDESC.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>

<body>
    <div>
        <div class="gallery-wrapper">
            <div class="IMG">
                <div class="main-img-box">
                    <span class="arrow left-arrow" onclick="prevImg()">❮</span>
                    <img id="mainImage" src="<?= BASE_URL ?>images/products/<?= $images[0]['file_name'] ?>">
                    <span class="arrow right-arrow" onclick="nextImg()">❯</span>
                </div>

                <div class="thumbnail-list">
                    <?php foreach ($images as $i => $img): ?>
                        <img src="<?= BASE_URL ?>images/products/<?= $img['file_name'] ?>"
                            class="thumb <?= $i == 0 ? 'active' : '' ?>" onclick="showImg(<?= $i ?>)">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="product-info">
                <div style="display : flex; justify-content: space-between; margin-bottom: 10px;">
                    <h2><?= htmlspecialchars($product['name']); ?></h2>
                    <button type="button" class="wishlist-btn" id="wishlistBtn">
                        <i class="fa-heart <?= $in_wishlist ? 'fa-solid active' : 'fa-regular' ?>"></i>
                    </button>
                </div>
                <p><strong>Category:</strong> <?= htmlspecialchars($product['category']); ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($product['description']); ?></p>
                <p><strong>Price:</strong> RM <?= htmlspecialchars(number_format($product['price'], 2)); ?></p>
                <p><strong>Stock:</strong> <?= htmlspecialchars($product['stock']); ?></p>

                <form method="POST" class="cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">

                    <div>
                        <div class="qty-box">
                            <button type="button" class="qty-btn" onclick="decreaseQty()">-</button>
                            <input type="number" name="qty" id="qtyInput" value="1" min="1"
                                max="<?= $product['stock']; ?>">
                            <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
                        </div>
                    </div>

                    <div>
                        <button type="submit" name="add_to_cart" class="add-cart-btn">Add to Cart</button>
                    </div>
                </form>

                <div>
                    <a href="<?= BASE_URL ?>product/view_products.php" class="back-btn">Back to Products</a>
                </div>
            </div>
        </div>

        <script>
            let images = [
                <?php foreach ($images as $img): ?> "<?= BASE_URL ?>images/products/<?= $img['file_name'] ?>",
                <?php endforeach; ?>
            ];

            let current = 0;

            function showImg(i) {
                current = i;
                document.getElementById("mainImage").src = images[i];
                updateActive();
            }

            function prevImg() {
                current = (current > 0) ? current - 1 : images.length - 1;
                document.getElementById("mainImage").src = images[current];
                updateActive();
            }

            function nextImg() {
                current = (current < images.length - 1) ? current + 1 : 0;
                document.getElementById("mainImage").src = images[current];
                updateActive();
            }

            function updateActive() {
                let thumbs = document.querySelectorAll(".thumb");
                thumbs.forEach(t => t.classList.remove("active"));
                thumbs[current].classList.add("active");
            }

            function increaseQty() {
                let input = document.getElementById("qtyInput");
                let max = parseInt(input.max);
                if (input.value < max) input.value = parseInt(input.value) + 1;
            }

            function decreaseQty() {
                let input = document.getElementById("qtyInput");
                if (input.value > 1) input.value = parseInt(input.value) - 1;
            }

            // --- Wishlist Toggle with SweetAlert ---
            document.getElementById('wishlistBtn').addEventListener('click', function () {
                let heart = this.querySelector('i');
                // Determine action based on current class
                let action = heart.classList.contains('fa-solid') ? 'remove' : 'add';

                if (!<?= $user_id ? 'true' : 'false' ?>) {
                    swal('Login Required', 'Please log in to use wishlist.', 'warning');
                    return;
                }

                // AJAX request
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        //  Parse JSON response from PHP
                        let response = JSON.parse(xhr.responseText);

                        // Toggle Icon Visuals
                        heart.classList.toggle('fa-solid');
                        heart.classList.toggle('fa-regular');
                        heart.classList.toggle('active');

                        //  Show SweetAlert Message (Mimics temp function)
                        swal({
                            title: action === 'add' ? "Wishlist" : "Removed",
                            text: response.message,
                            icon: "success",
                            buttons: false,
                            timer: 1500 // Auto close after 1.5 seconds for better UX
                        });
                    }
                };
                xhr.send("wishlist_action=" + action + "&product_id=<?= $product_id ?>");
            });
        </script>

        <?php include '../footer.php'; ?>

        <?php if ($msg = temp('success')): ?>
            <script>
                swal({ title: "Success", text: "<?= $msg ?>", icon: "success", button: "OK" });
            </script>
        <?php endif; ?>

        <?php if ($msg = temp('warning')): ?>
            <script>
                swal({ title: "Warning", text: "<?= $msg ?>", icon: "warning", button: "OK" });
            </script>
        <?php endif; ?>

</body>

</html>

<?php
ob_end_flush();
?>