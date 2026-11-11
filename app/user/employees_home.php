<?php

include 'employees_sidebar.php';
require_once '../lib/SimplePager.php';

$page = req('page', 1);
// Updated query to match new table and columns
$query = "SELECT ua.action_id, u.username AS admin_name,
                 ua.action_type, ua.description,ua.created_at
        FROM user_actions ua
        LEFT JOIN users u ON ua.user_id = u.user_id
        WHERE u.role = 'admin'
        ORDER BY ua.created_at DESC
";


$pager = new SimplePager($query, null, '10', $page);
$paginatedRecords = $pager->result;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/crud_product.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/pager.css">
</head>

<body>
    <section class="side-section">
        <div class="section">
            <h3>Action List</h3>
            <p class="pager-info">
                <?= $pager->count ?> of <?= $pager->item_count ?> product(s) |
                Page <?= $pager->page ?> of <?= $pager->page_count ?>
            </p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action ID</th>
                        <th>Admin Name</th>
                        <th>Description</th>
                        <th>Action</th>
                        <th>Action Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $index = 1 + ($pager->page - 1) * $pager->limit;
                    foreach ($paginatedRecords as $record): ?>
                        <tr>
                            <td><?= $index++ ?>.</td>
                            <td><?= htmlspecialchars($record['action_id']); ?></td>
                            <td><?= htmlspecialchars($record['admin_name']); ?></td>
                            <td><?= htmlspecialchars($record['description']); ?></td>
                            <td><?= htmlspecialchars($record['action_type']); ?></td>
                            <td><?= htmlspecialchars($record['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?= $pager->html() ?>
        </div>
    </section>
</body>

</html>