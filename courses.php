
<?php
require_once __DIR__ . '/includes/auth.php';

// دریافت دوره‌های منتشرشده از دیتابیس
try {
    $stmt = $pdo->prepare(
        "SELECT id, title, description, price, category, thumbnail
         FROM courses
         WHERE status = ?
         ORDER BY created_at DESC"
    );

    $stmt->execute(['published']);
    $courses = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $courses = [];
    $db_error = true;
}

// نام فارسی دسته‌بندی‌ها
$categories = [
    'programming' => 'برنامه‌نویسی',
    'design' => 'طراحی',
    'language' => 'زبان',
    'business' => 'کسب‌وکار'
];

// آیکون پیش‌فرض دسته‌ها
$icons = [
    'programming' => '💻',
    'design' => '🎨',
    'language' => '🌍',
    'business' => '📈'
];

// تبدیل اعداد قیمت
function formatPrice($price) {
    return number_format((float)$price, 0, '.', '٬');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>دوره‌های آموزشی | NOVA</title>

    <link rel="stylesheet" href="cstyle.css">
    <script src="js/app.js" defer></script>
</head>

<body>

<header class="header">

    <a class="logo" href="index.php">NOVA</a>

    <nav>
        <a href="index.php">خانه</a>
        <a class="active" href="courses.php">دوره‌ها</a>
        <a href="shop.php">فروشگاه</a>
    </nav>

    <div class="header-actions">

        <?php if (is_logged_in()): ?>

            <span>
                خوش آمدی،
                <?= e($_SESSION['user_name']) ?>
            </span>

            <form action="logout.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <button class="btn" type="submit">خروج</button>
            </form>

        <?php else: ?>

            <a href="login.php">ورود</a>
            <a class="btn" href="register.php">ثبت‌نام</a>

        <?php endif; ?>

    </div>

</header>

<main class="container">

    <section class="page-heading">

        <span class="eyebrow">یادگیری را شروع کن</span>

        <h1>دوره‌های آموزشی NOVA</h1>

        <p>
            مهارت‌های جدید یاد بگیر و قدم بعدی مسیرت را بساز.
        </p>

    </section>

    <section class="filters">

        <input
            id="courseSearch"
            type="search"
            placeholder="جست‌وجوی دوره..."
        >

        <select id="courseCategory">

            <option value="all">همه دسته‌ها</option>
            <option value="programming">برنامه‌نویسی</option>
            <option value="design">طراحی</option>
            <option value="language">زبان</option>
            <option value="business">کسب‌وکار</option>

        </select>

    </section>

    <section class="card-grid" id="courseGrid">

        <?php if (!empty($courses)): ?>

            <?php foreach ($courses as $course): ?>

                <?php
                    $category = strtolower(trim($course['category'] ?? ''));

                    $categoryNames = [
                        'برنامه‌نویسی' => 'programming',
                        'طراحی' => 'design',
                        'زبان' => 'language',
                        'کسب‌وکار' => 'business'
                    ];

                    if (isset($categoryNames[$category])) {
                        $category = $categoryNames[$category];
                    }

                    $categoryName = $categories[$category] ?? 'عمومی';
                    $icon = $icons[$category] ?? '📚';
                ?>

                <article
                    class="item-card"
                    data-category="<?= e($category) ?>"
                >

                    <div class="item-cover">

                        <?php if (!empty($course['thumbnail'])): ?>

                            <img
                                src="<?= e($course['thumbnail']) ?>"
                                alt="<?= e($course['title']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <?= $icon ?>

                        <?php endif; ?>

                    </div>

                    <div class="item-content">

                        <span class="tag">
                            <?= e($categoryName) ?>
                        </span>

                        <h3><?= e($course['title']) ?></h3>

                        <p>
                            <?= e($course['description']) ?>
                        </p>

                        <div class="item-footer">

                            <strong>
                                <?php if ((float)$course['price'] <= 0): ?>

                                    رایگان

                                <?php else: ?>

                                    <?= formatPrice($course['price']) ?> تومان

                                <?php endif; ?>
                            </strong>

                            <a href="course-details.php?id=<?= (int)$course['id'] ?>">
                                مشاهده دوره
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="empty-state">
                <?php if (!empty($db_error)): ?>

                    در دریافت اطلاعات دوره‌ها مشکلی پیش آمده است.

                <?php else: ?>

                    هنوز دوره منتشرشده‌ای ثبت نشده است.

                <?php endif; ?>
            </p>

        <?php endif; ?>

    </section>

    <p id="emptyCourses" class="empty-state" hidden>
        دوره‌ای با این مشخصات پیدا نشد.
    </p>

</main>

<footer class="footer">
    © تمام حقوق برای NOVA محفوظ است.
</footer>

</body>
</html>
