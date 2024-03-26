<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$uri = process_uri();
$page = ('page' == ($uri[2] ?? 'page')) ? intval(($uri[3] ?? '1')) : 1;

$limit = 5;
$start = ($page - 1) * $limit;
$babbler = new Supergnaw\Nestbox\Babbler\Babbler();
$entries = $babbler->fetch_entries_by_category(category: "Patch Notes", order_by: "sub_category", sort: "desc", start: $start, limit: $limit);
$totalEntries = intval($babbler->fetch_categories()['Patch Notes']);

$html = "";

// show entries
foreach ($entries as $entry) {
    $patchNotesVersion = parse_patch_notes_title_version($entry['title']);
    $publishedDate = $entry['published'];
    $lastEditDate = $entry['edited'];
    $editedBy = $entry['edited_by'];
    $content = $entry['content'];

    $html .= "
    <h2>{$patchNotesVersion}</h2>
    <h3>{$publishedDate}</h3>
    <h6>Last edited by {$editedBy} on {$lastEditDate}</h6>
    <div class='markdown-container'>{$content}</div>";
    $html .= "<hr>";
}

// create pagination
$prev = (1 < $page && 0 < $totalEntries)
    ? "<a class='paginate-prev' href='/{$uri[0]}/?{$uri[1]}/page/" . $page - 1 . "'><div>Previous</div></a>"
    : "&nbsp;";

$next = ((($page - 1) * $limit + $limit) < $totalEntries)
    ? "<a class='paginate-next' id='page-next' href='/{$uri[0]}/?{$uri[1]}/page/" . $page + 1 . "'><div>Next</div></a>"
    : "&nbsp;";

$html .= "
            <div class='hw-nav col3 paginate'>
                {$prev}
                <a class='paginate-top' href='#top'><div>Top</div></a>
                {$next}
            </div>";

// finalize page code
$html .= "
    <script src='https://cdn.jsdelivr.net/npm/marked/marked.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof marked === 'undefined') {
                document.write('<script src=\"js/marked.min.js\">\/script>');
            };
            parse_markdown();            
        }, false);
        
        function parse_markdown() {
            var markdown_containers = document.getElementsByClassName('markdown-container');
            for (var i = 0; i < markdown_containers.length; i++) {
               markdown_containers.item(i).innerHTML = marked.parse(markdown_containers.item(i).textContent);
               console.log(i);
            }
        };
    </script>";

function parse_patch_notes_title_version(string $title): string
{
    $output = [];
    foreach (explode(".", $title) as $number) {
        $output[] = intval($number);
    }
    return implode(".", $output);
}

return $html;