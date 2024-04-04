<?php
/**** GLOBAL FUNCTIONS ****/
// Generic Web Queries
// probably roll all the Git ones into a wrapper class just because
function fetch_raw_web(string $url): string
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $raw = curl_exec($ch);
    curl_close($ch);
    return $raw;
}

// Decode JSON from URL
function fetch_json(string $url): array
{
    try {
        return json_decode(fetch_raw_web($url));
    } catch (Error $e) {
        throw $e;
    }
}

// GIT API Request
function github_api_request(string $url): array
{
    return [];
}

// Git Repository Updates
function git_commit_timestamp(string $gitFilepath): string
{
    $api = "https://api.github.com/repos/supergnaw/homeworld-mobile-guide/commits?path={$gitFilepath}";
    $json = fetch_json($api);
    try {
        $json = $json[0];
        return $json->commit->author->date;
    } catch (Error $e) {
        die;
    }
}

// Update Table
function git_table_update(string $tableID): int
{
    // initiate nestbox
    $nb = new \Supergnaw\Nestbox\Nestbox();

    // validate table request
    if (!$nb->valid_table($tableID)) {
        return 0;
    }

    // fetch table metadata
    if ($nb->query_execute("SELECT * FROM `data_tables` WHERE `table_id` = :table_id;", ['table_id' => $tableID])) {
        $table = $nb->results()[0];
    }

    // last check was less than 5 minutes ago
    if (strtotime($table['git_last_check']) >= strtotime("-5 minutes")) {
        return 1;
    }

    // prepare timestamps
    $timestamp = git_commit_timestamp($table['git_filepath']);
    $table['git_last_check'] = date("Y-m-d H:i:s");

    // if last commit is same as local db timestamp, update last checked time
    if ($timestamp == $table['git_last_commit']) {
        if ($nb->query_execute("UPDATE `data_tables` SET `git_last_check` = :git_last_check WHERE `table_id` = :table_id;", $table)) {
            if (0 < $nb->row_count()) {
                return 2;
            }
        }
    } else {
        $table['git_last_commit'] = $timestamp;
    }

    // fetch git table
    $url = "https://raw.githubusercontent.com/supergnaw/homeworld-mobile-guide/main/{$table['git_filepath']}";
    $tableRaw = fetch_raw_web($url);
    if ('404: Not Found' == $tableRaw) {
        die($url);
    }
    $table['git_last_check'] = date("Y-m-d H:i:s");

    if (!$nb->query_execute("TRUNCATE {$tableID}")) return false;
    $insertCount = 0;
    $rows = md_table_to_array($tableRaw, $table['table_columns']);
    foreach ($rows as $row) $insertCount += $nb->insert($tableID, $row);

    // update metadata for table
    $table = [
        'table_id' => $tableID,
        'git_last_check' => $table['git_last_check'],
        'git_last_commit' => $table['git_last_commit'],
    ];

    if ($nb->query_execute("UPDATE `data_tables` SET `git_last_check` = :git_last_check, `git_last_commit` = :git_last_commit WHERE `table_id` = :table_id;", $table)) {
        return $insertCount;
    } else {
        return 3;
    }
}

// Convert MD to Array
// todo: check to see if this is even needed anymore
function md_table_to_array(string $tableData): array
{
    // format compendium for easy regexing
    $tableData = explode("\n", $tableData);
    foreach ($tableData as $r => $row) {
        $tableData[$r] = trim(preg_replace("/\s+/", " ", $row));
    }
    $tableData = implode("", $tableData);

    // separate headers from compendium
    preg_match('/\|(.*?(?=\|\|))\|[|\-]+\|\|(.*)\|/', $tableData, $matches);

    // parse headers
    foreach (explode("|", $matches[1]) as $i => $header) {
        $headers[$i] = preg_replace("/[^\w]+/i", "_", strtolower(trim($header)));
    }

    // parse rows
    foreach (explode("||", $matches[2]) as $i => $row) {
        foreach (explode("|", $row) as $j => $col) {
            $rows[$i][$headers[$j]] = trim($col);
        }
    }

    return $rows;
}

