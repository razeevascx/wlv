<?php

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/support/catalogue.php';

$data = catalogue_data();
if (isset($_GET['more'])) {
    require_once dirname(__DIR__) . '/support/rawg-browse.php';
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    try {
        $offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4000]]);
        if ($offset === false) throw new RuntimeException('Invalid page. Please refresh and try again.');
        $result = rawg_more_games($data, $offset ?? 0);
        $twig = new Twig\Environment(new Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/views'), ['autoescape' => 'html']);
        $html = '';
        foreach ($result['games'] as $game) {
            $html .= $twig->render('components/game-card.html', ['game' => $game, 'card_id_prefix' => 'more']);
        }
        echo json_encode(['html' => $html, 'count' => count($result['games']), 'offset' => $result['offset'], 'more' => $result['more']], JSON_THROW_ON_ERROR);
    } catch (RuntimeException $exception) {
        http_response_code(502);
        echo json_encode(['error' => $exception->getMessage()]);
    }
    exit;
}
$data['latest_games'] = [];
render_template('filter.html', $data);
