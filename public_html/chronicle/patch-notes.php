<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

//$titmouse = new \app\Nestbox\Titmouse\Titmouse('users', 'username');
$babbler = new \app\Nestbox\Babbler\Babbler();
//$api = new \app\PlayFab\Playfab(PLAYFAB_APP_ID);

// Process Request URI
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

/*
 * PATCH NOTES
 */
$html = "";

$entries = $babbler->fetch_entries_by_category(category: "Patch Notes", order_by: "sub_category", sort: "desc");

var_dump($entries);

return $html;