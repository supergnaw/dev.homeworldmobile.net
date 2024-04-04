<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$babbler = new \Supergnaw\Nestbox\Babbler\Babbler();

// Process Request URI
$uri = process_uri();

// Sub Navigation LInks
$linkList = [
    "news" => 'News',
    "events" => 'Events',
    "timers" => 'Timers',
    "patch-notes" => 'Patch Notes'
];
$linkActive = ($uri[1] ?? "n/a");
$linkPrefix = "/{$uri[0]}/";

/**
 * Generate HTML
 **/
$whitelist = array_keys($linkList);
if (!in_array($linkActive, $whitelist)) {
    $html = "
        <h1 style='text-align: center'>The Chronicle</h1>
        <p style='text-align: center'>Hub for current events.</p>
        <img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
} else {
    $target_file = ($uri[1] ?? '') . ".php";
    if (file_exists($target_file)) {
        $html = include($target_file);
    } else {
        $html = "<p>Invalid content. Are you lost?</p>";
    }
}

/**
 * DISPLAY DATA
 */
echo "
<!DOCTYPE html>
<html>
    <head>
        " . generate_html_header() . "
    </head>
    <body>
        " . generate_navtabs() . "
        <div class='main hw-outer-box'>
            <div class='title'>HOMEWORLD MOBILE</div>
            <div class='subtitle'>Unofficial Guide</div>
            <form method='post' action='/search/' id='search'>
                <div class='grid col5 hw-nav'>
                    <input style='grid-column: 1 / 5;' type='text' name='search' placeholder='Search Site' value='" . ($_POST['search'] ?? '') . "'>
                    <a style='grid-column: 5 / 6;' href='#' id='form-btn' onclick='submit_form(\"search\")'><div id='form-btn-txt' class='btn'>Search</div></a>
                </div>
            </form>
            " . generate_subnav(links: $linkList, active: $linkActive, prefix: $linkPrefix) . "
            <hr>
            {$html}
            <hr>
            <p style='text-align: center'><a href='/console/'><img src='/img/25x25bdgB.GIF'></a></p>
        </div>
    </body>
</html>";