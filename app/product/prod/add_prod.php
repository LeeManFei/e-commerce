<?php
include '../../user/employees_sidebar.php';

auth("admin");

// --- ADD PRODUCT ---
if (isset($_POST['add'])) {

    $admin_id = $_SESSION['user_id'];

    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $categoryValue = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $stock = filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT);
    $status = $_POST['status'] ?? 'active';

    // Validate required fields
    if (empty($name) || empty($price) || empty($stock)) {
        $_err['fields'] = "All fields except description are required.";
    }

    // Image upload
    $main_image = ''; // first image becomes main image
    $saved_images = []; // store all uploaded image names

    $image_folder = __DIR__ . "/../../images/products/";  // absolute path to images/products/
    if (!file_exists($image_folder)) {
        mkdir($image_folder, 0777, true);
    }

    if (!empty($_FILES['product_image']['name'][0])) {
        foreach ($_FILES['product_image']['name'] as $i => $img_name) {
            $tmp = $_FILES['product_image']['tmp_name'][$i];

            if ($img_name == "")
                continue;

            $ext = pathinfo($img_name, PATHINFO_EXTENSION);
            $new_name = uniqid('prod_', true) . '.' . $ext;
            $target = $image_folder . $new_name;

            if (move_uploaded_file($tmp, $target)) {
                $saved_images[] = $new_name;
                if ($i == 0) {
                    $main_image = $new_name; // first image = main
                }
            } else {
                $_err['upload'] = "Failed to upload image: $img_name";
            }
        }
    } else {
        $_err['fields'] = "At least one product image is required.";
    }

    if (empty($_err)) {
        try {
            // Insert product (with main image)
            $add_product = $conn->prepare("
                INSERT INTO `products` 
                (name, category, description, price, stock, status, product_image)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $add_product->execute([$name, $categoryValue, $description, $price, $stock, $status, $main_image]);

            $product_id = $conn->lastInsertId();

            // Insert ALL images into product_images table
            foreach ($saved_images as $img) {
                $insert = $conn->prepare("INSERT INTO product_images (product_id, file_name) VALUES (?, ?)");
                $insert->execute([$product_id, $img]);
            }

            // Log action
            log_user_action($conn, $admin_id, "add", "Added new product ID: $product_id");

            temp('info', "Product '$product_id' added successfully");
            redirect('productList.php');
        } catch (PDOException $e) {
            $_err['db'] = "Database error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/edit_profile.css">
</head>

<body>
    <section class="side-section">
        <div class="product-container">
            <form id="editProfileForm" action="" method="POST" enctype="multipart/form-data" class="admin_form">
                <div class="profile-header">
                    <h3>Add Product</h3>
                    <div>
                        <a href="productList.php" style="text-decoration:none;">
                            <button type="button" class="save-trigger" style="background-color:#333;color:#fff;">
                                Back
                            </button>
                        </a>
                        <button type="submit" name="add" class="save-trigger" value="Add Product">Add</button>
                    </div>
                </div>

                <!-- Error Display -->
                <?php err('fields'); ?>
                <?php err('db'); ?>

                <div class="form-group">
                    <label>PRODUCT NAME</label>
                    <div class="input-group">
                        <?php html_text('name', "placeholder='Enter product name' required maxlength='100'") ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>CATEGORY</label>
                    <div class="input-group">
                        <select name="category" id="category" required>
                            <?php foreach ($gcategory as $cat_value): ?>
                                <option value="<?= encode($cat_value); ?>" <?= $cat_value === 'Flowers' ? 'selected' : '' ?>>
                                    <?= encode($cat_value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>DESCRIPTION</label>
                    <div class="input-group">
                        <?php html_text('description', 'placeholder="Enter product description (optional)" maxlength="255"'); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>PRICE</label>
                    <div class="input-group">
                        <?php html_number('price', 'step="0.01" placeholder="Enter price"') ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>STOCK QUANTITY</label>
                    <div class="input-group">
                        <?php html_number('stock', 'min="0" placeholder="Enter stock quantity"') ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>PRODUCT IMAGE</label>
                    <div class="input-group">
                        <?php html_file('product_image[]', 'accept="image/*" ', 'multiple') ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>STATUS</label>
                    <div class="input-group">
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </section>

</body>

</html>