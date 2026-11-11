
<?php

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $error = 'درخواست معتبر نیست. دوباره تلاش کن.';
    } else {

        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare(
            'SELECT id, name, email, password
             FROM users
             WHERE email = ?'
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header('Location: index.php');
            exit;

        } else {
            $error = 'ایمیل یا رمز عبور اشتباه است.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود | NOVA</title>
<link rel="stylesheet" href="/nova/assets/css/auth.css?v=2">
<link rel="stylesheet" href="/nova/assets/css/auth.css?v=3">
<link rel="stylesheet" href="/nova/assets/css/auth.css?v=4">
</head>
<body>

<main class="nova-auth-page">
    <a class="nova-logo" href="index.php">NOVA</a>

    <form class="nova-auth-card" method="POST">
        <h1>خوش برگشتی!</h1>
        <p>برای ورود اطلاعاتت را وارد کن.</p>

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars(csrf_token()) ?>">

        <?php if ($error): ?>
            <p class="nova-error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <label>ایمیل</label>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label>رمز عبور</label>
        <input type="password" name="password" required>

        <button class="nova-submit" type="submit">ورود به حساب</button>

        <p class="nova-switch">
            حساب نداری؟
            <a href="register.php">ثبت‌نام کن</a>
        </p>
    </form>
</main>

</body>
</html>
