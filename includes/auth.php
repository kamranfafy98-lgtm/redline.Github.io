
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);

    session_start();
}

require_once __DIR__ . '/../config/database.php';

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        );
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return is_logged_in() ? (int) $_SESSION['user_id'] : null;
}
