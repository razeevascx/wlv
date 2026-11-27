<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/support/rawg.php';

$owner = require_user();
$games = $mysqli->execute_query(
    'SELECT game_id, game_name, game_description, released_date, rating, rawg_id
     FROM videogames WHERE owner_id = ? ORDER BY game_id DESC', [$owner]
)->fetch_all(MYSQLI_ASSOC);
foreach ($games as &$game) {
    $game['external'] = $game['rawg_id'] ? rawg_cached_game((int) $game['rawg_id']) : null;
}
unset($game);
$flash = $_SESSION['flash_toast'] ?? null;
unset($_SESSION['flash_toast']);
render_template('my-games.html', ['games' => $games, 'my_games' => true, 'flash_toast' => $flash]);
