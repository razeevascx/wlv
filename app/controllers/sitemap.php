<?php

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
$baseUrl = app_site_url();
$locations = [$baseUrl . '/'];
$result = $mysqli->query('SELECT game_id FROM videogames ORDER BY game_id');
while ($game = $result->fetch_assoc()) {
    $locations[] = $baseUrl . '/game-details.php?id=' . rawurlencode((string) $game['game_id']);
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($locations as $location) {
    echo '  <url><loc>' . htmlspecialchars($location, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
}
echo "</urlset>\n";