// Convert Database Rows To HTML
// todo: check to see if this is even needed anymore
function db_table_to_html(string $tableID): string
{
    // vars
    $sortFilter = ['ascending' => 'descending', 'descending' => 'ascending'];
    $uri = array_filter(explode("/", $_SERVER['REQUEST_URI']));
    $uri[3] = (array_key_exists(3, $uri)) ? strtolower(str_replace("-", "_", $uri[3])) : '';
    $uri[4] = (array_key_exists(4, $uri)) ? $sortFilter[$uri[4]] : 'ascending';

    // database fetching
    $nb = new \Supergnaw\Nestbox\Nestbox();
    if (!$nb->valid_table($tableID)) {
        return "";
    }

    // query selection
    if (!$nb->valid_schema($tableID, $uri[3])) {
        $query = "SELECT * FROM {$tableID};";
    } else {
        $query = ('ascending' == $uri[4])
            // these are reversed because of earlier var normalization
            ? "SELECT * FROM `{$tableID}` ORDER BY `{$uri[3]}` DESC;"
            : "SELECT * FROM `{$tableID}` ORDER BY `{$uri[3]}` ASC;";
    }

    if ($nb->query_execute($query)) {
        $rows = $nb->results();
    }

    if ($nb->query_execute("SELECT * FROM `data_tables` WHERE `table_id` = :table_id;", ['table_id' => $tableID])) {
        $res = $nb->results(true);
        if (empty($res)) {
            return "";
        } else {
            $tableMetadata = $res;
        }
    }
    $tableColumns = json_decode($tableMetadata['table_columns']);

    // headers
    $html = "<table><thead><tr>";

    foreach ($rows[0] as $col => $data) {
        if (property_exists($tableColumns, $col)) {
//            $filter = $get;
//            $filter = array_filter($filter);
            $uri[3] = strtolower(str_replace("_", "-", $col));
            $filter = "/" . implode("/", $uri) . "/";

            // column to sort
            $html .= "<th>{$tableColumns->{$col}} <a href='$filter'>&varr;</a></th>";
        }
    }
    $html .= "</tr></thead>";

    // rows
    $html .= "<tbody><tr>";
    foreach ($rows as $row => $cols) {
        $html .= "<tr>";
        foreach ($cols as $col => $data) {
            if (property_exists($tableColumns, $col)) {
                $html .= "<td>{$data}</td>";
            }
        }
        $html .= "</tr>";
    }
    $html .= "</tr></tbody></table>";

    return $html;
}

// Update Page
// todo: check to confirm this was the old way of updating pages
function git_page_update(string $pageID): bool
{
    $nb = new \Supergnaw\Nestbox\Nestbox();
    if ($nb->query_execute("SELECT * FROM `pages` WHERE page_id = :page_id;", ['page_id' => $pageID])) {
        $page = $nb->results()[0];
    }

    $page['git_last_check'] = (null == $page['git_last_check']) ? strtotime("-10 minutes") : $page['git_last_check'];
    if (strtotime($page['git_last_check']) >= strtotime("-5 minutes")) {
        return true;
    }

    $timestamp = git_commit_timestamp($page['git_filepath']);
    $page['git_last_check'] = date("Y-m-d H:i:s");
    if ($timestamp == $page['git_last_commit']) {
        if ($nb->query_execute("UPDATE `pages` SET `git_last_check` = :git_last_check WHERE page_id = :page_id;", $page)) {
            if (0 < $nb->row_count()) {
                return true;
            }
        }
        return false;
    }

    $url = "https://raw.githubusercontent.com/supergnaw/homeworld-mobile-guide/main/{$page['git_filepath']}";
    $page['page_md'] = fetch_raw_web($url);
    $page['git_last_commit'] = $timestamp;
    $page['git_last_check'] = date("Y-m-d H:i:s");
    if ($nb->query_execute("UPDATE `pages` SET `page_md` = :page_md, `git_last_commit` = :git_last_commit, `git_last_check` = :git_last_check WHERE page_id = :page_id;", $page)) {
        if (0 < $nb->row_count()) {
            return true;
        }
    }
    return false;
}

