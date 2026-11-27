<?php

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/support/catalogue.php';

$data = catalogue_data();
$data['flash_toast'] = $_SESSION['flash_toast'] ?? null;
unset($_SESSION['flash_toast']);
render_template('index.html', $data);
