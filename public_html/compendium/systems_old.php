<?php

declare(strict_types=1);

require_once(implode(separator: DIRECTORY_SEPARATOR, array: [$_SERVER['DOCUMENT_ROOT'], 'requires.php']));

$nb = new \Supergnaw\Nestbox\Nestbox();

$uri = array_filter(explode("/",$_SERVER['REQUEST_URI']));

$defaultCol = 'system_name';

$uri[3] = $uri[3] ?? $defaultCol;
$uri[4] = $uri[4] ?? 'asc';

$col = str_replace('-','_',$uri[3]);
$col = ($nb->valid_schema($table,$col)) ? $col : $defaultCol;

$dir = (in_array($uri[4],['asc','desc'])) ? $uri[4] : 'asc';

$sql = "SELECT * FROM `{$table}` ORDER BY `{$col}` {$dir};";
$rows = ($nb->query_execute($sql)) ? $nb->results() : [];

$col = str_replace('-','_',$col) ?? null;
$dir = ('desc' == $dir) ? 'asc' : 'desc';

$territories = [
    'Yaot' => "rgba(160,252,207,0.25)",
    'Tanoch' => "rgba(231,73,97,0.25)",
    'Iyatequa' => "rgba(51,83,124,0.25)",
    'Unknown' => "rgba(207,207,115,0.25)",
];

$output = "
    <table>
        <thead>
            <tr>
                <th>Name <a href='/compendium/{$table}/system-name/{$dir}/'>&varr;</a></th>
                <th>Territory <a href='/compendium/{$table}/territory/{$dir}/'>&varr;</a></th>
                <th>Tier <a href='/compendium/{$table}/tier/{$dir}/'>&varr;</a></th>
                <th>Trade Station <a href='/compendium/{$table}/trade-station/{$dir}/'>&varr;</a></th>
                <th>Resource Belts <a href='/compendium/{$table}/resource-belts/{$dir}/'>&varr;</a></th>
                <th>Other Info <a href='/compendium/{$table}/other/{$dir}/'>&varr;</a></th>
            </tr>
        </thead>
        <tbody>";
foreach ($rows as $row) {
    $ts = strtolower($row['trade_station']);
    $rb = strtolower($row['resource_belts']);
    $output .= "
            <tr style='background-color: {$territories[$row['territory']]};'>
                <td>{$row['system_name']}</td>
                <td>{$row['territory']}</td>
                <td>{$row['tier']}</td>
                <td><img src='/img/{$ts}.png'/></td>
                <td><img src='/img/{$rb}.png'/></td>
                <td>{$row['other']}</td>
            </tr>";
}
$output .= "
        </tbody>
    </table>";

echo $output;