<?php
include '../header.php';

// 1. Verify Login
auth("customer", "admin");
$current_user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'customer'; 

// 2. Validate Order ID
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid Order ID'); window.location.href='orders.php';</script>";
    exit;
}
$order_id = $_GET['id'];

// 3. Fetch Order Info (Security Check: If Customer, only view own orders)
$sql = "
    SELECT o.*, u.username, u.gmail AS email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
";

$params = [$order_id];
if ($role !== 'admin') {
    $sql .= " AND o.user_id = ?";
    $params[] = $current_user_id;
}

$orderStmt = $conn->prepare($sql);
$orderStmt->execute($params);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<div style='padding:50px; text-align:center;'>
            <h2>Order not found or access denied.</h2>
            <a href='orders.php'>Back to Orders</a>
          </div>";
    exit;
}

// 4. Fetch Order Items
$itemStmt = $conn->prepare("
    SELECT oi.*, p.name AS product_name, p.price, p.product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$grand_total = $order['total_amount']; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order['order_id']; ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/invoice.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="invoice-container">

    <div class="invoice-header">
        <div class="title">
            <h2>INVOICE</h2>
            <p class="order-id">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        <div class="company-info">
            <h3>NaBeGrad</h3>
            <p>123 Business Road, Kuala Lumpur</p>
            <p>NaBeGrad@gmail.com</p>
        </div>
    </div>

    <hr>

    <div class="info-row">
        <div class="bill-to">
            <h4>Bill To:</h4>
            <p><strong><?= htmlspecialchars($order['username']); ?></strong></p>
            <p><?= htmlspecialchars($order['phone']); ?></p>
            <p><?= htmlspecialchars($order['email']); ?></p>
            <p style="max-width: 300px; margin-top:5px;">
                <?= htmlspecialchars($order['address']); ?>
            </p>
        </div>
        <div class="order-meta">
            <p><strong>Order Date:</strong> <?= date('d F, Y', strtotime($order['order_date'])); ?></p>
            
            <p><strong>Status:</strong> 
                <span class="status-badge <?= $order['status'] ?>">
                    <?= ucfirst($order['status']); ?>
                </span>
            </p>

            <p><strong>Payment:</strong> 
                <span class="status-badge <?= $order['payment_status'] ?>">
                    <?= ucfirst($order['payment_status']); ?>
                </span>
            </p>
            
            <p><strong>Method:</strong> <?= strtoupper($order['payment_method']); ?></p>
        </div>
    </div>

    <table class="invoice-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($items as $item): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td>
                        <div class="product-cell">
                            <img src="<?= BASE_URL ?>images/products/<?= htmlspecialchars($item['product_image']); ?>" class="product-image" alt="">
                            <span><?= htmlspecialchars($item['product_name']); ?></span>
                        </div>
                    </td>
                    <td class="text-center"><?= (int)$item['quantity']; ?></td>
                    <td class="text-right">RM <?= number_format($item['price'],2); ?></td>
                    <td class="text-right">RM <?= number_format($item['subtotal'],2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right bold">Grand Total</td>
                <td class="text-right bold total-amount">RM <?= number_format($grand_total,2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="invoice-buttons no-print">
        <a href="<?= BASE_URL ?>order/orders.php" class="btn-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Orders
        </a>

        <button class="btn-print" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Print Invoice
        </button>
    </div>

</div>

</body>
</html>