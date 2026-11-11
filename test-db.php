
<?php

require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query(
    "SELECT message FROM connection_test LIMIT 1"
);

$result = $stmt->fetch();

if ($result) {
    echo '<h2 style="color:green;direction:rtl">';
    echo htmlspecialchars($result['message']);
    echo '</h2>';
} else {
    echo 'اتصال برقرار است، اما اطلاعات آزمایشی پیدا نشد.';
}
