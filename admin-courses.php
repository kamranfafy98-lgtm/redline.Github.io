<?php
require_once __DIR__ . '/includes/auth.php';

// فقط مدیر اجازه ورود دارد
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$checkAdmin = $pdo->prepare(
    "SELECT role FROM users WHERE id = ? LIMIT 1"
);
$checkAdmin->execute([current_user_id()]);

if ($checkAdmin->fetchColumn() !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = 'success';

// عملیات فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        exit('درخواست نامعتبر است. صفحه را تازه‌سازی کنید.');
    }

    $action = $_POST['action'] ?? '';

    // افزودن یا ویرایش دوره
    if ($action === 'save_course') {

        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $thumbnail = trim($_POST['thumbnail'] ?? '');
        $priceInput = $_POST['price'] ?? '';
        $status = $_POST['status'] ?? 'draft';

        if ($title === '' || $category === '') {
            $message = 'عنوان و دسته‌بندی الزامی هستند.';
            $messageType = 'error';

        } elseif (
            !is_numeric($priceInput) ||
            !is_finite((float) $priceInput) ||
            (float) $priceInput < 0 ||
            (float) $priceInput > 999999999999
        ) {
            $message = 'قیمت واردشده معتبر نیست.';
            $messageType = 'error';

        } elseif (!in_array($status, ['draft', 'published'], true)) {
            $message = 'وضعیت دوره معتبر نیست.';
            $messageType = 'error';

        } elseif (strlen($title) > 255 || strlen($category) > 100 || strlen($thumbnail) > 255) {
            $message = 'طول یکی از فیلدها بیش از حد مجاز است.';
            $messageType = 'error';

        } else {

            $price = (float) $priceInput;

            if ($courseId) {

                $update = $pdo->prepare(
                    "UPDATE courses
                     SET title = ?, description = ?, price = ?,
                         category = ?, thumbnail = ?, status = ?
                     WHERE id = ?"
                );

                $update->execute([
                    $title,
                    $description,
                    $price,
                    $category,
                    $thumbnail !== '' ? $thumbnail : null,
                    $status,
                    $courseId
                ]);

                $message = 'دوره با موفقیت ویرایش شد.';

            } else {

                $insert = $pdo->prepare(
                    "INSERT INTO courses
                     (title, description, price, category, thumbnail, status)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $insert->execute([
                    $title,
                    $description,
                    $price,
                    $category,
                    $thumbnail !== '' ? $thumbnail : null,
                    $status
                ]);

                $message = 'دوره جدید با موفقیت اضافه شد.';
            }
        }
    }

    // تغییر وضعیت انتشار
    elseif ($action === 'toggle_status') {

        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

        if ($courseId) {
            $toggle = $pdo->prepare(
                "UPDATE courses
                 SET status = CASE
                    WHEN status = 'published' THEN 'draft'
                    ELSE 'published'
                 END
                 WHERE id = ?"
            );

            $toggle->execute([$courseId]);

            $message = $toggle->rowCount()
                ? 'وضعیت دوره تغییر کرد.'
                : 'دوره پیدا نشد یا تغییری لازم نبود.';
        } else {
            $message = 'شناسه دوره نامعتبر است.';
            $messageType = 'error';
        }
    }

    // حذف دوره
    elseif ($action === 'delete_course') {

        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

        if (!$courseId) {
            $message = 'شناسه دوره نامعتبر است.';
            $messageType = 'error';

        } else {

            $enrollmentCheck = $pdo->prepare(
                "SELECT COUNT(*) FROM enrollments WHERE course_id = ?"
            );
            $enrollmentCheck->execute([$courseId]);

            if ((int) $enrollmentCheck->fetchColumn() > 0) {

                $message = 'این دوره ثبت‌نام‌کننده دارد و برای حفظ سوابق قابل حذف نیست. می‌توانید آن را پیش‌نویس کنید.';
                $messageType = 'error';

            } else {

                $delete = $pdo->prepare(
                    "DELETE FROM courses WHERE id = ?"
                );
                $delete->execute([$courseId]);

                if ($delete->rowCount()) {
                    $message = 'دوره با موفقیت حذف شد.';
                } else {
                    $message = 'دوره پیدا نشد.';
                    $messageType = 'error';
                }
            }
        }
    }
}

