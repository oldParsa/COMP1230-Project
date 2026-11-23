<?php

// CSRF Token for security
function csrfToken(): string {
    if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken()) . '">';
}

function requireCsrf(): void {
    $csrf = $_POST['csrf'] ?? null;
    if (!is_string($csrf) || !hash_equals(csrfToken(), $csrf)) {
        http_response_code(403);
        exit("This form was not received from this website. Please try again later.");
    }
}