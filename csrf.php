<?php

// CSRF Token for security
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