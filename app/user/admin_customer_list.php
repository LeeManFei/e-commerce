<?php
include 'employees_sidebar.php';
require_once '../lib/SimplePager.php';

auth('admin');

/* ---------------- FETCH CUSTOMERS FOR FILTER ---------------- */
$fetch_users = $conn->prepare("
   SELECT user_id, username 
   FROM users 
   WHERE role = 'customer'
   ORDER BY username ASC
");
$fetch_users->execute();

$users_list = [];
foreach ($fetch_users->fetchAll(PDO::FETCH_ASSOC) as $u) {
   $users_list[$u['user_id']] = $u['username'];
}

/* ---------------- FETCH ACTION TYPES ---------------- */
$action_types_stmt = $conn->prepare("SELECT DISTINCT action_type FROM user_actions");
$action_types_stmt->execute();

$action_type_options = [];
foreach ($action_types_stmt->fetchAll(PDO::FETCH_COLUMN) as $type) {
   $action_type_options[$type] = $type;
}

/* ---------------- HANDLE FILTER INPUTS ---------------- */
$search_query   = $_GET['search'] ?? '';
$selected_user  = $_GET['user'] ?? '';
$selected_action = $_GET['action_type'] ?? '';
$action_page    = $_GET['action_page'] ?? $_GET['page'] ?? 1;

/* ---------------- BUILD SQL ---------------- */
$sql = "
   SELECT 
      ua.action_id,
      u.username AS customer_name,
      ua.action_type,
      ua.description,
      ua.created_at
   FROM user_actions ua
   JOIN users u ON ua.user_id = u.user_id
   WHERE u.role = 'customer'
";

$params = [];

if ($search_query !== '') {
   $sql .= " AND ua.description LIKE ?";
   $params[] = "%{$search_query}%";
}

if ($selected_user !== '') {
   $sql .= " AND ua.user_id = ?";
   $params[] = $selected_user;
}

if ($selected_action !== '') {
   $sql .= " AND ua.action_type = ?";
   $params[] = $selected_action;
}

$sql .= " ORDER BY ua.created_at DESC";

/* ---------------- PAGINATION ---------------- */
$actionPager   = new SimplePager($sql, $params, 10, $action_page);
$actionRecords = $actionPager->result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Customer Action Logs</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/crud_product.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/pager.css">
   <link rel="stylesheet" href="<?= BASE_URL ?>css/search.css">
</head>

<body>

<section class="side-section">
<div class="section">

   <!-- FILTER FORM -->
   <form class="search-bar" method="GET">

      <?= html_search(
         'search',
         $search_query,
         "placeholder='Search by description'"
      ) ?>

      <?= html_select(
         'user',
         $users_list,
         'All Customers',
         '',
         $selected_user
      ) ?>

      <?= html_select(
         'action_type',
         $action_type_options,
         'All Actions',
         "class='action-select'",
         $selected_action
      ) ?>

      <button type="submit">
         <i class="fa fa-filter"></i> Filter
      </button>
   </form>

   <h3>Customer Action List</h3>

   <p class="pager-info">
      <?= $actionPager->count ?> of <?= $actionPager->item_count ?> action(s) |
      Page <?= $actionPager->page ?> of <?= $actionPager->page_count ?>
   </p>

   <!-- ACTION TABLE -->
   <table>
      <thead>
         <tr>
            <th>#</th>
            <th>Action ID</th>
            <th>Customer Name</th>
            <th>Description</th>
            <th>Action Type</th>
            <th>Action Date</th>
         </tr>
      </thead>

      <tbody>
      <?php if (!empty($actionRecords)): ?>

         <?php $index = 1 + ($actionPager->page - 1) * $actionPager->limit; ?>
         <?php foreach ($actionRecords as $record): ?>
         <tr>
            <td><?= $index++ ?></td>
            <td><?= htmlspecialchars($record['action_id']) ?></td>
            <td><?= htmlspecialchars($record['customer_name']) ?></td>
            <td><?= htmlspecialchars($record['description']) ?></td>
            <td><?= htmlspecialchars($record['action_type']) ?></td>
            <td><?= htmlspecialchars($record['created_at']) ?></td>
         </tr>
         <?php endforeach; ?>

      <?php else: ?>
         <tr>
            <td colspan="6" style="text-align:center;">
               No customer actions found.
            </td>
         </tr>
      <?php endif; ?>
      </tbody>
   </table>

   <!-- PAGINATION LINKS -->
   <?= $actionPager->html() ?>

</div>
</section>

</body>
</html>