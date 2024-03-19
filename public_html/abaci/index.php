<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));


// Process Request URI
$uri = explode("/",trim($_SERVER['REQUEST_URI'],"/"));
$category = $uri[0] ?? "compendium";
$uri[1] = $uri[1] ?? "overview";
$table = str_replace('-','_',$uri[1]);
$column = $uri[2] ?? "";
$direction = $uri[3] ?? "ascending";

// Selection Variables
$currentCategory = "/{$category}/";
$currentView = "/{$category}/{$uri[1]}/";

// Array of Tables
$toolLinks = [
    "/abaci/fabrication/" => "Fabrication",
//    "/abaci/officer/" => "Officer",
];

// Array of Requirements
$tools = [
    "fabrication" => "/abaci/fabrication.php",
];

/**
 * Generate Page Sections
 ***/

///// HTML Header /////
$header = generate_html_header();

///// Main Navigation Tabs /////
$navtabs = generate_navtabs();

///// Sub Navitagion Links /////
if (!empty($tableLinks)) {
    $subnav = generate_subnav(links: $tableLinks, active: "/{$category}/{$uri[1]}/");
} else {
    $subnav = "<img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
}

/// Generate HTML /////
if ('overview' == $uri[1]) {
    $html = "<img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
} else {
    $html = db_table_to_html(strtolower(str_replace("-","_",$uri[1])));
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
            {$subnav}";

if ('overview' != $uri[1]) {
    require_once ($tools[$uri[1]]);
} else {
    echo "<img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
}

echo "      <hr>
            <div class='hw-nav col1'>
                <a href='#top'><div>Top</div></a>
        </div></div>
    </body>
</html>";