
<?php
require_once __DIR__ . '/includes/auth.php';

// بررسی شناسه دوره
$courseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$courseId || $courseId < 1) {
    http_response_code(404);
    exit('شناسه دوره معتبر نیست.');
}

// دریافت اطلاعات دوره از دیتابیس
$stmt = $pdo->prepare(
    "SELECT id, title, description, price, category, thumbnail
     FROM courses
     WHERE id = ? AND status = 'published'
     LIMIT 1"
);

$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit('دوره موردنظر پیدا نشد.');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$price = number_format((float)$course['price']);
?>

<?php
$enrollMessage = '';

if (isset($_GET['enroll'])) {
    $messages = [
        'success' => 'ثبت‌نام شما با موفقیت انجام شد.',
        'exists' => 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.',
        'error' => 'ثبت‌نام انجام نشد. دوباره تلاش کنید.'
    ];

    $enrollMessage = $messages[$_GET['enroll']] ?? '';
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($course['title']) ?> | NOVA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            font-family: Tahoma, sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 25px #0000000d;
        }

        .course-image {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            border-radius: 12px;
        }

        h1 {
            margin-top: 25px;
        }

        .description {
            line-height: 2.2;
            color: #555;
        }

        .price {
            margin: 25px 0;
            font-size: 22px;
            font-weight: bold;
            color: #16865b;
        }

        a {
            display: inline-block;
            padding: 13px 22px;
            margin: 5px;
            border-radius: 8px;
            text-decoration: none;
            background: #171717;
            color: white;
        }

        a.back {
            background: #e9e9e9;
            color: #222;
        }
    </style>
</head>

<body>

<div class="container">

    <?php if (!empty($course['thumbnail'])): ?>
        <img
            class="course-image"
            src="<?= e($course['thumbnail']) ?>"
            alt="<?= e($course['title']) ?>"
        >
    <?php endif; ?>

    <h1><?= e($course['title']) ?></h1>

    <p class="description">
        <?= nl2br(e($course['description'])) ?>
    </p>

    <div class="price">
        قیمت: <?= $price ?> تومان
    </div>
	<?php if ($enrollMessage): ?>
    <p style="color: #16865b; margin: 20px 0;">
        <?= e($enrollMessage) ?>
    </p>
<?php endif; ?>

<?php if (is_logged_in()): ?>

    <form action="enroll.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="course_id"
            value="<?= (int)$course['id'] ?>"
        >

        <button type="submit">
            ثبت‌نام در دوره
        </button>

    </form>

<?php else: ?>

    <a href="login.php">
        برای ثبت‌نام وارد حساب شوید
    </a>

<?php endif; ?>

    <a href="courses.php" class="back">
        بازگشت به دوره‌ها
    </a>

    <?php if (is_logged_in()): ?>
        <a href="courses.php">
            بازگشت به پنل دوره‌ها
        </a>
    <?php else: ?>
        <a href="login.php">
            ورود به حساب کاربری
        </a>
    <?php endif; ?>

</div>

</body>
</html>
