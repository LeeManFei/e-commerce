<?php
include 'employees_sidebar.php';
require_once '../lib/SimplePager.php';

auth('admin');

// --- FETCH USERS FOR FILTER ---
$fetch_users = $conn->prepare("SELECT user_id, username FROM users WHERE role='customer' ORDER BY username ASC");
$fetch_users->execute();
$users = $fetch_users->fetchAll(PDO::FETCH_ASSOC);

// Prepare users list for select dropdown
$users_list = [];
foreach ($users as $u) {
   $users_list[$u['user_id']] = $u['username'];
}

$roles = ['admin' => 'Admin', 'customer' => 'Customer'];

// --- HANDLE FILTERS ---
$search_query = $_GET['search'] ?? '';
$selected_user = $_GET['user'] ?? '';
$selected_role = $_GET['role'] ?? ''; // for User Management

// --- BUILD DYNAMIC SQL FOR ACTIONS ---
$sql = "SELECT ua.action_id, u.username AS customer_name, ua.action_type, ua.description, ua.created_at
        FROM user_actions ua
        JOIN users u ON ua.user_id = u.user_id
        WHERE u.role = 'customer'";
$action_params = [];


// --- BUILD DYNAMIC SQL FOR USER MANAGEMENT ---
$page = req('page', 1);
$user_query = "SELECT user_id, username, gmail, phone, role, profile_image, created_at FROM users WHERE 1=1";
$user_params = [];

if ($selected_user !== '') {
   $user_query .= " AND user_id = ?";
   $user_params[] = $selected_user;
}

if ($selected_role !== '') {
   $user_query .= " AND role = ?";
   $user_params[] = $selected_role;
}

$user_query .= " ORDER BY created_at DESC";

// --- PAGINATE USERS ---
$pager = new SimplePager($user_query, $user_params, 10, $page);
$paginatedRecords = $pager->result;

// --- DELETE USER ---
if (is_post() && isset($_POST['delete'])) {
   $id = filter_var(req('user_id'), FILTER_SANITIZE_NUMBER_INT);
   $admin_id = $_SESSION['user_id'];
   $user_role = $_SESSION['user_role'] ?? '';

   if ($id == $admin_id) {
      temp('error', 'You cannot delete your own account!');
   } else {
      $check_user = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
      $check_user->execute([$id]);

      if ($check_user->rowCount() > 0) {
         $user = $check_user->fetch(PDO::FETCH_ASSOC);

         if ($user_role !== 'admin' && $user['role'] === 'admin') {
            temp('error', 'Only admins can delete other admins!');
         } else {
            // Delete profile image if exists
            $image_path = __DIR__ . '/../images/' . $user['profile_image'];
            if (!empty($user['profile_image']) && file_exists($image_path)) {
               unlink($image_path);
            }

            // Log action
            log_user_action($conn, $admin_id, "delete", "Deleted user ID: " . $id);

            // Delete related carts first
            $delete_carts = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
            $delete_carts->execute([$id]);

            // Delete user
            $delete_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $delete_user->execute([$id]);

            temp('info', 'User deleted successfully!');
            redirect('admin_crud_customer.php');
         }
      } else {
         temp('error', 'User not found!');
      }
   }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Manage Users</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/crud_product.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/pager.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/search.css">
</head>

<body>
   <section class="side-section">
      <div class="section">
         <form class="search-bar" method="GET" action="">
            <?= html_search('search', $search_query ?? '', "placeholder='Search by name'") ?>
            <?= html_select('role', $roles, 'All Roles', "class='action-select'", $selected_role ?? '') ?>
            <button type="submit">Search</button>
            <a href="admin_add_customer.php" class="popup-trigger">+ Add User</a>
         </form>

         <h3>User Management</h3>

         <p class="pager-info">
            <?= $pager->count ?> of <?= $pager->item_count ?> user(s) |
            Page <?= $pager->page ?> of <?= $pager->page_count ?>
         </p>

         <table>
            <thead>
               <tr>
                  <th>ID</th>
                  <th>Profile</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Created At</th>
                  <th>Order Details</th>
                  <th>Update</th>
                  <th>Delete</th>
               </tr>
            </thead>
            <tbody>
               <?php $index = 1 + ($pager->page - 1) * $pager->limit; ?>
               <?php foreach ($paginatedRecords as $user): ?>
                  <tr>
                     <td><?= $index++ ?>.</td>
                     <td>
                        <div class="image-container">
                           <img
                              src="<?= BASE_URL ?>images/<?= htmlspecialchars($user['profile_image'] ?? 'default.png'); ?>"
                              alt="Profile Image">
                        </div>
                     </td>
                     <td><?= htmlspecialchars($user['username']); ?></td>
                     <td><?= htmlspecialchars($user['gmail']); ?></td>
                     <td><?= htmlspecialchars($user['phone']); ?></td>
                     <td><?= ucfirst($user['role']); ?></td>
                     <td><?= $user['created_at']; ?></td>
                     <td><a href="admin_update_order.php?id=<?= $user['user_id']; ?>" class="btn view">View Order</a></td>
                     <td><a href="admin_update_customer.php?id=<?= $user['user_id']; ?>" class="btn edit">Update</a></td>
                     <td>
                        <form action="" method="POST">
                           <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                           <input type="submit" class="btn delete" name="delete" value="Delete"
                              onclick="return confirm('Are you sure you want to delete this user?');">
                        </form>
                     </td>
                  </tr>
               <?php endforeach; ?>
            </tbody>
         </table>

         <?= $pager->html() ?>
      </div>
   </section>
</body>

</html>