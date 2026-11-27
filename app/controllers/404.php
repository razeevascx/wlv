<?php

require_once dirname(__DIR__) . '/support/view.php';

http_response_code(404);
render_template('404.html');
