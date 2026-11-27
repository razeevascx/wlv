<?php
require_once __DIR__ . '/environment.php';

try {
    $mysqli = new mysqli(
        app_env('DB_HOST', '127.0.0.1'),
        app_env('DB_USER', '2463090'),
        app_env('DB_PASSWORD', '2463090'),
        app_env('DB_NAME', 'db2463090'),
        (int) app_env('DB_PORT', '3307')
    );
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}
