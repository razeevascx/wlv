<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/config/database.php';

$columns = array_column($mysqli->query('SHOW COLUMNS FROM videogames')->fetch_all(MYSQLI_ASSOC), 'Field');
if (!in_array('rawg_id', $columns, true)) {
    $mysqli->query('ALTER TABLE videogames ADD rawg_id INT UNSIGNED NULL UNIQUE');
}
$mysqli->query(file_get_contents(dirname(__DIR__) . '/database/rawg-cache.sql'));
echo "RAWG cache is ready in the existing database.\n";

foreach (explode(';', file_get_contents(dirname(__DIR__) . '/database/collections.sql')) as $sql) {
    if (trim($sql) !== '') {
        $mysqli->query($sql);
    }
}
if (!in_array('owner_id', $columns, true)) {
    $mysqli->query('ALTER TABLE videogames ADD owner_id INT UNSIGNED NULL, ADD FOREIGN KEY (owner_id) REFERENCES users(id)');
}
echo "Accounts and collections are ready.\n";
