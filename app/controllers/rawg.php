<?php

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/support/rawg.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    if (isset($_GET['id'])) {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            throw new RuntimeException('Please choose a valid game.', 422);
        }
        $game = rawg_details($id);
        echo json_encode($game, JSON_THROW_ON_ERROR);
    } else {
        $search = trim(preg_replace('/\s+/u', ' ', (string) ($_GET['search'] ?? '')) ?? '');
        if (mb_strlen($search) < 2 || mb_strlen($search) > 100) {
            throw new RuntimeException('Enter a game name between 2 and 100 characters.', 422);
        }
        $response = rawg_request('games', ['search' => mb_strtolower($search), 'page_size' => 6]);
        $results = array_map('rawg_normalize', $response['results'] ?? []);
        $results = array_values(array_filter($results, static fn(array $game): bool => !$game['adult_content']));
        echo json_encode(['results' => $results], JSON_THROW_ON_ERROR);
    }
} catch (RuntimeException $exception) {
    http_response_code(in_array($exception->getCode(), [422, 502, 503], true) ? $exception->getCode() : 502);
    echo json_encode(['error' => $exception->getMessage()]);
}
