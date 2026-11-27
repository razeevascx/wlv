<?php

function app_env(string $name, string $default = ''): string
{
    static $local;
    if ($local === null) {
        $path = dirname(__DIR__) . '/.env';
        $local = is_file($path) ? (parse_ini_file($path, false, INI_SCANNER_RAW) ?: []) : [];
    }
    $value = getenv($name);
    return $value !== false ? $value : (string) ($local[$name] ?? $default);
}

function app_site_url(): string
{
    $url = trim(app_env('SITE_URL', 'http://localhost:8000'));
    $parts = parse_url($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)
        || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])) {
        return 'http://localhost:8000';
    }

    return rtrim($url, '/');
}

function app_base_path(): string
{
    if (PHP_SAPI === 'cli-server') return '';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(dirname($script), '/.');
}
