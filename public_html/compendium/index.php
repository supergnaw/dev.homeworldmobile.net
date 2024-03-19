<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

// Process Request URI
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

/**
 * Generate Page Sections
 ***/

///// HTML Header /////
$header = generate_html_header();

///// Main Navigation Tabs /////
$navtabs = generate_navtabs();

///// Sub Navigation Links /////
$links = [
    "blueprints" => "Blueprints",
    "research" => "Research",
    "flagships" => "Flagships",
    "modules" => "Modules",
    "fabrication" => "Fabrication",
    "systems" => "Systems"
];
asort(array: $links);
$subnav = generate_subnav(links: ($links ?? []), active: ($uri[1] ?? "n/a"), prefix: "/{$uri[0]}/");

/**
 * DISPLAY DATA
 */

$content = ($uri[1] ?? false) ? "" : "
        <h1 style='text-align: center'>The Compendium</h1>
        <p style='text-align: center'>Catalog of ship, faction, item, and system data.</p>
        <img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";

$system_messages = show_session_alerts();
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
                    <input style='grid-column: 1 / 5;' type='text' name='search' placeholder='Search Site' value='". ($_POST['search'] ?? '') ."'>
                    <a style='grid-column: 5 / 6;' href='#' id='form-btn' onclick='submit_form(\"search\")'><div id='form-btn-txt' class='btn'>Search</div></a>
                </div>
            </form>
            {$subnav}
            <hr>
            {$system_messages}
            {$content}
            <div class='hw-nav col1'>
                <a href='#top'><div>Top</div></a>
            </div>
            <hr>
            <p style='text-align: center'><a href='/console/'><img src='/img/25x25bdgB.GIF'></a></p>
        </div>
    </body>
</html>
";