<?php
require_once_DIR_ . '/includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$userId = current_user_id();

$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$statusLabels = [
    'pending' => 'در انتظار پرداخت',
    'paid' => 'پرداخت شده',
    'processing' => 'در حال پردازش',
    'completed' => 'تکمیل شده',
    'cancelled' => 'لغو شده'
];

function safeOutput($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سفارش‌های من | NOVA</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Tahoma, sans-serif;
            background: #f4f6fa;
            color: #222;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .back {
            text-decoration: none;
            background: #222;
            color: white;
            padding: 11px 18px;
            border-radius: 9px;
        }

        .order {
            background: white;
            padding: 22px;
            border-radius: 14px;
            margin-bottom: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .status {
            background: #eee;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .total {
            text-align: left;
            font-weight: bold;
            margin-top: 18px;
        }

        .empty {
            background: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 14px;
        }

        @media (max-width: 600px) {
            .order { padding: 16px; }
            .item { flex-direction: column; }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>سفارش‌های من</h1>
        <a href="index.php" class="back">بازگشت به سایت</a>
    </div>

    <?php
    if (empty($orders)) {
        echo '<div class="empty">
                <h3>هنوز سفارشی ثبت نکرده‌اید.</h3>
                <a href="products.php">مشاهده محصولات</a>
              </div>';
    }

    foreach ($orders as $order) {

        $status = $order['status'];
        $statusText = $statusLabels[$status] ?? $status;

        $itemStmt = $pdo->prepare("
            SELECT product_title, price, quantity, subtotal
            FROM order_items
            WHERE order_id = ?
        ");

        $itemStmt->execute([(int)$order['id']]);
        $items = $itemStmt->fetchAll();
    ?>

        <div class="order">

            <div class="order-header">
                <div>
                    <strong>
                        سفارش شماره: <?= (int)$order['id'] ?>
                    </strong>

                    <p>
                        تاریخ: <?= safeOutput($order['created_at']) ?>
                    </p>
                </div>

                <span class="status">
                    <?= safeOutput($statusText) ?>
                </span>
            </div>

            <div class="items">
			<?php
                foreach ($items as $item) {
                    echo '<div class="item">';
                    echo '<span>' . safeOutput($item['product_title']);
                    echo ' × ' . (int)$item['quantity'] . '</span>';

                    echo '<span>' . number_format((float)$item['subtotal']);
                    echo ' تومان</span>';
                    echo '</div>';
                }
                ?>

            </div>

            <div class="total">
                مبلغ کل:
                <?= number_format((float)$order['total_amount']) ?>
                تومان
            </div>

        </div>

    <?php
    }
    ?>

</div>

</body>
</html>