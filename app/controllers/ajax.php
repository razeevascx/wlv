<?php

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$search = trim($_GET['search'] ?? '');
$search = substr($search, 0, 100);
$like = "%{$search}%";

$statement = $mysqli->prepare(
    'SELECT game_id, game_name FROM videogames WHERE game_name LIKE ? ORDER BY game_name LIMIT 10'
);
$statement->bind_param('s', $like);
$statement->execute();

echo json_encode($statement->get_result()->fetch_all(MYSQLI_ASSOC), JSON_THROW_ON_ERROR);
