<?php

require_once dirname(__DIR__, 2) . '/config/environment.php';

function rawg_request(string $path, array $query = []): array
{
    global $mysqli;
    // Cache API responses for one day. Browsing the catalogue never calls RAWG.
    ksort($query);
    $cacheKey = hash('sha256', $path . '?' . http_build_query($query));
    $statement = $mysqli->prepare('SELECT payload FROM rawg_cache WHERE cache_key = ? AND expires_at > UTC_TIMESTAMP()');
    $statement->bind_param('s', $cacheKey);
    $statement->execute();
    if ($row = $statement->get_result()->fetch_assoc()) {
        if (PHP_SAPI !== 'cli') header('X-RAWG-Cache: HIT');
        return json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
    }
    $key = app_env('RAWG_API_KEY');
    if ($key === '') {
        throw new RuntimeException('Game lookup is unavailable. You can still add a game manually.', 503);
    }
    $query['key'] = $key;
    $curl = curl_init('https://api.rawg.io/api/' . $path . '?' . http_build_query($query));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'VideogameCatalogue/1.0',
    ]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($body === false || $status !== 200) {
        throw new RuntimeException('RAWG could not be reached. Please try again or add the game manually.', 502);
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('RAWG returned an unreadable response. Please try again.', 502);
    }
    $payload = json_encode($data, JSON_THROW_ON_ERROR);
    $statement = $mysqli->prepare(
        'INSERT INTO rawg_cache (cache_key, payload, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY))
         ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at)'
    );
    $statement->bind_param('ss', $cacheKey, $payload);
    $statement->execute();
    if (PHP_SAPI !== 'cli') header('X-RAWG-Cache: MISS');
    return $data;
}

function rawg_cached_game(int $id, bool $includeScreenshots = false): ?array
{
    global $mysqli;
    $cacheKey = hash('sha256', 'games/' . $id . '?');
    $statement = $mysqli->prepare('SELECT payload FROM rawg_cache WHERE cache_key = ?');
    $statement->bind_param('s', $cacheKey);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    // Keep saved game metadata available even after its search cache expires.
    if (!$row) return null;
    $game = rawg_normalize(json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR));
    $game['screenshots'] = [];
    if ($includeScreenshots) $game['screenshots'] = rawg_screenshots($id, true);
    return $game;
}

function rawg_image(?string $url): ?string
{
    $parts = parse_url($url ?? '');
    return ($parts['scheme'] ?? '') === 'https' && ($parts['host'] ?? '') === 'media.rawg.io'
        ? $url : null;
}

function rawg_match(array $saved, array $results): ?array
{
    $normalize = static fn(string $title): string => preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($title));
    foreach ($results as $result) {
        if ($normalize($result['name'] ?? '') === $normalize($saved['game_name'])
            && substr($result['released'] ?? '', 0, 4) === substr($saved['released_date'], 0, 4)) {
            return $result;
        }
    }
    return null;
}

function rawg_game_data(array $saved): ?array
{
    global $mysqli;
    $rawgId = $saved['rawg_id'] ? (int) $saved['rawg_id'] : null;
    try {
        if (!$rawgId) {
            $response = rawg_request('games', ['search' => mb_strtolower($saved['game_name']), 'page_size' => 6]);
            $match = rawg_match($saved, $response['results'] ?? []);
            if (!$match) return null;
            $rawgId = (int) $match['id'];
        }
        $external = rawg_details($rawgId);
        if (!$saved['rawg_id']) {
            $statement = $mysqli->prepare('UPDATE videogames SET rawg_id = ? WHERE game_id = ? AND rawg_id IS NULL');
            $statement->bind_param('ii', $rawgId, $saved['game_id']);
            try {
                $statement->execute();
            } catch (mysqli_sql_exception $exception) {
                // A duplicate catalogue entry can still display the cached metadata.
                if ($exception->getCode() !== 1062) throw $exception;
            }
        }
        return $external;
    } catch (RuntimeException) {
        // An API outage must not prevent viewing a saved game.
        return $rawgId ? rawg_cached_game($rawgId, true) : null;
    }
}

function rawg_normalize(array $game): array
{
    $rating = (float) ($game['rating'] ?? 0);
    $top = (float) ($game['rating_top'] ?? 5);
    if ($top <= 0) $top = 5;
    $description = $game['description_raw'] ?? strip_tags(str_replace(
        ['</p>', '<br>', '<br/>', '<br />'], "\n", $game['description'] ?? ''
    ));
    $adultContent = preg_match('/\b(sex|porn|hentai|erotic|adult)\b/i', $game['name'] ?? '') === 1
        || mb_strtolower($game['esrb_rating']['name'] ?? '') === 'adults only';
    if ($adultContent) $description = '';
    $names = static fn(array $items): array => array_values(array_filter(array_column($items, 'name')));
    return [
        'id' => (int) $game['id'],
        'name' => mb_substr($game['name'] ?? '', 0, 150),
        'description' => mb_strcut(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, 5000, 'UTF-8'),
        'adult_content' => $adultContent,
        'released' => $game['released'] ?? '',
        'rating' => (int) round(min(10, max(0, $top > 0 ? $rating / $top * 10 : 0))),
        'cover' => rawg_image($game['background_image'] ?? null),
        'genres' => $names($game['genres'] ?? []),
        'platforms' => $names(array_column($game['platforms'] ?? [], 'platform')),
        'developers' => $names($game['developers'] ?? []),
        'publishers' => $names($game['publishers'] ?? []),
        'esrb' => $game['esrb_rating']['name'] ?? null,
        'playtime' => (int) ($game['playtime'] ?? 0),
        'ratings_count' => (int) ($game['ratings_count'] ?? 0),
        'metacritic' => $game['metacritic'] ?? null,
        'rawg_rating' => $rating,
        'rawg_rating_top' => $top,
        'rawg_url' => 'https://rawg.io/games/' . rawurlencode($game['slug'] ?? (string) $game['id']),
    ];
}

function rawg_details(int $id): array
{
    $game = rawg_normalize(rawg_request('games/' . $id));
    $game['screenshots'] = [];
    $game['screenshots'] = rawg_screenshots($id);
    return $game;
}


function rawg_screenshots(int $id, bool $cachedOnly = false): array
{
    global $mysqli;
    $images = [];
    $page = 1;
    do {
        $query = ['page' => $page, 'page_size' => 40];
        try {
            if ($cachedOnly) {
                $key = hash('sha256', 'games/' . $id . '/screenshots?' . http_build_query($query));
                $row = $mysqli->execute_query('SELECT payload FROM rawg_cache WHERE cache_key = ?', [$key])->fetch_assoc();
                if (!$row && $page === 1) {
                    $key = hash('sha256', 'games/' . $id . '/screenshots?page_size=4');
                    $row = $mysqli->execute_query('SELECT payload FROM rawg_cache WHERE cache_key = ?', [$key])->fetch_assoc();
                }
                if (!$row) break;
                $response = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
            } else {
                $response = rawg_request('games/' . $id . '/screenshots', $query);
            }
            foreach ($response['results'] ?? [] as $shot) {
                if ($url = rawg_image($shot['image'] ?? null)) $images[] = $url;
            }
        } catch (RuntimeException) {
            // Keep images from earlier pages when RAWG becomes unavailable.
            if ($page === 1 && !$cachedOnly) return rawg_screenshots($id, true);
            break;
        }
        $page++;
    } while (!empty($response['next']) && !empty($response['results']));
    return array_values(array_unique($images));
}