// Add <a>nchors to # Headers
// todo: reimplement with Parsedown Extended
function add_header_anchors(string $html): string
{
    preg_match_all('/<(h)(\d)>(.*?(?=<))(<\/h\d>)/i', $html, $headers);

    foreach ($headers[3] as $h => $header) {
        $id = string_to_anchor($header);
        $tag = "<{$headers[1][$h]}{$headers[2][$h]} id='{$id}'>{$header}{$headers[4][$h]}";
        $html = str_replace($headers[0][$h], $tag, $html);
    }

    return $html;
}

// Convert # Header Text to header-text
// todo: confirm Parsedown Extended migration means this won't be used anymore
function string_to_anchor(string $s): string
{
    $s = preg_replace('/[^A-Za-z09]+/', '-', $s);
    $s = trim($s, '-');
    return strtolower($s);
}

// todo: confirm this is no longer needed
function anchor_to_string(string $a): string
{
    return preg_replace(pattern: "/[\-]/", replacement: " ", subject: $a);
}

// Generate HTML for table of contents
// todo: reimplement with Parsedown Extended
function generate_table_of_contents(string $md, int $toc_level, string $prefix = ''): string
{
    $toc = [];
    if (0 >= $toc_level) return "";
    $toc_level = min($toc_level, 6);

    // parse table of contents level pattern
    $toc_level = min($toc_level, 6);
    $toc_pattern = "/^#{{$toc_level}}([^#].*)$/";

    // parse section level pattern
    $sec_level = $toc_level - 1;
    $sec_pattern = "/^#{{$sec_level}}([^#].*)$/";

    $lines = explode("\n", $md);
    foreach ($lines as $line) {
        // parse section
        if (preg_match($sec_pattern, $line, $matches)) {
            $section = trim($matches[1]);
        }

        // parse item
        if (preg_match($toc_pattern, $line, $matches)) {
            $section_header = "<h4>{$section}</h4>";
            $title = trim($matches[1]);
            $anchor = string_to_anchor($title);
            $link = "<a href='{$prefix}#{$anchor}'>{$title}</a>";
            $toc[$section_header][] = "<li>{$link}</li>";
            sort(array: $toc[$section_header]);
        }
    }

//    sort(array: $toc);

    $n = 0;
    foreach ($toc as $k => $v) $n += 4 + count($toc[$k]);
    $split = ceil($n / 2);

    $output = [];
    $total = 0;
    $col = 1;
    foreach ($toc as $k => $v) {
        $total += 4 + count($v);

        if ($total > $split) {
            $col = 2;
            $total = 0;
        }

        $list = implode("\n", $v);
        $output[$col][] = $k;
        $output[$col][] = "<ul>{$list}</ul>";
    }

    foreach ($output as $k => $v) {
        $output[$k] = implode("\n", $output[$k]);
    }

    $output = implode("</div><div>", $output);
//    $output = "<div class='grid-2 hw-border-box'><div>{$output}</div></div>";

    return (!empty($output)) ? "<div class='grid-2 hw-border-box'><div>{$output}</div></div>" : "";
}

// Generate HTML for table of category
// todo: check if depricated
function generate_table_of_category(string $category): string
{
    $bb = new \Supergnaw\Nestbox\Babbler\Babbler();
    $pages = $bb->fetch_entries_by_category($category);
    $toc = [];
    foreach ($pages as $page) {
        $toc[] = "<h2>{$page['title']}</h2>";
        $category = strtolower(str_replace(" ", "-", $page['category']));
        $title = strtolower(str_replace(" ", "-", $page['title']));
        $prefix = "/{$category}/{$title}/";
        $toc[] = generate_table_of_contents($page['content'], 3, $prefix);
    }
    return implode("\n", $toc);
}

