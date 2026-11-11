<?php
include_once 'C:\xampp\htdocs\PHPASS\app\_base.php';
// Allow only admin
auth('admin');

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>css/sidebar.css">
  <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
  <!-- Flash message -->
  <div id="info"><?= temp('info') ?></div>

  <div class="sidebar">
    <div class="logo-details">
      <i class="fa-solid fa-graduation-cap icon"></i>
      <div class="logo_name">
        <?php echo ucfirst($_SESSION['user_role']); ?>
      </div>
      <i class='bx bx-menu' id="btn"></i>
    </div>

    <li>
      <a href="<?= BASE_URL ?>user/employees_home.php">
        <i class='bx bx-grid-alt'></i>
        <span class="links_name">Dashboard</span>
      </a>
      <span class="tooltip">Dashboard</span>
    </li>

    <li>
      <a href="<?= BASE_URL ?>product/prod/productList.php">
        <i class='bx bx-box'></i>
        <span class="links_name">Products</span>
      </a>
      <span class="tooltip">Manage Products</span>
    </li>

    <li>
      <a href="<?= BASE_URL ?>user/admin_crud_banner.php">
        <i class='bx bx-image-alt'></i>
        <span class="links_name">Banners</span>
      </a>
      <span class="tooltip">Banners</span>
    </li>

    <li>
      <a href="<?= BASE_URL ?>user/admin_customer_list.php">
        <i class='bx bx-user-check'></i>
        <span class="links_name">Customer Action</span>
      </a>
      <span class="tooltip">Customer Action List</span>
    </li>

    <li>
      <a href="<?= BASE_URL ?>user/admin_crud_customer.php">
        <i class='bx bx-user'></i>
        <span class="links_name">Manage Staff</span>
      </a>
      <span class="tooltip">User Management</span>
    </li>

    <li>
      <a href="<?= BASE_URL ?>user/edit_profile.php">
        <i class='bx bx-user-circle'></i>
        <span class="links_name">Profile</span>
      </a>
      <span class="tooltip">Edit Profile</span>
    </li>

    <li>
      <a href="<?= BASE_URL ?>home.php">
        <i class='bx bx-door-open'></i>
        <span class="links_name">Customer Portal</span>
      </a>
      <span class="tooltip">Customer Portal</span>
    </li>

    <li class="profile">
      <div class="profile-details">
        <img src="<?= BASE_URL ?>images/<?php echo htmlspecialchars($_SESSION['profile_image'] ?? 'default.png'); ?>"
          alt="Profile Picture">
        <div class="name_job">
          <div class="name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
          <div class="job"><?php echo ucfirst($_SESSION['user_role']); ?></div>
        </div>
        <a href="<?= BASE_URL ?>logout.php" title="Logout"><i class='bx bx-log-out' id="log_out"></i></a>
      </div>
    </li>
  </div>

  <script>
    let sidebar = document.querySelector(".sidebar");
    let offBtn = document.querySelector("#btn");

    offBtn.addEventListener("click", () => {
      sidebar.classList.toggle("open");
      menuBtnChange();
    });

    function menuBtnChange() {
      if (sidebar.classList.contains("open")) {
        offBtn.classList.replace("bx-menu", "bx-menu-alt-right");
      } else {
        offBtn.classList.replace("bx-menu-alt-right", "bx-menu");
      }
    }
  </script>
</body>

</html>