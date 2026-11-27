<?php

require_once dirname(__DIR__, 2) . '/config/environment.php';

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');
echo "User-agent: *\n";
echo "Disallow: /ajax.php\n";
echo "Disallow: /rawg.php\n";
echo "Disallow: /add-game.php\n";
echo "Disallow: /delete-game.php\n";
echo "Disallow: /update-game.php\n";
echo 'Sitemap: ' . app_site_url() . "/sitemap.xml\n";
