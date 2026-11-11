<?php
require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// بررسی دسترسی مدیر
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([current_user_id()]);
$userRole = $stmt->fetchColumn();

if ($userRole !== 'admin') {
    http_response_code(403);
    exit('شما اجازه دسترسی به این صفحه را ندارید.');
}

$message = '';
$error = '';

// حذف محصول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verify_csrf()) {
        $error = 'درخواست نامعتبر است.';
    } else {
        $id = (int) $_POST['delete_id'];

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        $message = 'محصول با موفقیت حذف شد.';
    }
}

// تغییر وضعیت محصول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    if (!verify_csrf()) {
        $error = 'درخواست نامعتبر است.';
    } else {
        $id = (int) $_POST['toggle_id'];

        $stmt = $pdo->prepare("
            UPDATE products
            SET status = CASE
                WHEN status = 'published' THEN 'draft'
                ELSE 'published'
            END
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        $message = 'وضعیت محصول تغییر کرد.';
    }
}

// افزودن یا ویرایش محصول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {

    if (!verify_csrf()) {
        $error = 'درخواست نامعتبر است.';
    } else {

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $product_type = $_POST['product_type'] ?? 'digital';
        $stock = max(0, (int) ($_POST['stock'] ?? 0));
        $image = trim($_POST['image'] ?? '');
        $status = $_POST['status'] ?? 'draft';

        if ($title === '') {
            $error = 'عنوان محصول الزامی است.';
        } elseif ($price < 0) {
            $error = 'قیمت نمی‌تواند منفی باشد.';
        } elseif (!in_array($product_type, ['physical', 'digital'], true)) {
            $error = 'نوع محصول نامعتبر است.';
        } elseif (!in_array($status, ['draft', 'published'], true)) {
            $error = 'وضعیت محصول نامعتبر است.';
        } else {

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE products SET
                        title = ?,
                        description = ?,
                        price = ?,
                        category = ?,
                        product_type = ?,
                        stock = ?,
                        image = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $title,
                    $description,
                    $price,
                    $category,
                    $product_type,
                    $stock,
                    $image,
                    $status,
                    $id
                ]);

                $message = 'محصول ویرایش شد.';

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (title, description, price, category,
                     product_type, stock, image, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $title,
                    $description,
                    $price,
                    $category,
                    $product_type,
                    $stock,
                    $image,
                    $status
                ]);

                $message = 'محصول جدید اضافه شد.';
            }
        }
    }
}

// دریافت محصول برای ویرایش
$editProduct = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// جستجو
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE title LIKE ? OR category LIKE ?
        ORDER BY id DESC
    ");

    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
}

$products = $stmt->fetchAll();

$token = csrf_token();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مدیریت محصولات | NOVA</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Tahoma, sans-serif;
    background: #f3f4f8;
    color: #222;
}

.sidebar {
    width: 240px;
    height: 100vh;
    background: #151525;
    color: white;
    position: fixed;
    right: 0;
    top: 0;
    padding: 25px 15px;
}

.sidebar h2 {
    color: #8b7cff;
    text-align: center;
    margin-bottom: 35px;
}

.sidebar a {
    display: block;
    color: #ddd;
    text-decoration: none;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 8px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #302c55;
    color: white;
}

.main {
    margin-right: 240px;
    padding: 30px;
}

