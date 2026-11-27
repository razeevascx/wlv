<?php

require_once dirname(__DIR__) . '/bootstrap.php';
$owner = require_user();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    require __DIR__ . '/404.php';
}

$statement = $mysqli->prepare('SELECT * FROM videogames WHERE game_id = ? AND owner_id = ?');
$statement->bind_param('ii', $id, $owner);
$statement->execute();
$game = $statement->get_result()->fetch_assoc();

if (!$game) {
    require __DIR__ . '/404.php';
}

render_template('edit-game.html', [
    'game' => $game,
    'csrf_token' => csrf_token(),
]);
