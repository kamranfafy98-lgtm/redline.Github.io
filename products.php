```php
<?php
require_once __DIR__ . '/includes/auth.php';

// فقط محصولات منتشرشده نمایش داده می‌شوند
$stmt = $pdo->query("
    SELECT *
    FROM products
    WHERE status = 'published'
    ORDER BY id DESC
");

$products = $stmt->fetchAll();

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';

if ($search !== '' || in_array($type, ['physical', 'digital'], true)) {
    $sql = "
        SELECT *
        FROM products
        WHERE status = 'published'
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (title LIKE ? OR category LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (in_array($type, ['physical', 'digital'], true)) {
        $sql .= " AND product_type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فروشگاه | NOVA</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Tahoma, sans-serif;
    background: #f6f7fb;
    color: #202033;
}

header {
    background: #17172a;
    color: white;
    padding: 20px 7%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}

.logo {
    color: #8e80ff;
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

.hero {
    padding: 55px 7%;
    background: linear-gradient(120deg, #25234b, #6654d9);
    color: white;
}

.hero h1 {
    font-size: 32px;
}

.hero p {
    color: #dddafc;
    line-height: 2;
}

.container {
    padding: 35px 7%;
}

.search-box {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

input, select {
    padding: 13px;
    border: 1px solid #ddd;
    border-radius: 9px;
    font-family: inherit;
}

input {
    flex: 1;
    min-width: 180px;
}

button {
    background: #6855e8;
    color: white;
    border: 0;
    border-radius: 9px;
    padding: 13px 22px;
    cursor: pointer;
    font-family: inherit;
}

.products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 22px;
}

.product {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px #00000008;
    transition: .25s;
}

.product:hover {
    transform: translateY(-5px);
}

.product img {
    width: 100%;
    height: 190px;
    object-fit: cover;
    background: #e9e9f2;
}

.product-info {
    padding: 18px;
}

.product-info h3 {
    margin-top: 0;
}

.category {
    color: #777;
    font-size: 13px;
}

.type {
    display: inline-block;
    background: #eeeaff;
    color: #6552d9;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    margin: 8px 0;
}

.price {
    font-weight: bold;
    color: #5b49d1;
    margin: 12px 0;
}

.details {
    display: block;
    text-align: center;
    background: #6855e8;
    color: white;
    text-decoration: none;
    padding: 12px;
    border-radius: 9px;
}

.empty {
    background: white;
    padding: 35px;
    text-align: center;
    border-radius: 12px;
    grid-column: 1 / -1;
}

footer {
    background: #17172a;
    color: #ccc;
    text-align: center;
    padding: 25px;
    margin-top: 50px;
}

@media(max-width: 600px) {
    .hero h1 {
        font-size: 24px;
    }

    header {
        padding: 18px;
    }

    .container {
        padding: 25px 18px;
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
        <a href="dashboard.php">حساب کاربری</a>
		<a href="cart.php">🛒 سبد خرید</a>
    </nav>
</header>

<section class="hero">
    <h1>فروشگاه NOVA</h1>
    <p>محصولات دیجیتال و فیزیکی مورد نیازت رو اینجا پیدا کن.</p>
</section>

<main class="container">

    <h2>محصولات فروشگاه</h2>

    <form method="GET" class="search-box">

        <input
            type="text"
            name="search"
            placeholder="جستجوی محصول یا دسته‌بندی..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <select name="type">
            <option value="">همه محصولات</option>
            <option value="digital" <?= $type === 'digital' ? 'selected' : '' ?>>
                محصولات دیجیتال
            </option>
            <option value="physical" <?= $type === 'physical' ? 'selected' : '' ?>>
                محصولات فیزیکی
            </option>
        </select>

        <button type="submit">جستجو</button>

    </form>

    <div class="products">

        <?php if (empty($products)): ?>

            <div class="empty">
                <h3>محصولی پیدا نشد!</h3>
                <p>فعلاً محصولی برای نمایش وجود ندارد.</p>
            </div>

        <?php endif; ?>

        <?php foreach ($products as $product): ?>

            <article class="product">

                <?php if (!empty($product['image'])): ?>
                    <img
                        src="<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['title']) ?>"
                    >
                <?php else: ?>
                    <img
                        src="https://placehold.co/600x400?text=NOVA"
                        alt="تصویر پیش‌فرض محصول"
                    >
                <?php endif; ?>

                <div class="product-info">

                    <h3><?= htmlspecialchars($product['title']) ?></h3>

                    <div class="category">
                        <?= htmlspecialchars($product['category'] ?? 'بدون دسته‌بندی') ?>
                    </div>

                    <span class="type">
                        <?= $product['product_type'] === 'physical'
                            ? 'محصول فیزیکی'
                            : 'محصول دیجیتال' ?>
                    </span>

                    <p class="price">
                        <?= number_format((float) $product['price']) ?> تومان
                    </p>

                    <a
                        class="details"
                        href="product-details.php?id=<?= (int) $product['id'] ?>"
                    >
                        مشاهده جزئیات
                    </a>

                </div>
            </article>

        <?php endforeach; ?>

    </div>

</main>

<footer>
    © NOVA - تمامی حقوق محفوظ است.
</footer>

</body>
</html>
```