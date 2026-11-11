<?php
include '../../user/employees_sidebar.php';
require_once '../../lib/SimplePager.php';

$page = req('page', 1);
$name = req('name');
$category = req('category');
$min_price = req('min_price');
$max_price = req('max_price');

$sort = req('sort', 'product_id');
$dir = req('dir', 'asc');

$sql_category = ($category == '') ? 'All' : $category;
$sql_min = ($min_price == '') ? 0 : $min_price;
$sql_max = ($max_price == '') ? 99999999 : $max_price;


$allowed_sort_fields = [
   'product_id' => 'Product ID',
   'name' => 'Name',
   'category' => 'Category',
   'price' => 'Price',
   'stock' => 'Stock',
   'status' => 'Status',
   'created_at' => 'Created At'
];


if (!array_key_exists($sort, $allowed_sort_fields)) {
   $sort = 'product_id'; 
}
if (!in_array($dir, ['asc', 'desc'])) {
   $dir = 'asc'; 
}


$query = "SELECT * FROM products 
          WHERE name LIKE ? 
          AND (category = ? OR ? = 'All') 
          AND price >= ? 
          AND price <= ?
          ORDER BY $sort $dir";


$params = [
   "%$name%",
   $sql_category,
   $sql_category,
   $sql_min,
   $sql_max
];


$p = new SimplePager($query, $params, '5', $page);
$product_allData = $p->result;

$categories_stmt = $conn->prepare("SELECT DISTINCT category FROM products");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

auth("admin");

if (isset($_POST['delete'])) {
   $id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
   $admin_id = $_SESSION['user_id'];

   try {
      $check_product = $conn->prepare("SELECT * FROM `products` WHERE product_id = ?");
      $check_product->execute([$id]);

      if ($check_product->rowCount() > 0) {
         $product = $check_product->fetch(PDO::FETCH_ASSOC);

         $stmt_images = $conn->prepare("SELECT file_name FROM product_images WHERE product_id = ?");
         $stmt_images->execute([$id]);
         $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

         foreach ($images as $img) {
            $img_path = '../prod/images/' . $img['file_name'];
            if (file_exists($img_path)) {
               unlink($img_path);
            }
         }

         $conn->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);

         log_user_action($conn, $admin_id, 'delete', 'Deleted product ID: ' . $id); // 修复：原 $product_id 未定义，改为 $id

         $delete_product = $conn->prepare("DELETE FROM `products` WHERE product_id = ?");
         $delete_product->execute([$id]);

         temp('info', "Product '$id' and its images deleted successfully");
      } else {
         temp('info', "Product '$id' not found or already deleted");
      }
   } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
         temp('info', "Cannot delete product '$id' because it is being used.");
      } else {
         throw $e;
      }
   }

   redirect('productList.php');
   exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Manage Products</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
   <script src="../../js/app.js"></script>
   <link rel="stylesheet" href="<?= BASE_URL ?>css/crud_product.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/pager.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/search.css">
</head>

<body>
   <section class="side-section">
      <div class="section">
         <form class="search-bar" method="GET" action="">
            <?= html_search('name', '', "placeholder='Search by product name'") ?>
            <?= html_select('category', array_combine($categories, $categories), 'All', "class='category-select'", $category ?? '') ?>
            <?= html_number('min_price', $min_price ?? '', 0, '', 1, "placeholder='Min Price' id='min-price'") ?>
            <?= html_number('max_price', $max_price ?? '', 0, '', 1, "placeholder='Max Price' id='max-price'") ?>
            <button type="submit">Search</button>
            <a href="add_prod.php" class="popup-trigger">+ Add Product</a>
         </form>

         <!-- PRODUCT TABLE -->
         <p class="pager-info">
            <?= $p->count ?> of <?= $p->item_count ?> product(s) |
            Page <?= $p->page ?> of <?= $p->page_count ?>
         </p>
         <table class="table">
            <thead>
               <tr>
                  <th>No.</th>

                  <?php
                  $sort_fields_step1 = ['product_id' => 'Product ID'];
                  $href_params = "name=$name&category=$category&min_price=$min_price&max_price=$max_price&page=$page";
                  table_headers($sort_fields_step1, $sort, $dir, $href_params);
                  ?>

                  <th>Product Image</th>

                  <?php
                  $sort_fields_step2 = [
                     'name' => 'Name',
                     'category' => 'Category'
                  ];
                  table_headers($sort_fields_step2, $sort, $dir, $href_params);
                  ?>

                  <th>Description</th>

                  <?php
                  $sort_fields_step3 = [
                     'price' => 'Price',
                     'stock' => 'Stock',
                     'status' => 'Status',
                     'created_at' => 'Created At'
                  ];
                  table_headers($sort_fields_step3, $sort, $dir, $href_params);
                  ?>

                  <th>Update</th>
                  <th>Delete</th>
               </tr>
            </thead>
            <tbody>
               <?php
               $index = 1 + ($p->page - 1) * $p->limit;
               foreach ($product_allData as $product): ?>
                  <tr>
                     <td><?= $index++ ?>.</td>
                     <td><?= $product['product_id']; ?></td>
                     <td>
                        <div class="image-container">
                           <?php if (!empty($product['product_image'])): ?>
                              <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($product['product_image']); ?>"
                                 alt="Product Image">
                           <?php else: ?>
                              <img src="<?= BASE_URL ?>images/default.png" alt="No Image">
                           <?php endif; ?>
                        </div>
                     </td>
                     <td><?= htmlspecialchars($product['name']); ?></td>
                     <td><?= htmlspecialchars($product['category']); ?></td>
                     <td><?= htmlspecialchars($product['description']); ?></td>
                     <td>$<?= htmlspecialchars(number_format($product['price'], 2)); ?></td>
                     <td><?= $product['stock']; ?></td>
                     <td><?= ucfirst($product['status']); ?></td>
                     <td><?= $product['created_at']; ?></td>
                     <td><button data-get="prod_update.php?pid=<?= $product['product_id']; ?>"
                           class="btn edit">Modify</button></td>
                     <td>
                        <form action="" method="POST">
                           <input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">
                           <input type="submit" class="btn delete" name="delete" value="Delete"
                              onclick="return confirm('Are you sure you want to delete this product?');">
                        </form>
                     </td>
                  </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
         <?= $p->html("name=$name&category=$category&min_price=$min_price&max_price=$max_price&sort=$sort&dir=$dir") ?>
      </div>
   </section>

   <?php if (!empty($_err)): ?>
      <script>
         document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('popup-overlay').style.display = 'flex';
         });
      </script>
   <?php endif; ?>
</body>

</html>