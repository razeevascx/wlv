<?php

require_once __DIR__ . '/config/environment.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

$basePath = app_base_path();
if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
    $path = substr($path, strlen($basePath)) ?: '/';
}

// Only public assets are served directly by the development server.
if (PHP_SAPI === 'cli-server' && str_starts_with($path, '/public/')) {
    $file = realpath(__DIR__ . $path);
    if ($file && str_starts_with($file, __DIR__ . '/public/') && is_file($file)
        && in_array(pathinfo($file, PATHINFO_EXTENSION), ['css', 'js', 'svg'], true)) {
        return false;
    }
}

$routes = [
    '/captcha.php' => 'captcha',
    '/login.php' => 'login',
    '/signup.php' => 'signup',
    '/logout.php' => 'logout',
    '/my-games.php' => 'my-games',
    '/collections' => 'collections',
    '/collections.php' => 'collections',
    '/' => 'home',
    '/index.php' => 'home',
    '/filter.php' => 'filter',
    '/add-game-form.php' => 'add-game-form',
    '/add-game.php' => 'add-game',
    '/ajax.php' => 'ajax',
    '/rawg.php' => 'rawg',
    '/delete-game.php' => 'delete-game',
    '/edit-game.php' => 'edit-game',
    '/game-details.php' => 'game-details',
    '/search-games.php' => 'search-games',
    '/update-game.php' => 'update-game',
    '/robots.txt' => 'robots',
    '/sitemap.xml' => 'sitemap',
];

require __DIR__ . '/app/controllers/' . ($routes[$path] ?? '404') . '.php';
