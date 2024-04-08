<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$nb = new \Supergnaw\Nestbox\Nestbox();

if (!$nb->valid_schema('symbology')) {
    $sql = "CREATE TABLE IF NOT EXISTS `symbology` (
                `symbol_name` VARCHAR( 64 ) NOT NULL PRIMARY KEY ,
                `symbol_regex` VARCHAR( 128 ) NOT NULL UNIQUE ,
                `symbol_image` VARCHAR( 256 ) NOT NULL
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $nb->query_execute($sql);
    $nb->load_table_schema();
}

$results = $nb->select('symbology');
$output = [];
foreach ($results as $symbol) {
    $output[] = [
        'Name' => $symbol['symbol_name'],
        'RegEx' => $symbol['symbol_regex'],
        'Image' => $symbol['symbol_image']
    ];
}
$table = array_2_table($output);

$rawImages = glob("../img/ui/generic/*");
$radios = [];
foreach ($rawImages as $rawImage) {
    $img = trim($rawImage, "\.\.");
    $imgTag = "<img src='{$img}' style='width: 100%;'>";
    $radios[] = "
            <label style='text-align: center;'>
                {$imgTag}
                <input type='radio' name='publish_date_type' value='{$img}'>
            </label>";
}
$radios = implode($radios);

$html = "
            <div class='grid'>
                <label for='page_category'>Image</label>
                <input type='text' id='page_title' name='image' value=''>
            </div>
            <div class='grid'>
                <label for='page_category'>RegEx</label>
                <input type='text' id='page_title' name='regex' value=''>
            </div>
            <div class='grid'>
                <fieldset>
                    <legend>Symbol</legend>
                    <div class='grid col12'>
                        {$radios}
                    </div>
                </fieldset>
            </div>";

$content = $html;
$content .= ("edit" != ($uri[2] ?? false))
    ? "
            <div class='hw-nav col7'>
                <a style='grid-column: 4 / 5;' href='/console/?symbology/edit/'><div id='form-btn-txt' class='btn'>Add Symbol</div></a>
            </div>\n"
    : "";

return $content;