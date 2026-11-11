
<?php
require_once __DIR__ . '/includes/auth.php';

// فقط کاربران واردشده اجازه مشاهده پنل را دارند
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// دریافت اطلاعات کاربر
$userStmt = $pdo->prepare(
    "SELECT id, name, email, created_at
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$userStmt->execute([current_user_id()]);
$user = $userStmt->fetch();

if (!$user) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php');
    exit;
}

// دریافت دوره‌های ثبت‌نام‌شده کاربر
$courseStmt = $pdo->prepare(
    "SELECT
        courses.id,
        courses.title,
        courses.description,
        courses.price,
        courses.thumbnail,
        enrollments.created_at AS enrollment_date
     FROM enrollments
     INNER JOIN courses
        ON courses.id = enrollments.course_id
     WHERE enrollments.user_id = ?
     ORDER BY enrollments.created_at DESC"
);

$courseStmt->execute([current_user_id()]);
$enrolledCourses = $courseStmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>پنل کاربری | NOVA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        header {
            background: #171717;
            color: white;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header a {
            color: white;
            text-decoration: none;
            margin: 5px;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome,
        .profile,
        .courses {
            background: white;
            padding: 25px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px #00000008;
        }

        .welcome h1 {
            margin-top: 0;
        }

        .profile p {
            line-height: 2;
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .course-card {
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            padding-bottom: 18px;
        }

        .course-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .course-card h3,
        .course-card p,
        .course-card a {
            margin-right: 15px;
            margin-left: 15px;
        }

        .course-card a,
        .btn {
            display: inline-block;
            background: #171717;
            color: white;
            padding: 11px 17px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .empty {
            background: #f5f6fa;
            padding: 25px;
            border-radius: 10px;
            line-height: 2;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #777;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px;
            }

            .container {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <strong>NOVA</strong>

    <nav>
        <a href="index.php">صفحه اصلی</a>
        <a href="courses.php">دوره‌ها</a>
        <a href="shop.php">فروشگاه</a>
    </nav>
</header>

<main class="container">

    <section class="welcome">
        <h1>
            سلام <?= e($user['name']) ?> 👋
        </h1>

        <p>
            به پنل کاربری NOVA خوش آمدی.
        </p>
    </section>

    <section class="profile">
        <h2>اطلاعات حساب کاربری</h2>

        <p>
            نام:
            <?= e($user['name']) ?>
        </p>

        <p>
            ایمیل:
            <?= e($user['email']) ?>
        </p>

        <p>
            تاریخ عضویت:
            <?= e($user['created_at']) ?>
        </p>
    </section>

    <section class="courses">

        <h2>دوره‌های من</h2>

        <?php if (count($enrolledCourses) > 0): ?>

            <div class="course-grid">

                <?php foreach ($enrolledCourses as $course): ?>

                    <article class="course-card">

                        <?php if (!empty($course['thumbnail'])): ?>

                            <img
                                src="<?= e($course['thumbnail']) ?>"
                                alt="<?= e($course['title']) ?>"
                            >

                        <?php endif; ?>

                        <h3><?= e($course['title']) ?></h3>

                        <p>
                            قیمت:
                            <?= number_format((float)$course['price']) ?>
                            تومان
                        </p>

                        <p>
                            تاریخ ثبت‌نام:
                            <?= e($course['enrollment_date']) ?>
                        </p>

                        <a href="course-details.php?id=<?= (int)$course['id'] ?>">
                            مشاهده دوره
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty">
                هنوز در هیچ دوره‌ای ثبت‌نام نکرده‌ای.

                <br>

                <a href="courses.php" class="btn">
                    مشاهده دوره‌ها
                </a>
            </div>

        <?php endif; ?>

    </section>

    <a href="index.php" class="btn">
        بازگشت به صفحه اصلی
    </a>
	<a href="my-orders.php" class="btn "> سفارش های من </a>

</main>

<footer>
    تمامی حقوق برای NOVA محفوظ است.
</footer>

</body>
</html>