// Find {{content-tags}} and replace with compendium from table
// todo: find a new home for this but it's likely still needed
function add_dynamic_content(string $text): string
{
    preg_match_all('/\{\{(.+?(?=\}))\}\}/', $text, $matches);
    $nb = new \Supergnaw\Nestbox\Nestbox();
    foreach ($matches[1] as $m => $id) {
        if ($nb->query_execute("SELECT * FROM `dynamic_content` WHERE `content_id` = :content_id;", ['content_id' => $id])) {
            $row = $nb->results();
            if (!empty($row)) {
                $text = str_replace($matches[0][$m], $row[0]['content_raw'], $text);
            }
        }
    }
    return $text;
}

// Find specific strings in document and replace them with images
// todo: will this be depricated with Parsedown Extended migration?
function add_inline_images(string $html): string
{
    $html = str_replace("\n", "{{im-a-new-line-bitch}}", $html);
    preg_match_all('/<p>.*?(?=<\/p>)<\/p>/i', $html, $paragraphs);

    $items = [
        ["regex" => "/(\W|^)T([012345])(\W|$)/", "img" => "$1<img class=\"in-line\" src=\"/img/ui/tier/$2.png\"/>$3"]
    ];

    $new_html = $html;
    foreach ($items as $item) {
        $html = trim(preg_replace($item['regex'], $item['img'], $html));
    }

    $html = str_replace("{{im-a-new-line-bitch}}", "\n", $html);
    return $html;
}

// Find specific strings in document and replace them with links to other pages
// todo: check if Parsedown Extended migration will make this obsolete
function add_inline_links(string $html, array $links): string
{
    foreach ($links as $regex => $target) {
        $html = preg_replace($regex, $target, $html);
    }
    return $html;
}

// Generate HTML Header
// todo: verify Parsedown Extended migration will make this obsolute
function generate_html_header(): string
{
    // main
    $header = [];
    $header[] = '<link rel="icon" type="image/png" href="/favicon.png">';
    // styles
    $header[] = "<style>";
    $styles = glob(implode(DIRECTORY_SEPARATOR, [$_SERVER['DOCUMENT_ROOT'], 'css', '*.css']));
    foreach ($styles as $style) $header[] = file_get_contents($style);
    $header[] = "</style>";

    // scripts
    $analytics = implode(DIRECTORY_SEPARATOR, [$_SERVER['DOCUMENT_ROOT'], 'analytics.php']);
    if (file_exists($analytics)) $header[] = get_file_contents($analytics);
    $header[] = "
        <script>
            function submit_form(formID) {
                console.log(formID);
                document.getElementById(formID).submit();
            }
        </script>";

    // aggregate
    $header = implode("\n", $header);
    return $header;
}

// Generate Tabbed Navigation
// todo: find a new home for this or verify it's still needed
function generate_navtabs(): string
{
    $links = [
        '/chronicle/' => 'Chronicle',
        '/enchiridion/' => 'Enchiridion',
        '/mythos/' => 'Mythos',
        '/compendium/' => 'Compendium',
        '/abaci/' => 'Abaci',
        '/vanguard/' => 'Vanguard',
    ];

    $current = explode("/", trim($_SERVER['REQUEST_URI'], "/"))[0] ?? '';

    if ('console' == $current) {
        $links["/console/"] = 'Console';
    }

    // count tabs
    $tabNum = count($links);
    $navTabs = "<div class='main-tabs col{$tabNum}-auto'>";
    foreach ($links as $link => $text) {
        $selected = (str_contains(haystack: $link, needle: $current) && !empty($current)) ? 'selected' : '';
        $text_lower = strtolower($text);
        $navTabs .= "
            <div class='tab {$selected}'>
                <a class='{$text_lower}' href='{$link}'>
                    <div><span class='tab-text'>&nbsp;{$text}</span></div>
                </a>
            </div>";
    }
    return $navTabs . "</div>";
}

