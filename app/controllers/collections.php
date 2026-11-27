<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$owner = require_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'collection_id', FILTER_VALIDATE_INT);
    $game = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    if ($action !== 'create') {
        $owned = $mysqli->execute_query('SELECT id FROM collections WHERE id = ? AND owner_id = ?', [$id, $owner]);
        if (!$owned->num_rows) require __DIR__ . '/404.php';
    }
    switch ($action) {
        case 'create':
        case 'rename':
            $name = trim($_POST['name'] ?? '');
            if ($name === '' || mb_strlen($name) > 150) {
                http_response_code(422);
                exit('Enter a collection name of up to 150 characters.');
            }
            if ($action === 'create') {
                $mysqli->execute_query('INSERT INTO collections (owner_id, name) VALUES (?, ?)', [$owner, $name]);
                $id = $mysqli->insert_id;
            } else {
                $mysqli->execute_query('UPDATE collections SET name = ? WHERE id = ? AND owner_id = ?', [$name, $id, $owner]);
            }
            break;
        case 'add':
            $result = $mysqli->execute_query('SELECT game_id FROM videogames WHERE game_id = ?', [$game]);
            if (!$result->num_rows) require __DIR__ . '/404.php';
            $mysqli->execute_query('INSERT INTO collection_games (collection_id, game_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE game_id = collection_games.game_id', [$id, $game]);
            break;
        case 'remove':
            $mysqli->execute_query('DELETE FROM collection_games WHERE collection_id = ? AND game_id = ?', [$id, $game]);
            break;
        default:
            http_response_code(400);
            exit('Unknown collection action.');
    }
    redirect('collections.php?id=' . $id);
}

require_once dirname(__DIR__) . '/support/rawg.php';
$collections = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$selected = null;
$available = [];
$games = [];
if ($id !== null) {
    $selected = $mysqli->execute_query('SELECT id, name FROM collections WHERE id = ? AND owner_id = ?', [$id, $owner])->fetch_assoc();
    if (!$selected) require __DIR__ . '/404.php';
    $available = $mysqli->execute_query(
        'SELECT g.game_id, g.game_name FROM videogames g
         WHERE NOT EXISTS (SELECT 1 FROM collection_games c WHERE c.collection_id = ? AND c.game_id = g.game_id)
         ORDER BY g.game_name, g.game_id', [$id]
    )->fetch_all(MYSQLI_ASSOC);
    $games = $mysqli->execute_query('SELECT g.game_id, g.game_name, g.rating, g.rawg_id FROM collection_games c JOIN videogames g USING (game_id) WHERE c.collection_id = ? ORDER BY g.game_name', [$id])->fetch_all(MYSQLI_ASSOC);
    foreach ($games as &$game) {
        $game['external'] = $game['rawg_id'] ? rawg_cached_game((int) $game['rawg_id']) : null;
    }
    unset($game);
} else {
    $collections = $mysqli->execute_query(
        'SELECT c.id, c.name, COUNT(m.game_id) AS game_count, MIN(g.rawg_id) AS preview_id
         FROM collections c LEFT JOIN collection_games m ON m.collection_id = c.id
         LEFT JOIN videogames g ON g.game_id = m.game_id
         WHERE c.owner_id = ? GROUP BY c.id, c.name ORDER BY c.name, c.id', [$owner]
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($collections as &$collection) {
        $collection['cover'] = $collection['preview_id'] ? (rawg_cached_game((int) $collection['preview_id'])['cover'] ?? null) : null;
    }
    unset($collection);
}
render_template('collections.html', compact('collections', 'selected', 'games', 'available'));
