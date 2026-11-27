<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config/environment.php';

function render_template(string $template, array $data = []): never
{
    static $twig;

    $twig ??= new Twig\Environment(
        new Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/views'),
        ['autoescape' => 'html']
    );

    $accountError = $_SESSION['account_error'] ?? [];
    unset($_SESSION['account_error']);
    $data['account_error'] = $accountError;
    $data['app_base'] = app_base_path() . '/';
    $data['csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' https://media.rawg.io data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'nonce-" . $data['csp_nonce'] . "' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; connect-src 'self' https://api.rawg.io");
    echo $twig->render($template, $data + ['user_id' => $_SESSION['user_id'] ?? null, 'csrf_token' => function_exists('csrf_token') ? csrf_token() : null]);
    exit;
}
