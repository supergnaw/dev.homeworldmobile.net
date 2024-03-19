<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$nb = new \app\Nestbox\Nestbox();

$uri = array_filter(explode("/",$_SERVER['REQUEST_URI']));

$defaultCol = 'item_name';

$uri[3] = $uri[3] ?? $defaultCol;
$uri[4] = $uri[4] ?? 'asc';

$col = str_replace('-','_',$uri[3]);
$col = ($nb->valid_schema($table,$col)) ? $col : $defaultCol;

$dir = (in_array($uri[4],['asc','desc'])) ? $uri[4] : 'asc';

$sql = "SELECT * FROM `{$table}` ORDER BY `{$col}` {$dir};";
$rows = ($nb->query_execute($sql)) ? $nb->results() : [];

$col = str_replace('-','_',$col) ?? null;
$dir = ('desc' == $dir) ? 'asc' : 'desc';

$output = "
    <table>
        <thead>
            <tr>
                <th>Item <a href='/compendium/{$table}/item-name/{$dir}/'>&varr;</a></th>
                <th>Type <a href='/compendium/{$table}/item-type/{$dir}/'>&varr;</a></th>
                <th>Acquisition <a href='/compendium/{$table}/location-found/{$dir}/'>&varr;</a></th>
            </tr>
        </thead>
        <tbody>";
foreach ($rows as $row) {
    $item = ($nb->query_execute("SELECT * FROM `fabrication` WHERE `item` = :item",['item'=>$row['item_name']]))
        ? $nb->results(true) : [];
    $rowItem = (!empty($item)) ? "<a href='/abaci/fabrication/{$item['item']}/1/'>{$item['item']}</a>" : $row['item_name'];
    $output .= "
            <tr>
                <td>{$rowItem}</td>
                <td>{$row['item_type']}</td>
                <td>{$row['location_found']}</td>
            </tr>";
}
$output .= "
        </tbody>
    </table>";

echo $output;