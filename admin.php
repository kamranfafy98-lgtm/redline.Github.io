<?php
require_once __DIR__ . '/includes/auth.php';

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// بررسی نقش مدیر از دیتابیس
$stmt = $pdo->prepare(
    "SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1"
);
$stmt->execute([current_user_id()]);
$admin = $stmt->fetch();

// اگر کاربر وجود نداشت یا مدیر نبود
if (!$admin || $admin['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// دریافت آمار سایت
$totalUsers = (int) $pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$totalCourses = (int) $pdo->query(
    "SELECT COUNT(*) FROM courses"
)->fetchColumn();

$totalEnrollments = (int) $pdo->query(
    "SELECT COUNT(*) FROM enrollments"
)->fetchColumn();

$totalPublishedCourses = (int) $pdo->query(
    "SELECT COUNT(*) FROM courses WHERE status = 'published'"
)->fetchColumn();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت | NOVA</title>

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
            width: 250px;
            height: 100vh;
            background: #171b35;
            color: white;
            padding: 30px 20px;
        }

        .logo {
            font-size: 30px;
            font-weight: bold;
            color: #7c83ff;
            text-align: center;
            margin-bottom: 40px;
        }

        .sidebar a {
            display: block;
            text-decoration: none;
            color: #d5d7e8;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: .2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #343b68;
            color: white;
        }

        .main {
            margin-right: 250px;
            padding: 35px;
        }

        .topbar {
            background: white;
            padding: 22px 25px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px #00000008;
        }

        .topbar h1 {
            font-size: 23px;
        }

        .welcome {
            color: #666;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px #00000008;
        }

        .stat-card h3 {
            color: #777;
            font-size: 14px;
            margin-bottom: 18px;
        }

        .stat-card strong {
            font-size: 30px;
            color: #555ee8;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 25px;
            box-shadow: 0 4px 20px #00000008;
        }

        .section h2 {
            font-size: 19px;
            margin-bottom: 15px;
        }

        .section p {
            color: #777;
            line-height: 2;
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .quick-links a {
            text-decoration: none;
            background: #555ee8;
            color: white;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 14px;
        }

        .quick-links a:hover {
            background: #4149c5;
        }

        .logout {
            margin-top: 25px;
            width: 100%;
            border: none;
            cursor: pointer;
            background: #a83248;
            color: white;
            padding: 13px;
            border-radius: 9px;
            font-size: 14px;
        }

        @media (max-width: 1000px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-right: 0;
                padding: 18px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="logo">NOVA</div>

    <a href="admin.php" class="active">🏠 داشبورد مدیریت</a>
    <a href="index.php">🌐 مشاهده سایت</a>
	<a href="admin-users.php">📚 مدیریت کاربران‌</a>
	<a href="admin-products.php">🛍️ مدیریت محصولات</a>
    <a href="courses.php">📚 مشاهده دوره‌ها</a>
	<a href="admin-courses.php">📚 مدیریت دوره‌ها</a>
    <a href="dashboard.php">👤 پنل کاربری</a>

    <form action="logout.php" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >
        <button type="submit" class="logout">خروج از حساب</button>
    </form>

</aside>

<main class="main">

    <header class="topbar">
        <h1>داشبورد مدیریت</h1>

        <div class="welcome">
            خوش آمدید، <?= e($admin['name']) ?>
        </div>
    </header>

    <section class="stats">

        <div class="stat-card">
            <h3>تعداد کاربران</h3>
            <strong><?= $totalUsers ?></strong>
        </div>

        <div class="stat-card">
            <h3>تعداد کل دوره‌ها</h3>
            <strong><?= $totalCourses ?></strong>
        </div>

        <div class="stat-card">
            <h3>دوره‌های منتشرشده</h3>
            <strong><?= $totalPublishedCourses ?></strong>
        </div>

        <div class="stat-card">
            <h3>ثبت‌نام‌های دوره‌ها</h3>
            <strong><?= $totalEnrollments ?></strong>
        </div>

    </section>

    <section class="section">
        <h2>به پنل مدیریت NOVA خوش آمدید</h2>

        <p>
            از این بخش می‌توانید وضعیت کلی سایت را مشاهده کنید.
            در مراحل بعدی امکانات مدیریت کاربران، دوره‌ها،
            محصولات و سفارش‌ها را اضافه خواهیم کرد.
        </p>

        <div class="quick-links">
            <a href="index.php">مشاهده صفحه اصلی</a>
            <a href="courses.php">مشاهده دوره‌ها</a>
        </div>
    </section>

</main>

</body>
</html>