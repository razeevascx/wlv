<?php

function game_filter_options(array $games): array
{
    $options = ['author' => [], 'genre' => [], 'platform' => []];
    foreach ($games as $game) {
        $external = $game['external'] ?? [];
        foreach ([
            'author' => array_merge($external['developers'] ?? [], $external['publishers'] ?? []),
            'genre' => $external['genres'] ?? [],
            'platform' => $external['platforms'] ?? [],
        ] as $field => $values) {
            foreach ($values as $value) $options[$field][$value] = $value;
        }
    }
    foreach ($options as &$values) {
        natcasesort($values);
        $values = array_values($values);
    }
    return $options;
}

function game_genre_sections(array $games): array
{
    $sections = [];
    $unclassified = [];
    foreach ($games as $game) {
        $genres = array_unique($game['external']['genres'] ?? []);
        if (!$genres) $unclassified[] = $game;
        foreach ($genres as $genre) $sections[$genre][] = $game;
    }
    uksort($sections, 'strnatcasecmp');
    if ($unclassified) $sections['More games'] = $unclassified;
    return $sections;
}

function game_matches_filters(array $game, array $filters): bool
{
    if ($filters['name'] !== '' && mb_stripos($game['game_name'], $filters['name']) === false) return false;
    $year = (int) substr($game['released_date'], 0, 4);
    if ($filters['from_year'] !== '' && $year < (int) $filters['from_year']) return false;
    if ($filters['to_year'] !== '' && $year > (int) $filters['to_year']) return false;
    if ($filters['min_rating'] !== '' && $game['rating'] < (int) $filters['min_rating']) return false;
    $external = $game['external'] ?? [];
    foreach ([
        'author' => array_merge($external['developers'] ?? [], $external['publishers'] ?? []),
        'genre' => $external['genres'] ?? [],
        'platform' => $external['platforms'] ?? [],
    ] as $field => $values) {
        if ($filters[$field] !== '' && !in_array($filters[$field], $values, true)) return false;
    }
    return true;
}
