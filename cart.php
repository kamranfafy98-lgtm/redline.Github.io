<?php

require_once __DIR__ . '/includes/auth.php';

// ساخت سبد خرید در صورت نبودن
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$error = '';

// پردازش عملیات سبد خرید
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $error = 'درخواست معتبر نیست. صفحه را دوباره بارگذاری کن.';
    } else {

        $action = $_POST['action'] ?? '';

        // افزودن محصول به سبد
        if ($action === 'add') {

            $productId = filter_input(
                INPUT_POST,
                'product_id',
                FILTER_VALIDATE_INT
            );

            if (!$productId || $productId < 1) {
                $error = 'شناسه محصول معتبر نیست.';
            } else {

                $stmt = $pdo->prepare("
                    SELECT id, product_type, stock
                    FROM products
                    WHERE id = ? AND status = 'published'
                    LIMIT 1
                ");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();

                if (!$product) {
                    $error = 'محصول پیدا نشد یا منتشر نشده است.';
                } else {

                    $currentQty = (int) (
                        $_SESSION['cart'][$productId] ?? 0
                    );

                    $stock = (int) $product['stock'];

                    if (
                        $product['product_type'] === 'physical'
                        && ($stock < 1 || $currentQty >= $stock)
                    ) {
                        $error = $stock < 1
                            ? 'این محصول در حال حاضر ناموجود است.'
                            : 'موجودی انبار برای افزودن تعداد بیشتر کافی نیست.';
                    } else {

                        $_SESSION['cart'][$productId] = $currentQty + 1;

                        $_SESSION['cart_message'] =
                            'محصول به سبد خرید اضافه شد.';

                        header('Location: cart.php');
                        exit;
                    }
                }
            }

        // تغییر تعداد محصول در سبد
        } elseif ($action === 'update') {

            $productId = filter_input(
                INPUT_POST,
                'product_id',
                FILTER_VALIDATE_INT
            );

            $quantity = filter_input(
                INPUT_POST,
                'quantity',
                FILTER_VALIDATE_INT
            );

            if (
                !$productId ||
                $productId < 1 ||
                $quantity === false ||
                $quantity === null ||
                $quantity < 1
            ) {
                $error = 'تعداد یا شناسه محصول معتبر نیست.';
            } elseif (!isset($_SESSION['cart'][$productId])) {
                $error = 'این محصول در سبد خرید وجود ندارد.';
            } else {

                $stmt = $pdo->prepare("
                    SELECT id, product_type, stock
                    FROM products
                    WHERE id = ? AND status = 'published'
                    LIMIT 1
                ");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();

                if (!$product) {
                    unset($_SESSION['cart'][$productId]);
                    $error = 'محصول پیدا نشد یا دیگر منتشر نیست.';
                } elseif (
                    $product['product_type'] === 'physical'
                    && $quantity > (int) $product['stock']
                ) {
                    $error = 'تعداد درخواستی بیشتر از موجودی انبار است. موجودی فعلی: '
                        . (int) $product['stock'] . ' عدد.';
                } else {

                    $_SESSION['cart'][$productId] = $quantity;

                    $_SESSION['cart_message'] =
                        'تعداد محصول به‌روزرسانی شد.';

                    header('Location: cart.php');
                    exit;
                }
            }

        // حذف محصول از سبد
        } elseif ($action === 'remove') {

            $productId = filter_input(
                INPUT_POST,
                'product_id',
                FILTER_VALIDATE_INT
            );

            if (!$productId || $productId < 1) {
                $error = 'شناسه محصول معتبر نیست.';
            } else {

                unset($_SESSION['cart'][$productId]);

                $_SESSION['cart_message'] =
                    'محصول از سبد خرید حذف شد.';

                header('Location: cart.php');
                exit;
            }

        } else {
            $error = 'عملیات نامعتبر است.';
        }
    }
}

// دریافت پیام بعد از انتقال
if (isset($_SESSION['cart_message'])) {
    $message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}

// دریافت اطلاعات محصولات سبد خرید و بررسی موجودی
$cartItems = [];
$total = 0;
$totalQuantity = 0;

foreach ($_SESSION['cart'] as $id => $quantity) {

    $id = (int) $id;
    $quantity = (int) $quantity;

    if ($id < 1 || $quantity < 1) {
        unset($_SESSION['cart'][$id]);
        continue;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id = ? AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        unset($_SESSION['cart'][$id]);
        continue;
    }

    // بررسی موجودی فقط برای محصولات فیزیکی
    if ($product['product_type'] === 'physical') {

        $stock = (int) $product['stock'];

        // اگر موجودی صفر شده، محصول از سبد حذف شود
        if ($stock < 1) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        // اگر تعداد سبد بیشتر از موجودی فعلی باشد، اصلاح شود
        if ($quantity > $stock) {
            $quantity = $stock;
            $_SESSION['cart'][$id] = $quantity;

            $message = 'تعداد یکی از محصولات به دلیل تغییر موجودی انبار اصلاح شد.';
        }
    }

    $subtotal = (float) $product['price'] * $quantity;

    $product['quantity'] = $quantity;
    $product['subtotal'] = $subtotal;

    $cartItems[] = $product;

    $total += $subtotal;
    $totalQuantity += $quantity;
}

$token = csrf_token();

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سبد خرید | NOVA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f6fb;
            color: #222238;
        }

        header {
            background: #17172a;
            padding: 20px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            color: #9385ff;
            font-size: 28px;
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
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 {
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px #00000008;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #eee;
            padding: 18px 0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 100px;
            height: 90px;
            object-fit: contain;
            border-radius: 10px;
            background: #f1f0f8;
        }

        .item-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .item-price {
            color: #6552d9;
            font-size: 14px;
            margin-top: 6px;
        }

        .stock-info {
            margin-top: 10px;
            font-size: 13px;
            line-height: 1.8;
        }

        .quantity-form {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .quantity-form input {
            width: 75px;
            padding: 9px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-family: inherit;
        }

        button,
        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            font-family: inherit;
            text-decoration: none;
            font-size: 13px;
        }

        .update-btn {
            background: #eeeaff;
            color: #5542d0;
        }

        .remove-btn {
            background: #ffe7e7;
            color: #c22;
        }

        .primary {
            display: inline-block;
            background: #6855e8;
            color: white;
            text-align: center;
        }

        .message {
            background: #e3f8e9;
            color: #18763c;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .total {
            font-size: 22px;
            font-weight: bold;
            color: #6552d9;
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
        }

        .empty p {
            color: #777;
            line-height: 2;
        }

        footer {
            margin-top: 60px;
            padding: 25px;
            background: #17172a;
            color: #ccc;
            text-align: center;
        }

        @media (max-width: 650px) {
            .cart-item {
                grid-template-columns: 75px 1fr;
                gap: 12px;
            }

            .cart-item img {
                width: 75px;
                height: 75px;
            }

            .quantity-form {
                grid-column: 1 / -1;
            }

            .summary {
                flex-direction: column;
                align-items: stretch;
            }

            .summary .primary {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="index.php" class="logo">NOVA</a>

    <nav>
        <a href="index.php">خانه</a>
        <a href="courses.php">دوره‌ها</a>
        <a href="products.php">فروشگاه</a>
        <a href="cart.php">سبد خرید</a>
    </nav>
</header>

<main class="container">

    <h1>🛒 سبد خرید من</h1>

    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

        <section class="card empty">
            <h2>سبد خریدت خالیه!</h2>

            <p>
                هنوز محصولی به سبد خرید اضافه نکردی.
            </p>

            <a href="products.php" class="btn primary">
                رفتن به فروشگاه
            </a>
        </section>

    <?php else: ?>

        <section class="card">

            <?php foreach ($cartItems as $item): ?>

                <div class="cart-item">

                    <?php if (!empty($item['image'])): ?>
                        <img
                            src="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php else: ?>
                        <img
                            src="https://placehold.co/200x200?text=NOVA"
                            alt="NOVA"
                        >
                    <?php endif; ?>

                    <div>

                        <div class="item-title">
                            <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <div class="item-price">
                            قیمت واحد:
                            <?= number_format((float) $item['price']) ?>
                            تومان
                        </div>

                        <div class="item-price">
                            جمع:
                            <?= number_format((float) $item['subtotal']) ?>
                            تومان
                        </div>

                        <div class="stock-info">

                            <?php if ($item['product_type'] === 'physical'): ?>

                                <span style="color: #555;">
                                    موجودی انبار:
                                    <strong style="color: #16803d;">
                                        <?= (int) $item['stock'] ?> عدد
                                    </strong>
                                </span>

                                <?php if ((int) $item['stock'] <= 3): ?>
                                    <div style="color: #c22; margin-top: 5px;">
                                        موجودی این محصول محدود است.
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>

                                <span style="color: #16803d;">
                                    محصول دیجیتال - موجودی نامحدود
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="quantity-form">

                        <form method="POST" class="quantity-form">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <input type="hidden" name="action" value="update">

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $item['id'] ?>"
                            >

                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                <?= $item['product_type'] === 'physical'
                                    ? 'max="' . (int) $item['stock'] . '"'
                                    : '' ?>
                                value="<?= (int) $item['quantity'] ?>"
                                required
                            >

                            <button type="submit" class="update-btn">
                                بروزرسانی
                            </button>

                        </form>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <input type="hidden" name="action" value="remove">

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $item['id'] ?>"
                            >

                            <button type="submit" class="remove-btn">
                                حذف
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        </section>

        <section class="card">

            <div class="summary">

                <div>
                    <p>تعداد کل محصولات: <?= (int) $totalQuantity ?></p>

                    <div class="total">
                        مبلغ کل:
                        <?= number_format($total) ?> تومان
                    </div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">

    <a href="products.php" class="btn primary">
        ادامه خرید
    </a>

    <a href="checkout.php" class="btn primary">
        تسویه حساب و ثبت سفارش
    </a>

</div>

            </div>

        </section>

    <?php endif; ?>

</main>

<footer>
    © NOVA - تمامی حقوق محفوظ است.
</footer>

</body>
</html>
