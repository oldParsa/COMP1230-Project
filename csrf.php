<?php

// CSRF Token for security
function accessDenied(): void {
    http_response_code(403);
    ?>
    <?php require "header.php"; ?>
    <h1>403</h1>
    <h2>You can only change your own study groups.</h2>
    <a href="index.php">Return to the home page</a>
    <?php require "footer.php"; ?>
    <?php

    exit;
}
function csrfToken(): string {
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function requireCsrf(): void {
    $csrf = $_POST['csrf_token'] ?? null;
    if (!is_string($csrf) || !hash_equals(csrfToken(), $csrf)) {
        http_response_code(403);
        exit("This form was not received from this website. Please try again later.");
    }
}