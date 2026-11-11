
<?php
require_once __DIR__ . '/includes/auth.php';

// فقط درخواست POST پذیرفته می‌شود
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('روش درخواست مجاز نیست.');
}

// بررسی ورود کاربر
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// بررسی CSRF
if (!verify_csrf()) {
    http_response_code(403);
    exit('درخواست معتبر نیست.');
}

// اعتبارسنجی شناسه دوره
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

if (!$courseId || $courseId < 1) {
    exit('شناسه دوره معتبر نیست.');
}

// بررسی وجود و انتشار دوره
$stmt = $pdo->prepare(
    "SELECT id FROM courses
     WHERE id = ? AND status = 'published'
     LIMIT 1"
);
$stmt->execute([$courseId]);

if (!$stmt->fetch()) {
    http_response_code(404);
    exit('دوره پیدا نشد.');
}

// ثبت‌نام کاربر
try {
    $stmt = $pdo->prepare(
        "INSERT INTO enrollments (user_id, course_id)
         VALUES (?, ?)"
    );

    $stmt->execute([
        current_user_id(),
        $courseId
    ]);

    header("Location: course-details.php?id=$courseId&enroll=success");
    exit;

} catch (PDOException $e) {

    // ثبت‌نام قبلی در همین دوره
    if ($e->getCode() === '23000') {
        header("Location: course-details.php?id=$courseId&enroll=exists");
        exit;
    }

    error_log($e->getMessage());

    header("Location: course-details.php?id=$courseId&enroll=error");
    exit;
}
