<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$babbler = new \Supergnaw\Nestbox\Babbler\Babbler(NESTBOX_DB_HOST, NESTBOX_DB_USER, NESTBOX_DB_PASS, NESTBOX_DB_NAME);

// Process Request URI
//$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));
$uri = process_uri();

var_dump($uri);
//die($uri);

/**
 * Generate Page Sections
 ***/

///// HTML Header /////
$header = generate_html_header();

///// Main Navigation Tabs /////
$navtabs = generate_navtabs();

///// Sub Navigation Links /////
$links = [
    "news" => 'News',
    "events" => 'Events',
    "timers" => 'Timers',
    "patch-notes" => 'Patch Notes'
];

$subnav = generate_subnav(links: $links, active: ($uri[1] ?? "n/a"), prefix: "/{$uri[0]}/");

///// Generate HTML /////
$whitelist = ["events", "news", "patch-notes", "timers"];
if (!in_array($uri[1] ?? false, $whitelist)) {
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
        {$header}
    </head>
    <body>
        {$navtabs}
        <div class='main hw-outer-box'>
            <div class='title'>HOMEWORLD MOBILE</div>
            <div class='subtitle'>Unofficial Guide</div>
            <form method='post' action='/search/' id='search'>
                <div class='grid col5 hw-nav'>
                    <input style='grid-column: 1 / 5;' type='text' name='search' placeholder='Search Site' value='" . ($_POST['search'] ?? '') . "'>
                    <a style='grid-column: 5 / 6;' href='#' id='form-btn' onclick='submit_form(\"search\")'><div id='form-btn-txt' class='btn'>Search</div></a>
                </div>
            </form>
            {$subnav}
            <hr>
            {$html}
            <div class='hw-nav col1'>
                <a href='#top'><div>Top</div></a>
            </div>
            <hr>
            <p style='text-align: center'><a href='/console/'><img src='/img/25x25bdgB.GIF'></a></p>
        </div>
    </body>
</html>";