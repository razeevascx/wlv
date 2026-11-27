<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_once dirname(__DIR__) . '/support/rawg.php';
$remoteId = filter_input(INPUT_GET, 'rawg_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && !$remoteId) require __DIR__ . '/404.php';

$statement = $mysqli->prepare($remoteId ? 'SELECT * FROM videogames WHERE rawg_id = ?' : 'SELECT * FROM videogames WHERE game_id = ?');
$lookup = $remoteId ?: $id;
$statement->bind_param('i', $lookup);
$statement->execute();
$game = $statement->get_result()->fetch_assoc();
if ($game) {
    $game['external'] = rawg_game_data($game);
} elseif ($remoteId) {
    try {
        $external = rawg_details($remoteId);
    } catch (RuntimeException) {
        $external = rawg_cached_game($remoteId, true);
    }
    if (!$external) require __DIR__ . '/404.php';
    $game = [
        'game_id' => null,
        'rawg_id' => $remoteId,
        'game_name' => $external['name'],
        'game_description' => $external['description'],
        'released_date' => $external['released'],
        'rating' => $external['rating'],
        'external' => $external,
    ];
} else {
    require __DIR__ . '/404.php';
}

$collections = [];
if ($game['game_id'] && isset($_SESSION['user_id'])) {
    $collections = $mysqli->execute_query(
        'SELECT c.id, c.name, m.game_id IS NOT NULL AS saved
         FROM collections c LEFT JOIN collection_games m ON m.collection_id = c.id AND m.game_id = ?
         WHERE c.owner_id = ? ORDER BY c.name, c.id',
        [$game['game_id'], $_SESSION['user_id']]
    )->fetch_all(MYSQLI_ASSOC);
}

render_template('game-details.html', [
    'game' => $game,
    'collections' => $collections,
    'csrf_token' => csrf_token(),
    'flash_toast' => (function (): ?array {
        $flash = $_SESSION['flash_toast'] ?? null;
        unset($_SESSION['flash_toast']);
        return $flash;
    })(),
]);
