<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

//$titmouse = new \app\Nestbox\Titmouse\Titmouse('users', 'username');
//$babbler = new \app\Nestbox\Babbler\Babbler();
//$api = new \app\PlayFab\Playfab(PLAYFAB_APP_ID);

// Process Request URI
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

/*
 * EVENT BANNERS
 */
$events_uri = "https://func-nimbusx-webview-live.azureedge.net/api/serving";
$data = [];
try {
    $news = new app\NimbusNews();
    $obj = $news->get_news();
    foreach ($obj as $event) {
        $img = "<img class=\"banner\" style=\"max-width: 100%;\" src=\"{$event["event_link"]}\"/>";
        $img = (!preg_match("/external\?url=(https?:\/\/.*)$/i", urldecode($event["event_action"]), $matches))
            ? $img : "<a href=\"{$matches[1]}\" target=\"_blank\">{$img}</a>";
        $img = "<div>{$img}</div>";
        $data[] = $img;
    }
} catch (\Exception $e) {
    die($e);
}
$data = implode(separator: "\n", array: $data);
$html = "<div class=\"event-gallery\">{$data}</div>";

return $html;