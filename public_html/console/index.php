<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$titmouse = new \Supergnaw\Nestbox\Titmouse\Titmouse();
$babbler = new \Supergnaw\Nestbox\Babbler\Babbler();

// Process Request URI
$uri = process_uri();

$linkList = [
    "content" => 'Content',
    "tables" => 'Data Tables',
    "users" => 'Users',
    "symbology" => 'Symbology',
    "images" => 'Images',
    "logout" => 'Logout',
];
$linkActive = ($uri[1] ?? "n/a");
$linkPrefix = "/{$uri[0]}/";

/**
 * Generate Page Sections
 ***/
$whitelist = array_keys($linkList);
if (!in_array($linkActive, $whitelist) || empty($_SESSION[$titmouse->session_key()])) {
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
        " . generate_html_header() . "
    </head>
    <body>
        " . generate_navtabs() . "
        <div class='main hw-outer-box'>
            <div class='title'>HOMEWORLD MOBILE</div>
            <div class='subtitle'>Unofficial Guide</div>
            " . generate_subnav(links: $linkList, active: $linkActive, prefix: $linkPrefix) . "
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