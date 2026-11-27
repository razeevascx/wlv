<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/support/view.php';

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true, 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach ($_POST as $value) {
        if (!is_string($value)) {
            http_response_code(400);
            exit('Invalid form input.');
        }
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(400);
        exit('Invalid form token.');
    }
}

function redirect(string $location): never
{
    header("Location: " . app_base_path() . "/" . ltrim($location, "/"));
    exit;
}

function require_user(): int
{
    if (empty($_SESSION['user_id'])) redirect('index.php?auth=login');
    return $_SESSION['user_id'];
}
