<?php

declare(strict_types=1);

require_once( implode(DIRECTORY_SEPARATOR,[$_SERVER['DOCUMENT_ROOT'],'requires.php']));

//$nestbox = new \app\Nestbox\Nestbox();
$babbler = new \app\Nestbox\Babbler\Babbler();

// Process Request URI
$uri = explode("/",trim($_SERVER['REQUEST_URI'],"/"));

/**
 * Generate Page Sections
 ***/

///// HTML Header /////
$header = generate_html_header();

///// Main Navigation Tabs /////
$navtabs = generate_navtabs();

///// Sub Navigation Links /////
$babbler->query_execute("SELECT `title` FROM `babbler_entries` WHERE `category` = 'gameplay' ORDER BY `title` ASC;");
$sub_links = $babbler->results();
$links = [];
foreach ($sub_links as $page) {
    $link = strtolower(str_replace(search: ' ', replace: '-', subject: $page['title']));
    $links[$link] = $page['title'];
}
$dynamic_links = [];
$links = array_merge($links, $dynamic_links);
asort(array: $links);
$subnav = generate_subnav(links: ($links ?? []), active: ($uri[1] ?? "n/a"), prefix: "/{$uri[0]}/");

///// Generate HTML /////
if (empty($uri[1] ?? null)) {
    $html = "
        <h1 style='text-align: center'>The Enchiridion</h1>
        <p style='text-align: center'>Documentation and guides covering various topics and objectives.</p>
        <img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
}
else {
    $title = preg_replace(pattern: "/[\s\-]+/", replacement: "%", subject: "%$uri[1]%");
    $page = $babbler->fetch_entry_by_category_and_title(category: $uri[0], title: $title);
    $html = "<p>Last edited {$page['edited']} by <a href='/stats/player/{$page['edited_by']}/'>{$page['edited_by']}</a></p>";
    $links = [
        "/\b(officers?)\b/i" => "<a href='/enchiridion/officers/'>\$1</a>",
        "/\b(ores?|gase?s?)\b/i" => "<a href='/compendium/mining/'>\$1</a>",
    ];
    $html .= generate_page_html(markdown: $page['content'], inline_links: $links);
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
                    <input style='grid-column: 1 / 5;' type='text' name='search' placeholder='Search Site' value='". ($_POST['search'] ?? '') ."'>
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