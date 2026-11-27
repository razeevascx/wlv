<?php

require_once __DIR__ . '/rawg.php';
require_once __DIR__ . '/game-filters.php';

function catalogue_data(): array
{
    global $mysqli;
    $filters = [];
    foreach (['name', 'author', 'genre', 'platform'] as $field) {
        $value = filter_input(INPUT_GET, $field);
        $filters[$field] = is_string($value) ? trim($value) : '';
    }
    foreach (['from_year', 'to_year', 'min_rating'] as $field) {
        $range = $field === 'min_rating' ? ['min_range' => 0, 'max_range' => 10] : ['min_range' => 1900, 'max_range' => 2100];
        $value = filter_input(INPUT_GET, $field, FILTER_VALIDATE_INT, ['options' => $range]);
        $filters[$field] = is_int($value) ? $value : '';
    }
    $sort = filter_input(INPUT_GET, 'sort');
    $sort = in_array($sort, ['newest', 'title', 'rating'], true) ? $sort : 'newest';

    $games = $mysqli->query('SELECT game_id, game_name, game_description, released_date, rating, rawg_id FROM videogames ORDER BY released_date DESC, game_name')->fetch_all(MYSQLI_ASSOC);
    foreach ($games as &$game) {
        $game['external'] = $game['rawg_id'] ? rawg_cached_game((int) $game['rawg_id']) : null;
    }
    unset($game);
    $latestGames = array_slice(array_values(array_filter($games, static fn(array $game): bool => $game['released_date'] <= date('Y-m-d'))), 0, 4);
    $filterOptions = game_filter_options($games);
    $games = array_values(array_filter($games, static fn(array $game): bool => game_matches_filters($game, $filters)));
    if ($sort === 'title') {
        usort($games, static fn(array $a, array $b): int => strnatcasecmp($a['game_name'], $b['game_name']));
    } elseif ($sort === 'rating') {
        usort($games, static fn(array $a, array $b): int => ($b['rating'] <=> $a['rating']) ?: strnatcasecmp($a['game_name'], $b['game_name']));
    }

    $view = filter_input(INPUT_GET, 'view') === 'list' ? 'list' : 'grid';
    $genreSections = game_genre_sections($games);
    if ($filters['genre'] !== '') {
        $genreSections = array_intersect_key($genreSections, [$filters['genre'] => true]);
    }
    $viewLinks = [];
    foreach (['grid', 'list'] as $layout) {
        $viewLinks[$layout] = 'index.php?' . http_build_query(array_merge($filters, ['sort' => $sort, 'view' => $layout]));
    }

    return array_merge($filters, [
        'sort' => $sort,
        'view' => $view,
        'view_links' => $viewLinks,
        'filter_options' => $filterOptions,
        'games' => $games,
        'genre_sections' => $genreSections,
        'latest_games' => $latestGames,
        'add_game_modal' => true,
        'csrf_token' => csrf_token(),
    ]);
}
