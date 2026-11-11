
<?php

require_once __DIR__ . '/includes/auth.php';

// دریافت شناسه محصول
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('محصول موردنظر پیدا نشد.');
}

// دریافت محصول منتشرشده
$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ? AND status = 'published'
    LIMIT 1
");

$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('محصول موردنظر وجود ندارد یا منتشر نشده است.');
}

$isPhysical = $product['product_type'] === 'physical';
$stock = (int) $product['stock'];

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?> | NOVA
    </title>

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
            gap: 22px;
            flex-wrap: wrap;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            color: #a99cff;
        }

        .container {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .breadcrumb {
            margin-bottom: 25px;
            color: #888;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #6654dc;
            text-decoration: none;
        }

        .product-card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            box-shadow: 0 5px 25px rgba(0,0,0,.04);
        }

        .product-image {
            width: 100%;
            min-height: 350px;
            max-height: 480px;
            object-fit: contain;
            background: #f0eff8;
            border-radius: 14px;
        }

        .image-placeholder {
            min-height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0eff8;
            border-radius: 14px;
            color: #7564df;
            font-size: 42px;
            font-weight: bold;
        }

        .product-info {
            padding: 10px 0;
        }

        .category {
            color: #777;
            font-size: 13px;
            margin-bottom: 15px;
        }

        h1 {
            font-size: 27px;
            line-height: 1.8;
            margin: 0 0 15px;
        }

        .type {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 30px;
            background: #eeeaff;
            color: #6654dc;
            font-size: 12px;
        }

        .price {
            font-size: 25px;
            font-weight: bold;
            color: #6552d9;
            margin: 25px 0;
        }

        .description {
            line-height: 2.2;
            color: #666;
            font-size: 14px;
            margin: 25px 0;
            overflow-wrap: anywhere;
        }

        .stock {
            font-size: 14px;
            margin-bottom: 25px;
        }

        .available {
            color: #168344;
        }

        .unavailable {
            color: #d22;
        }

        .cart-button {
            display: block;
            width: 100%;
            text-align: center;
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: #6855e8;
            color: white;
            font-family: inherit;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            opacity: .65;
        }

        .cart-button:disabled {
            cursor: not-allowed;
        }

        footer {
            margin-top: 70px;
            padding: 25px;
            background: #17172a;
            color: #ccc;
            text-align: center;
        }

        @media(max-width: 750px) {
            .product-card {
                grid-template-columns: 1fr;
                padding: 20px;
                gap: 20px;
            }

            h1 {
                font-size: 22px;
            }

            .product-image,
            .image-placeholder {
                min-height: 250px;
            }

            header {
                padding: 18px;
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

        <?php if (is_logged_in()): ?>
            <a href="dashboard.php">حساب کاربری</a>
        <?php else: ?>
            <a href="login.php">ورود</a>
        <?php endif; ?>
    </nav>

</header>

<main class="container">

    <div class="breadcrumb">
        <a href="index.php">خانه</a>
        /
        <a href="products.php">فروشگاه</a>
        /
        <?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>
    </div>

    <section class="product-card">

        <div>

            <?php if (!empty($product['image'])): ?>

                <img
                    class="product-image"
                    src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                >

                <div class="image-placeholder" style="display:none">
                    NOVA
                </div>

            <?php else: ?>

                <div class="image-placeholder">
                    NOVA
                </div>

            <?php endif; ?>

        </div>

        <div class="product-info">

            <div class="category">
                دسته‌بندی:
                <?= htmlspecialchars($product['category'] ?: 'عمومی', ENT_QUOTES, 'UTF-8') ?>
            </div>

            <h1>
                <?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <span class="type">
                <?= $isPhysical ? 'محصول فیزیکی' : 'محصول دیجیتال' ?>
            </span>

            <div class="price">
                <?= number_format((float) $product['price']) ?> تومان
            </div>

            <div class="stock">

                <?php if (!$isPhysical): ?>

                    <span class="available">
                        ✓ محصول دیجیتال
                    </span>

                <?php elseif ($stock > 0): ?>

                    <span class="available">
                        ✓ موجود در انبار (<?= $stock ?> عدد)
                    </span>

                <?php else: ?>

                    <span class="unavailable">
                        ✕ ناموجود
                    </span>

                <?php endif; ?>

            </div>

            <h3>توضیحات محصول</h3>

            <div class="description">
                <?php if (!empty($product['description'])): ?>

                    <?= nl2br(htmlspecialchars(
                        $product['description'],
                        ENT_QUOTES,
                        'UTF-8'
                    )) ?>

                <?php else: ?>

                    توضیحاتی برای این محصول ثبت نشده است.

                <?php endif; ?>
            </div>

<form action="cart.php" method="POST">
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars(csrf_token()) ?>">

    <input type="hidden" name="action" value="add">

    <input type="hidden" name="product_id"
           value="<?= (int) $product['id'] ?>">

    <button class="cart-button" type="submit"
        <?= ($isPhysical && $stock < 1) ? 'disabled' : '' ?>>
        افزودن به سبد خرید
    </button>
</form>

        </div>

    </section>

</main>

<footer>
    © NOVA - تمامی حقوق محفوظ است.
</footer>

</body>
</html>
