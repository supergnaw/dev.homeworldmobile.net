<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$babbler = new \Supergnaw\Nestbox\Babbler\Babbler(NESTBOX_DB_HOST, NESTBOX_DB_USER, NESTBOX_DB_PASS, NESTBOX_DB_NAME);

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
$sub_categories = $babbler->fetch_sub_categories(category: ($uri[0] ?? 'lore'));
$links = [];
foreach ($sub_categories as $category) {
    $link = "/" . strtolower(str_replace(search: ' ', replace: '-', subject: "{$uri[0]}/{$category['sub_category']}/"));
    $text = ucwords(str_replace('-',' ', $category['sub_category']));
    $links[$link] = $text;
}
$dynamic_links = [];
$links = array_merge($links, $dynamic_links);
asort(array: $links);
$subnav = generate_subnav(links: ($links ?? []), active: ($uri[1] ?? "n/a"), prefix: "/{$uri[0]}/");

///// Generate HTML /////
if (empty($uri[1] ?? "")) {
    $html = "
        <h1 style='text-align: center'>Mythos</h1>
        <p style='text-align: center'>Exposition of game lore.</p>
        <img src='/img/guidestone.jpg' style='width: 100%;max-width: 100%;'>";
}
elseif (empty($uri[2] ?? "")) {
    $pages = $babbler->fetch_entries_by_category(category: $uri[0], sub_category: $uri[1], order_by: 'title', sort: 'ASC');
    $html = "";
    foreach ($pages as $page) {
        $prefix = "/". $uri[0] ."/". $uri[1] ."/". string_to_anchor($page['title']);
        $html .= generate_table_of_contents(md: $page['content'], toc_level: 3, prefix: $prefix);
    }
}
elseif (!empty($uri[2] ?? "")) {
    $title = preg_replace(pattern: "/[\s\-]+/", replacement: "%", subject: "%$uri[1]%");
    $page = $babbler->fetch_entry_by_category_and_title(category: $uri[0], sub_category: $uri[1], title: $title);
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