
<?php

require_once __DIR__ . '/includes/auth.php';

// فقط کاربران واردشده اجازه ثبت سفارش دارند
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// بررسی وجود سبد خرید
if (
    !isset($_GET['order']) &&
    (
        !isset($_SESSION['cart']) ||
        !is_array($_SESSION['cart']) ||
        empty($_SESSION['cart'])
    )
) {
    header('Location: cart.php');
    exit;
}

$message = '';
$error = '';
$order = null;

// نمایش نتیجه سفارش پس از انتقال
if (isset($_GET['order'])) {
    $orderId = filter_input(
        INPUT_GET,
        'order',
        FILTER_VALIDATE_INT
    );

    if ($orderId && $orderId > 0) {
        $stmt = $pdo->prepare("
            SELECT id, total_amount, status, created_at
            FROM orders
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $orderId,
            current_user_id()
        ]);

        $order = $stmt->fetch();
    }
}

// ثبت سفارش
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $error = 'درخواست معتبر نیست. صفحه را دوباره بارگذاری کن.';
    } elseif (empty($_SESSION['cart'])) {
        $error = 'سبد خرید خالی است.';
    } else {

        try {

            $pdo->beginTransaction();

            $cart = $_SESSION['cart'];
            $items = [];
            $total = 0;

            // مرتب‌سازی شناسه‌ها برای قفل‌گذاری منظم
            ksort($cart);

            // بررسی دوباره محصولات و موجودی در دیتابیس
            foreach ($cart as $productId => $quantity) {

                $productId = (int) $productId;
                $quantity = (int) $quantity;

                if (
                    $productId < 1 ||
                    $quantity < 1
                ) {
                    throw new Exception(
                        'اطلاعات یکی از محصولات معتبر نیست.'
                    );
                }

                $stmt = $pdo->prepare("
                    SELECT id, title, price, product_type, stock
                    FROM products
                    WHERE id = ?
                    AND status = 'published'
                    LIMIT 1
                    FOR UPDATE
                ");

                $stmt->execute([$productId]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception(
                        'یکی از محصولات دیگر در دسترس نیست.'
                    );
                }

                // بررسی موجودی محصولات فیزیکی
                if ($product['product_type'] === 'physical') {

                    $stock = (int) $product['stock'];

                    if ($stock < $quantity) {
                        throw new Exception(
                            'موجودی محصول «' .
                            $product['title'] .
                            '» کافی نیست. موجودی فعلی: ' .
                            $stock
                        );
                    }
                }

                $price = (float) $product['price'];

                if ($price < 0) {
                    throw new Exception(
                        'قیمت یکی از محصولات معتبر نیست.'
                    );
                }

                $subtotal = round($price * $quantity, 2);

                $items[] = [
                    'id' => $productId,
                    'title' => $product['title'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'type' => $product['product_type']
                ];

                $total += $subtotal;
            }

            $total = round($total, 2);

            if (empty($items)) {
                throw new Exception(
                    'محصولی برای ثبت سفارش وجود ندارد.'
                );
            }

            // ساخت سفارش اصلی
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id,
                    total_amount,
                    status
                )
                VALUES (?, ?, 'pending')
            ");

            $stmt->execute([
                current_user_id(),
                $total
            ]);

            $orderId = (int) $pdo->lastInsertId();

            // ثبت محصولات سفارش
            $insertItem = $pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_title,
                    price,
                    quantity,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            // کم کردن موجودی محصولات فیزیکی
            $updateStock = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
                AND stock >= ?
            ");

            foreach ($items as $item) {

                $insertItem->execute([
                    $orderId,
                    $item['id'],
                    $item['title'],
                    $item['price'],
                    $item['quantity'],
                    $item['subtotal']
                ]);

                if ($item['type'] === 'physical') {

                    $updateStock->execute([
                        $item['quantity'],
                        $item['id'],
                        $item['quantity']
                    ]);

                    if ($updateStock->rowCount() !== 1) {
                        throw new Exception(
                            'موجودی یکی از محصولات تغییر کرده است.'
                        );
                    }
                }
            }

            // ثبت موفق تمام عملیات
            $pdo->commit();

            // خالی کردن سبد خرید
            $_SESSION['cart'] = [];

            // انتقال به صفحه نتیجه
            header(
                'Location: checkout.php?order=' . $orderId
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof PDOException) {
                error_log($e->getMessage());
                $error = 'خطایی در ثبت سفارش رخ داد. دوباره تلاش کن.';
            } else {
                $error = $e->getMessage();
            }
        }
    }
}

$token = csrf_token();

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>تسویه حساب | NOVA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f6fb;
            color: #222238;
            font-family: Tahoma, Arial, sans-serif;
        }

        header {
            background: #17172a;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: #9385ff;
            font-size: 27px;
            font-weight: bold;
            text-decoration: none;
        }

        nav {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .container {
            max-width: 850px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px #00000008;
        }

        h1 {
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 13px 20px;
            border: none;
            border-radius: 8px;
            background: #6855e8;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
        }

        .secondary {
            background: #eeeaff;
            color: #5542d0;
        }

        .message {
            background: #e3f8e9;
            color: #18763c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .total {
            font-size: 23px;
            font-weight: bold;
            color: #6552d9;
            margin: 20px 0;
        }

        .status {
            display: inline-block;
            background: #fff0cf;
            color: #946000;
            padding: 7px 12px;
            border-radius: 7px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        footer {
            margin-top: 60px;
            padding: 25px;
            background: #17172a;
            color: #ccc;
            text-align: center;
        }
    </style>
</head>

<body>

<header>
    <a href="index.php" class="logo">NOVA</a>

    <nav>
        <a href="index.php">خانه</a>
        <a href="products.php">فروشگاه</a>
        <a href="cart.php">سبد خرید</a>
        <a href="dashboard.php">حساب کاربری</a>
    </nav>
</header>

<main class="container">

    <h1>تسویه حساب</h1>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($order): ?>

        <section class="card">

            <div class="message">
                سفارش شما با موفقیت ثبت شد.
            </div>

            <h2>
                سفارش شماره:
                <?= (int) $order['id'] ?>
            </h2>

            <p>
                مبلغ سفارش:
                <?= number_format((float) $order['total_amount']) ?>
                تومان
            </p>

            <p>
                وضعیت:
                <span class="status">
                    در انتظار پرداخت
                </span>
            </p>

            <p>
                تاریخ ثبت:
                <?= htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p>
                درگاه پرداخت هنوز متصل نشده است.
            </p>

            <div class="actions">
                <a href="products.php" class="btn">
                    بازگشت به فروشگاه
                </a>

                <a href="dashboard.php" class="btn secondary">
                    حساب کاربری
                </a>
            </div>

        </section>

    <?php else: ?>

        <section class="card">

            <h2>تأیید سفارش</h2>

            <p>
                با انتخاب دکمه زیر، سفارش شما ثبت می‌شود.
            </p>

            <p>
                موجودی محصولات فیزیکی پس از ثبت موفق سفارش کم خواهد شد.
            </p>

            <p>
                سفارش در وضعیت «در انتظار پرداخت» قرار می‌گیرد.
            </p>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                >

                <button type="submit" class="btn">
                    ثبت نهایی سفارش
                </button>

                <a href="cart.php" class="btn secondary">
                    بازگشت به سبد خرید
                </a>

            </form>

        </section>

    <?php endif; ?>

</main>

<footer>
    © NOVA - تمامی حقوق محفوظ است.
</footer>

</body>
</html>
