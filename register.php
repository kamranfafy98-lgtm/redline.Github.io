
<?php

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $error = 'درخواست معتبر نیست. صفحه را دوباره بارگذاری کن.';
    } else {

        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($name === '' || strlen($name) > 120) {
            $error = 'نام واردشده معتبر نیست.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'ایمیل معتبر نیست.';
        } elseif (strlen($password) < 8) {
            $error = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
        } elseif ($password !== $confirm) {
            $error = 'رمز عبور و تکرار آن یکسان نیستند.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE email = ?'
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'این ایمیل قبلاً ثبت شده است.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password)
                     VALUES (?, ?, ?)'
                );

                $stmt->execute([$name, $email, $hash]);

                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;

                header('Location: index.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام | NOVA</title>
	<link rel="stylesheet" href="/nova/assets/css/auth.css?v=2">
 <link rel="stylesheet" href="/nova/assets/css/auth.css?v=3">
 <link rel="stylesheet" href="/nova/assets/css/auth.css?v=4">
</head>
<body>

<main class="nova-auth-page">
    <a class="nova-logo" href="index.php">NOVA</a>

    <form class="nova-auth-card" method="POST">
        <h1>ساخت حساب NOVA</h1>
        <p>به جمع یادگیرندگان NOVA بپیوند.</p>

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars(csrf_token()) ?>">

        <?php if ($error): ?>
            <p class="nova-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <label>نام و نام خانوادگی</label>
        <input type="text" name="name" required maxlength="120"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

        <label>ایمیل</label>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>رمز عبور</label>
        <input type="password" name="password" required minlength="8">

        <label>تکرار رمز عبور</label>
        <input type="password" name="confirm_password"
               required minlength="8">

        <button class="nova-submit" type="submit">ایجاد حساب</button>

        <p class="nova-switch">
            حساب داری؟
            <a href="login.php">وارد شو</a>
        </p>
    </form>
</main>

</body>
</html>
