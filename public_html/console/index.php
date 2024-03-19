<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$titmouse = new \app\Nestbox\Titmouse\Titmouse('users', 'username');

// Process Request URI
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

/**
 * Generate Page Sections
 ***/

///// HTML Header /////
$header = generate_html_header();

///// Main Navigation Tabs /////
$navtabs = generate_navtabs();

///// Sub Navitagion Links /////
$links = [
    "/console/content/" => 'Content',
    "/console/tables/" => 'Data Tables',
    "/console/users/" => 'Users',
    "/console/symbology/" => 'Symbology',
    "/console/images/" => 'Images',
    "/console/logout/" => 'Logout',
];
$subnav = generate_subnav($links, "/console/" . ($uri[1] ?? 'overview') . '/');

///// Generate HTML /////
if (empty($_SESSION[$titmouse->session_key()])) {
    $post_response = '';
    $content = include("login.php");
} else {
    $post_response = include("_post.php");

    $target_file = ($uri[1] ?? '') . ".php";
    $whitelist = ["content", "tables", "users", "symbology", "images", "logout"];
    if (in_array($uri[1] ?? '', $whitelist) and file_exists($target_file)) {
        $content = include($target_file);
    } else {
        $content = "<img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
    }
}

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
            {$subnav}
            <hr>
            {$system_messages}
            {$post_response}
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