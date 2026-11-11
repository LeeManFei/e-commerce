<?php
include '../../user/employees_sidebar.php';

auth("admin");

$admin_id = $_SESSION['user_id'];
$product_id = req('pid') ?? null;

if (!$product_id) {
    die("Product ID not found!");
}

// Fetch existing product
$pdo = $conn->prepare("SELECT * FROM `products` WHERE product_id = ?");
$pdo->execute([$product_id]);
$product_data = $pdo->fetch(PDO::FETCH_ASSOC);

// Load all images for this product
$stmt = $conn->prepare("SELECT file_name FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- UPDATE PRODUCT ---
if (isset($_POST['update'])) {

    // Basic fields
    $name = $_POST['name'];
    $categoryValue = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $status = $_POST['status'] ?? 'active';

    $_err = [];

    // Validate fields
    if (empty($name) || empty($price) || empty($stock)) {
        $_err['fields'] = "All fields except description are required.";
    }

    if (empty($categoryValue)) {
        $categoryValue = $product_data['category'];
    }

    // IMAGE LOGIC START ------------------------------

    $image_folder = __DIR__ . "../../images/products/";  // product/prod/images/

    if (!file_exists($image_folder)) {
        mkdir($image_folder, 0777, true);
    }

    // Keep old main image unless replaced
    $main_image = $product_data['product_image'];

    $saved_images = []; // store ALL uploaded images

    $files_uploaded = !empty($_FILES['product_image']['name'][0]);

    if ($files_uploaded) {


        // DELETE ALL OLD IMAGES (MAIN + EXTRA)
        $image_folder = "../../images/products/";

        // 1. DELETE EXTRA IMAGES FROM product_images TABLE
        $old_images = $conn->prepare("SELECT file_name FROM product_images WHERE product_id = ?");
        $old_images->execute([$product_id]);
        $old_images = $old_images->fetchAll(PDO::FETCH_ASSOC);

        foreach ($old_images as $img) {
            $file_path = $image_folder . $img['file_name'];

            if (file_exists($file_path)) {
                unlink($file_path);   // <--- DELETE LOCAL FILE
            }
        }

        // Delete DB rows
        $conn->prepare("DELETE FROM product_images WHERE product_id = ?")
            ->execute([$product_id]);


        // 2. DELETE OLD MAIN IMAGE FROM products TABLE
        if (!empty($product_data['product_image'])) {
            $main_path = $image_folder . $product_data['product_image'];

            if (file_exists($main_path)) {
                unlink($main_path);   // <--- DELETE MAIN IMAGE FILE
            }
        }

        foreach ($_FILES['product_image']['name'] as $i => $img_name) {

            if ($img_name == "") continue;

            $tmp = $_FILES['product_image']['tmp_name'][$i];
            $ext = pathinfo($img_name, PATHINFO_EXTENSION);
            $new_name = uniqid('prod_', true) . "." . $ext;

            $target = $image_folder . $new_name;

            if (move_uploaded_file($tmp, $target)) {

                // FIRST uploaded image = NEW MAIN IMAGE
                if ($i == 0) {

                    // delete old main image
                    if (
                        !empty($product_data['product_image']) &&
                        file_exists($image_folder . $product_data['product_image'])
                    ) {

                        unlink($image_folder . $product_data['product_image']);
                    }

                    $main_image = $new_name;
                }

                $saved_images[] = $new_name;
            }
        }
    }


    // Save new images (if any)
    foreach ($saved_images as $img) {
        $insert = $conn->prepare("
        INSERT INTO product_images (product_id, file_name)
        VALUES (?, ?)
    ");
        $insert->execute([$product_id, $img]);
    }


    // IMAGE LOGIC END --------------------------------


    // UPDATE PRODUCT INFO -----------------------------
    if (empty($_err)) {

        try {

            $update_query = $conn->prepare("
                UPDATE `products` SET 
                    name = ?, 
                    category = ?, 
                    description = ?, 
                    price = ?, 
                    stock = ?, 
                    status = ?, 
                    product_image = ?
                WHERE product_id = ?
            ");

            $update_query->execute([
                $name,
                $categoryValue,
                $description,
                $price,
                $stock,
                $status,
                $main_image,   // <-- updated or existing main image
                $product_id
            ]);

            // LOG ADMIN ACTION
            $log = $conn->prepare("
                INSERT INTO user_actions (user_id, action_type, description)
                VALUES (?, 'update', ?)
            ");
            $log->execute([$admin_id, 'Updated product ID: ' . $product_id]);

            temp('info', "Product '$product_id' updated successfully");
            redirect('productList.php');
            exit();
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
    <title>Update Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/edit_profile.css">
</head>

<body>
    <section class="side-section">
        <div class="product-container">
            <form id="editProfileForm" action="" method="POST" enctype="multipart/form-data" class="admin_form">
                <div class="profile-header">
                    <h3>Update Product</h3>
                    <div>
                        <a href="productList.php" style="text-decoration:none;">
                            <button type="button" class="save-trigger" style="background-color:#333;color:#fff;">
                                Back
                            </button>
                        </a>
                        <button type="submit" name="update" class="save-trigger">Update</button>
                    </div>
                </div>

                <!-- Error Display -->
                <?php err('fields'); ?>
                <?php err('db'); ?>

                <div class="form-group">
                    <label>PRODUCT NAME</label>
                    <div class="input-group">
                        <?php html_text('name', "placeholder='Enter product name' required maxlength='100'", $product_data['name']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>CATEGORY</label>
                    <div class="input-group">
                        <?php
                        html_select('category', $gcategory, $product_data['category']);
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>DESCRIPTION</label>
                    <div class="input-group">
                        <?php html_text('description', "placeholder='Enter product description' required maxlength='255'", $product_data['description']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>PRICE</label>
                    <div class="input-group">
                        <?php html_text('price', "type='number' step='0.01' placeholder='Enter price' required", $product_data['price']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>STOCK QUANTITY</label>
                    <div class="input-group">
                        <?php html_text('stock', "type='number' min='0' placeholder='Enter stock quantity' required", $product_data['stock']); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>CURRENT PROFILE IMAGE</label>
                    <div class="image-container">
                        <?php foreach ($images as $i => $img): ?>
                            <img src="../../images/products/<?= $img['file_name'] ?>">
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>PROFILE IMAGE</label>
                    <div class="input-group">
                        <?php html_file('product_image[]', 'accept="image/*" ', 'multiple') ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>STATUS</label>
                    <div class="input-group">
                        <?php
                        $statuses = [
                            'active' => 'Active',
                            'inactive' => 'Inactive'
                        ];
                        // Pre-fill with current status
                        $GLOBALS['status'] = $product_data['status'];
                        html_select('status', $statuses, null, "required");
                        ?>
                    </div>
                </div>
            </form>
        </div>
    </section>
</body>

</html>