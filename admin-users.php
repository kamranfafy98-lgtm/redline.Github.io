<?php
require_once __DIR__ . '/includes/auth.php';

// فقط مدیر اجازه ورود دارد
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$checkAdmin = $pdo->prepare(
    "SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1"
);
$checkAdmin->execute([current_user_id()]);
$admin = $checkAdmin->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = 'success';

// انجام عملیات مدیریت کاربران
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        exit('درخواست نامعتبر است. صفحه را تازه‌سازی کنید.');
    }

    $action = $_POST['action'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (!$userId || $userId < 1) {
        $message = 'شناسه کاربر نامعتبر است.';
        $messageType = 'error';

    } elseif ($userId === current_user_id()) {
        $message = 'امکان تغییر نقش یا حذف حساب خودتان وجود ندارد.';
        $messageType = 'error';

    } else {

        $targetStmt = $pdo->prepare(
            "SELECT id, name, role FROM users WHERE id = ? LIMIT 1"
        );
        $targetStmt->execute([$userId]);
        $targetUser = $targetStmt->fetch();

        if (!$targetUser) {
            $message = 'کاربر موردنظر پیدا نشد.';
            $messageType = 'error';

        } else {

            // تغییر نقش
            if ($action === 'change_role') {

                $newRole = $_POST['role'] ?? '';

                if (!in_array($newRole, ['user', 'admin'], true)) {
                    $message = 'نقش انتخاب‌شده معتبر نیست.';
                    $messageType = 'error';

                } elseif (
                    $targetUser['role'] === 'admin'
                    && $newRole === 'user'
                ) {
                    $adminCount = (int) $pdo->query(
                        "SELECT COUNT(*) FROM users WHERE role = 'admin'"
                    )->fetchColumn();

                    if ($adminCount <= 1) {
                        $message = 'نمی‌توانید آخرین مدیر سایت را حذف کنید.';
                        $messageType = 'error';
                    }
                }

                if ($message === '') {
                    $update = $pdo->prepare(
                        "UPDATE users SET role = ? WHERE id = ?"
                    );
                    $update->execute([$newRole, $userId]);

                    $message = 'نقش کاربر با موفقیت تغییر کرد.';
                }
            }

            // حذف کاربر
            elseif ($action === 'delete_user') {

                if ($targetUser['role'] === 'admin') {
                    $adminCount = (int) $pdo->query(
                        "SELECT COUNT(*) FROM users WHERE role = 'admin'"
                    )->fetchColumn();

                    if ($adminCount <= 1) {
                        $message = 'امکان حذف آخرین مدیر سایت وجود ندارد.';
                        $messageType = 'error';
                    }
                }

                if ($message === '') {
                    try {
                        $pdo->beginTransaction();

                        // حذف ثبت‌نام‌های دوره کاربر
                        $deleteEnrollments = $pdo->prepare(
                            "DELETE FROM enrollments WHERE user_id = ?"
                        );
                        $deleteEnrollments->execute([$userId]);

                        // حذف کاربر
                        $deleteUser = $pdo->prepare(
                            "DELETE FROM users WHERE id = ?"
                        );
                        $deleteUser->execute([$userId]);

                        $pdo->commit();

                        $message = 'کاربر با موفقیت حذف شد.';

                    } catch (PDOException $ex) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        error_log($ex->getMessage());

                        $message = 'حذف کاربر انجام نشد. ممکن است اطلاعات دیگری به این حساب وابسته باشد.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

// جست‌وجوی کاربران
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT id, name, email, role, created_at
         FROM users
         WHERE name LIKE ? OR email LIKE ?
         ORDER BY id DESC"
    );

    $keyword = '%' . $search . '%';
    $stmt->execute([$keyword, $keyword]);

} else {
    $stmt = $pdo->query(
        "SELECT id, name, email, role, created_at
         FROM users
         ORDER BY id DESC"
    );
}

$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>مدیریت کاربران | NOVA</title>

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
            padding: 35px;
        }

        .header {
            background: white;
            padding: 23px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px #00000008;
        }

        .header h1 {
            font-size: 23px;
        }

        .content {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 4px 20px #00000008;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .search-box input {
            flex: 1;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
        }

        button {
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .search-btn {
            background: #555ee8;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 15px 12px;
            text-align: right;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        th {
            background: #f7f8fc;
            color: #555;
        }

        .role {
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .admin-role {
            background: #e9eaff;
            color: #4e55d8;
        }

        .user-role {
            background: #e5f8ed;
            color: #25804b;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions form {
            display: inline-flex;
        }

        .change-btn {
            background: #555ee8;
            color: white;
            padding: 9px 12px;
            border-radius: 7px;
        }

        .delete-btn {
            background: #b9384c;
            color: white;
            padding: 9px 12px;
            border-radius: 7px;
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
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="logo">NOVA</div>

    <a href="admin.php">🏠 داشبورد مدیریت</a>
    <a href="admin-users.php" class="active">👥 مدیریت کاربران</a>
    <a href="courses.php">📚 مشاهده دوره‌ها</a>
    <a href="index.php">🌐 مشاهده سایت</a>

</aside>

<main class="main">

    <header class="header">
        <h1>مدیریت کاربران</h1>
    </header>

    <section class="content">

        <?php if ($message !== ''): ?>
            <div class="message <?= e($messageType) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="search-box">

            <input
                type="text"
                name="search"
                placeholder="جست‌وجو با نام یا ایمیل..."
                value="<?= e($search) ?>"
            >

            <button type="submit" class="search-btn">
                جست‌وجو
            </button>

        </form>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>نام</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>تاریخ عضویت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($users) > 0): ?>

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <td><?= (int) $user['id'] ?></td>

                            <td><?= e($user['name']) ?></td>

                            <td><?= e($user['email']) ?></td>

                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="role admin-role">مدیر</span>
                                <?php else: ?>
                                    <span class="role user-role">کاربر عادی</span>
                                <?php endif; ?>
                            </td>

                            <td><?= e($user['created_at']) ?></td>

                            <td>

                                <?php if ((int) $user['id'] !== current_user_id()): ?>

                                    <div class="actions">

                                        <form method="POST">

                                            <input type="hidden" name="csrf_token"
                                                value="<?= e(csrf_token()) ?>">

                                            <input type="hidden" name="action"
                                                value="change_role">

                                            <input type="hidden" name="user_id"
                                                value="<?= (int) $user['id'] ?>">

                                            <input type="hidden" name="role"
                                                value="<?= $user['role'] === 'admin' ? 'user' : 'admin' ?>">

                                            <button type="submit" class="change-btn">
                                                <?= $user['role'] === 'admin'
                                                    ? 'تبدیل به کاربر'
                                                    : 'ارتقا به مدیر' ?>
                                            </button>

                                        </form>

                                        <form method="POST"
                                            onsubmit="return confirm('آیا از حذف این کاربر مطمئن هستید؟ ثبت‌نام‌های دوره او نیز حذف می‌شوند.');">

                                            <input type="hidden" name="csrf_token"
                                                value="<?= e(csrf_token()) ?>">

                                            <input type="hidden" name="action"
                                                value="delete_user">

                                            <input type="hidden" name="user_id"
                                                value="<?= (int) $user['id'] ?>">

                                            <button type="submit" class="delete-btn">
                                                حذف
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <span>حساب شما</span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            کاربری پیدا نشد.
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