<?php

declare(strict_types=1);

require_once( implode(DIRECTORY_SEPARATOR,[$_SERVER['DOCUMENT_ROOT'],'requires.php']));

$nb = new \app\Nestbox\Nestbox();

$uri = array_filter(explode("/",$_SERVER['REQUEST_URI']));

$defaultCol = 'item';

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
                <th>Item <a href='/compendium/{$table}/module/{$dir}/'>&varr;</a></th>
                <th>Type <a href='/compendium/{$table}/size/{$dir}/'>&varr;</a></th>
                <th>Mat 1 <a href='/compendium/{$table}/material_1/{$dir}/'>&varr;</a></th>
                <th>Qty 1 <a href='/compendium/{$table}/quantity_1/{$dir}/'>&varr;</a></th>
                <th>Mat 2 <a href='/compendium/{$table}/material_2/{$dir}/'>&varr;</a></th>
                <th>Qty 2 <a href='/compendium/{$table}/quantity_2/{$dir}/'>&varr;</a></th>
                <th>Mat 3 <a href='/compendium/{$table}/material_3/{$dir}/'>&varr;</a></th>
                <th>Qty 3 <a href='/compendium/{$table}/quantity_3/{$dir}/'>&varr;</a></th>
            </tr>
        </thead>
        <tbody>";
foreach ($rows as $row) {
    $itemLink = "<a href='/abaci/fabrication/{$row['item']}/1/'>{$row['item']}</a>";

    $material = ($nb->query_execute("SELECT * FROM `fabrication` WHERE `item` = :item",['item'=>$row['material_1']]))
        ? $nb->results(true) : [];
    $mat1 = (!empty($material)) ? "<a href='/abaci/fabrication/{$row['material_1']}/1/'>{$row['material_1']}</a>" : $row['material_1'];
    $qty1 = (!empty($material)) ? number_format($row['quantity_1'],0) : '';

    $material = ($nb->query_execute("SELECT * FROM `fabrication` WHERE `item` = :item",['item'=>$row['material_2']]))
        ? $nb->results(true) : [];
    $mat2 = (!empty($material)) ? "<a href='/abaci/fabrication/{$row['material_2']}/1/'>{$row['material_2']}</a>" : $row['material_2'];
    $qty2 = (!empty($material)) ? number_format($row['quantity_2'],0) : '';

    $material = ($nb->query_execute("SELECT * FROM `fabrication` WHERE `item` = :item",['item'=>$row['material_3']]))
        ? $nb->results(true) : [];
    $mat3 = (!empty($material)) ? "<a href='/abaci/fabrication/{$row['material_3']}/1/'>{$row['material_3']}</a>" : $row['material_3'];
    $qty3 = (!empty($material)) ? number_format($row['quantity_3'],0) : '';

//    $mat2Link = "<a href='/abaci/fabrication/{$row['material_2']}/1/'>{$row['material_2']}</a>";
//    $mat3Link = "<a href='/abaci/fabrication/{$row['material_3']}/1/'>{$row['material_3']}</a>";
    $output .= "
            <tr>
                <td>{$itemLink}</td>
                <td>{$row['build_type']}</td>
                <td>{$mat1}</td>
                <td>{$qty1}</td>
                <td>{$mat2}</td>
                <td>{$qty2}</td>
                <td>{$mat3}</td>
                <td>{$qty3}</td>
            </tr>";
}
$output .= "
        </tbody>
    </table>";

echo $output;