// جست‌وجوی دوره‌ها
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $pdo->prepare(
        "SELECT * FROM courses
         WHERE title LIKE ? OR category LIKE ?
         ORDER BY id DESC"
    );

    $keyword = '%' . $search . '%';
    $stmt->execute([$keyword, $keyword]);

} else {

    $stmt = $pdo->query(
        "SELECT * FROM courses ORDER BY id DESC"
    );
}

$courses = $stmt->fetchAll();

// دریافت دوره برای ویرایش
$editCourse = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId) {
    $editStmt = $pdo->prepare(
        "SELECT * FROM courses WHERE id = ? LIMIT 1"
    );
    $editStmt->execute([$editId]);
    $editCourse = $editStmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>مدیریت دوره‌ها | NOVA</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Tahoma, sans-serif;
        }

        body {
            background: #f4f6fb;
            color: #20243a;
        }

        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #171b35;
            padding: 30px 20px;
        }

        .logo {
            text-align: center;
            color: #7c83ff;
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 40px;
        }

        .sidebar a {
            display: block;
            color: #ddd;
            text-decoration: none;
            padding: 14px;
            border-radius: 9px;
            margin-bottom: 10px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #343b68;
            color: white;
        }

        .main {
            margin-right: 240px;
            padding: 30px;
        }

        .header,
        .panel {
            background: white;
            padding: 25px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px #00000008;
        }

        .header h1 {
            font-size: 23px;
        }

        .panel h2 {
            font-size: 19px;
            margin-bottom: 20px;
        }

        .message {
            padding: 14px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #e5f8ed;
            color: #25804b;
        }

        .error {
            background: #ffe9eb;
            color: #b9384c;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            color: #555;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 13px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #555ee8;
        }

        button, .btn {
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 8px;
            padding: 11px 16px;
            font-size: 13px;
            display: inline-block;
        }

        .primary {
            background: #555ee8;
            color: white;
        }

        .secondary {
            background: #eee;
            color: #333;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-form input {
            flex: 1;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 10px;
            text-align: right;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        th {
            background: #f7f8fc;
        }

        .status {
            padding: 7px 10px;
            border-radius: 20px;
            font-size: 11px;
        }

        .published {
            background: #e5f8ed;
            color: #25804b;
        }

        .draft {
            background: #fff2d9;
            color: #9a6810;
        }

        .actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .edit {
            background: #555ee8;
            color: white;
        }

        .toggle {
            background: #e9eaff;
            color: #444bc1;
        }

        .delete {
            background: #ffe9eb;
            color: #b9384c;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #888;
        }

        @media(max-width: 750px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-right: 0;
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="logo">NOVA</div>

    <a href="admin.php">🏠 داشبورد مدیریت</a>
    <a href="admin-users.php">👥 مدیریت کاربران</a>
    <a href="admin-courses.php" class="active">📚 مدیریت دوره‌ها</a>
    <a href="index.php">🌐 مشاهده سایت</a>

</aside>

<main class="main">

    <header class="header">
        <h1>مدیریت دوره‌ها</h1>
    </header>

    <?php if ($message !== ''): ?>
        <div class="message <?= e($messageType) ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="panel">

        <h2>
            <?= $editCourse ? 'ویرایش دوره' : 'افزودن دوره جدید' ?>
        </h2>

        <form method="POST">

            <input type="hidden" name="csrf_token"
                value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="action" value="save_course">

            <?php if ($editCourse): ?>
                <input type="hidden" name="course_id"
                    value="<?= (int) $editCourse['id'] ?>">
            <?php endif; ?>

            <div class="form-grid">

                <div class="field">
                    <label>عنوان دوره</label>
                    <input type="text" name="title" maxlength="255"
                        required value="<?= e($editCourse['title'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>دسته‌بندی</label>
                    <input type="text" name="category" maxlength="100"
                        required value="<?= e($editCourse['category'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>قیمت (تومان)</label>
                    <input type="number" name="price" min="0" step="0.01"
                        required value="<?= e($editCourse['price'] ?? '0') ?>">
                </div>

                <div class="field">
                    <label>آدرس تصویر دوره</label>
                    <input type="text" name="thumbnail" maxlength="255"
                        value="<?= e($editCourse['thumbnail'] ?? '') ?>"
                        placeholder="مثلاً images/html.jpg">
                </div>

                <div class="field">
                    <label>وضعیت دوره</label>

                    <select name="status">
                        <option value="draft"
                            <?= ($editCourse['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                            پیش‌نویس
                        </option>

                        <option value="published"
                            <?= ($editCourse['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                            منتشرشده
                        </option>
                    </select>
                </div>

                <div class="field full">
                    <label>توضیحات دوره</label>
                    <textarea name="description"><?= e($editCourse['description'] ?? '') ?></textarea>
                </div>

            </div>

            <br>

            <button type="submit" class="primary">
                <?= $editCourse ? 'ذخیره تغییرات' : 'افزودن دوره' ?>
            </button>

            <?php if ($editCourse): ?>
                <a href="admin-courses.php" class="btn secondary">
                    لغو ویرایش
                </a>
            <?php endif; ?>

        </form>

    </section>

    <section class="panel">

        <h2>فهرست دوره‌ها</h2>

        <form method="GET" class="search-form">

            <input type="text" name="search"
                placeholder="جست‌وجوی عنوان یا دسته‌بندی..."
                value="<?= e($search) ?>">

            <button type="submit" class="primary">
                جست‌وجو
            </button>

        </form>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>عنوان</th>
                        <th>دسته‌بندی</th>
                        <th>قیمت</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($courses) > 0): ?>

                    <?php foreach ($courses as $course): ?>

                        <tr>

                            <td><?= (int) $course['id'] ?></td>

                            <td><?= e($course['title']) ?></td>

                            <td><?= e($course['category']) ?></td>

                            <td>
                                <?= number_format((float) $course['price']) ?>
                                تومان
                            </td>

                            <td>
                                <?php if ($course['status'] === 'published'): ?>
                                    <span class="status published">منتشرشده</span>
                                <?php else: ?>
                                    <span class="status draft">پیش‌نویس</span>
                                <?php endif; ?>
                            </td>

                            <td>

                                <div class="actions">

                                    <a class="btn edit"
                                        href="admin-courses.php?edit=<?= (int) $course['id'] ?>">
                                        ویرایش
                                    </a>

                                    <form method="POST">

                                        <input type="hidden" name="csrf_token"
                                            value="<?= e(csrf_token()) ?>">

                                        <input type="hidden" name="action"
                                            value="toggle_status">

                                        <input type="hidden" name="course_id"
                                            value="<?= (int) $course['id'] ?>">

                                        <button type="submit" class="toggle">
                                            <?= $course['status'] === 'published'
                                                ? 'پیش‌نویس کردن'
                                                : 'انتشار دوره' ?>
                                        </button>

                                    </form>

                                    <form method="POST"
                                        onsubmit="return confirm('از حذف این دوره مطمئن هستید؟');">

                                        <input type="hidden" name="csrf_token"
                                            value="<?= e(csrf_token()) ?>">

                                        <input type="hidden" name="action"
                                            value="delete_course">

                                        <input type="hidden" name="course_id"
                                            value="<?= (int) $course['id'] ?>">

                                        <button type="submit" class="delete">
                                            حذف
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            دوره‌ای پیدا نشد.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>