// Generate Sub-Nav
function generate_subnav(array $links, string $active = '', string $prefix = ''): string
{
    $linkNum = count($links);
    if (in_array($linkNum, [1])) {
        $classCols = "col1";
    } elseif (in_array($linkNum, [2])) {
        $classCols = "col2";
    } elseif (in_array($linkNum, [3, 6, 9, 15, 18])) {
        $classCols = "col3";
    } elseif (in_array($linkNum, [5, 10, 15, 20])) {
        $classCols = "col5";
    } else {
        $classCols = "col4";
    }

    $subnav = "<div class='hw-nav {$classCols}-auto'>";
    foreach ($links as $link => $text) {
        $link = (str_starts_with($link, "?")) ? $link : "?{$link}";
        $class = (str_contains(haystack: $link, needle: $active) && !empty($active)) ? "class='active'" : "";
        $subnav .= "<a href='{$prefix}{$link}' {$class}><div class='btn'>{$text}</div></a>";
    }
    $subnav .= "</div>";

    return $subnav;
}

function generate_select_field(array $options, string $selected = null, string $style = null, string $name = null, string $id = null): string
{
    // check for associative
    if (array_is_list($options)) {
        $newOptions = [];
        foreach ($options as $option) $newOptions[$option] = $option;
        $options = $newOptions;
    }

    // set empty id
    if (empty($id)) $id = $name;

    // generate output
    $output = "<select style='{$style}' id='{$id}' name='{$name}'>\n";
    foreach ($options as $optionName => $optionValue) {
        $output .= ($optionName == $selected || $optionValue == $selected)
            ? "    <option value='{$optionValue}' selected>{$optionName}</option>\n"
            : "    <option value='{$optionValue}'>{$optionName}</option>\n";
    }
    $output .= "</select>";
    return $output;
}

// Process URI
function process_uri(): array
{
    $uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));
    foreach ($uri as $k => $v) {
        $uri[$k] = trim($v, "?");
    }
    return $uri;
}

// Generate Table Nav
// todo: verify this is depricated
function generate_table_nav(string $tableID = null): string
{
    $nb = new \Supergnaw\Nestbox\Nestbox();
    $tables = ($nb->query_execute("SELECT * FROM `data_tables`;")) ? $nb->results() : [];
    $tableCount = count($tables);
    switch ($tableCount) {
        case 1:
            $classCols = "col1";
            break;
        case 2:
            $classCols = "col2";
            break;
        case 3:
        case 6:
        case 9:
        case 12:
        case 15:
        case 18:
            $classCols = "col3";
            break;
        case 5:
        case 10:
        case 20:
            $classCols = "col5";
            break;
        case 7:
        case 14:
            $classCols = "col7";
            break;
        case 11:
            $classCols = "col11";
            break;
        case 4:
        case 8:
        default:
            $classCols = "col4";
    }

    $subnav = "<div class='hw-nav {$classCols}'>";
    foreach ($tables as $tbl) {
        $class = ($tableID == $tbl['table_id']) ? "class='active'" : "";
        $subnav .= "<a href='/compendium/{$tbl['table_id']}' {$class}><div class='btn'>{$tbl['table_button_text']}</div></a>";
    }
    $subnav .= "</div>";

    return $subnav;
}

// Generate Page HTML
// todo: migrate from Parsdown to Parsedown Extended
// todo: alternatively, javascript MarkDown parser?
function generate_page_html(string $markdown, array $inline_links = []): string
{
    $pd = new Parsedown();

    $html = $pd->parse($markdown);
    $html = add_inline_links($html, $inline_links);
    $html = add_inline_images($html);
    $html = add_header_anchors($html);
    $html = add_dynamic_content($html);
    if (strpos($html, "{{table-of-contents}}")) {
        $toc = generate_table_of_contents($markdown, 3);
        $html = str_replace("{{table-of-contents}}", $toc, $html);
    }
    return $html;
}

// Convert Multi-Dimensional Array to Table
// todo: find a better home for this
function array_2_table(array $arr, bool $justified = true): string
{
    if (empty($arr)) {
        return "";
    }

    $table = ["<table>"];
    $headers = [];
    $rows = [];
    foreach ($arr as $row) {
        foreach ($row as $header => $col) {
            if (!in_array($header, $headers)) $headers[] = $header;
        }
        $rows[] = "<tr><td>";
        $rows[] = implode("</td><td>", $row);
        $rows[] = "</td></td>";
    }
    $width = ($justified) ? floor(100 / count($headers)) : "";

    $table[] = "<thead><th style='width: {$width}%'>";
    $table[] = implode("</th><th style='width: {$width}%'>", $headers);
    $table[] = "</th></thead>";
    $table[] = "<tbody>";
    $table[] = implode($rows);
    $table[] = "</tbody>";
    $table[] = "</table>";

    return implode($table);
}

