<?php

declare(strict_types=1);

require_once( implode(DIRECTORY_SEPARATOR,[$_SERVER['DOCUMENT_ROOT'],'requires.php']));

$nb = new \app\Nestbox\Nestbox();

$uri = array_filter(explode("/",$_SERVER['REQUEST_URI']));

$defaultCol = 'item_name';

$uri[3] = $uri[3] ?? $defaultCol;
$uri[4] = $uri[4] ?? 'asc';

$output = "";

echo $output;