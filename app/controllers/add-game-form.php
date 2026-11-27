<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_user();
redirect('my-games.php?add=1');
