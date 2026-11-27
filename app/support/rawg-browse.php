<?php

require_once __DIR__ . '/rawg.php';

function rawg_reference(string $type, string $name): ?int
{
    $query = ['page_size' => 50];
    if (in_array($type, ['developers', 'publishers'], true)) $query['search'] = $name;
    $response = rawg_request($type, $query);
    foreach ($response['results'] ?? [] as $item) {
        if (mb_strtolower($item['name']) === mb_strtolower($name)) return (int) $item['id'];
    }
    return null;
}

function rawg_browse_query(array $filters): array
{
    $query = ['page_size' => 4, 'ordering' => match ($filters['sort']) {
        'title' => 'name', 'rating' => '-rating', default => '-released',
    }];
    if ($filters['name'] !== '') $query['search'] = $filters['name'];
    foreach (['genre' => 'genres', 'platform' => 'platforms'] as $field => $type) {
        if ($filters[$field] !== '') {
            $id = rawg_reference($type, $filters[$field]);
            if (!$id) throw new RuntimeException('This filter could not be matched on RAWG. Try another filter.');
            $query[$type] = $id;
        }
    }
    if ($filters['author'] !== '') {
        $id = rawg_reference('developers', $filters['author']);
        $type = 'developers';
        if (!$id) {
            $type = 'publishers';
            $id = rawg_reference($type, $filters['author']);
        }
        if (!$id) throw new RuntimeException('This developer or publisher could not be matched on RAWG.');
        $query[$type] = $id;
    }
    $start = $filters['from_year'] !== '' ? $filters['from_year'] . '-01-01' : '1970-01-01';
    $end = $filters['to_year'] !== '' ? $filters['to_year'] . '-12-31' : date('Y-m-d');
    if ($start > $end) throw new RuntimeException('The start year must not be later than the end year.');
    $query['dates'] = $start . ',' . $end;
    return $query;
}

function rawg_more_games(array $filters, int $offset): array
{
    global $mysqli;
    $query = rawg_browse_query($filters);
    $saved = $mysqli->query('SELECT rawg_id, game_name FROM videogames')->fetch_all(MYSQLI_ASSOC);
    $ids = array_column($saved, 'rawg_id');
    $names = array_map('mb_strtolower', array_column($saved, 'game_name'));
    $games = [];
    $more = true;
    // Bound work per click; an offset preserves unconsumed items within each API page.
    for ($requests = 0; $requests < 3 && count($games) < 4 && $more; $requests++) {
        $response = rawg_request('games', array_merge($query, ['page' => intdiv($offset, 4) + 1]));
        $items = $response['results'] ?? [];
        for ($index = $offset % 4; $index < count($items); $index++) {
            $offset++;
            $external = rawg_normalize($items[$index]);
            if ($external['adult_content']) continue;
            if (in_array($external['id'], $ids) || in_array(mb_strtolower($external['name']), $names, true)) continue;
            if ($filters['min_rating'] !== '' && $external['rating'] < (int) $filters['min_rating']) continue;
            $games[] = [
                'game_id' => 'rawg-' . $external['id'],
                'game_name' => $external['name'],
                'rating' => $external['rating'],
                'external' => $external,
                'details_url' => 'game-details.php?rawg_id=' . $external['id'],
            ];
            if (count($games) === 4) break;
        }
        $more = ($offset % 4 !== 0 && $offset % 4 < count($items)) || !empty($response['next']);
        if (!$items) $more = false;
    }
    return ['games' => $games, 'offset' => $offset, 'more' => $more];
}
