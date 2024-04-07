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

$html = "";

$table = ["<table><thead><tr><th>Name</th><th>RegEx</th><th>Image</th></tr></thead><tbody>"];
foreach ($results as $row) {
    $table[] = "<tr>
        <td>{$row['symbol_name']}</td>
        <td>{$row['symbol_regex']}</td>
        <td>{$row['symbol_image']}</td>
    </tr>";
}
$table[] = "</tbody></table>";
$table = implode("\n", $table);

return $table;