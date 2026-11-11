<?php
include_once '_base.php';

$total_cart_items = count_cart_items($conn, $user_id);
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/layout.css">
</head>

<header>
    <!-- Flash message -->
    <div id="info"><?= temp('info') ?></div>
    <nav>
        <input type="checkbox" id="sidebar-active">
        <a href="shopping_cart.php" class="cart-btn2">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>
                <?= $total_cart_items; ?>
            </span>
        </a>
        <label for="sidebar-active" class="open-sidebar">
            <i id="menu-btn" class="fa-solid fa-bars"></i>
        </label>


        <label id="overlay" for="sidebar-active"></label>
        <div class="links-container">
            <label for="sidebar-active" class="close-sidebar">
                <i class="fa-solid fa-xmark"></i>
            </label>

            <a class="home-link" href="<?= BASE_URL ?>home.php">
                <i class="fa-solid fa-graduation-cap" style="margin-right : 10px; font-size : 25px;"></i>
                <p class="logo"><strong>NaBeGrad</strong></p>
            </a>

            <div class="sub-container" style="display : flex;">
                <a href="<?= BASE_URL ?>home.php">
                    <p class="p1">Home</p>
                </a>
                <a href="<?= BASE_URL ?>product/view_products.php">
                    <p class="p1">Products</p>
                </a>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="sub-container" style="display : flex; align-items: center; justify-content: center;">
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                        <a href="<?= BASE_URL ?>user/employees_home.php" class="navbar-icon portal-btn">
                            <i class="fa-solid fa-door-open" style="margin-right : 10px;"></i>Admin Portal
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>order/shopping_cart.php" class="cart-btn">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>
                            <?= $total_cart_items; ?>
                        </span>
                    </a>
                    <?php if (isset($_SESSION['profile_image'])): ?>
                        <img src="<?= BASE_URL ?>images/<?php echo $_SESSION['profile_image']; ?>" alt="Profile Picture"
                            width="50" class="cust-picture" onclick="toggleMenu()">
                        <div class="sub-menu-wrap" id="subMenu">
                            <div class="sub-menu">
                                <div class="user-info">
                                    <img src="<?= BASE_URL ?>images/<?php echo $_SESSION['profile_image']; ?>"
                                        alt="Profile Picture" width="50" class="user-picture">
                                    <h3><?php echo $_SESSION['name']; ?></h3>
                                </div>
                                <hr>
                                <a href="<?= BASE_URL ?>order/wishlist.php" class="sub-menu-link">
                                    <i class="fa-solid fa-heart"></i>
                                    <p>Wishlist</p>
                                    <span>></span>
                                </a>
                                <a href="<?= BASE_URL ?>order/orders.php" class="sub-menu-link">
                                    <i class="fa-solid fa-receipt"></i>
                                    <p>My Orders</p>
                                    <span>></span>
                                </a>
                                <?php if ($_SESSION['user_role'] !== 'admin'): ?>
                                    <a href="<?= BASE_URL ?>user/edit_profile.php" class="sub-menu-link">
                                        <i class="fa-solid fa-gear"></i>
                                        <p>Edit Profile</p>
                                        <span>></span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>logout.php" class="sub-menu-link">
                                    <i class="fa-solid fa-right-to-bracket"></i>
                                    <p>Logout</p>
                                    <span>></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login_form.php" class="navbar-icon login-btn">
                        <i class="fa-solid fa-user" style="margin-right : 10px;"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
<script>
    let subMenu = document.getElementById("subMenu");
    function toggleMenu() {
        subMenu.classList.toggle("open-menu");
    }
</script>
<script src="js/script.js"></script>