h1 {
    margin-top: 0;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 14px;
    margin-bottom: 25px;
    box-shadow: 0 3px 15px #00000008;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

input, textarea, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
    margin-top: 7px;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.full {
    grid-column: 1 / -1;
}

button, .btn {
    border: none;
    padding: 11px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    display: inline-block;
    font-size: 13px;
}

.primary {
    background: #6855e8;
    color: white;
}

.edit {
    background: #e8e5ff;
    color: #5542d0;
}

.delete {
    background: #ffe5e5;
    color: #d22;
}

.toggle {
    background: #e4f5e9;
    color: #168344;
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

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    text-align: right;
}

th {
    background: #f8f8fc;
}

.table-wrap {
    overflow-x: auto;
}

.search {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.search input {
    max-width: 350px;
}

.actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

@media(max-width: 800px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .main {
        margin: 0;
        padding: 15px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <h2>NOVA ADMIN</h2>
    <a href="admin.php">داشبورد مدیریت</a>
    <a href="admin-users.php">مدیریت کاربران</a>
    <a href="admin-courses.php">مدیریت دوره‌ها</a>
    <a href="admin-products.php" class="active">مدیریت محصولات</a>
    <a href="index.php">مشاهده سایت</a>
</aside>

<main class="main">

<h1>مدیریت محصولات فروشگاه</h1>
<p>افزودن، ویرایش و مدیریت محصولات فیزیکی و دیجیتال</p>

<?php if ($message): ?>
    <div class="message">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<section class="card">

<h3>
    <?= $editProduct ? 'ویرایش محصول' : 'افزودن محصول جدید' ?>
</h3>

<form method="POST">
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars($token) ?>">

    <input type="hidden" name="id"
           value="<?= (int) ($editProduct['id'] ?? 0) ?>">

    <div class="form-grid">

        <div>
            <label>عنوان محصول</label>
            <input type="text" name="title" required
                   value="<?= htmlspecialchars($editProduct['title'] ?? '') ?>">
        </div>

        <div>
            <label>قیمت (تومان)</label>
            <input type="number" name="price" min="0" step="0.01"
                   value="<?= htmlspecialchars((string) ($editProduct['price'] ?? '0')) ?>">
        </div>

        <div>
            <label>دسته‌بندی</label>
            <input type="text" name="category"
                   value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>">
        </div>

        <div>
            <label>نوع محصول</label>
            <select name="product_type">
                <option value="digital"
                    <?= ($editProduct['product_type'] ?? '') === 'digital' ? 'selected' : '' ?>>
                    دیجیتال
                </option>

                <option value="physical"
                    <?= ($editProduct['product_type'] ?? '') === 'physical' ? 'selected' : '' ?>>
                    فیزیکی
                </option>
            </select>
        </div>

        <div>
            <label>موجودی انبار</label>
            <input type="number" name="stock" min="0"
                   value="<?= (int) ($editProduct['stock'] ?? 0) ?>">
        </div>

        <div>
            <label>آدرس تصویر محصول</label>
            <input type="text" name="image"
                   placeholder="uploads/products/example.jpg"
                   value="<?= htmlspecialchars($editProduct['image'] ?? '') ?>">
        </div>

        <div>
            <label>وضعیت</label>
            <select name="status">
                <option value="draft"
                    <?= ($editProduct['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                    پیش‌نویس
                </option>

                <option value="published"
                    <?= ($editProduct['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                    منتشر شده
                </option>
            </select>
        </div>

        <div class="full">
            <label>توضیحات محصول</label>
            <textarea name="description"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
        </div>

        <div class="full">
            <button type="submit" name="save_product" class="primary">
                <?= $editProduct ? 'ذخیره تغییرات' : 'افزودن محصول' ?>
            </button>

            <?php if ($editProduct): ?>
                <a href="admin-products.php" class="btn edit">لغو ویرایش</a>
            <?php endif; ?>
        </div>

    </div>
</form>

</section>

<section class="card">

<h3>لیست محصولات</h3>

<form method="GET" class="search">
    <input type="text" name="search"
           placeholder="جستجوی محصول..."
           value="<?= htmlspecialchars($search) ?>">

    <button class="primary">جستجو</button>
    <a href="admin-products.php" class="btn edit">نمایش همه</a>
</form>

<div class="table-wrap">
<table>

<thead>
<tr>
    <th>شناسه</th>
    <th>عنوان</th>
    <th>قیمت</th>
    <th>نوع</th>
    <th>موجودی</th>
    <th>وضعیت</th>
    <th>عملیات</th>
</tr>
</thead>

<tbody>

<?php if (empty($products)): ?>
<tr>
    <td colspan="7">محصولی ثبت نشده است.</td>
</tr>
<?php endif; ?>

<?php foreach ($products as $product): ?>
<tr>

    <td><?= (int) $product['id'] ?></td>

    <td><?= htmlspecialchars($product['title']) ?></td>

    <td><?= number_format((float) $product['price']) ?> تومان</td>

    <td>
        <?= $product['product_type'] === 'physical' ? 'فیزیکی' : 'دیجیتال' ?>
    </td>

    <td><?= (int) $product['stock'] ?></td>

    <td>
        <?= $product['status'] === 'published' ? 'منتشر شده' : 'پیش‌نویس' ?>
    </td>

    <td>
        <div class="actions">

            <a class="btn edit"
               href="admin-products.php?edit=<?= (int) $product['id'] ?>">
                ویرایش
            </a>

            <form method="POST">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="toggle_id"
                       value="<?= (int) $product['id'] ?>">

                <button class="toggle" type="submit">
                    تغییر وضعیت
                </button>
            </form>

            <form method="POST"
                  onsubmit="return confirm('از حذف این محصول مطمئن هستید؟')">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($token) ?>">

                <input type="hidden" name="delete_id"
                       value="<?= (int) $product['id'] ?>">

                <button class="delete" type="submit">
                    حذف
                </button>
            </form>

        </div>
    </td>

</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

</section>

</main>
</body>
</html>