// Generate A Thumbnail Image
// todo: verify if this is actually still required or if it will be rolled into Lorikeet
function generate_thumbnail(string $srcImg, string $dstImg, int $width = 100, int $height = 100, bool $crop = true): bool
{
    if (!file_exists($srcImg)) return false;

    $imgSize = getimagesize($srcImg);
    $w = $imgSize[0];
    $h = $imgSize[1];
    $mime = $imgSize['mime'];

    $wRatio = $width / $w;
    $hRatio = $height / $h;

    $scale = (true === $crop) ? max($wRatio, $hRatio) : min($wRatio, $hRatio);

    $wNew = $w * $scale;
    $hNew = $h * $scale;
    var_dump("widthRatio", $wNew, "heightRatio", $hNew);

    if ('image/png' == $mime) {
        $srcImg = imagecreatefrompng($srcImg);
    } elseif ('image/jpg' == $mime) {
        $srcImg = imagecreatefromjpeg($srcImg);
    } else {
        return false;
    }

    $canvas = ImageCreateTrueColor($wNew, $hNew);

    $xSrc = (true === $crop) ? $w / 2 - $wNew / 2 : 0;
    $ySrc = (true === $crop) ? $h / 2 - $hNew / 2 : 0;

    imagecopyresampled(
        $canvas,
        $srcImg,
        0, 0,
        $xSrc, $ySrc,
        $wNew,
        $hNew,
        $w,
        $h
    );

    if (imagepng($canvas, $dstImg, 5)) {
        var_dump("image created: {$dstImg}");
        return true;
    } else {
        die('failed to create image');
    }
}

/***** COUNTDOWN TIMER PARSERS *****/
// todo: find a better home for this since it appears be exclusively for research. maybe also fabrication/refining?
function parse_seconds(string|int $seconds): string
{
    $output = [];
    list($d, $seconds) = parse_seconds_increment_helper('d', $seconds);
    $output[] = sprintf("%02d", $d);
    list($h, $seconds) = parse_seconds_increment_helper('h', $seconds);
    $output[] = sprintf("%02d", $h);
    list($m, $seconds) = parse_seconds_increment_helper('m', $seconds);
    $output[] = sprintf("%02d", $m);
    list($s, $seconds) = parse_seconds_increment_helper('s', $seconds);
    $output[] = sprintf("%02d", $s);
    return implode(separator: ":", array: $output);
}

function parse_seconds_increment_helper(string $increment, int $seconds): array
{
    if ('d' == $increment) $total = 86400;
    if ('h' == $increment) $total = 3600;
    if ('m' == $increment) $total = 60;
    if ('s' == $increment) $total = 1;
    return [intdiv($seconds, $total), $seconds % $total];
}

function parse_requirements(string $requirements): array
{
    $output = [];
    if (0 == strlen($requirements)) return $output;
    $requirements = explode("\n", $requirements);
    foreach ($requirements as $requirement) {
        list($req, $lvl) = explode(":", $requirement);
        $output[trim($req, "_c")] = $lvl;
    }
    return $output;
}

/**** SESSION FUNCTIONS ****/
function save_session_alert(string $text, string $classes = ""): void
{
    if (!isset($_SESSION["alerts"])) {
        $_SESSION["alerts"] = [];
    }

    $_SESSION["alerts"][$text] = $classes;
}

function show_session_alerts(): string
{
    if (!isset($_SESSION["alerts"])) return "";
    if (0 == count($_SESSION["alerts"])) return "";

    $alerts = "";
    foreach ($_SESSION["alerts"] as $message => $classes) {
        $alerts .= "<div class='hw-border-box {$classes}'><p class=''>{$message}</p></div>";
    }
    $_SESSION["alerts"] = [];
    return $alerts;
}