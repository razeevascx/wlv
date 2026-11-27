<?php

require_once dirname(__DIR__) . '/bootstrap.php';
$owner = require_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(422);
    exit('Invalid game.');
}
$mysqli->begin_transaction();
try {
    $owned = $mysqli->execute_query(
        'SELECT game_id FROM videogames WHERE game_id = ? AND owner_id = ? FOR UPDATE',
        [$id, $owner]
    );
    if (!$owned->num_rows) {
        $mysqli->rollback();
        require __DIR__ . '/404.php';
    }
    $mysqli->execute_query('DELETE FROM collection_games WHERE game_id = ?', [$id]);
    $mysqli->execute_query('DELETE FROM videogames WHERE game_id = ? AND owner_id = ?', [$id, $owner]);
    $mysqli->commit();
} catch (Throwable $exception) {
    $mysqli->rollback();
    throw $exception;
}
$_SESSION['flash_toast'] = ['message' => 'Game deleted successfully.', 'type' => 'success'];
if (($_POST['return_to'] ?? '') === 'my-games') redirect('my-games.php');
redirect('index.php');
