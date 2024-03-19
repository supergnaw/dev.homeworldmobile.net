<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$nb = new \app\Nestbox\Nestbox();

$uri = array_filter(explode("/",$_SERVER['REQUEST_URI']));

$defaultCol = 'name';

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
                <th>Name <a href='/compendium/{$table}/name/{$dir}/'>&varr;</a></th>
                <th>Class <a href='/compendium/{$table}/class/{$dir}/'>&varr;</a></th>
                <th>Faction <a href='/compendium/{$table}/faction/{$dir}/'>&varr;</a></th>
                <th>Notes & Tactics</th>
            </tr>
        </thead>
        <tbody>";
foreach ($rows as $row) {
    $output .= "
            <tr>
                <td>{$row['name']}</td>
                <td>{$row['class']}</td>
                <td>{$row['faction']}</td>
                <td>{$row['notes_tactics']}</td>
            </tr>";
}
$output .= "
        </tbody>
    </table>";

echo $output;